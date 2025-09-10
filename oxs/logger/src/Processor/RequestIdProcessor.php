<?php
declare(strict_types=1);


namespace OxidSupport\Logger\Processor;

use OxidSupport\Logger\Request\CorrelationIdProvider;

final class RequestIdProcessor // @todo
{
    public function __invoke(array $record): array
    {
        $record['context']['correlationId'] = CorrelationIdProvider::get();
        return $record;
    }
}
