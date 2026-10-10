<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Symfony\Lock\Tests;

use RoadRunner\Lock as RR;
use Spiral\RoadRunner\Symfony\Lock\RoadRunnerStore;
use Symfony\Component\Lock\LockFactory;
use Testo\Assert;
use Testo\Test;

#[Test]
final class IntegrationTest
{
    public function testLock(): void
    {
        $responseList = [
            'test-lock' => [
                'uuid1',
                false,
                'uuid2',
            ],
        ];
        $rrLock = \Mockery::mock(RR\LockInterface::class)->shouldIgnoreMissing();
        $rrLock->shouldReceive('lock')->times(3)->andReturnUsing(function (string $name) use (&$responseList) {
            $array_shift = \array_shift($responseList[$name]);
            return $array_shift;
        });
        $rrLock->shouldReceive('updateTTL')->times(2)->andReturn(true);

        $rrLock->shouldReceive('release')->times(2)->andReturn(true);

        // lock
        $factory = new LockFactory(new RoadRunnerStore($rrLock));
        $lock1 = $factory->createLock('test-lock');
        Assert::true($lock1->acquire());

        $lock2 = $factory->createLock('test-lock');
        Assert::false($lock2->acquire());

        $lock1->release();

        // lock 2
        Assert::true($lock2->acquire());
        $lock2->release();
    }

    public function testLockCanBeAcquiredAgainAfterRelease(): void
    {
        $rrLock = \Mockery::mock(RR\LockInterface::class);
        $rrLock->shouldReceive('lock')->twice()->andReturn('lock-id');
        $rrLock->shouldReceive('updateTTL')->twice()->andReturn(true);
        $rrLock->shouldReceive('release')->twice()->andReturn(true);
        $rrLock->shouldReceive('exists')->andReturn(false);

        $lock = (new LockFactory(new RoadRunnerStore($rrLock)))->createLock('test-lock', autoRelease: false);

        Assert::true($lock->acquire());
        $lock->release();
        Assert::true($lock->acquire());
        $lock->release();
    }

    public function testLockThatWasNeverAcquired(): void
    {
        $rrLock = \Mockery::mock(RR\LockInterface::class);
        $rrLock->shouldReceive('exists')->andReturn(false);
        $rrLock->shouldReceive('release')->once()->andReturn(false);

        $lock = (new LockFactory(new RoadRunnerStore($rrLock)))->createLock('test-lock', autoRelease: false);

        Assert::false($lock->isAcquired());
        $lock->release();
    }
}
