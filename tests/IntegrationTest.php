<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Symfony\Lock\Tests;

use PHPUnit\Framework\TestCase;
use RoadRunner\Lock\LockInterface as RrLock;
use Spiral\RoadRunner\Symfony\Lock\RoadRunnerStore;
use Symfony\Component\Lock\LockFactory;

final class IntegrationTest extends TestCase
{
    public function testLock(): void
    {
        $responseList = [
            'test-lock' => [
                'uuid1',
                false,
                'uuid2'
            ],
        ];
        $rrLock = $this->createMock(RrLock::class);
        $rrLock
            ->expects(self::exactly(3))
            ->method('lock')
            ->willReturnCallback(function (string $name) use (&$responseList) {
                $array_shift = \array_shift($responseList[$name]);
                return $array_shift;
            });
        $rrLock
            ->expects(self::exactly(2))
            ->method('updateTTL')
            ->willReturn(true);

        $rrLock
            ->expects(self::exactly(2))
            ->method('release')
            ->willReturn(true);

        // lock
        $factory = new LockFactory(new RoadRunnerStore($rrLock));
        $lock1 = $factory->createLock('test-lock');
        self::assertTrue($lock1->acquire());

        $lock2 = $factory->createLock('test-lock');
        self::assertFalse($lock2->acquire());

        $lock1->release();

        // lock 2
        self::assertTrue($lock2->acquire());
        $lock2->release();
    }
}
