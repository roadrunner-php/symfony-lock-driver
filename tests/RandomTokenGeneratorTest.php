<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Symfony\Lock\Tests;

use Spiral\RoadRunner\Symfony\Lock\RandomTokenGenerator;
use Testo\Assert;
use Testo\Test;

#[Test]
final class RandomTokenGeneratorTest
{
    public function testDefaultTokenIs32BytesInHex(): void
    {
        $token = (new RandomTokenGenerator())->generate();

        Assert::same(\strlen($token), 64);
        Assert::true(\ctype_xdigit($token));
    }

    public function testTokenLengthFollowsByteLength(): void
    {
        $token = (new RandomTokenGenerator(4))->generate();

        Assert::same(\strlen($token), 8);
        Assert::true(\ctype_xdigit($token));
    }

    public function testTokensAreUnique(): void
    {
        $generator = new RandomTokenGenerator();

        Assert::notSame($generator->generate(), $generator->generate());
    }
}
