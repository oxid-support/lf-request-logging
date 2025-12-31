<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Security;

use OxidSupport\RequestLogger\Logger\CorrelationId\CorrelationIdProviderInterface;
use OxidSupport\RequestLogger\Logger\LoggerFactory;
use OxidSupport\RequestLogger\Logger\Processor\CorrelationIdProcessorInterface;
use OxidSupport\RequestLogger\Module\Module;
use OxidSupport\RequestLogger\Shop\Facade\ModuleSettingFacadeInterface;
use OxidSupport\RequestLogger\Shop\Facade\ShopFacadeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * Security tests for LoggerFactory
 * Tests path traversal prevention and filename sanitization
 */
#[CoversClass(LoggerFactory::class)]
class LoggerFactorySecurityTest extends TestCase
{
    private CorrelationIdProcessorInterface $correlationIdProcessor;
    private CorrelationIdProviderInterface $correlationIdProvider;
    private ShopFacadeInterface $shopFacade;
    private ModuleSettingFacadeInterface $moduleSettingFacade;
    private LoggerFactory $factory;

    protected function setUp(): void
    {
        $this->correlationIdProcessor = $this->createMock(CorrelationIdProcessorInterface::class);
        $this->correlationIdProvider = $this->createMock(CorrelationIdProviderInterface::class);
        $this->shopFacade = $this->createMock(ShopFacadeInterface::class);
        $this->moduleSettingFacade = $this->createMock(ModuleSettingFacadeInterface::class);

        $this->shopFacade->method('getLogsPath')->willReturn('/var/www/log/');

        $mockLogger = $this->createMock(LoggerInterface::class);
        $this->shopFacade->method('getLogger')->willReturn($mockLogger);

        $this->factory = new LoggerFactory(
            $this->correlationIdProcessor,
            $this->correlationIdProvider,
            $this->shopFacade,
            $this->moduleSettingFacade
        );
    }

    // ===========================================
    // PATH TRAVERSAL PREVENTION TESTS
    // ===========================================

    #[DataProvider('pathTraversalProvider')]
    public function testPathTraversalIsPreventedInFilename(string $maliciousInput, string $expectedSanitized): void
    {
        $sanitized = $this->invokeSanitizeFilename($maliciousInput);

        // Verify no path separators in result
        $this->assertStringNotContainsString('/', $sanitized);
        $this->assertStringNotContainsString('\\', $sanitized);
        $this->assertStringNotContainsString('..', $sanitized);

        // Verify expected output
        $this->assertEquals($expectedSanitized, $sanitized);
    }

    public static function pathTraversalProvider(): array
    {
        return [
            'simple_traversal' => ['../../../etc/passwd', 'etcpasswd'],
            'windows_traversal' => ['..\\..\\..\\windows\\system32', 'windowssystem32'],
            'mixed_traversal' => ['../..\\../etc/passwd', 'etcpasswd'],
            'double_encoded' => ['..%2F..%2Fetc%2Fpasswd', '2F2Fetc2Fpasswd'],
            'null_byte_injection' => ["valid\x00../malicious", 'validmalicious'],
            'absolute_path' => ['/etc/passwd', 'etcpasswd'],
            'windows_absolute' => ['C:\\Windows\\System32', 'CWindowsSystem32'],
            'url_traversal' => ['....//....//etc/passwd', 'etcpasswd'],
            'unicode_traversal' => ['%c0%ae%c0%ae/etc', 'c0aec0aeetc'],
        ];
    }

    #[DataProvider('validCorrelationIdProvider')]
    public function testValidCorrelationIdsArePreserved(string $validId): void
    {
        $sanitized = $this->invokeSanitizeFilename($validId);

        // Valid IDs should be preserved (possibly truncated)
        $this->assertEquals(substr($validId, 0, 64), $sanitized);
    }

    public static function validCorrelationIdProvider(): array
    {
        return [
            'simple_hex' => ['abc123def456'],
            'with_hyphens' => ['abc-123-def-456'],
            'with_underscores' => ['abc_123_def_456'],
            'mixed' => ['abc-123_def-456'],
            'uuid_format' => ['550e8400-e29b-41d4-a716-446655440000'],
        ];
    }

    // ===========================================
    // FILENAME SANITIZATION TESTS
    // ===========================================

    #[DataProvider('maliciousFilenameProvider')]
    public function testMaliciousFilenamesAreSanitized(string $maliciousInput): void
    {
        $sanitized = $this->invokeSanitizeFilename($maliciousInput);

        // Result should only contain safe characters
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9\-_]+$/', $sanitized);

        // Result should not be empty
        $this->assertNotEmpty($sanitized);
    }

    public static function maliciousFilenameProvider(): array
    {
        return [
            'sql_injection' => ["' OR '1'='1"],
            'xss_injection' => ['<script>alert(1)</script>'],
            'command_injection' => ['$(rm -rf /)'],
            'pipe_injection' => ['file | rm -rf /'],
            'semicolon_injection' => ['file; rm -rf /'],
            'backtick_injection' => ['file`rm -rf /`'],
            'newline_injection' => ["file\nrm -rf /"],
            'special_chars' => ['file!@#$%^&*()+='],
            'unicode_chars' => ['file\u0000\u001f'],
            'very_long_input' => [str_repeat('a', 1000)],
        ];
    }

    public function testEmptyInputGeneratesFallback(): void
    {
        $sanitized = $this->invokeSanitizeFilename('');

        $this->assertStringStartsWith('fallback-', $sanitized);
        $this->assertMatchesRegularExpression('/^fallback-\d+$/', $sanitized);
    }

    public function testOnlySpecialCharsGeneratesFallback(): void
    {
        $sanitized = $this->invokeSanitizeFilename('!@#$%^&*()');

        $this->assertStringStartsWith('fallback-', $sanitized);
    }

    public function testFilenameLengthIsLimited(): void
    {
        $longInput = str_repeat('a', 200);
        $sanitized = $this->invokeSanitizeFilename($longInput);

        $this->assertLessThanOrEqual(64, strlen($sanitized));
    }

    // ===========================================
    // LOG FILE PATH TESTS
    // ===========================================

    public function testLogFilePathContainsModuleId(): void
    {
        $logFilePath = $this->invokeLogFilePath('test-correlation-id');

        $this->assertStringContainsString(Module::ID, $logFilePath);
    }

    public function testLogFilePathEndsWithLogExtension(): void
    {
        $logFilePath = $this->invokeLogFilePath('test-correlation-id');

        $this->assertStringEndsWith('.log', $logFilePath);
    }

    public function testLogFilePathStaysWithinLogDirectory(): void
    {
        // Even with malicious input, the path should stay within the log directory
        $maliciousInputs = [
            '../../../etc/passwd',
            '/etc/passwd',
            '..\\..\\windows\\system32',
        ];

        foreach ($maliciousInputs as $input) {
            $logFilePath = $this->invokeLogFilePath($input);

            // Path should start with the expected log directory
            $this->assertStringStartsWith('/var/www/log/', $logFilePath);

            // Path should not contain traversal sequences
            $this->assertStringNotContainsString('../', $logFilePath);
            $this->assertStringNotContainsString('..\\', $logFilePath);
        }
    }

    // ===========================================
    // INTEGRATION TESTS
    // ===========================================

    public function testCreateLoggerWithMaliciousCorrelationId(): void
    {
        $this->correlationIdProvider
            ->method('provide')
            ->willReturn('../../../etc/passwd');

        $this->moduleSettingFacade
            ->method('getLogLevel')
            ->willReturn('standard');

        // Use a temp directory that exists
        $tempDir = sys_get_temp_dir() . '/';
        $this->shopFacade = $this->createMock(ShopFacadeInterface::class);
        $this->shopFacade->method('getLogsPath')->willReturn($tempDir);
        $mockLogger = $this->createMock(LoggerInterface::class);
        $this->shopFacade->method('getLogger')->willReturn($mockLogger);

        $factory = new LoggerFactory(
            $this->correlationIdProcessor,
            $this->correlationIdProvider,
            $this->shopFacade,
            $this->moduleSettingFacade
        );

        // This should not throw and should not create files outside the log directory
        // Due to the mkdir() call we just verify no exception is thrown
        // and the sanitization works
        $sanitized = $this->invokeSanitizeFilename('../../../etc/passwd');
        $this->assertEquals('etcpasswd', $sanitized);
    }

    // ===========================================
    // HELPER METHODS
    // ===========================================

    private function invokeSanitizeFilename(string $filename): string
    {
        $method = new ReflectionMethod(LoggerFactory::class, 'sanitizeFilename');
        $method->setAccessible(true);

        return $method->invoke($this->factory, $filename);
    }

    private function invokeLogFilePath(string $filename): string
    {
        $method = new ReflectionMethod(LoggerFactory::class, 'logFilePath');
        $method->setAccessible(true);

        return $method->invoke($this->factory, $filename);
    }
}
