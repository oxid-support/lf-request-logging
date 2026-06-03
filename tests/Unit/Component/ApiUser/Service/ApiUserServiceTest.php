<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\ApiUser\Service;

use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserService;
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserServiceInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApiUserService::class)]
final class ApiUserServiceTest extends TestCase
{
    public function testImplementsApiUserServiceInterface(): void
    {
        $reflection = new \ReflectionClass(ApiUserService::class);

        $this->assertTrue($reflection->implementsInterface(ApiUserServiceInterface::class));
    }

    public function testClassIsFinal(): void
    {
        $reflection = new \ReflectionClass(ApiUserService::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function testLoadApiUserMethodExists(): void
    {
        $reflection = new \ReflectionClass(ApiUserService::class);

        $this->assertTrue($reflection->hasMethod('loadApiUser'));
    }

    public function testResetPasswordMethodExists(): void
    {
        $reflection = new \ReflectionClass(ApiUserService::class);

        $this->assertTrue($reflection->hasMethod('resetPassword'));
    }

    public function testSetPasswordForApiUserMethodExists(): void
    {
        $reflection = new \ReflectionClass(ApiUserService::class);

        $this->assertTrue($reflection->hasMethod('setPasswordForApiUser'));
    }

    public function testResetPasswordForApiUserMethodExists(): void
    {
        $reflection = new \ReflectionClass(ApiUserService::class);

        $this->assertTrue($reflection->hasMethod('resetPasswordForApiUser'));
    }

    public function testLoadApiUserReturnsBoolean(): void
    {
        $reflection = new \ReflectionClass(ApiUserService::class);
        $method = $reflection->getMethod('loadApiUser');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }

    public function testResetPasswordReturnsVoid(): void
    {
        $reflection = new \ReflectionClass(ApiUserService::class);
        $method = $reflection->getMethod('resetPassword');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    public function testSetPasswordForApiUserReturnsVoid(): void
    {
        $reflection = new \ReflectionClass(ApiUserService::class);
        $method = $reflection->getMethod('setPasswordForApiUser');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    public function testResetPasswordForApiUserReturnsVoid(): void
    {
        $reflection = new \ReflectionClass(ApiUserService::class);
        $method = $reflection->getMethod('resetPasswordForApiUser');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    public function testConstructorRequiresQueryBuilderFactoryAndTokenInvalidator(): void
    {
        $reflection = new \ReflectionClass(ApiUserService::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertCount(2, $constructor->getParameters());

        $params = $constructor->getParameters();
        $this->assertEquals('queryBuilderFactory', $params[0]->getName());
        $this->assertEquals('tokenInvalidator', $params[1]->getName());
    }

    public function testAllPublicApiMethodsArePublic(): void
    {
        $reflection = new \ReflectionClass(ApiUserService::class);
        $methods = ['loadApiUser', 'resetPassword', 'setPasswordForApiUser', 'resetPasswordForApiUser'];

        foreach ($methods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPublic(), "Method $methodName should be public");
        }
    }

    public function testNoStandaloneInvalidateMethodOnApiUserService(): void
    {
        $reflection = new \ReflectionClass(ApiUserService::class);

        // Token invalidation lives on TokenInvalidatorService and is delegated to
        // from resetPasswordForApiUser(). It must not become a standalone method
        // on ApiUserService, because that would let callers wipe tokens without
        // resetting the password too. See OXS-3054.
        $this->assertFalse(
            $reflection->hasMethod('invalidateTokensForUser'),
            'Token invalidation must not live on ApiUserService directly'
        );
        $this->assertFalse(
            $reflection->hasMethod('invalidateApiUserTokens'),
            'Token invalidation must not live on ApiUserService directly'
        );
    }
}
