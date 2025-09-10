<?php
declare(strict_types=1);

namespace OxidSupport\RequestLogger\CorrelationId;

interface CorrelationIdProviderInterface
{
    public static function get(): string;
}
