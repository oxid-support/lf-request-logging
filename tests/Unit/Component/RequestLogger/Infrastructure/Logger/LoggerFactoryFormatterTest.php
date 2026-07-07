<?php

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\RequestLogger\Infrastructure\Logger;

use Monolog\Formatter\LineFormatter;
use OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\CorrelationId\CorrelationIdProviderInterface;
use OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\LoggerFactory;
use OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\Processor\CorrelationIdProcessorInterface;
use OxidSupport\Heartbeat\Shop\Facade\ModuleSettingFacadeInterface;
use OxidSupport\Heartbeat\Shop\Facade\ShopFacadeInterface;
use PHPUnit\Framework\TestCase;

/**
 * Standalone formatter test that does NOT depend on vfsStream (which is not a
 * direct dev dependency, so the main LoggerFactoryTest is skipped). Uses a real
 * temp directory; the StreamHandler opens its file lazily on first write, so
 * create() only creates the directory, never a log file.
 */
final class LoggerFactoryFormatterTest extends TestCase
{
    private string $tmpDir = '';

    protected function setUp(): void
    {
        if (!class_exists('Monolog\Logger')) {
            $this->markTestSkipped('Monolog is not installed');
        }

        $this->tmpDir = sys_get_temp_dir() . '/hb-fmt-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        if ($this->tmpDir !== '' && is_dir($this->tmpDir)) {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->tmpDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $file) {
                $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
            }
            @rmdir($this->tmpDir);
        }
    }

    private function buildFactory(): LoggerFactory
    {
        $provider = $this->createMock(CorrelationIdProviderInterface::class);
        $provider->method('provide')->willReturn('cid');

        $shopFacade = $this->createMock(ShopFacadeInterface::class);
        $shopFacade->method('getLogsPath')->willReturn($this->tmpDir . '/');

        $moduleSettingFacade = $this->createMock(ModuleSettingFacadeInterface::class);
        $moduleSettingFacade->method('getLogLevel')->willReturn('debug');

        // The processor is a Monolog callable; return the record unchanged so
        // logging works when this factory actually writes a line.
        $processor = $this->createMock(CorrelationIdProcessorInterface::class);
        $processor->method('__invoke')->willReturnArgument(0);

        return new LoggerFactory(
            $processor,
            $provider,
            $shopFacade,
            $moduleSettingFacade
        );
    }

    public function testCreatedLogFileIsNotWorldReadable(): void
    {
        // Logs contain session ids, usernames, IPs and request parameters.
        // They must never be world-readable (0644 under a common umask).
        $logger = $this->buildFactory()->create();
        $logger->info('permission-check'); // forces the stream to open + chmod

        $files = glob($this->tmpDir . '/*/*.log');
        $this->assertNotEmpty($files, 'a log file must have been written');

        $filePerms = fileperms($files[0]) & 0777;
        $this->assertSame(
            0640,
            $filePerms,
            sprintf('Log file must be 0640, got 0%o', $filePerms)
        );

        $dirPerms = fileperms(dirname($files[0])) & 0777;
        $this->assertSame(
            0,
            $dirPerms & 0007,
            sprintf('Log directory must not be world-accessible, got 0%o', $dirPerms)
        );
    }

    public function testCreatedLoggerDisallowsInlineLineBreaks(): void
    {
        // allowInlineLineBreaks must be off so an injected newline in logged
        // data cannot become a separate, forged log record (log injection).
        $provider = $this->createMock(CorrelationIdProviderInterface::class);
        $provider->method('provide')->willReturn('cid');

        $shopFacade = $this->createMock(ShopFacadeInterface::class);
        $shopFacade->method('getLogsPath')->willReturn($this->tmpDir . '/');

        $moduleSettingFacade = $this->createMock(ModuleSettingFacadeInterface::class);
        $moduleSettingFacade->method('getLogLevel')->willReturn('debug');

        $factory = new LoggerFactory(
            $this->createMock(CorrelationIdProcessorInterface::class),
            $provider,
            $shopFacade,
            $moduleSettingFacade
        );

        $logger = $factory->create();
        $formatter = $logger->getHandlers()[0]->getFormatter();

        $this->assertInstanceOf(LineFormatter::class, $formatter);

        $ref = new \ReflectionProperty(LineFormatter::class, 'allowInlineLineBreaks');
        $ref->setAccessible(true);

        $this->assertFalse(
            $ref->getValue($formatter),
            'LineFormatter must disallow inline line breaks to prevent log injection.'
        );
    }
}
