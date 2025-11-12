<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Logger\Processor;

use OxidSupport\RequestLogger\Logger\CorrelationId\CorrelationIdProviderInterface;

final class CorrelationIdProcessor implements CorrelationIdProcessorInterface
{
    private CorrelationIdProviderInterface $correlationIdProvider;

    public function __construct(CorrelationIdProviderInterface $correlationIdProvider)
    {
        $this->correlationIdProvider = $correlationIdProvider;
    }

    public function __invoke(array $record): array
    {
        $record['context']['correlationId'] = $this->correlationIdProvider->provide();
        return $record;
    }
}
