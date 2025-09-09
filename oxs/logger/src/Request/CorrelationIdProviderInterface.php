<?php
declare(strict_types=1);

namespace OxidSupport\Logger\Request;

interface CorrelationIdProviderInterface
{
    public static function get(): string;
}
