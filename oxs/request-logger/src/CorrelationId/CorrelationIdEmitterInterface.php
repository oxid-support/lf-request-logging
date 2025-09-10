<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\CorrelationId;

interface CorrelationIdEmitterInterface
{
    public function emit(string $id): void;
}
