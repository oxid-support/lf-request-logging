<?php
declare(strict_types=1);

namespace OxidSupport\Logger\Logger;

use OxidSupport\Logger\Processor\RequestIdProcessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ShopLogger implements ShopLoggerInterface
{
    public function __construct(
        #[Autowire(service: 'oxs.logger.request')]
        private LoggerInterface $logger,
    ) {
    }

    public function create(): void
    {
        $logDir = $this->logDirectoryPath();
        $this->ensureLogDirectoryExists($logDir);

        $this->logger->pushProcessor(new RequestIdProcessor());
    }

    private function logDirectoryPath(): string
    {
        return
            OX_BASE_PATH .
            'log' . DIRECTORY_SEPARATOR;
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
            error_log(sprintf('ShopLogger: Failed to create log directory: %s', $dir));
        }
    }

    public function logStart(array $record): void
    {
        $this->logger->info('request.start', $record);
    }

    public function logSymbols(array $record): void
    {
        $this->logger->info('request.symbols', $record);
    }

    public function logFinish(array $record): void
    {
        $this->logger->info('request.finish', $record);
    }
}
