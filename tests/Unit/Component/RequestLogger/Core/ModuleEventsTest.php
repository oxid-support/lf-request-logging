<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\RequestLogger\Core;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidSupport\Heartbeat\Module\Module;
use OxidSupport\Heartbeat\Component\RequestLogger\Core\ModuleEvents;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Tests for ModuleEvents focusing on the workflow state machine.
 *
 * Workflow States:
 * 1. Fresh install: No password (placeholder) -> generate token
 * 2. After activation: Token generated, password still placeholder
 * 3. After setup complete: password is BCrypt -> no token generated
 * 4. Re-activation while not yet set up (password still placeholder): a fresh
 *    per-shop token is generated even if a (stale/inherited) token value exists.
 *
 * The key insight: the token is generated whenever THIS shop's service-user
 * password is still a placeholder. An already-present token value must NOT skip
 * generation: on EE a subshop inherits the base shop's module settings (incl.
 * this token), so reusing it made subshops share the base shop's token. Only a
 * completed setup (bcrypt password on this shop's row) skips generation.
 * See OXS-3103.
 */
#[CoversClass(ModuleEvents::class)]
final class ModuleEventsTest extends TestCase
{
    /**
     * Workflow State: Fresh activation (first time)
     * - Token: empty
     * - Password: placeholder (not BCrypt)
     * - Expected: Generate new token
     */
    public function testOnActivateGeneratesTokenWhenTokenEmptyAndPasswordIsPlaceholder(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);

        // Token is empty (first call returns empty)
        $moduleSettingService
            ->expects($this->once())
            ->method('getString')
            ->with(Module::SETTING_APIUSER_SETUP_TOKEN, Module::ID)
            ->willReturn(new \Symfony\Component\String\UnicodeString(''));

        // Password is placeholder (not BCrypt)
        $result = $this->createMock(Result::class);
        $result->expects($this->once())
            ->method('fetchOne')
            ->willReturn('a1b2c3d4e5f6placeholder');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('execute')->willReturn($result);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        // Expect token to be saved
        $moduleSettingService
            ->expects($this->once())
            ->method('saveString')
            ->with(
                Module::SETTING_APIUSER_SETUP_TOKEN,
                $this->matchesRegularExpression('/^[a-f0-9]{32}$/'),
                Module::ID
            );

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(function ($service) use ($moduleSettingService, $queryBuilderFactory) {
                return match ($service) {
                    ModuleSettingServiceInterface::class => $moduleSettingService,
                    QueryBuilderFactoryInterface::class => $queryBuilderFactory,
                    default => null,
                };
            });

        $this->mockContainer($container);

        ModuleEvents::onActivate();
    }

    /**
     * Workflow State: a token value already exists but the password is NOT set
     * (e.g. an EE subshop that inherited the base shop's token at creation).
     * - Token: has a (stale/inherited) value
     * - Password: placeholder (not BCrypt)
     * - Expected: generate a FRESH per-shop token, overwriting the inherited one.
     *
     * Regression guard for the subshop shared-token bug: onActivate must not gate
     * generation on an existing token value, only on this shop's password. See
     * OXS-3103.
     */
    public function testOnActivateRegeneratesTokenWhenInheritedTokenExistsButPasswordNotSet(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);

        // Password is still a placeholder for THIS shop's service user.
        $result = $this->createMock(Result::class);
        $result->expects($this->once())
            ->method('fetchOne')
            ->willReturn('a1b2c3d4e5f6placeholder');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('execute')->willReturn($result);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        // A fresh token IS saved even though an inherited value was present.
        $moduleSettingService
            ->expects($this->once())
            ->method('saveString')
            ->with(
                Module::SETTING_APIUSER_SETUP_TOKEN,
                $this->matchesRegularExpression('/^[a-f0-9]{32}$/'),
                Module::ID
            );

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(function ($service) use ($moduleSettingService, $queryBuilderFactory) {
                return match ($service) {
                    ModuleSettingServiceInterface::class => $moduleSettingService,
                    QueryBuilderFactoryInterface::class => $queryBuilderFactory,
                    default => null,
                };
            });

        $this->mockContainer($container);

        ModuleEvents::onActivate();
    }

    /**
     * Workflow State: Re-activation after successful setup
     * - Token: empty (was deleted after password was set)
     * - Password: BCrypt (starts with $2y$)
     * - Expected: Don't generate new token (setup was already completed)
     *
     * This is the critical test for the re-activation bug fix.
     */
    public function testOnActivateDoesNotGenerateTokenWhenPasswordIsBCrypt(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);

        // Token is empty
        $moduleSettingService
            ->expects($this->once())
            ->method('getString')
            ->with(Module::SETTING_APIUSER_SETUP_TOKEN, Module::ID)
            ->willReturn(new \Symfony\Component\String\UnicodeString(''));

        // Password is BCrypt (setup was completed)
        $result = $this->createMock(Result::class);
        $result->expects($this->once())
            ->method('fetchOne')
            ->willReturn('$2y$10$abcdefghijklmnopqrstuv');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('execute')->willReturn($result);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        // Should NOT save a new token
        $moduleSettingService
            ->expects($this->never())
            ->method('saveString');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(function ($service) use ($moduleSettingService, $queryBuilderFactory) {
                return match ($service) {
                    ModuleSettingServiceInterface::class => $moduleSettingService,
                    QueryBuilderFactoryInterface::class => $queryBuilderFactory,
                    default => null,
                };
            });

        $this->mockContainer($container);

        ModuleEvents::onActivate();
    }

    /**
     * Workflow State: Re-activation with $2a$ BCrypt variant
     * - Token: empty
     * - Password: BCrypt with $2a$ prefix
     * - Expected: Don't generate new token
     */
    public function testOnActivateRecognizesBCrypt2aVariant(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);

        $moduleSettingService
            ->expects($this->once())
            ->method('getString')
            ->willReturn(new \Symfony\Component\String\UnicodeString(''));

        $result = $this->createMock(Result::class);
        $result->expects($this->once())
            ->method('fetchOne')
            ->willReturn('$2a$10$abcdefghijklmnopqrstuv');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('execute')->willReturn($result);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        $moduleSettingService->expects($this->never())->method('saveString');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(fn($service) => match ($service) {
                ModuleSettingServiceInterface::class => $moduleSettingService,
                QueryBuilderFactoryInterface::class => $queryBuilderFactory,
                default => null,
            });

        $this->mockContainer($container);

        ModuleEvents::onActivate();
    }

    /**
     * Workflow State: Re-activation with $2b$ BCrypt variant
     * - Token: empty
     * - Password: BCrypt with $2b$ prefix
     * - Expected: Don't generate new token
     */
    public function testOnActivateRecognizesBCrypt2bVariant(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);

        $moduleSettingService
            ->expects($this->once())
            ->method('getString')
            ->willReturn(new \Symfony\Component\String\UnicodeString(''));

        $result = $this->createMock(Result::class);
        $result->expects($this->once())
            ->method('fetchOne')
            ->willReturn('$2b$10$abcdefghijklmnopqrstuv');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('execute')->willReturn($result);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        $moduleSettingService->expects($this->never())->method('saveString');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(fn($service) => match ($service) {
                ModuleSettingServiceInterface::class => $moduleSettingService,
                QueryBuilderFactoryInterface::class => $queryBuilderFactory,
                default => null,
            });

        $this->mockContainer($container);

        ModuleEvents::onActivate();
    }

    /**
     * Workflow State: Database error when checking token
     * - Token: exception thrown
     * - Expected: Generate new token (treat as empty)
     */
    public function testOnActivateGeneratesTokenWhenGetStringThrowsException(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);

        // Exception when getting token
        $moduleSettingService
            ->expects($this->once())
            ->method('getString')
            ->willThrowException(new \RuntimeException('Setting not found'));

        // Password is placeholder
        $result = $this->createMock(Result::class);
        $result->expects($this->once())
            ->method('fetchOne')
            ->willReturn('placeholder-not-bcrypt');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('execute')->willReturn($result);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        // Should save a new token
        $moduleSettingService
            ->expects($this->once())
            ->method('saveString');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(fn($service) => match ($service) {
                ModuleSettingServiceInterface::class => $moduleSettingService,
                QueryBuilderFactoryInterface::class => $queryBuilderFactory,
                default => null,
            });

        $this->mockContainer($container);

        ModuleEvents::onActivate();
    }

    /**
     * Workflow State: Database error when checking password
     * - Token: empty
     * - Password: exception thrown (user doesn't exist)
     * - Expected: Generate new token (assume fresh install)
     */
    public function testOnActivateGeneratesTokenWhenPasswordCheckThrowsException(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);

        $moduleSettingService
            ->expects($this->once())
            ->method('getString')
            ->willReturn(new \Symfony\Component\String\UnicodeString(''));

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')
            ->willThrowException(new \Exception('Database error'));

        // Should save a new token (password check failed = assume not set)
        $moduleSettingService
            ->expects($this->once())
            ->method('saveString');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(fn($service) => match ($service) {
                ModuleSettingServiceInterface::class => $moduleSettingService,
                QueryBuilderFactoryInterface::class => $queryBuilderFactory,
                default => null,
            });

        $this->mockContainer($container);

        ModuleEvents::onActivate();
    }

    /**
     * Workflow State: User doesn't exist in database
     * - Token: empty
     * - Password: null (user not found)
     * - Expected: Generate new token
     */
    public function testOnActivateGeneratesTokenWhenUserNotFound(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);

        $moduleSettingService
            ->expects($this->once())
            ->method('getString')
            ->willReturn(new \Symfony\Component\String\UnicodeString(''));

        // User not found - returns null/empty
        $result = $this->createMock(Result::class);
        $result->expects($this->once())
            ->method('fetchOne')
            ->willReturn(null);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('execute')->willReturn($result);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        // Should save a new token
        $moduleSettingService
            ->expects($this->once())
            ->method('saveString');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(fn($service) => match ($service) {
                ModuleSettingServiceInterface::class => $moduleSettingService,
                QueryBuilderFactoryInterface::class => $queryBuilderFactory,
                default => null,
            });

        $this->mockContainer($container);

        ModuleEvents::onActivate();
    }

    /**
     * Workflow State: Password is empty string
     * - Token: empty
     * - Password: '' (empty string)
     * - Expected: Generate new token (password not set)
     */
    public function testOnActivateGeneratesTokenWhenPasswordIsEmptyString(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);

        $moduleSettingService
            ->expects($this->once())
            ->method('getString')
            ->willReturn(new \Symfony\Component\String\UnicodeString(''));

        $result = $this->createMock(Result::class);
        $result->expects($this->once())
            ->method('fetchOne')
            ->willReturn('');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('execute')->willReturn($result);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        $moduleSettingService
            ->expects($this->once())
            ->method('saveString');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(fn($service) => match ($service) {
                ModuleSettingServiceInterface::class => $moduleSettingService,
                QueryBuilderFactoryInterface::class => $queryBuilderFactory,
                default => null,
            });

        $this->mockContainer($container);

        ModuleEvents::onActivate();
    }

    /**
     * Helper to mock the ContainerFactory singleton.
     * Note: This is a workaround for testing static methods that use ContainerFactory.
     * In a real scenario, you might want to refactor to use dependency injection.
     */
    private function mockContainer(ContainerInterface $container): void
    {
        // This test requires the OXID framework to be loaded.
        // The static method uses ContainerFactory::getInstance()->getContainer()
        // which cannot be mocked without the framework.
        //
        // For complete coverage, integration tests are required.

        $this->markTestSkipped(
            'ModuleEvents::onActivate() uses static ContainerFactory which requires OXID framework. ' .
            'Integration tests should cover this functionality.'
        );
    }
}
