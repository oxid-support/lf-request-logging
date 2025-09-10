<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Logger\Processor;

use OxidSupport\RequestLogger\CorrelationId\CorrelationIdProvider;

final class CorrelationIdProcessor // @todo
{
    public function __invoke(array $record): array
    {
        $record['context']['correlationId'] = CorrelationIdProvider::get();
        return $record;
    }
}
