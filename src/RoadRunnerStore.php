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
     * @param float $initialTtl Default lock time-to-live, in seconds. Defaults to 300. When it elapses the lock is
     *                          released automatically; 0 means the lock never expires on its own.
     * @param float $initialWaitTtl Default time to wait for the lock to become free before giving up, in seconds.
     *                              Defaults to 0, which is effectively non-blocking: the RoadRunner server caps a 0
     *                              wait at 1ms, so acquiring an already-held lock fails (throws LockConflictedException)
     *                              almost immediately. A positive value blocks for up to that duration.
     */
    public function __construct(
        private readonly RR\LockInterface $lock,
        private readonly TokenGeneratorInterface $tokens = new RandomTokenGenerator(),
        private readonly float $initialTtl = 300.0,
        private readonly float $initialWaitTtl = 0,
    ) {
        \assert($this->initialTtl >= 0);
        \assert($this->initialWaitTtl >= 0);
    }

    /**
     * Clone the current instance with different ttl / waitTtl values.
     *
     * @param float $ttl Lock time-to-live, in seconds. 0 means the lock never expires on its own.
     * @param float|null $waitTtl Time to wait for the lock to become free, in seconds. Null keeps the current
     *                            instance's waitTtl. See the constructor for the meaning of 0 (non-blocking).
     */
    public function withTtl(float $ttl, ?float $waitTtl = null): self
    {
        $waitTtl ??= $this->initialWaitTtl;
        return new self($this->lock, $this->tokens, $ttl, $waitTtl);
    }

    #[\Override]
    public function save(Key $key): void
    {
        \assert(false === $key->hasState(__CLASS__));

        try {
            $lockId = $this->getUniqueToken($key);

            /** @var non-empty-string $resource */
            $resource = (string)$key;

            $status = $this->lock->lock($resource, $lockId, $this->initialTtl, $this->initialWaitTtl);

            if (false === $status) {
                throw new LockConflictedException('RoadRunner. Failed to make lock');
            }

            $key->setState(__CLASS__, $lockId);
        } catch (RPCException $e) {
            throw new LockAcquiringException(message: 'RoadRunner. RPC call error', previous: $e);
        }
    }

    #[\Override]
    public function saveRead(Key $key): void
    {
        \assert(false === $key->hasState(__CLASS__));
        $lockId = $this->getUniqueToken($key);

        /** @var non-empty-string $resource */
        $resource = (string)$key;
        $status = $this->lock->lockRead($resource, $lockId, $this->initialTtl, $this->initialWaitTtl);

        if (false === $status) {
            throw new LockConflictedException('RoadRunner. Failed to make read lock');
        }

        $key->setState(__CLASS__, $lockId);
    }

    #[\Override]
    public function exists(Key $key): bool
    {
        \assert($key->hasState(__CLASS__));

        $lockId = $this->getUniqueToken($key);

        /** @var non-empty-string $resource */
        $resource = (string)$key;

        return $this->lock->exists($resource, $lockId);
    }

    #[\Override]
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

    #[\Override]
    public function delete(Key $key): void
    {
        \assert($key->hasState(__CLASS__));
        $lockId = $this->getUniqueToken($key);

        /** @var non-empty-string $resource */
        $resource = (string)$key;
        $this->lock->release($resource, $lockId);
    }

    #[\Override]
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
