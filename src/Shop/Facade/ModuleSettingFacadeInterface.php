<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Shop\Facade;

interface ModuleSettingFacadeInterface
{
    public function getLogLevel(): string;

    public function getRedactItems(): array;

    public function isRedactAllValuesEnabled(): bool;

    public function isLogFrontendEnabled(): bool;

    public function isLogAdminEnabled(): bool;
}
