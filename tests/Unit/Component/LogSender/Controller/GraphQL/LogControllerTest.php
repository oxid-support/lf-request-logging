<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\LogSender\Controller\GraphQL;

use InvalidArgumentException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidSupport\Heartbeat\Component\LogSender\Controller\GraphQL\LogController;
use OxidSupport\Heartbeat\Component\LogSender\DataType\LogContentType;
use OxidSupport\Heartbeat\Component\LogSender\DataType\LogPath;
use OxidSupport\Heartbeat\Component\LogSender\DataType\LogPathType;
use OxidSupport\Heartbeat\Component\LogSender\DataType\LogSource;
use OxidSupport\Heartbeat\Component\LogSender\DataType\LogSourceType;
use OxidSupport\Heartbeat\Component\LogSender\Service\LogCollectorServiceInterface;
use OxidSupport\Heartbeat\Component\LogSender\Service\LogReaderServiceInterface;
use OxidSupport\Heartbeat\Component\LogSender\Service\LogSenderStatusServiceInterface;
use OxidSupport\Heartbeat\Module\Module;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(LogController::class)]
final class LogControllerTest extends TestCase
{
    private LogController $sut;
    private LogCollectorServiceInterface&MockObject $mockCollector;
    private LogReaderServiceInterface&MockObject $mockReader;
    private LogSenderStatusServiceInterface&MockObject $mockStatus;
    private ModuleSettingServiceInterface&MockObject $mockSettings;

    private string $tempDir;
    private array $tempFiles = [];

    protected function setUp(): void
    {
        $this->mockCollector = $this->createMock(LogCollectorServiceInterface::class);
        $this->mockReader = $this->createMock(LogReaderServiceInterface::class);
        $this->mockStatus = $this->createMock(LogSenderStatusServiceInterface::class);
        $this->mockSettings = $this->createMock(ModuleSettingServiceInterface::class);

        // Default: component is active (individual tests override for inactive scenarios)
        $this->mockStatus->method('isActive')->willReturn(true);

        $this->sut = new LogController(
            $this->mockCollector,
            $this->mockReader,
            $this->mockStatus,
            $this->mockSettings
        );

        // Create temp directory for file-based tests
        $this->tempDir = sys_get_temp_dir() . '/logcontroller_test_' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        // Clean up temp files
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
        @rmdir($this->tempDir);
    }

    private function createTempFile(string $name, string $content): string
    {
        $path = $this->tempDir . '/' . $name;
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;
        return $path;
    }

    // =========================================================================
    // logSenderSources() Tests
    // =========================================================================

    public function testLogSenderSourcesReturnsEnabledAndAvailableSources(): void
    {
        $source1 = $this->createSource('source1', 'Source 1', true);
        $source2 = $this->createSource('source2', 'Source 2', true);
        $source3 = $this->createSource('source3', 'Source 3', true);

        $this->mockSettings->method('getCollection')
            ->with(Module::SETTING_LOGSENDER_ENABLED_SOURCES, Module::ID)
            ->willReturn(['source1', 'source2']); // source3 not enabled

        $this->mockCollector->method('getSources')
            ->willReturn([$source1, $source2, $source3]);

        $result = $this->sut->logSenderSources();

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(LogSourceType::class, $result);
    }

    public function testLogSenderSourcesExcludesUnavailableSources(): void
    {
        $availableSource = $this->createSource('available', 'Available', true);
        $unavailableSource = $this->createSource('unavailable', 'Unavailable', false);

        $this->mockSettings->method('getCollection')
            ->willReturn(['available', 'unavailable']);

        $this->mockCollector->method('getSources')
            ->willReturn([$availableSource, $unavailableSource]);

        $result = $this->sut->logSenderSources();

        $this->assertCount(1, $result);
        $this->assertEquals('available', $result[0]->getId());
    }

    public function testLogSenderSourcesReturnsEmptyArrayWhenNoSourcesEnabled(): void
    {
        $source = $this->createSource('source1', 'Source', true);

        $this->mockSettings->method('getCollection')
            ->willReturn([]);

        $this->mockCollector->method('getSources')
            ->willReturn([$source]);

        $result = $this->sut->logSenderSources();

        $this->assertEmpty($result);
    }

    public function testLogSenderSourcesReturnsEmptyArrayWhenSettingsThrowsException(): void
    {
        $this->mockSettings->method('getCollection')
            ->willThrowException(new \Exception('Settings error'));

        $this->mockCollector->method('getSources')
            ->willReturn([]);

        $result = $this->sut->logSenderSources();

        $this->assertEmpty($result);
    }

    public function testLogSenderSourcesReturnsEmptyArrayWhenComponentInactive(): void
    {
        $inactiveStatus = $this->createMock(LogSenderStatusServiceInterface::class);
        $inactiveStatus->method('isActive')->willReturn(false);
        $controller = new LogController(
            $this->mockCollector,
            $this->mockReader,
            $inactiveStatus,
            $this->mockSettings
        );

        $result = $controller->logSenderSources();

        $this->assertSame([], $result);
    }

    // =========================================================================
    // logSenderContent() Tests - FILE Sources
    // =========================================================================

    public function testLogSenderContentReturnsContentForFileSource(): void
    {
        $filePath = $this->createTempFile('test.log', 'Log content here');
        $logPath = new LogPath($filePath, LogPathType::FILE(), 'Test Log');
        $source = $this->createSourceWithPaths('test_source', 'Test', [$logPath], true);

        $this->mockSettings->method('getCollection')
            ->willReturn(['test_source']);
        $this->mockSettings->method('getInteger')
            ->willReturn(1048576);

        $this->mockCollector->method('getSourceById')
            ->with('test_source')
            ->willReturn($source);

        $this->mockReader->method('readFile')
            ->with($filePath, 1048576)
            ->willReturn('Log content here');

        $this->mockReader->method('getFileInfo')
            ->with($filePath)
            ->willReturn(['size' => 1000, 'modified' => 1234567890]);

        $result = $this->sut->logSenderContent('test_source');

        $this->assertInstanceOf(LogContentType::class, $result);
        $this->assertEquals('test_source', $result->getSourceId());
        $this->assertEquals('Log content here', $result->getContent());
        $this->assertEquals(1000, $result->getSize());
    }

    public function testLogSenderContentWithCustomMaxBytes(): void
    {
        $filePath = $this->createTempFile('test.log', 'content');
        $logPath = new LogPath($filePath, LogPathType::FILE(), 'Test Log');
        $source = $this->createSourceWithPaths('test_source', 'Test', [$logPath], true);

        $this->mockSettings->method('getCollection')
            ->willReturn(['test_source']);

        $this->mockCollector->method('getSourceById')
            ->willReturn($source);

        $this->mockReader->expects($this->once())
            ->method('readFile')
            ->with($filePath, 5000)
            ->willReturn('content');

        $this->mockReader->method('getFileInfo')
            ->willReturn(['size' => 100, 'modified' => time()]);

        $this->sut->logSenderContent('test_source', 5000);
    }

    public function testLogSenderContentClampsMaxBytesToConfiguredCeiling(): void
    {
        // A caller must not exceed the admin-configured max bytes. An
        // unclamped huge value both bypasses that limit and loads the whole
        // file into memory (DoS). The controller clamps down to the ceiling.
        $filePath = $this->createTempFile('test.log', 'content');
        $logPath = new LogPath($filePath, LogPathType::FILE(), 'Test Log');
        $source = $this->createSourceWithPaths('test_source', 'Test', [$logPath], true);

        $this->mockSettings->method('getCollection')
            ->willReturn(['test_source']);
        $this->mockSettings->method('getInteger')
            ->willReturn(1000);

        $this->mockCollector->method('getSourceById')
            ->willReturn($source);

        $this->mockReader->expects($this->once())
            ->method('readFile')
            ->with($filePath, 1000) // clamped down from PHP_INT_MAX
            ->willReturn('content');

        $this->mockReader->method('getFileInfo')
            ->willReturn(['size' => 100, 'modified' => time()]);

        $this->sut->logSenderContent('test_source', PHP_INT_MAX);
    }

    public function testLogSenderContentDetectsTruncatedContent(): void
    {
        $filePath = $this->createTempFile('test.log', 'Some content');
        $logPath = new LogPath($filePath, LogPathType::FILE(), 'Test Log');
        $source = $this->createSourceWithPaths('test_source', 'Test', [$logPath], true);

        $this->mockSettings->method('getCollection')
            ->willReturn(['test_source']);
        $this->mockSettings->method('getInteger')
            ->willReturn(1000);

        $this->mockCollector->method('getSourceById')
            ->willReturn($source);

        $this->mockReader->method('readFile')
            ->willReturn('[...truncated...]' . "\nActual content");

        $this->mockReader->method('getFileInfo')
            ->willReturn(['size' => 5000, 'modified' => time()]);

        $result = $this->sut->logSenderContent('test_source');

        $this->assertTrue($result->isTruncated());
    }

    // =========================================================================
    // logSenderContent() Tests - DIRECTORY Sources (Bug Fix Tests)
    // =========================================================================

    public function testLogSenderContentHandlesDirectorySource(): void
    {
        // This test verifies the bug fix for directory handling
        $logFile = $this->createTempFile('test.log', 'Directory log content');

        $directoryPath = new LogPath($this->tempDir . '/', LogPathType::DIRECTORY(), 'Logs', '', '*.log');
        $source = $this->createSourceWithPaths('dir_source', 'Directory Source', [$directoryPath], true);

        $this->mockSettings->method('getCollection')
            ->willReturn(['dir_source']);
        $this->mockSettings->method('getInteger')
            ->willReturn(1048576);

        $this->mockCollector->method('getSourceById')
            ->with('dir_source')
            ->willReturn($source);

        $this->mockReader->method('readFile')
            ->willReturn('Directory log content');

        $this->mockReader->method('getFileInfo')
            ->willReturn(['size' => 21, 'modified' => time()]);

        $result = $this->sut->logSenderContent('dir_source');

        $this->assertInstanceOf(LogContentType::class, $result);
        $this->assertEquals('Directory log content', $result->getContent());
    }

    public function testLogSenderContentSelectsNewestFileFromDirectory(): void
    {
        $this->createTempFile('old.log', 'Old content');
        sleep(1); // Ensure different modification times
        $this->createTempFile('new.log', 'New content');

        $directoryPath = new LogPath($this->tempDir . '/', LogPathType::DIRECTORY(), 'Logs', '', '*.log');
        $source = $this->createSourceWithPaths('dir_source', 'Dir', [$directoryPath], true);

        $this->mockSettings->method('getCollection')
            ->willReturn(['dir_source']);
        $this->mockSettings->method('getInteger')
            ->willReturn(1048576);

        $this->mockCollector->method('getSourceById')
            ->willReturn($source);

        // Verify the newest file is selected
        $this->mockReader->expects($this->once())
            ->method('readFile')
            ->with($this->stringContains('new.log'), $this->anything())
            ->willReturn('New content');

        $this->mockReader->method('getFileInfo')
            ->willReturn(['size' => 11, 'modified' => time()]);

        $result = $this->sut->logSenderContent('dir_source');

        $this->assertStringContainsString('new.log', $result->getPath());
    }

    public function testLogSenderContentThrowsWhenDirectoryIsEmpty(): void
    {
        // No files in directory
        $directoryPath = new LogPath($this->tempDir . '/', LogPathType::DIRECTORY(), 'Logs', '', '*.log');
        $source = $this->createSourceWithPaths('dir_source', 'Dir', [$directoryPath], true);

        $this->mockSettings->method('getCollection')
            ->willReturn(['dir_source']);
        $this->mockSettings->method('getInteger')
            ->willReturn(1048576);

        $this->mockCollector->method('getSourceById')
            ->willReturn($source);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("No readable file found in source 'dir_source'");

        $this->sut->logSenderContent('dir_source');
    }

    public function testLogSenderContentRespectsFilePatternInDirectory(): void
    {
        $this->createTempFile('test.log', 'Log content');
        $this->createTempFile('test.txt', 'Text content'); // Should be ignored

        $directoryPath = new LogPath($this->tempDir . '/', LogPathType::DIRECTORY(), 'Logs', '', '*.log');
        $source = $this->createSourceWithPaths('dir_source', 'Dir', [$directoryPath], true);

        $this->mockSettings->method('getCollection')
            ->willReturn(['dir_source']);
        $this->mockSettings->method('getInteger')
            ->willReturn(1048576);

        $this->mockCollector->method('getSourceById')
            ->willReturn($source);

        $this->mockReader->expects($this->once())
            ->method('readFile')
            ->with($this->stringContains('.log'), $this->anything())
            ->willReturn('Log content');

        $this->mockReader->method('getFileInfo')
            ->willReturn(['size' => 11, 'modified' => time()]);

        $result = $this->sut->logSenderContent('dir_source');

        $this->assertStringContainsString('.log', $result->getPath());
        $this->assertStringNotContainsString('.txt', $result->getPath());
    }

    // =========================================================================
    // logSenderContent() Tests - Error Cases
    // =========================================================================

    public function testLogSenderContentThrowsWhenSourceNotEnabled(): void
    {
        $this->mockSettings->method('getCollection')
            ->willReturn(['other_source']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Source 'disabled_source' is not enabled for sending");

        $this->sut->logSenderContent('disabled_source');
    }

    public function testLogSenderContentThrowsWhenSourceNotAvailable(): void
    {
        $source = $this->createSource('test_source', 'Test', false);

        $this->mockSettings->method('getCollection')
            ->willReturn(['test_source']);

        $this->mockCollector->method('getSourceById')
            ->willReturn($source);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Source 'test_source' is not available");

        $this->sut->logSenderContent('test_source');
    }

    public function testLogSenderContentReturnsNullWhenComponentInactive(): void
    {
        $inactiveStatus = $this->createMock(LogSenderStatusServiceInterface::class);
        $inactiveStatus->method('isActive')->willReturn(false);
        $controller = new LogController(
            $this->mockCollector,
            $this->mockReader,
            $inactiveStatus,
            $this->mockSettings
        );

        $result = $controller->logSenderContent('any_source');

        $this->assertNull($result);
    }

    public function testLogSenderContentThrowsWhenNoReadablePathFound(): void
    {
        $nonExistentPath = new LogPath('/non/existent/path.log', LogPathType::FILE(), 'Test');
        $source = $this->createSourceWithPaths('test_source', 'Test', [$nonExistentPath], true);

        $this->mockSettings->method('getCollection')
            ->willReturn(['test_source']);
        $this->mockSettings->method('getInteger')
            ->willReturn(1048576);

        $this->mockCollector->method('getSourceById')
            ->willReturn($source);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("No readable file found in source 'test_source'");

        $this->sut->logSenderContent('test_source');
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    private function createSource(string $id, string $name, bool $available): LogSource
    {
        $path = new LogPath('/var/log/' . $id . '.log', LogPathType::FILE(), $name);

        return new LogSource(
            $id,
            $name,
            'Description for ' . $name,
            LogSource::ORIGIN_PROVIDER,
            $id,
            [$path],
            $available
        );
    }

    private function createSourceWithPaths(string $id, string $name, array $paths, bool $available): LogSource
    {
        return new LogSource(
            $id,
            $name,
            'Description for ' . $name,
            LogSource::ORIGIN_PROVIDER,
            $id,
            $paths,
            $available
        );
    }
}
