<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Symfony\Lock;

use RoadRunner\Lock as RR;
use Spiral\Goridge\RPC\Exception\RPCException;
use Symfony\Component\Lock\BlockingStoreInterface;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\SharedLockStoreInterface;
use Symfony\Component\Lock\Store\ExpiringStoreTrait;

final class RoadRunnerStore implements SharedLockStoreInterface, BlockingStoreInterface
{
    use ExpiringStoreTrait;

    /**
     * @param float $initialTtl The time-to-live of the lock, in seconds. Defaults to 0 (forever).
     * @param float $initialWaitTtl How long to wait to acquire lock until returning false.
     */
    public function __construct(
        private readonly RR\LockInterface $lock,
        private readonly TokenGeneratorInterface $tokens = new RandomTokenGenerator(),
        private readonly float $initialTtl = 300.0,
        private readonly float $initialWaitTtl = 60,
    ) {
        \assert($this->initialTtl >= 0);
        \assert($this->initialWaitTtl >= 0);
    }

    public function withTtl(float $ttl): self
    {
        return new self($this->lock, $this->tokens, $ttl, $this->initialWaitTtl);
    }

    public function save(Key $key): void
    {
        \assert(false === $key->hasState(__CLASS__));

        try {
            $lockId = $this->getUniqueToken($key);

            /** @var non-empty-string $resource */
            $resource = (string)$key;

            $status = $this->lock->lock($resource, $lockId, $this->initialTtl);

            if (false === $status) {
                throw new LockConflictedException('RoadRunner. Failed to make lock');
            }

            $key->setState(__CLASS__, $lockId);
        } catch (RPCException $e) {
            throw new LockAcquiringException(message: 'RoadRunner. RPC call error', previous: $e);
        }
    }

    public function saveRead(Key $key): void
    {
        \assert(false === $key->hasState(__CLASS__));
        $lockId = $this->getUniqueToken($key);

        /** @var non-empty-string $resource */
        $resource = (string)$key;
        $status = $this->lock->lockRead($resource, $lockId, $this->initialTtl);

        if (false === $status) {
            throw new LockConflictedException('RoadRunner. Failed to make read lock');
        }

        $key->setState(__CLASS__, $lockId);
    }

    public function exists(Key $key): bool
    {
        \assert($key->hasState(__CLASS__));

        $lockId = $this->getUniqueToken($key);

        /** @var non-empty-string $resource */
        $resource = (string)$key;

        return $this->lock->exists($resource, $lockId);
    }

    public function putOffExpiration(Key $key, float $ttl): void
    {
        \assert($key->hasState(__CLASS__));
        \assert($ttl > 0);

        $lockId = $this->getUniqueToken($key);

        /** @var non-empty-string $resource */
        $resource = (string)$key;

        if (false === $this->lock->updateTTL($resource, $lockId, $ttl)) {
            throw new LockConflictedException('RoadRunner. Failed to update lock ttl');
        }
    }

    public function delete(Key $key): void
    {
        \assert($key->hasState(__CLASS__));
        $lockId = $this->getUniqueToken($key);

        /** @var non-empty-string $resource */
        $resource = (string)$key;
        $this->lock->release($resource, $lockId);
    }

    public function waitAndSave(Key $key): void
    {
        $lockId = $this->getUniqueToken($key);

        /** @var non-empty-string $resource */
        $resource = (string)$key;

        $status = $this->lock->lock($resource, $lockId, $this->initialTtl, $this->initialWaitTtl);

        $key->setState(__CLASS__, $lockId);
        if ($status === false) {
            throw new LockConflictedException('RoadRunner. Failed to make lock');
        }

        $this->checkNotExpired($key);
    }

    /**
     * @return non-empty-string
     */
    private function getUniqueToken(Key $key): string
    {
        if (!$key->hasState(__CLASS__)) {
            $token = $this->tokens->generate();
            $key->setState(__CLASS__, $token);
        }

        /** @var non-empty-string $state */
        $state = $key->getState(__CLASS__);

        return $state;
    }
}
