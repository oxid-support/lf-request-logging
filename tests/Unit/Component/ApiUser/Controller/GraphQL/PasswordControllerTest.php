<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\ApiUser\Controller\GraphQL;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Controller\GraphQL\PasswordController;
use OxidSupport\Heartbeat\Component\ApiUser\Exception\InvalidTokenException;
use OxidSupport\Heartbeat\Component\ApiUser\Exception\PasswordTooShortException;
use OxidSupport\Heartbeat\Component\ApiUser\Exception\SetPasswordFailedException;
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserServiceInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\TokenInvalidatorInterface;
use OxidSupport\Heartbeat\Module\Module;
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

    public function testSetPasswordMethodIsPublic(): void
    {
        $reflection = new ReflectionMethod(PasswordController::class, 'heartbeatSetPassword');

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

    /**
     * heartbeatResetPassword stays removed from the GraphQL surface: a stolen
     * token must never be able to rotate the password to re-establish access.
     */
    public function testResetPasswordMutationStaysRemoved(): void
    {
        $this->assertFalse(
            method_exists(PasswordController::class, 'heartbeatResetPassword'),
            'heartbeatResetPassword must not be exposed as a GraphQL mutation'
        );
        $this->assertNotContains('heartbeatResetPassword', Module::SUPPORTED_OPERATIONS);
    }

    public function testInvalidateTokensMutationExistsAsKillSwitch(): void
    {
        $this->assertTrue(
            method_exists(PasswordController::class, 'heartbeatInvalidateTokens'),
            'heartbeatInvalidateTokens must be available as the leak-response kill switch'
        );
        $this->assertContains('heartbeatInvalidateTokens', Module::SUPPORTED_OPERATIONS);

        $doc = (new ReflectionMethod(PasswordController::class, 'heartbeatInvalidateTokens'))->getDocComment();
        $this->assertStringContainsString('@Mutation', $doc);
        $this->assertStringContainsString('@Logged', $doc);
        $this->assertStringContainsString('@Right', $doc);
    }

    public function testInvalidateTokensDelegatesToInvalidatorAndReturnsCount(): void
    {
        $invalidator = $this->createMock(TokenInvalidatorInterface::class);
        $invalidator->expects($this->once())
            ->method('invalidateForApiUser')
            ->willReturn(3);

        $controller = new PasswordController(
            $this->createMock(ApiUserServiceInterface::class),
            $this->createMock(ModuleSettingBridgeInterface::class),
            $invalidator
        );

        $this->assertSame(3, $controller->heartbeatInvalidateTokens());
    }

    /**
     * The mutation is reachable unauthenticated, so it must not reveal whether a
     * setup is currently pending. "No token stored" and "wrong token" must yield
     * the SAME generic error, closing the setup-status oracle that would help an
     * attacker time a token brute force.
     */
    public function testSetPasswordGivesGenericErrorWhenNoTokenStored(): void
    {
        $settings = $this->createMock(ModuleSettingBridgeInterface::class);
        $settings->method('get')->willReturn(''); // no setup token pending

        $controller = new PasswordController(
            $this->createMock(ApiUserServiceInterface::class),
            $settings,
            $this->createMock(TokenInvalidatorInterface::class)
        );

        $this->expectException(InvalidTokenException::class);
        $controller->heartbeatSetPassword('any-token', 'a-strong-password-1234');
    }

    public function testSetPasswordRejectsPasswordShorterThanTwelve(): void
    {
        $storedToken = 'a-valid-setup-token-value';

        $settings = $this->createMock(ModuleSettingBridgeInterface::class);
        $settings->method('get')->willReturn($storedToken);

        $controller = new PasswordController(
            $this->createMock(ApiUserServiceInterface::class),
            $settings,
            $this->createMock(TokenInvalidatorInterface::class)
        );

        $this->expectException(PasswordTooShortException::class);
        $controller->heartbeatSetPassword($storedToken, 'elevenchars'); // 11 chars
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

        $controller = new PasswordController($service, $settings, $this->createMock(TokenInvalidatorInterface::class));

        try {
            $controller->heartbeatSetPassword($storedToken, 'a-strong-password-1234');
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

        $controller = new PasswordController($service, $settings, $this->createMock(TokenInvalidatorInterface::class));

        $result = $controller->heartbeatSetPassword($storedToken, 'a-strong-password-1234');

        $this->assertTrue($result);
        $this->assertSame([''], $savedValues, 'token is cleared once and not restored on success');
    }
}
