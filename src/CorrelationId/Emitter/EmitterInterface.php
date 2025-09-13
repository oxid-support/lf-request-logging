<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\CorrelationId\Emitter;

interface EmitterInterface
{
    public function emit(string $id): void;
}
