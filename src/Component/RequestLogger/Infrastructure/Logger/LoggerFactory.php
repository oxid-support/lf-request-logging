<?php

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger;

use Exception;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\CorrelationId\CorrelationIdProviderInterface;
use OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\Processor\CorrelationIdProcessorInterface;
use OxidSupport\Heartbeat\Module\Module;
use OxidSupport\Heartbeat\Shop\Facade\ModuleSettingFacadeInterface;
use OxidSupport\Heartbeat\Shop\Facade\ShopFacadeInterface;

class LoggerFactory
{
    private const LOG_DIRECTORY_NAME = 'oxs-request-logger';

    private CorrelationIdProcessorInterface $correlationIdProcessor;
    private CorrelationIdProviderInterface $correlationIdProvider;
    private ShopFacadeInterface $facade;
    private ModuleSettingFacadeInterface $moduleSettingFacade;

    public function __construct(
        CorrelationIdProcessorInterface $correlationIdProcessor,
        CorrelationIdProviderInterface $correlationIdProvider,
        ShopFacadeInterface $facade,
        ModuleSettingFacadeInterface $moduleSettingFacade
    ) {
        $this->correlationIdProcessor = $correlationIdProcessor;
        $this->correlationIdProvider = $correlationIdProvider;
        $this->facade = $facade;
        $this->moduleSettingFacade = $moduleSettingFacade;
    }

    /**
     * @throws Exception
     */
    public function create(): Logger
    {
        $this->ensureLogDirectoryExists(
            $this->logDirectoryPath()
        );

        $handler = new StreamHandler(
            $this->logFilePath(
                $this->correlationIdProvider->provide()
            ),
            $this->mapLogLevelToMonologLevel(
                $this->moduleSettingFacade->getLogLevel()
            ),
            true,
            // Logs contain session ids, usernames, IPs and request data; never
            // world-readable. 0640 = owner rw, group r, others none.
            0640
        );

        $handler->setFormatter(
            // allowInlineLineBreaks=false: an injected newline in logged data
            // must not become a separate, forged log record (log injection).
            new LineFormatter(null, null, false, true)
        );

        $logger = new Logger(Module::ID);
        $logger->pushHandler($handler);

        $logger->pushProcessor(
            $this->correlationIdProcessor
        );

        return $logger;
    }

    private function mapLogLevelToMonologLevel(string $level): int
    {
        switch ($level) {
            case 'standard':
                return Logger::INFO;
            case 'detailed':
                return Logger::DEBUG;
            default:
                return Logger::INFO;
        }
    }

    private function logFilePath(string $filename): string
    {
        $dir = $this->logDirectoryPath();

        // Security: Sanitize correlation ID to prevent path traversal attacks
        // Only allow alphanumeric characters, hyphens, and underscores
        $sanitizedFilename = $this->sanitizeFilename($filename);

        $filename = sprintf('%s-%s.log', self::LOG_DIRECTORY_NAME, $sanitizedFilename);

        return $dir . $filename;
    }

    /**
     * Sanitizes a filename to prevent path traversal and other file system attacks.
     * Only allows alphanumeric characters, hyphens, and underscores.
     *
     * @param string $filename The filename to sanitize
     * @return string The sanitized filename
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove any path traversal sequences first
        $filename = str_replace(['../', '..\\', '/', '\\'], '', $filename);

        // Only allow safe characters: alphanumeric, hyphen, underscore
        $sanitized = preg_replace('/[^a-zA-Z0-9\-_]/', '', $filename);

        // Ensure the filename is not empty after sanitization
        if ($sanitized === '' || $sanitized === null) {
            // Generate a fallback using current timestamp
            $sanitized = 'fallback-' . time();
        }

        // Limit filename length to prevent excessive path lengths
        return substr($sanitized, 0, 64);
    }

    private function logDirectoryPath(): string
    {
        // Per-shop subdirectory so EE subshops do not share one flat log dir.
        // On CE the shop id is always 1, so this is a no-op there. The LogSender
        // read path (RequestLogger LogPathProvider) mirrors this layout. See OXS-3130.
        return
            $this->facade->getLogsPath() .
            self::LOG_DIRECTORY_NAME .
            DIRECTORY_SEPARATOR .
            $this->facade->getShopId() .
            DIRECTORY_SEPARATOR;
    }

    private function ensureLogDirectoryExists(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }

        // Try to create; if it fails, check again to be safe against race conditions.
        // 0750: owner rwx, group rx, others none (logs must not be world-accessible).
        if (!mkdir($dir, 0750, true) && !is_dir($dir)) {
            // Emit an error rather than suppressing; avoids failing silently.
            // Using error_log keeps this method independent of $this->logger configuration order.

            $errorMessage = sprintf(
                'Module %s: Failed to create log directory: %s, due to missing permissions (0750).',
                Module::ID,
                $dir
            );

            $this->logToShopLogDir($errorMessage);
            $this->logToPhpErrorLog($errorMessage);
        }
    }

    private function logToShopLogDir(string $message): void
    {
        $this->facade->getLogger()->error($message);
    }

    public function logToPhpErrorLog(string $message): void
    {
        error_log($message);
    }
}
