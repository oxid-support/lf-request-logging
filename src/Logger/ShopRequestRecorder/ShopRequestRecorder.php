<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Logger\ShopRequestRecorder;

use Psr\Log\LoggerInterface;

final class ShopRequestRecorder implements ShopRequestRecorderInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function logStart(array $record): void
    {
        $this->logger->info('request.start', $record);
    }

    public function logSymbols(array $record): void
    {
        $this->logger->debug('request.symbols', $record);
    }

    public function logFinish(array $record): void
    {
        $this->logger->info('request.finish', $record);
    }
}
