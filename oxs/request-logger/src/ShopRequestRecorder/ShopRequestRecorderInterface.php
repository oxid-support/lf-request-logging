<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\ShopRequestRecorder;

interface ShopRequestRecorderInterface
{

    public function logStart(array $record): void;

    public function logSymbols(array $record): void;

    public function logFinish(array $record): void;
}
