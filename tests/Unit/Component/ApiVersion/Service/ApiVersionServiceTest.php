<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\ApiVersion\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;
use OxidSupport\Heartbeat\Component\ApiVersion\DataType\ComponentStatusType;
use OxidSupport\Heartbeat\Component\ApiVersion\Service\ApiVersionService;
use OxidSupport\Heartbeat\Module\Module;
use PHPUnit\Framework\TestCase;
use TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface;

final class ApiVersionServiceTest extends TestCase
{
    /** @var string[] Operations not provided by this module (e.g. from graphql-base) */
    private const EXTERNAL_OPERATIONS = [
        'token',
    ];

    private function createService(
        ?ModuleSettingBridgeInterface $settingService = null,
        bool $isLogged = true
    ): ApiVersionService {
        return new ApiVersionService(
            $settingService ?? $this->createMockSettingService(),
            $this->createMockAuthenticationService($isLogged)
        );
    }

    private function createMockSettingService(
        bool $requestLogger = false,
        bool $logSender = false,
        bool $diagnosticsProvider = false
    ): ModuleSettingBridgeInterface {
        $mock = $this->createMock(ModuleSettingBridgeInterface::class);
        $mock->method('get')->willReturnMap([
            [Module::SETTING_REQUESTLOGGER_ACTIVE, Module::ID, $requestLogger],
            [Module::SETTING_LOGSENDER_ACTIVE, Module::ID, $logSender],
            [Module::SETTING_DIAGNOSTICSPROVIDER_ACTIVE, Module::ID, $diagnosticsProvider],
        ]);

        return $mock;
    }

    private function createMockAuthenticationService(bool $isLogged): AuthenticationServiceInterface
    {
        $mock = $this->createMock(AuthenticationServiceInterface::class);
        $mock->method('isLogged')->willReturn($isLogged);

        return $mock;
    }

    public function testUnauthenticatedReturnsDiscoveryOnly(): void
    {
        $service = $this->createService(null, false);
        $result = $service->getApiVersion();

        $this->assertTrue($result->isInstalled());
        $this->assertNull($result->getApiVersion());
        $this->assertNull($result->getApiSchemaHash());
        $this->assertNull($result->getModuleVersion());
        $this->assertNull($result->getSupportedOperations());
        $this->assertNull($result->getComponentStatus());
    }

    public function testAuthenticatedReturnsAllFields(): void
    {
        $service = $this->createService(null, true);
        $result = $service->getApiVersion();

        $this->assertTrue($result->isInstalled());
        $this->assertSame(Module::API_VERSION, $result->getApiVersion());
        $this->assertSame(Module::VERSION, $result->getModuleVersion());
        $this->assertSame(Module::SUPPORTED_OPERATIONS, $result->getSupportedOperations());
        $this->assertNotEmpty($result->getApiSchemaHash());
        $this->assertSame(16, strlen($result->getApiSchemaHash() ?? ''));
        $this->assertNotNull($result->getComponentStatus());
    }

    public function testAuthenticatedReturnsComponentStatus(): void
    {
        $service = $this->createService(
            $this->createMockSettingService(true, false, true),
            true
        );
        $result = $service->getApiVersion();

        $statuses = $result->getComponentStatus();
        $this->assertNotNull($statuses);
        $this->assertCount(3, $statuses);

        $statusMap = [];
        foreach ($statuses as $status) {
            $this->assertInstanceOf(ComponentStatusType::class, $status);
            $statusMap[$status->getName()] = $status->isActive();
        }

        $this->assertTrue($statusMap['requestLogger']);
        $this->assertFalse($statusMap['logSender']);
        $this->assertTrue($statusMap['diagnosticsProvider']);
    }

    public function testComponentStatusDefaultsToFalseOnError(): void
    {
        $mock = $this->createMock(ModuleSettingBridgeInterface::class);
        $mock->method('get')->willThrowException(new \RuntimeException('Setting not found'));

        $service = $this->createService($mock, true);
        $result = $service->getApiVersion();

        $statuses = $result->getComponentStatus() ?? [];
        foreach ($statuses as $status) {
            $this->assertFalse(
                $status->isActive(),
                "Component '{$status->getName()}' should default to false on error"
            );
        }
    }

    public function testComputeSchemaHashIsDeterministic(): void
    {
        $ops = ['b', 'a', 'c'];

        $this->assertSame(
            ApiVersionService::computeSchemaHash($ops),
            ApiVersionService::computeSchemaHash($ops)
        );
    }

    public function testComputeSchemaHashIsOrderIndependent(): void
    {
        $this->assertSame(
            ApiVersionService::computeSchemaHash(['a', 'b', 'c']),
            ApiVersionService::computeSchemaHash(['c', 'a', 'b'])
        );
    }

    public function testComputeSchemaHashChangesWhenOperationsChange(): void
    {
        $hash1 = ApiVersionService::computeSchemaHash(['a', 'b']);
        $hash2 = ApiVersionService::computeSchemaHash(['a', 'b', 'c']);

        $this->assertNotSame($hash1, $hash2);
    }

    /**
     * Safeguard: Scans all GraphQL controller classes for @Query and @Mutation
     * docblock annotations and verifies they match Module::SUPPORTED_OPERATIONS.
     *
     * If this test fails, you likely added/removed a GraphQL operation
     * but forgot to update Module::SUPPORTED_OPERATIONS.
     */
    public function testSupportedOperationsMatchActualGraphQLRoutes(): void
    {
        $controllerDir = dirname(__DIR__, 5) . '/src/Component';
        $actualOperations = self::EXTERNAL_OPERATIONS;

        $controllerFiles = glob($controllerDir . '/*/Controller/GraphQL/*.php');
        $this->assertNotEmpty($controllerFiles, 'No GraphQL controller files found');

        foreach ($controllerFiles as $file) {
            $className = $this->resolveClassName($file);

            if ($className === null || !class_exists($className)) {
                continue;
            }

            $reflection = new \ReflectionClass($className);

            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $docComment = $method->getDocComment();
                $isQuery = $docComment !== false && str_contains($docComment, '@Query');
                $isMutation = $docComment !== false && str_contains($docComment, '@Mutation');

                if ($isQuery || $isMutation) {
                    $actualOperations[] = $method->getName();
                }
            }
        }

        sort($actualOperations);

        $expected = Module::SUPPORTED_OPERATIONS;
        sort($expected);

        $missing = array_diff($actualOperations, $expected);
        $extra = array_diff($expected, $actualOperations);

        $message = '';
        if (!empty($missing)) {
            $message .= 'Operations in controllers but missing from Module::SUPPORTED_OPERATIONS: '
                . implode(', ', $missing) . '. ';
        }
        if (!empty($extra)) {
            $message .= 'Operations in Module::SUPPORTED_OPERATIONS but not found in controllers: '
                . implode(', ', array_diff($extra, self::EXTERNAL_OPERATIONS)) . '. ';
        }

        $this->assertSame($expected, $actualOperations, $message);
    }

    private function resolveClassName(string $filePath): ?string
    {
        // Derive FQCN from path: .../src/Component/{Name}/Controller/GraphQL/{Class}.php
        if (!preg_match('#/src/Component/(\w+)/Controller/GraphQL/(\w+)\.php$#', $filePath, $matches)) {
            return null;
        }

        return sprintf(
            'OxidSupport\\Heartbeat\\Component\\%s\\Controller\\GraphQL\\%s',
            $matches[1],
            $matches[2]
        );
    }
}
