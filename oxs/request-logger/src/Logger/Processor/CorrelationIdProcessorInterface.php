<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Logger\Processor;

interface CorrelationIdProcessorInterface
{
    public function __invoke(array $record): array;
}
