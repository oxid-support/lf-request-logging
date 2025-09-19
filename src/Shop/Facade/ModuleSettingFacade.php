<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Shop\Facade;

use OxidSupport\RequestLogger\Module\Module;
use OxidSupport\RequestLogger\Shop\Compatibility\ModuleSettings\ModuleSettingsPort;

class ModuleSettingFacade implements ModuleSettingFacadeInterface
{
    public function __construct(
        private ModuleSettingsPort $moduleSettingPort,
    ) {}

    public function getLogLevel(): string
    {
        return $this->moduleSettingPort->getString(
            Module::ID . '_log-level',
            Module::ID
        );
    }

    public function getRedactItems(): array
    {
        return
            $this->moduleSettingPort->getCollection(
                Module::ID . '_redact',
                Module::ID
            );
    }
}
