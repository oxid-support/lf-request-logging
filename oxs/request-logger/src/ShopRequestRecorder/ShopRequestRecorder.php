<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\ShopRequestRecorder;

use Psr\Log\LoggerInterface;

final class ShopRequestRecorder implements ShopRequestRecorderInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

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
