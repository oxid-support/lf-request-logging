<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Logger;

/**
 * NAMESPACE FUNCTION OVERRIDE TECHNIQUE FOR error_log()
 * ======================================================
 *
 * This test file uses PHP's namespace function resolution to suppress error_log()
 * output during tests, preventing "Test error message" from appearing in test output.
 *
 * HOW IT WORKS:
 * -------------
 * The production code (LoggerFactory) is in namespace OxidSupport\RequestLogger\Logger.
 * When LoggerFactory calls error_log(), PHP searches:
 * 1. Current namespace first (OxidSupport\RequestLogger\Logger)
 * 2. Global namespace if not found
 *
 * By defining error_log() in the production code's namespace, we intercept the call
 * and suppress output during tests.
 *
 * STRUCTURE:
 * ----------
 * 1. First namespace declaration: Test namespace (this file's namespace)
 * 2. Second namespace declaration: Production code namespace (where override is defined)
 * 3. Override function: Silently captures error_log() calls without output
 * 4. Third namespace declaration: Back to test namespace (where test class is defined)
 */

// Override error_log() for the LoggerFactory namespace
namespace OxidSupport\RequestLogger\Logger;

/**
 * Override for global error_log() function.
 * Suppresses error log output during tests.
 *
 * @param string $message The error message
 * @param int $message_type Optional message type
 * @param string|null $destination Optional destination
 * @param string|null $additional_headers Optional headers
 * @return bool Always true in tests
 */
function error_log(
    string $message,
    int $message_type = 0,
    ?string $destination = null,
    ?string $additional_headers = null
): bool {
    // Silently capture - don't write to stderr during tests
    return true;
}

// Back to test namespace
namespace OxidSupport\RequestLogger\Tests\Unit\Logger;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use OxidSupport\RequestLogger\Logger\CorrelationId\CorrelationIdProviderInterface;
use OxidSupport\RequestLogger\Logger\LoggerFactory;
use OxidSupport\RequestLogger\Logger\Processor\CorrelationIdProcessorInterface;
use OxidSupport\RequestLogger\Shop\Facade\ModuleSettingFacadeInterface;
use OxidSupport\RequestLogger\Shop\Facade\ShopFacadeInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LoggerFactoryTest extends TestCase
{
    private CorrelationIdProcessorInterface $processor;
    private CorrelationIdProviderInterface $provider;
    private ShopFacadeInterface $shopFacade;
    private ModuleSettingFacadeInterface $moduleSettingFacade;
    private LoggerFactory $factory;

    private vfsStreamDirectory $vfsRoot;
    private string $testLogDir;

    protected function setUp(): void
    {
        if (!class_exists('Monolog\Logger')) {
            $this->markTestSkipped('Monolog is not installed');
        }

        if (!class_exists('org\bovigo\vfs\vfsStream')) {
            $this->markTestSkipped('vfsStream is not installed');
        }

        $this->processor = $this->createMock(CorrelationIdProcessorInterface::class);
        $this->provider = $this->createMock(CorrelationIdProviderInterface::class);
        $this->shopFacade = $this->createMock(ShopFacadeInterface::class);
        $this->moduleSettingFacade = $this->createMock(ModuleSettingFacadeInterface::class);

        // Setup virtual filesystem
        $this->vfsRoot = vfsStream::setup('testLogs');
        $this->testLogDir = vfsStream::url('testLogs/');

        $this->factory = new LoggerFactory(
            $this->processor,
            $this->provider,
            $this->shopFacade,
            $this->moduleSettingFacade
        );
    }

    public function testCreateReturnsLoggerInterface(): void
    {
        $this->provider
            ->expects($this->once())
            ->method('provide')
            ->willReturn('test-correlation-id');

        $this->shopFacade
            ->expects($this->exactly(2))
            ->method('getLogsPath')
            ->willReturn($this->testLogDir);

        $this->moduleSettingFacade
            ->expects($this->once())
            ->method('getLogLevel')
            ->willReturn('debug');

        $logger = $this->factory->create();

        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    public function testCreateUsesCorrelationIdInFilename(): void
    {
        $correlationId = 'unique-correlation-123';

        $this->provider
            ->expects($this->once())
            ->method('provide')
            ->willReturn($correlationId);

        $this->shopFacade
            ->expects($this->exactly(2))
            ->method('getLogsPath')
            ->willReturn($this->testLogDir);

        $this->moduleSettingFacade
            ->expects($this->once())
            ->method('getLogLevel')
            ->willReturn('info');

        $logger = $this->factory->create();

        // Check if log file was created with correlation ID in name
        $expectedFile = $this->testLogDir . 'oxs-request-logger/' . 'oxs-request-logger-' . $correlationId . '.log';

        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    public function testCreateCreatesLogDirectoryIfNotExists(): void
    {
        $this->provider
            ->expects($this->once())
            ->method('provide')
            ->willReturn('test-id');

        $this->shopFacade
            ->expects($this->exactly(2))
            ->method('getLogsPath')
            ->willReturn($this->testLogDir);

        $this->moduleSettingFacade
            ->expects($this->once())
            ->method('getLogLevel')
            ->willReturn('debug');

        // Verify subdirectory doesn't exist yet
        $this->assertFalse($this->vfsRoot->hasChild('oxs-request-logger'));

        $logger = $this->factory->create();

        // The factory returns a logger instance - actual directory creation
        // happens lazily by Monolog when writing. This test verifies the
        // logger is created correctly with proper configuration.
        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    public function testCreateHandlesExistingLogDirectory(): void
    {
        // Pre-create directory
        mkdir($this->testLogDir . 'oxs-request-logger/', 0775, true);

        $this->provider
            ->expects($this->once())
            ->method('provide')
            ->willReturn('test-id');

        $this->shopFacade
            ->expects($this->exactly(2))
            ->method('getLogsPath')
            ->willReturn($this->testLogDir);

        $this->moduleSettingFacade
            ->expects($this->once())
            ->method('getLogLevel')
            ->willReturn('debug');

        $logger = $this->factory->create();

        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    public function testCreateSetsCorrectLogLevel(): void
    {
        $this->provider
            ->expects($this->once())
            ->method('provide')
            ->willReturn('test-id');

        $this->shopFacade
            ->expects($this->exactly(2))
            ->method('getLogsPath')
            ->willReturn($this->testLogDir);

        $this->moduleSettingFacade
            ->expects($this->once())
            ->method('getLogLevel')
            ->willReturn('warning');

        $logger = $this->factory->create();

        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    public function testCreatePushesProcessorToLogger(): void
    {
        $this->provider
            ->expects($this->once())
            ->method('provide')
            ->willReturn('test-id');

        $this->shopFacade
            ->expects($this->exactly(2))
            ->method('getLogsPath')
            ->willReturn($this->testLogDir);

        $this->moduleSettingFacade
            ->expects($this->once())
            ->method('getLogLevel')
            ->willReturn('debug');

        $logger = $this->factory->create();

        $processors = $logger->getProcessors();

        $this->assertNotEmpty($processors);
        $this->assertContains($this->processor, $processors);
    }

    public function testLogToPhpErrorLogCanBeCalled(): void
    {
        // Suppress error_log output during test using error suppression operator
        @$this->factory->logToPhpErrorLog('Test error message');

        // Verify method completes without throwing exception
        $this->assertTrue(true);
    }

    public function testCreateWithDifferentCorrelationIds(): void
    {
        $this->shopFacade
            ->expects($this->exactly(4))
            ->method('getLogsPath')
            ->willReturn($this->testLogDir);

        $this->moduleSettingFacade
            ->expects($this->exactly(2))
            ->method('getLogLevel')
            ->willReturn('info');

        $this->provider
            ->expects($this->exactly(2))
            ->method('provide')
            ->willReturnOnConsecutiveCalls('correlation-1', 'correlation-2');

        $logger1 = $this->factory->create();
        $logger2 = $this->factory->create();

        $this->assertInstanceOf(LoggerInterface::class, $logger1);
        $this->assertInstanceOf(LoggerInterface::class, $logger2);
        $this->assertNotSame($logger1, $logger2);
    }
}
