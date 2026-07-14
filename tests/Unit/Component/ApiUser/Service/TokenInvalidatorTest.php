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

    public function testInvalidateForUserIdTakesOneStringParameterAndReturnsInt(): void
    {
        // Id-based entry point for the User-model save hook: it must target the
        // exact saved row, not a shop-scope-resolved user. See OXS-3133.
        $reflection = new \ReflectionClass(TokenInvalidator::class);
        $method = $reflection->getMethod('invalidateForUserId');

        $this->assertEquals('int', $method->getReturnType()?->getName());
        $this->assertCount(1, $method->getParameters());
        $this->assertEquals('userId', $method->getParameters()[0]->getName());
        $this->assertEquals('string', $method->getParameters()[0]->getType()?->getName());
    }

    public function testInvalidateForUserIdIsDeclaredOnTheInterface(): void
    {
        // Both entry points belong to the contract so every caller (User model,
        // password reset) goes through the same identity-guarded service.
        $reflection = new \ReflectionClass(TokenInvalidatorInterface::class);

        $this->assertTrue($reflection->hasMethod('invalidateForUserId'));
    }

    public function testConstructorDoesNotDependOnApiUserService(): void
    {
        // Intentionally no ApiUserService dependency: ApiUserService injects
        // TokenInvalidator and a circular dependency would result. See OXS-3054.
        // ApiUserShopScope is injected to scope the lookup to the current shop
        // under EE mall-users-off (per-subshop service user). See OXS-3046.
        $reflection = new \ReflectionClass(TokenInvalidator::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $names = array_map(
            static fn (\ReflectionParameter $p): string => $p->getName(),
            $constructor->getParameters()
        );
        $this->assertContains('queryBuilderFactory', $names);
        $this->assertContains('apiUserShopScope', $names);
        $this->assertNotContains('apiUserService', $names);
    }
}
