<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Symfony\Lock\Tests;

use PHPUnit\Framework\TestCase;
use RoadRunner\Lock\LockInterface as RrLock;
use Spiral\RoadRunner\Symfony\Lock\RoadRunnerStore;
use Symfony\Component\Lock\Key;

final class RoadRunnerStoreTest extends TestCase
{
    public function testSave(): void
    {
        $rrLock = $this->createMock(RrLock::class);
        $rrLock->expects(self::once())
            ->method('lock')
            ->with('resource-name', '*');
        $store = new RoadRunnerStore($rrLock);
        $store->save(new Key('resource-name'));
    }

    public function testExists(): void
    {
        $rrLock = $this->createMock(RrLock::class);
        $rrLock->expects(self::once())
            ->method('exists')
            ->with('resource-name', '*');
        $store = new RoadRunnerStore($rrLock);
        $store->exists(new Key('resource-name'));
    }

    public function testPutOffExpiration(): void
    {
        $rrLock = $this->createMock(RrLock::class);
        $rrLock->expects(self::once())
            ->method('updateTTL')
            ->with('resource-name', '*', 3600.0);
        $store = new RoadRunnerStore($rrLock);
        $store->putOffExpiration(new Key('resource-name'), 3600.0);
    }

    public function testDelete(): void
    {
        $rrLock = $this->createMock(RrLock::class);
        $rrLock->expects(self::once())
            ->method('forceRelease')
            ->with('resource-name');
        $store = new RoadRunnerStore($rrLock);
        $store->delete(new Key('resource-name'));
    }

    public function testSaveRead(): void
    {
        $rrLock = $this->createMock(RrLock::class);
        $rrLock->expects(self::once())
            ->method('lockRead')
            ->with('resource-name', '*');
        $store = new RoadRunnerStore($rrLock);
        $store->saveRead(new Key('resource-name'));
    }
}
