<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Logger;

use Exception;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use OxidSupport\RequestLogger\Logger\Processor\CorrelationIdProcessorInterface;
use OxidSupport\RequestLogger\Module\Module;
use OxidSupport\RequestLogger\CorrelationId\CorrelationIdProviderInterface;

class LoggerFactory
{
    public function __construct(
        private CorrelationIdProcessorInterface $correlationIdProcessor,
        private CorrelationIdProviderInterface $correlationIdProvider,
    ) {}

    /**
     * @throws Exception
     */
    public function create(): Logger
    {
        $dir = $this->logDirectoryPath();
        $this->ensureLogDirectoryExists($dir);

        $handler = new StreamHandler(
            $this->logfilePath(
                $this->correlationIdProvider->provide()
            )
        );

        $handler->setFormatter(
            new JsonFormatter()
        );

        $logger = new Logger(Module::ID);
        $logger->pushHandler($handler);

        $logger->pushProcessor(
            $this->correlationIdProcessor
        );

        return $logger;
    }

    private function logfilePath(string $filename): string
    {
        $dir = $this->logDirectoryPath();
        $filename = sprintf('%s-%s.json', Module::ID, $filename);

        return $dir . $filename;
    }

    private function logDirectoryPath(): string
    {
        return OX_BASE_PATH . 'log' . DIRECTORY_SEPARATOR;
    }

    private function ensureLogDirectoryExists(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }

        // Try to create; if it fails, check again to be safe against race conditions.
        if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
            // Emit a warning rather than suppressing; avoids failing silently.
            // Using error_log keeps this method independent from $this->logger configuration order.
            error_log(sprintf('ShopLogger: Failed to create log directory: %s', $dir)); //@todo
        }
    }
}
