<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Symfony\Lock;

use RoadRunner\Lock\LockInterface as RrLockInterface;
use Spiral\Goridge\RPC\Exception\RPCException;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\Exception\LockReleasingException;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\SharedLockStoreInterface;

final class RoadRunnerStore implements SharedLockStoreInterface
{
    public function __construct(
        private readonly RrLockInterface $rrLock,
        private float $initialTtl = 300.0,
        private float $initialWaitTtl = 0,
    ) {
    }

    public function save(Key $key): void
    {
        \assert(false === $key->hasState(__CLASS__));
        try {
            $lockId = $this->rrLock->lock((string) $key, null, $this->initialTtl, $this->initialWaitTtl);
            if (false === $lockId) {
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
        $lockId = $this->rrLock->lockRead((string)$key, null, $this->initialTtl, $this->initialWaitTtl);
        if (false === $lockId) {
            throw new LockConflictedException('RoadRunner. Failed to make read lock');
        }
        $key->setState(__CLASS__, $lockId);
    }

    public function exists(Key $key): bool
    {
        \assert($key->hasState(__CLASS__));
        return $this->rrLock->exists((string) $key, $key->getState(__CLASS__));
    }

    public function putOffExpiration(Key $key, float $ttl): void
    {
        \assert($key->hasState(__CLASS__));
        if (false === $this->rrLock->updateTTL((string) $key, $key->getState(__CLASS__), $ttl)) {
            throw new LockConflictedException('RoadRunner. Failed to update lock ttl');
        }
    }

    public function delete(Key $key): void
    {
        \assert($key->hasState(__CLASS__));
        if (false === $this->rrLock->release((string) $key, $key->getState(__CLASS__))) {
            throw new LockReleasingException('RoadRunner. Failed to release lock');
        }
    }
}
