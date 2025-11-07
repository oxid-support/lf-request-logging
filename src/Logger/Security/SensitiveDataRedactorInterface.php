<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Logger\Security;

interface SensitiveDataRedactorInterface
{
    public function redact(array $values): array;
}
