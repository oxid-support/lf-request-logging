<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\CorrelationId;

interface CorrelationIdValidatorInterface
{
    public function isValid(string $id): bool;
}
