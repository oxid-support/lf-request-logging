<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Logger\Processor;

use OxidSupport\RequestLogger\CorrelationId\CorrelationIdProviderInterface;

final class CorrelationIdProcessor implements CorrelationIdProcessorInterface
{
    public function __construct(
        private CorrelationIdProviderInterface $correlationIdProvider,
    ) {}

    public function __invoke(array $record): array
    {
        $record['context']['correlationId'] = $this->correlationIdProvider->provide();
        return $record;
    }
}
