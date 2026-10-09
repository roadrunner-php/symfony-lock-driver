<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Symfony\Lock\Tests;

use Mockery\MockInterface;
use Testo\Test;
use Testo\Assert;
use Testo\Expect;
use Testo\Data\DataProvider;
use Testo\Lifecycle\BeforeTest;
use RoadRunner\Lock\LockInterface as RrLock;
use Spiral\RoadRunner\Symfony\Lock\RoadRunnerStore;
use Spiral\RoadRunner\Symfony\Lock\TokenGeneratorInterface;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\Key;

#[Test]
final class RoadRunnerStoreTest
{
    private RrLock&MockInterface $rrLock;
    private TokenGeneratorInterface&MockInterface $tokens;

    public static function dataWithTtl(): iterable
    {
        yield [
            100,
            null,
            100,
            0,
        ];

        yield [
            0.1,
            0.1,
            0.1,
            0.1,
        ];

        yield [
            0,
            0,
            0,
            0,
        ];
    }

    public function testSaveSuccess(): void
    {
        $this->rrLock->shouldReceive('lock')->once()->with('resource-name', 'random-id', \Mockery::andAnyOtherArgs())->andReturn('lock-id');

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $key = new Key('resource-name');
        $store->save($key);

        Assert::true($key->hasState(RoadRunnerStore::class));
        Assert::same($key->getState(RoadRunnerStore::class), 'random-id');
    }

    public function testSaveReadSuccess(): void
    {
        $this->rrLock->shouldReceive('lockRead')->once()->with('resource-name', 'random-id', \Mockery::andAnyOtherArgs())->andReturn('lock-id');

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $key = new Key('resource-name');
        $store->saveRead($key);
    }

    public function testExistsSuccess(): void
    {
        $this->rrLock->shouldReceive('exists')->once()->with('resource-name', \Mockery::andAnyOtherArgs())->andReturn(true);

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $key = new Key('resource-name');
        $key->setState(RoadRunnerStore::class, 'lock-id');
        $store->exists($key);
    }

    public function testPutOffExpirationSuccess(): void
    {
        $this->rrLock->shouldReceive('updateTTL')->once()->with('resource-name', 'lock-id', 3600.0, \Mockery::andAnyOtherArgs())->andReturn(true);

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $key = new Key('resource-name');
        $key->setState(RoadRunnerStore::class, 'lock-id');
        $store->putOffExpiration($key, 3600.0);
    }

    public function testDeleteSuccess(): void
    {
        $this->rrLock->shouldReceive('release')->once()->with('resource-name', \Mockery::andAnyOtherArgs())->andReturn(true);

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $key = new Key('resource-name');
        $key->setState(RoadRunnerStore::class, 'lock-id');
        $store->delete($key);
    }

    public function testSaveFail(): void
    {
        Expect::exception(LockConflictedException::class)->withMessageContaining('RoadRunner. Failed to make lock');

        $this->rrLock->shouldReceive('lock')->once()->with('resource-name', 'random-id', \Mockery::andAnyOtherArgs())->andReturn(false);

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $store->save(new Key('resource-name'));
    }

    public function testSaveReadFail(): void
    {
        Expect::exception(LockConflictedException::class)->withMessageContaining('RoadRunner. Failed to make read lock');

        $this->rrLock->shouldReceive('lockRead')->once()->with('resource-name', 'random-id', \Mockery::andAnyOtherArgs())->andReturn(false);

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $key = new Key('resource-name');
        $store->saveRead($key);
    }

    public function testExistsFail(): void
    {
        $this->rrLock->shouldReceive('exists')->once()->with('resource-name', \Mockery::andAnyOtherArgs())->andReturn(false);

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $key = new Key('resource-name');
        $key->setState(RoadRunnerStore::class, 'lock-id');
        Assert::false($store->exists($key));
    }

    public function testPutOffExpirationFail(): void
    {
        Expect::exception(LockConflictedException::class)->withMessageContaining('RoadRunner. Failed to update lock ttl');

        $this->rrLock->shouldReceive('updateTTL')->once()->with('resource-name', 'lock-id', 3600.0, \Mockery::andAnyOtherArgs())->andReturn(false);

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $key = new Key('resource-name');
        $key->setState(RoadRunnerStore::class, 'lock-id');
        $store->putOffExpiration($key, 3600.0);
    }

    public function testDeleteFail(): void
    {
        $this->rrLock->shouldReceive('release')->once()->with('resource-name', \Mockery::andAnyOtherArgs())->andReturn(false);

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $key = new Key('resource-name');
        $key->setState(RoadRunnerStore::class, 'lock-id');
        $store->delete($key);
    }

    public function testWaitAndSaveSuccess(): void
    {
        $this->rrLock->shouldReceive('lock')->once()->with('resource-name', 'random-id', 300, 0, \Mockery::andAnyOtherArgs())->andReturn('lock-id');

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $key = new Key('resource-name');
        $store->waitAndSave($key);

        Assert::true($key->hasState(RoadRunnerStore::class));
        Assert::same($key->getState(RoadRunnerStore::class), 'random-id');
    }

    public function testWaitAndSaveFail(): void
    {
        Expect::exception(LockConflictedException::class)->withMessageContaining('RoadRunner. Failed to make lock');

        $this->rrLock->shouldReceive('lock')->once()->with('resource-name', 'random-id', 300, 0, \Mockery::andAnyOtherArgs())->andReturn(false);

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $store->waitAndSave(new Key('resource-name'));
    }

    #[DataProvider('dataWithTtl')]
    public function testWithTtl(float $ttl, ?float $waitTtl, float $ttlExp, float $waitTtlExp): void
    {
        $this->rrLock->shouldReceive('lock')->once()->with('resource-name', 'random-id', $ttlExp, $waitTtlExp, \Mockery::andAnyOtherArgs())->andReturn('lock-id');

        $s = new RoadRunnerStore($this->rrLock, $this->tokens);
        $s->withTtl($ttl, $waitTtl)->save(new Key('resource-name'));
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->rrLock = \Mockery::mock(RrLock::class)->shouldIgnoreMissing();
        $this->tokens = \Mockery::mock(TokenGeneratorInterface::class)->shouldIgnoreMissing();

        $this->tokens->shouldReceive('generate')->andReturn('random-id');
    }
}
