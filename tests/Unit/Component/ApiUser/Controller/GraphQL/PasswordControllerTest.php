<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\ApiUser\Controller\GraphQL;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Controller\GraphQL\PasswordController;
use OxidSupport\Heartbeat\Component\ApiUser\Exception\SetPasswordFailedException;
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserServiceInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\TokenGeneratorInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\TokenInvalidatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

#[CoversClass(PasswordController::class)]
final class PasswordControllerTest extends TestCase
{
    public function testSetPasswordMethodHasMutationAnnotation(): void
    {
        $reflection = new ReflectionMethod(PasswordController::class, 'heartbeatSetPassword');

        $this->assertStringContainsString(
            '@Mutation',
            $reflection->getDocComment(),
            "heartbeatSetPassword must have @Mutation annotation"
        );
    }

    public function testSetPasswordUsesTokenAuthNotSessionAuth(): void
    {
        $reflection = new ReflectionMethod(PasswordController::class, 'heartbeatSetPassword');

        // Should NOT have @Logged - uses token-based auth instead
        $this->assertStringNotContainsString(
            '@Logged',
            $reflection->getDocComment(),
            "heartbeatSetPassword must NOT have @Logged - uses token auth"
        );
    }

    public function testResetPasswordMethodHasMutationAnnotation(): void
    {
        $reflection = new ReflectionMethod(PasswordController::class, 'heartbeatResetPassword');

        $this->assertStringContainsString(
            '@Mutation',
            $reflection->getDocComment(),
            "heartbeatResetPassword must have @Mutation annotation"
        );
    }

    public function testResetPasswordRequiresAuthentication(): void
    {
        $reflection = new ReflectionMethod(PasswordController::class, 'heartbeatResetPassword');

        $this->assertStringContainsString(
            '@Logged',
            $reflection->getDocComment(),
            "heartbeatResetPassword must have @Logged annotation"
        );
    }

    public function testResetPasswordRequiresSpecificRight(): void
    {
        $reflection = new ReflectionMethod(PasswordController::class, 'heartbeatResetPassword');

        $this->assertStringContainsString(
            '@Right',
            $reflection->getDocComment(),
            "heartbeatResetPassword must have @Right annotation"
        );
    }

    public function testSetPasswordMethodIsPublic(): void
    {
        $reflection = new ReflectionMethod(PasswordController::class, 'heartbeatSetPassword');

        $this->assertTrue($reflection->isPublic());
    }

    public function testResetPasswordMethodIsPublic(): void
    {
        $reflection = new ReflectionMethod(PasswordController::class, 'heartbeatResetPassword');

        $this->assertTrue($reflection->isPublic());
    }

    public function testSetPasswordHasTokenParameter(): void
    {
        $reflection = new ReflectionMethod(PasswordController::class, 'heartbeatSetPassword');
        $parameters = $reflection->getParameters();

        $parameterNames = array_map(fn($p) => $p->getName(), $parameters);

        $this->assertContains('token', $parameterNames);
    }

    public function testSetPasswordHasPasswordParameter(): void
    {
        $reflection = new ReflectionMethod(PasswordController::class, 'heartbeatSetPassword');
        $parameters = $reflection->getParameters();

        $parameterNames = array_map(fn($p) => $p->getName(), $parameters);

        $this->assertContains('password', $parameterNames);
    }

    public function testSetPasswordReturnsBool(): void
    {
        $reflection = new ReflectionMethod(PasswordController::class, 'heartbeatSetPassword');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }

    public function testResetPasswordReturnsString(): void
    {
        $reflection = new ReflectionMethod(PasswordController::class, 'heartbeatResetPassword');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    /**
     * OXS-3068: when setPasswordForApiUser fails after the token was cleared, the
     * token must be restored so the setup stays retryable, and a typed exception
     * must surface instead of leaving the service user locked out.
     */
    public function testSetPasswordRestoresTokenWhenServiceFails(): void
    {
        $storedToken = 'a-valid-setup-token-value';
        $savedValues = [];

        $settings = $this->createMock(ModuleSettingBridgeInterface::class);
        $settings->method('get')->willReturn($storedToken);
        $settings->method('save')->willReturnCallback(
            function (string $name, $value) use (&$savedValues): void {
                $savedValues[] = $value;
            }
        );

        $service = $this->createMock(ApiUserServiceInterface::class);
        $service->method('setPasswordForApiUser')
            ->willThrowException(new \RuntimeException('internal token table missing'));

        $controller = new PasswordController(
            $service,
            $settings,
            $this->createMock(TokenGeneratorInterface::class),
            $this->createMock(TokenInvalidatorInterface::class)
        );

        try {
            $controller->heartbeatSetPassword($storedToken, 'password123');
            $this->fail('Expected SetPasswordFailedException');
        } catch (SetPasswordFailedException $e) {
            $this->assertInstanceOf(\RuntimeException::class, $e->getPrevious());
        }

        // Token is first cleared (TOCTOU), then restored because the service failed.
        $this->assertSame(['', $storedToken], $savedValues, 'token must be cleared then restored on failure');
    }

    /**
     * OXS-3068: on success the token stays cleared (not restored) and the
     * mutation returns true.
     */
    public function testSetPasswordKeepsTokenClearedOnSuccess(): void
    {
        $storedToken = 'a-valid-setup-token-value';
        $savedValues = [];

        $settings = $this->createMock(ModuleSettingBridgeInterface::class);
        $settings->method('get')->willReturn($storedToken);
        $settings->method('save')->willReturnCallback(
            function (string $name, $value) use (&$savedValues): void {
                $savedValues[] = $value;
            }
        );

        $service = $this->createMock(ApiUserServiceInterface::class);

        $controller = new PasswordController(
            $service,
            $settings,
            $this->createMock(TokenGeneratorInterface::class),
            $this->createMock(TokenInvalidatorInterface::class)
        );

        $result = $controller->heartbeatSetPassword($storedToken, 'password123');

        $this->assertTrue($result);
        $this->assertSame([''], $savedValues, 'token is cleared once and not restored on success');
    }
}
