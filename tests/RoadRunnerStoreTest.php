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
use Spiral\Goridge\RPC\Exception\RPCException;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\Exception\LockExpiredException;
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

    public function testSaveRetryAfterConflictReusesToken(): void
    {
        $this->rrLock->shouldReceive('lock')->twice()->with('resource-name', 'random-id', \Mockery::andAnyOtherArgs())
            ->andReturn(false, 'lock-id');
        $tokens = \Mockery::mock(TokenGeneratorInterface::class);
        $tokens->shouldReceive('generate')->once()->andReturn('random-id');

        $store = new RoadRunnerStore($this->rrLock, $tokens);
        $key = new Key('resource-name');

        try {
            $store->save($key);
            Assert::fail('The first attempt must conflict.');
        } catch (LockConflictedException) {
        }
        $store->save($key);

        Assert::same($key->getState(RoadRunnerStore::class), 'random-id');
    }

    public function testWithTtlKeepsWaitTtlOfCurrentInstance(): void
    {
        $this->rrLock->shouldReceive('lock')->once()->with('resource-name', 'random-id', 10.0, 5.0)->andReturn('lock-id');

        $store = new RoadRunnerStore($this->rrLock, $this->tokens, initialWaitTtl: 5.0);
        $store->withTtl(10.0)->save(new Key('resource-name'));
    }

    public function testWithTtlLeavesOriginalStoreUnchanged(): void
    {
        $this->rrLock->shouldReceive('lock')->once()->with('resource-name', 'random-id', 300.0, 0.0)->andReturn('lock-id');

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        Assert::notSame($store->withTtl(10.0, 1.0), $store);

        $store->save(new Key('resource-name'));
    }

    public function testSaveWrapsRpcError(): void
    {
        $rpcError = new RPCException('connection lost');
        Expect::exception(LockAcquiringException::class)
            ->withMessage('RoadRunner. RPC call error')
            ->withPrevious($rpcError);

        $this->rrLock->shouldReceive('lock')->once()->andThrow($rpcError);

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $store->save(new Key('resource-name'));
    }

    public function testSaveReadStoresToken(): void
    {
        $this->rrLock->shouldReceive('lockRead')->once()->andReturn('lock-id');

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $key = new Key('resource-name');
        $store->saveRead($key);

        Assert::same($key->getState(RoadRunnerStore::class), 'random-id');
    }

    public function testOperationsOnAcquiredKeyUseItsToken(): void
    {
        $this->rrLock->shouldReceive('exists')->once()->with('resource-name', 'lock-id')->andReturn(true);
        $this->rrLock->shouldReceive('updateTTL')->once()->with('resource-name', 'lock-id', 60.0)->andReturn(true);
        $this->rrLock->shouldReceive('release')->once()->with('resource-name', 'lock-id')->andReturn(true);
        $this->tokens->shouldNotReceive('generate');

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $key = new Key('resource-name');
        $key->setState(RoadRunnerStore::class, 'lock-id');

        Assert::true($store->exists($key));
        $store->putOffExpiration($key, 60.0);
        $store->delete($key);
    }

    public function testWaitAndSaveReleasesExpiredKey(): void
    {
        Expect::exception(LockExpiredException::class);

        $this->rrLock->shouldReceive('lock')->once()->andReturn('lock-id');
        $this->rrLock->shouldReceive('release')->once()->with('resource-name', 'random-id')->andReturn(true);

        $store = new RoadRunnerStore($this->rrLock, $this->tokens);
        $key = new Key('resource-name');
        $key->reduceLifetime(-1);
        $store->waitAndSave($key);
    }

    public function testDefaultTokenGeneratorProducesRandomToken(): void
    {
        $this->rrLock->shouldReceive('lock')->once()->andReturn('lock-id');

        $store = new RoadRunnerStore($this->rrLock);
        $key = new Key('resource-name');
        $store->save($key);

        $token = $key->getState(RoadRunnerStore::class);
        Assert::same(\strlen($token), 64);
        Assert::true(\ctype_xdigit($token));
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->rrLock = \Mockery::mock(RrLock::class)->shouldIgnoreMissing();
        $this->tokens = \Mockery::mock(TokenGeneratorInterface::class)->shouldIgnoreMissing();

        $this->tokens->shouldReceive('generate')->andReturn('random-id');
    }
}
