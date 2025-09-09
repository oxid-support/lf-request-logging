<?php
declare(strict_types=1);

namespace OxidSupport\Logger\Logger;

use OxidSupport\Logger\Processor\RequestContextProcessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ShopLogger implements ShopLoggerInterface
{
    public function __construct(
        #[Autowire(service: 'oxs.logger.request')]
        private LoggerInterface $logger,
    ) {}

    public function create(): void
    {
        $logFile = $this->logFilename();
        if (!is_dir($logFile)) {
            @mkdir($logFile, 0775, true);
        }

        $this->logger->pushProcessor(new RequestContextProcessor());

        // Stacktrace NUR für Aktions-Events
        $this->logger->pushProcessor(new StackTraceProcessor(
            maxDepth: (int)($_ENV['OXSL_STACK_DEPTH'] ?? 12),
            includeArgs: false
        ));
    }

    private function logFilename(): string
    {
        return
            OX_BASE_PATH .
            'log' . DIRECTORY_SEPARATOR;
    }

    public function logRoute(array $record): void
    {
        $this->logger->info('request.route', $record);
    }

    public function logSymbols(array $record): void
    {
        $this->logger->info('request.symbols', $record['symbols']);
    }

    public function logFinish(array $record): void
    {
       $this->logger->info('request.finish', $record);
    }
}
