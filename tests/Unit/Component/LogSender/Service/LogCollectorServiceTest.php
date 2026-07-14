<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\LogSender\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidSupport\Heartbeat\Component\LogSender\DataType\LogPath;
use OxidSupport\Heartbeat\Component\LogSender\DataType\LogPathType;
use OxidSupport\Heartbeat\Component\LogSender\DataType\LogSource;
use OxidSupport\Heartbeat\Component\LogSender\Exception\LogSourceNotFoundException;
use OxidSupport\Heartbeat\Component\LogSender\Service\LogCollectorService;
use OxidSupport\Heartbeat\Component\LogSender\Service\LogCollectorServiceInterface;
use OxidSupport\Heartbeat\Component\LogSender\Service\InstallationWideLogPathProviderInterface;
use OxidSupport\Heartbeat\Component\LogSender\Service\LogPathProviderInterface;
use OxidSupport\Heartbeat\Component\LogSender\Service\StaticPathGuardInterface;
use OxidSupport\Heartbeat\Module\Module;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(LogCollectorService::class)]
final class LogCollectorServiceTest extends TestCase
{
    private ModuleSettingServiceInterface&MockObject $moduleSettingService;
    private StaticPathGuardInterface&MockObject $staticPathGuard;
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);
        // Allow-all guard by default; the guard's own rejection logic is covered
        // in StaticPathGuardTest. See OXS-3131.
        $this->staticPathGuard = $this->createMock(StaticPathGuardInterface::class);
        $this->staticPathGuard->method('isAllowed')->willReturn(true);
        $this->testDir = sys_get_temp_dir() . '/logcollector_test_' . uniqid();
        mkdir($this->testDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testDir)) {
            rmdir($this->testDir);
        }
        parent::tearDown();
    }

    private function createService(array $providers = []): LogCollectorService
    {
        return new LogCollectorService($this->moduleSettingService, $this->staticPathGuard, $providers);
    }

    private function createMockProvider(
        string $id,
        string $name,
        string $description,
        array $paths,
        bool $isActive = true
    ): LogPathProviderInterface&MockObject {
        $provider = $this->createMock(LogPathProviderInterface::class);
        $provider->method('getProviderId')->willReturn($id);
        $provider->method('getProviderName')->willReturn($name);
        $provider->method('getProviderDescription')->willReturn($description);
        $provider->method('getLogPaths')->willReturn($paths);
        $provider->method('isActive')->willReturn($isActive);

        return $provider;
    }

    // getSources() tests - no providers, no static paths

    public function testGetSourcesReturnsEmptyArrayWithNoProvidersAndNoStaticPaths(): void
    {
        $this->moduleSettingService
            ->method('getCollection')
            ->willReturn([]);

        $service = $this->createService([]);

        $this->assertEquals([], $service->getSources());
    }

    // getSources() tests - with static paths

    public function testGetSourcesReturnsStaticPathSources(): void
    {
        $this->moduleSettingService
            ->method('getCollection')
            ->with(Module::SETTING_LOGSENDER_STATIC_PATHS, Module::ID)
            ->willReturn([
                [
                    'path' => $this->testDir,
                    'type' => 'directory',
                    'name' => 'Test Logs',
                    'description' => 'Test description',
                ],
            ]);

        $service = $this->createService([]);
        $sources = $service->getSources();

        $this->assertCount(1, $sources);
        $this->assertInstanceOf(LogSource::class, $sources[0]);
        $this->assertEquals('static_0', $sources[0]->id);
        $this->assertEquals('Test Logs', $sources[0]->name);
        $this->assertEquals(LogSource::ORIGIN_STATIC, $sources[0]->origin);
        $this->assertTrue($sources[0]->available);
    }

    public function testGetSourcesHandlesInvalidStaticPathConfig(): void
    {
        $this->moduleSettingService
            ->method('getCollection')
            ->willReturn([
                ['invalid' => 'config'],
                ['path' => '/some/path'], // missing type
                ['type' => 'file'], // missing path
            ]);

        $service = $this->createService([]);

        $this->assertEquals([], $service->getSources());
    }

    public function testGetSourcesHandlesSettingException(): void
    {
        $this->moduleSettingService
            ->method('getCollection')
            ->willThrowException(new \RuntimeException('Setting not found'));

        $service = $this->createService([]);

        $this->assertEquals([], $service->getSources());
    }

    // getSources() tests - with providers

    public function testGetSourcesReturnsProviderSources(): void
    {
        $this->moduleSettingService
            ->method('getCollection')
            ->willReturn([]);

        $logPath = new LogPath(
            path: $this->testDir,
            type: LogPathType::DIRECTORY,
            name: 'Provider Logs',
        );

        $provider = $this->createMockProvider(
            'testprovider',
            'Test Provider',
            'Test provider description',
            [$logPath]
        );

        $service = $this->createService([$provider]);
        $sources = $service->getSources();

        $this->assertCount(1, $sources);
        $this->assertEquals('provider_testprovider', $sources[0]->id);
        $this->assertEquals('Test Provider', $sources[0]->name);
        $this->assertEquals(LogSource::ORIGIN_PROVIDER, $sources[0]->origin);
        $this->assertEquals('testprovider', $sources[0]->providerId);
    }

    public function testGetSourcesIncludesInactiveProvidersAsUnavailable(): void
    {
        $this->moduleSettingService
            ->method('getCollection')
            ->willReturn([]);

        $logPath = new LogPath(
            path: $this->testDir,
            type: LogPathType::DIRECTORY,
            name: 'Inactive Logs',
        );

        $provider = $this->createMockProvider(
            'inactive',
            'Inactive Provider',
            'Description',
            [$logPath],
            false // isActive = false
        );

        $service = $this->createService([$provider]);
        $sources = $service->getSources();

        $this->assertCount(1, $sources);
        $this->assertEquals('provider_inactive', $sources[0]->id);
        $this->assertFalse($sources[0]->available);
    }

    public function testGetSourcesIncludesInactiveProviderWithMissingPathAsUnavailable(): void
    {
        $this->moduleSettingService
            ->method('getCollection')
            ->willReturn([]);

        $logPath = new LogPath(
            path: '/nonexistent/path',
            type: LogPathType::DIRECTORY,
            name: 'Missing Logs',
        );

        $provider = $this->createMockProvider(
            'inactive_missing',
            'Inactive Missing Provider',
            'Description',
            [$logPath],
            false // isActive = false
        );

        $service = $this->createService([$provider]);
        $sources = $service->getSources();

        $this->assertCount(1, $sources);
        $this->assertEquals('provider_inactive_missing', $sources[0]->id);
        $this->assertFalse($sources[0]->available);
        $this->assertCount(1, $sources[0]->paths);
    }

    public function testGetSourcesMarksActiveProviderWithExistingPathAsAvailable(): void
    {
        $this->moduleSettingService
            ->method('getCollection')
            ->willReturn([]);

        $logPath = new LogPath(
            path: $this->testDir,
            type: LogPathType::DIRECTORY,
            name: 'Existing Logs',
        );

        $provider = $this->createMockProvider(
            'active_existing',
            'Active Existing Provider',
            'Description',
            [$logPath],
            true // isActive = true
        );

        $service = $this->createService([$provider]);
        $sources = $service->getSources();

        $this->assertCount(1, $sources);
        $this->assertTrue($sources[0]->available);
    }

    public function testGetSourcesMarksProviderUnavailableWhenPathDoesNotExist(): void
    {
        $this->moduleSettingService
            ->method('getCollection')
            ->willReturn([]);

        $logPath = new LogPath(
            path: '/nonexistent/path',
            type: LogPathType::DIRECTORY,
            name: 'Missing Logs',
        );

        $provider = $this->createMockProvider(
            'missingpaths',
            'Missing Provider',
            'Description',
            [$logPath]
        );

        $service = $this->createService([$provider]);
        $sources = $service->getSources();

        $this->assertCount(1, $sources);
        $this->assertFalse($sources[0]->available);
    }

    public function testGetSourcesMarksProviderUnavailableWhenNoPaths(): void
    {
        $this->moduleSettingService
            ->method('getCollection')
            ->willReturn([]);

        $provider = $this->createMockProvider(
            'nopaths',
            'No Paths Provider',
            'Description',
            [] // empty paths
        );

        $service = $this->createService([$provider]);
        $sources = $service->getSources();

        $this->assertCount(1, $sources);
        $this->assertFalse($sources[0]->available);
    }

    // getSources() tests - combined static and provider

    public function testGetSourcesCombinesStaticAndProviderSources(): void
    {
        $this->moduleSettingService
            ->method('getCollection')
            ->willReturn([
                [
                    'path' => $this->testDir,
                    'type' => 'directory',
                    'name' => 'Static Logs',
                ],
            ]);

        $logPath = new LogPath(
            path: $this->testDir,
            type: LogPathType::DIRECTORY,
            name: 'Provider Logs',
        );

        $provider = $this->createMockProvider(
            'testprovider',
            'Test Provider',
            'Description',
            [$logPath]
        );

        $service = $this->createService([$provider]);
        $sources = $service->getSources();

        $this->assertCount(2, $sources);
        $this->assertEquals(LogSource::ORIGIN_STATIC, $sources[0]->origin);
        $this->assertEquals(LogSource::ORIGIN_PROVIDER, $sources[1]->origin);
    }

    // getSourceById() tests

    public function testGetSourceByIdReturnsMatchingSource(): void
    {
        $this->moduleSettingService
            ->method('getCollection')
            ->willReturn([
                [
                    'path' => $this->testDir,
                    'type' => 'directory',
                    'name' => 'Test Logs',
                ],
            ]);

        $service = $this->createService([]);
        $source = $service->getSourceById('static_0');

        $this->assertEquals('static_0', $source->id);
        $this->assertEquals('Test Logs', $source->name);
    }

    public function testGetSourceByIdThrowsExceptionForNonExistentId(): void
    {
        $this->moduleSettingService
            ->method('getCollection')
            ->willReturn([]);

        $service = $this->createService([]);

        $this->expectException(LogSourceNotFoundException::class);
        $service->getSourceById('nonexistent');
    }

    // getStaticPaths() tests

    public function testGetStaticPathsReturnsConfiguredPaths(): void
    {
        $this->moduleSettingService
            ->method('getCollection')
            ->willReturn([
                [
                    'path' => '/var/log/test.log',
                    'type' => 'file',
                    'name' => 'Test Log',
                    'description' => 'A test log file',
                    'pattern' => '*.log',
                ],
            ]);

        $service = $this->createService([]);
        $paths = $service->getStaticPaths();

        $this->assertCount(1, $paths);
        $this->assertInstanceOf(LogPath::class, $paths[0]);
        $this->assertEquals('/var/log/test.log', $paths[0]->path);
        $this->assertEquals(LogPathType::FILE, $paths[0]->type);
        $this->assertEquals('Test Log', $paths[0]->name);
        $this->assertEquals('A test log file', $paths[0]->description);
        $this->assertEquals('*.log', $paths[0]->filePattern);
    }

    public function testGetStaticPathsUsesBasenameAsDefaultName(): void
    {
        $this->moduleSettingService
            ->method('getCollection')
            ->willReturn([
                [
                    'path' => '/var/log/myapp.log',
                    'type' => 'file',
                ],
            ]);

        $service = $this->createService([]);
        $paths = $service->getStaticPaths();

        $this->assertEquals('myapp.log', $paths[0]->name);
    }

    public function testGetStaticPathsSkipsInvalidTypes(): void
    {
        $this->moduleSettingService
            ->method('getCollection')
            ->willReturn([
                [
                    'path' => '/var/log/test.log',
                    'type' => 'invalid_type',
                ],
            ]);

        $service = $this->createService([]);

        $this->assertEquals([], $service->getStaticPaths());
    }

    // Service class tests

    public function testServiceImplementsInterface(): void
    {
        $service = $this->createService([]);

        $this->assertInstanceOf(LogCollectorServiceInterface::class, $service);
    }

    public function testClassIsFinal(): void
    {
        $reflection = new \ReflectionClass(LogCollectorService::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function testConstructorAcceptsTraversableProviders(): void
    {
        $this->moduleSettingService
            ->method('getCollection')
            ->willReturn([]);

        $provider = $this->createMockProvider('test', 'Test', 'Desc', []);
        $traversable = new \ArrayIterator([$provider]);

        $service = new LogCollectorService($this->moduleSettingService, $this->staticPathGuard, $traversable);

        $this->assertInstanceOf(LogCollectorService::class, $service);
    }

    public function testIsInstallationWideSourceTrueOnlyForMarkedProvider(): void
    {
        // OXS-3132: only providers marked installation-wide count; plain providers
        // and static sources are shop-scoped and must not be treated as such.
        // Anonymous class (not createMockForIntersectionOfInterfaces) so the test
        // runs on every line's PHPUnit version.
        $marked = new class implements LogPathProviderInterface, InstallationWideLogPathProviderInterface {
            public function getLogPaths(): array
            {
                return [];
            }
            public function getProviderId(): string
            {
                return 'oxid_core';
            }
            public function getProviderName(): string
            {
                return 'Core';
            }
            public function getProviderDescription(): string
            {
                return '';
            }
            public function isActive(): bool
            {
                return true;
            }
        };

        $plain = $this->createMockProvider('requestlogger', 'RL', 'Desc', []);

        $service = $this->createService([$marked, $plain]);

        $this->assertTrue($service->isInstallationWideSource('provider_oxid_core'));
        $this->assertFalse($service->isInstallationWideSource('provider_requestlogger'));
        $this->assertFalse($service->isInstallationWideSource('static_0'));
    }

    public function testGetStaticPathsDropsPathsRejectedByGuard(): void
    {
        // OXS-3131: cross-shop / sensitive paths are filtered out on read.
        $this->moduleSettingService->method('getCollection')->willReturn([
            ['path' => '/allowed/a.log', 'type' => 'file'],
            ['path' => '/rejected/b.log', 'type' => 'file'],
        ]);
        $guard = $this->createMock(StaticPathGuardInterface::class);
        $guard->method('isAllowed')->willReturnCallback(fn($p) => $p === '/allowed/a.log');

        $service = new LogCollectorService($this->moduleSettingService, $guard, []);

        $paths = $service->getStaticPaths();

        $this->assertCount(1, $paths);
        $this->assertSame('/allowed/a.log', $paths[0]->path);
    }
}
