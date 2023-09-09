<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Symfony\Lock;

use RoadRunner\Lock\LockInterface as RrLockInterface;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\PersistingStoreInterface;
use Symfony\Component\Lock\SharedLockStoreInterface;

final class RoadRunnerStore implements SharedLockStoreInterface
{
    private string $processId;

    public function __construct(
        private readonly RrLockInterface $rrLock,
        ?string $processId = null
    ) {
        $this->processId = $processId ?? '*';
    }

    public function save(Key $key): void
    {
        $this->rrLock->lock((string) $key, $this->processId);
    }

    public function exists(Key $key): bool
    {
        return $this->rrLock->exists((string) $key, $this->processId);
    }

    public function putOffExpiration(Key $key, float $ttl): void
    {
        $this->rrLock->updateTTL((string) $key, $this->processId, $ttl);
    }

    public function delete(Key $key): void
    {
        $this->rrLock->forceRelease((string) $key);
    }

    public function saveRead(Key $key): void
    {
        $this->rrLock->lockRead((string) $key, $this->processId);
    }
}
