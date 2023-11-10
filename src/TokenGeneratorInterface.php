<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Symfony\Lock;

interface TokenGeneratorInterface
{
    /**
     * Generates a new token.
     *
     * @return non-empty-string
     */
    public function generate(): string;
}