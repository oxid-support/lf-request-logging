<?php

declare(strict_types=1);

namespace OxidSupport\Logger\Logger;

interface ShopLoggerInterface
{
    public function create(): void;

    public function logRoute(array $record): void;

    public function logSymbols(array $record): void;

    public function logFinish(array $record): void;
}
