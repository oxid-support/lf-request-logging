<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\ApiUser\Service;

use OxidSupport\Heartbeat\Component\ApiUser\Service\TokenGenerator;
use OxidSupport\Heartbeat\Component\ApiUser\Service\TokenGeneratorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TokenGenerator::class)]
final class TokenGeneratorTest extends TestCase
{
    public function testImplementsTokenGeneratorInterface(): void
    {
        $reflection = new \ReflectionClass(TokenGenerator::class);

        $this->assertTrue($reflection->implementsInterface(TokenGeneratorInterface::class));
    }

    public function testClassIsFinal(): void
    {
        $reflection = new \ReflectionClass(TokenGenerator::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function testGenerateMethodExists(): void
    {
        $reflection = new \ReflectionClass(TokenGenerator::class);

        $this->assertTrue($reflection->hasMethod('generate'));
    }

    public function testGenerateMethodIsPublic(): void
    {
        $reflection = new \ReflectionClass(TokenGenerator::class);
        $method = $reflection->getMethod('generate');

        $this->assertTrue($method->isPublic());
    }

    public function testGenerateMethodReturnsString(): void
    {
        $reflection = new \ReflectionClass(TokenGenerator::class);
        $method = $reflection->getMethod('generate');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    public function testGenerateMethodHasNoParameters(): void
    {
        $reflection = new \ReflectionClass(TokenGenerator::class);
        $method = $reflection->getMethod('generate');

        $this->assertCount(0, $method->getParameters());
    }

    public function testGenerateReturnsCryptographicallyStrongToken(): void
    {
        // The setup token is the only gate on the unauthenticated
        // heartbeatSetPassword mutation, so it must come from a CSPRNG.
        // random_bytes(32) rendered as hex is 64 lowercase hex chars.
        $token = (new TokenGenerator())->generate();

        $this->assertSame(
            1,
            preg_match('/^[a-f0-9]{64}$/', $token),
            'Setup token must be 64 hex chars from a CSPRNG (random_bytes(32)), '
            . 'not an md5(uniqid()) value.'
        );
    }

    public function testGenerateReturnsDistinctTokens(): void
    {
        $generator = new TokenGenerator();

        $tokens = [];
        for ($i = 0; $i < 100; $i++) {
            $tokens[$generator->generate()] = true;
        }

        $this->assertCount(100, $tokens, 'Every generated token must be unique.');
    }
}
