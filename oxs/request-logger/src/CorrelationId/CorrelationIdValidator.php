<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\CorrelationId;

final class CorrelationIdValidator implements CorrelationIdValidatorInterface
{
    public function isValid(string $id): bool
    {
        return strlen($id) === 32 && ctype_xdigit($id);
    }
}
