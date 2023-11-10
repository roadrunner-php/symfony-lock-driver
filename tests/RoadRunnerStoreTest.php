<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Symfony\Lock\Tests;

use PHPUnit\Framework\TestCase;
use RoadRunner\Lock\LockInterface as RrLock;
use Spiral\RoadRunner\Symfony\Lock\RoadRunnerStore;
use Spiral\RoadRunner\Symfony\Lock\TokenGeneratorInterface;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\Exception\LockReleasingException;
use Symfony\Component\Lock\Key;

final class RoadRunnerStoreTest extends TestCase
{
    private RrLock|\PHPUnit\Framework\MockObject\MockObject $rrLock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rrLock = $this->createMock(RrLock::class);
        $this->tokens = $this->createMock(TokenGeneratorInterface::class);

        $this->tokens->method('generate')
            ->willReturn('random-id');
    }

    public function testSaveSuccess(): void
    {
        $this->rrLock->expects(self::once())
            ->method('lock')
            ->with('resource-name', 'random-id')
            ->willReturn('lock-id');

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $key = new Key('resource-name');
        $store->save($key);

        $this->assertTrue($key->hasState(RoadRunnerStore::class));
        $this->assertSame('random-id', $key->getState(RoadRunnerStore::class));
    }

    public function testSaveReadSuccess(): void
    {
        $this->rrLock->expects(self::once())
            ->method('lockRead')
            ->with('resource-name', 'random-id')
            ->willReturn('lock-id');

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $key = new Key('resource-name');
        $store->saveRead($key);
    }

    public function testExistsSuccess(): void
    {
        $this->rrLock->expects(self::once())
            ->method('exists')
            ->with('resource-name')
            ->willReturn(true);

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $key = new Key('resource-name');
        $key->setState(RoadRunnerStore::class, 'lock-id');
        $store->exists($key);
    }

    public function testPutOffExpirationSuccess(): void
    {
        $this->rrLock->expects(self::once())
            ->method('updateTTL')
            ->with('resource-name', 'lock-id', 3600.0)
            ->willReturn(true);

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $key = new Key('resource-name');
        $key->setState(RoadRunnerStore::class, 'lock-id');
        $store->putOffExpiration($key, 3600.0);
    }

    public function testDeleteSuccess(): void
    {
        $this->rrLock->expects(self::once())
            ->method('release')
            ->with('resource-name')
            ->willReturn(true);

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $key = new Key('resource-name');
        $key->setState(RoadRunnerStore::class, 'lock-id');
        $store->delete($key);
    }

    public function testSaveFail(): void
    {
        $this->expectException(LockConflictedException::class);
        $this->expectExceptionMessage('RoadRunner. Failed to make lock');

        $this->rrLock->expects(self::once())
            ->method('lock')
            ->with('resource-name', 'random-id')
            ->willReturn(false);

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $store->save(new Key('resource-name'));
    }

    public function testSaveReadFail(): void
    {
        $this->expectException(LockConflictedException::class);
        $this->expectExceptionMessage('RoadRunner. Failed to make read lock');

        $this->rrLock->expects(self::once())
            ->method('lockRead')
            ->with('resource-name', 'random-id');

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $key = new Key('resource-name');
        $store->saveRead($key);
    }

    public function testExistsFail(): void
    {
        $this->rrLock->expects(self::once())
            ->method('exists')
            ->with('resource-name')
            ->willReturn(false);

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $key = new Key('resource-name');
        $key->setState(RoadRunnerStore::class, 'lock-id');
        $this->assertFalse($store->exists($key));
    }

    public function testPutOffExpirationFail(): void
    {
        $this->expectException(LockConflictedException::class);
        $this->expectExceptionMessage('RoadRunner. Failed to update lock ttl');

        $this->rrLock->expects(self::once())
            ->method('updateTTL')
            ->with('resource-name', 'lock-id', 3600.0)
            ->willReturn(false);

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $key = new Key('resource-name');
        $key->setState(RoadRunnerStore::class, 'lock-id');
        $store->putOffExpiration($key, 3600.0);
    }

    public function testDeleteFail(): void
    {
        $this->expectException(LockReleasingException::class);
        $this->expectExceptionMessage('RoadRunner. Failed to release lock');

        $this->rrLock->expects(self::once())
            ->method('release')
            ->with('resource-name')
            ->willReturn(false);

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $key = new Key('resource-name');
        $key->setState(RoadRunnerStore::class, 'lock-id');
        $store->delete($key);
    }
}
