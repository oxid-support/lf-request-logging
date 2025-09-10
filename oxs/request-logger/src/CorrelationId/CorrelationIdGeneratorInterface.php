<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\CorrelationId;

interface CorrelationIdGeneratorInterface
{
    public function generate(): string;
}
