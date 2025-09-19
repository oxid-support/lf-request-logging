<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Logger;

use Exception;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use OxidSupport\RequestLogger\Logger\CorrelationId\CorrelationIdProviderInterface;
use OxidSupport\RequestLogger\Logger\Processor\CorrelationIdProcessorInterface;
use OxidSupport\RequestLogger\Module\Module;
use OxidSupport\RequestLogger\Shop\Facade\ModuleSettingFacadeInterface;
use OxidSupport\RequestLogger\Shop\Facade\ShopFacadeInterface;

class LoggerFactory
{
    public function __construct(
        private CorrelationIdProcessorInterface $correlationIdProcessor,
        private CorrelationIdProviderInterface $correlationIdProvider,
        private ShopFacadeInterface $facade,
        private ModuleSettingFacadeInterface $moduleSettingFacade
    ) {}

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
            $this->moduleSettingFacade->getLogLevel()
        );

        $handler->setFormatter(
            new LineFormatter(null, null, true, true)
        );

        $logger = new Logger(Module::ID);
        $logger->pushHandler($handler);

        $logger->pushProcessor(
            $this->correlationIdProcessor
        );

        return $logger;
    }

    private function logFilePath(string $filename): string
    {
        $dir = $this->logDirectoryPath();
        $filename = sprintf('%s-%s.log', Module::ID, $filename);

        return $dir . $filename;
    }

    private function logDirectoryPath(): string
    {
        return
            $this->facade->getLogsPath() .
            Module::ID .
            DIRECTORY_SEPARATOR;
    }

    private function ensureLogDirectoryExists(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }

        // Try to create; if it fails, check again to be safe against race conditions.
        if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
            // Emit an error rather than suppressing; avoids failing silently.
            // Using error_log keeps this method independent of $this->logger configuration order.

            $errorMessage = sprintf(
                'Module %s: Failed to create log directory: %s, due to missing permissions (0775).',
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
