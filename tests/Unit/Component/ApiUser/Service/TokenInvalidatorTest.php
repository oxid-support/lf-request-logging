<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\ApiUser\Service;

use OxidSupport\Heartbeat\Component\ApiUser\Service\TokenInvalidator;
use OxidSupport\Heartbeat\Component\ApiUser\Service\TokenInvalidatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TokenInvalidator::class)]
final class TokenInvalidatorTest extends TestCase
{
    public function testImplementsTokenInvalidatorInterface(): void
    {
        $reflection = new \ReflectionClass(TokenInvalidator::class);

        $this->assertTrue($reflection->implementsInterface(TokenInvalidatorInterface::class));
    }

    public function testClassIsFinal(): void
    {
        $reflection = new \ReflectionClass(TokenInvalidator::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function testInvalidateForApiUserReturnsInt(): void
    {
        $reflection = new \ReflectionClass(TokenInvalidator::class);
        $method = $reflection->getMethod('invalidateForApiUser');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('int', $returnType->getName());
    }

    public function testInvalidateForApiUserHasNoParameters(): void
    {
        // No userId parameter on purpose: the service is hardcoded to the
        // heartbeat-api user so no caller can wipe other users' tokens through
        // this service. See OXS-3054.
        $reflection = new \ReflectionClass(TokenInvalidator::class);
        $method = $reflection->getMethod('invalidateForApiUser');

        $this->assertCount(0, $method->getParameters());
    }

    public function testConstructorOnlyTakesQueryBuilderFactory(): void
    {
        // Intentionally no ApiUserService dependency: ApiUserService injects
        // TokenInvalidator and a circular dependency would result. See OXS-3054.
        $reflection = new \ReflectionClass(TokenInvalidator::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertCount(1, $constructor->getParameters());
        $this->assertEquals('queryBuilderFactory', $constructor->getParameters()[0]->getName());
    }
}
