<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Symfony\Lock;

final class RandomTokenGenerator implements TokenGeneratorInterface
{
    /**
     * @param int<1,max> $length
     */
    public function __construct(
        private readonly int $length = 32,
    ) {
    }

    public function generate(): string
    {
        return \bin2hex(\random_bytes($this->length));
    }
}
