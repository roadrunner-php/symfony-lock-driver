<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Symfony\Lock\Tests;

use PHPUnit\Framework\TestCase;
use RoadRunner\Lock\LockInterface as RrLock;
use Spiral\RoadRunner\Symfony\Lock\RoadRunnerStore;

use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\Exception\LockReleasingException;
use Symfony\Component\Lock\Key;

final class RoadRunnerStoreTest extends TestCase
{
    public function testSaveSuccess(): void
    {
        $rrLock = $this->createMock(RrLock::class);
        $rrLock->expects(self::once())
            ->method('lock')
            ->with('resource-name', null)
            ->willReturn('lock-id');
        $store = new RoadRunnerStore($rrLock, 'pid');
        $store->save(new Key('resource-name'));
    }

    public function testSaveReadSuccess(): void
    {
        $rrLock = $this->createMock(RrLock::class);
        $rrLock->expects(self::once())
            ->method('lockRead')
            ->with('resource-name', null)
            ->willReturn('lock-id');
        $store = new RoadRunnerStore($rrLock, 'pid');
        $store->saveRead(new Key('resource-name'));
    }

    public function testExistsSuccess(): void
    {
        $rrLock = $this->createMock(RrLock::class);
        $rrLock->expects(self::once())
            ->method('exists')
            ->with('resource-name', 'pid')
            ->willReturn(true);
        $store = new RoadRunnerStore($rrLock, 'pid');
        $store->exists(new Key('resource-name'));
    }

    public function testPutOffExpirationSuccess(): void
    {
        $rrLock = $this->createMock(RrLock::class);
        $rrLock->expects(self::once())
            ->method('updateTTL')
            ->with('resource-name', 'pid', 3600.0)
            ->willReturn(true);
        $store = new RoadRunnerStore($rrLock, 'pid');
        $store->putOffExpiration(new Key('resource-name'), 3600.0);
    }

    public function testDeleteSuccess(): void
    {
        $rrLock = $this->createMock(RrLock::class);
        $rrLock->expects(self::once())
            ->method('release')
            ->with('resource-name', 'pid')
            ->willReturn(true);
        $store = new RoadRunnerStore($rrLock, 'pid');
        $store->delete(new Key('resource-name'));
    }

    public function testSaveFail(): void
    {
        self::expectException(LockConflictedException::class);
        self::expectExceptionMessage('RoadRunner. Failed to make lock');

        $rrLock = $this->createMock(RrLock::class);
        $rrLock->expects(self::once())
            ->method('lock')
            ->with('resource-name', null)
            ->willReturn(false);
        $store = new RoadRunnerStore($rrLock, 'pid');
        $store->save(new Key('resource-name'));
    }

    public function testSaveReadFail(): void
    {
        self::expectException(LockConflictedException::class);
        self::expectExceptionMessage('RoadRunner. Failed to make read lock');

        $rrLock = $this->createMock(RrLock::class);
        $rrLock->expects(self::once())
            ->method('lockRead')
            ->with('resource-name', null);
        $store = new RoadRunnerStore($rrLock, 'pid');
        $store->saveRead(new Key('resource-name'));
    }

    public function testExistsFail(): void
    {
        $rrLock = $this->createMock(RrLock::class);
        $rrLock->expects(self::once())
            ->method('exists')
            ->with('resource-name', 'pid')
            ->willReturn(false);
        $store = new RoadRunnerStore($rrLock, 'pid');
        self::assertFalse(
            $store->exists(new Key('resource-name'))
        );
    }

    public function testPutOffExpirationFail(): void
    {
        self::expectException(LockConflictedException::class);
        self::expectExceptionMessage('RoadRunner. Failed to update lock ttl');

        $rrLock = $this->createMock(RrLock::class);
        $rrLock->expects(self::once())
            ->method('updateTTL')
            ->with('resource-name', 'pid', 3600.0)
            ->willReturn(false);
        $store = new RoadRunnerStore($rrLock, 'pid');
        $store->putOffExpiration(new Key('resource-name'), 3600.0);
    }

    public function testDeleteFail(): void
    {
        self::expectException(LockReleasingException::class);
        self::expectExceptionMessage('RoadRunner. Failed to release lock');

        $rrLock = $this->createMock(RrLock::class);
        $rrLock->expects(self::once())
            ->method('release')
            ->with('resource-name', 'pid')
            ->willReturn(false);
        $store = new RoadRunnerStore($rrLock, 'pid');
        $store->delete(new Key('resource-name'));
    }
}
