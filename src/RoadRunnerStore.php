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
        private readonly string          $processId
    ) {
    }

    public function save(Key $key): void
    {
        try {
            $lockId = $this->rrLock->lock((string)$key);
            if (false === $lockId) {
                throw new LockConflictedException('RoadRunner. Failed to make lock');
            }
        } catch (RPCException $e) {
            throw new LockAcquiringException(message: 'RoadRunner. RPC call error', previous: $e);
        }
    }

    public function saveRead(Key $key): void
    {
        if (false === $this->rrLock->lockRead((string)$key)) {
            throw new LockConflictedException('RoadRunner. Failed to make read lock');
        }
    }

    public function exists(Key $key): bool
    {
        return $this->rrLock->exists((string)$key, $this->processId);
    }

    public function putOffExpiration(Key $key, float $ttl): void
    {
        if (false === $this->rrLock->updateTTL((string)$key, $this->processId, $ttl)) {
            throw new LockConflictedException('RoadRunner. Failed to update lock ttl');
        }
    }

    public function delete(Key $key): void
    {
        if (false === $this->rrLock->release((string)$key, $this->processId)) {
            throw new LockReleasingException('RoadRunner. Failed to release lock');
        }
    }
}
