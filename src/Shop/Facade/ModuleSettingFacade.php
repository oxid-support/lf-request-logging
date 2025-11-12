<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Shop\Facade;

use OxidSupport\RequestLogger\Module\Module;
use OxidSupport\RequestLogger\Shop\Compatibility\ModuleSettings\ModuleSettingsPort;

class ModuleSettingFacade implements ModuleSettingFacadeInterface
{
    private ModuleSettingsPort $moduleSettingPort;

    public function __construct(ModuleSettingsPort $moduleSettingPort)
    {
        $this->moduleSettingPort = $moduleSettingPort;
    }

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

    public function isRedactAllValuesEnabled(): bool
    {
        return $this->moduleSettingPort->getBoolean(
            Module::ID . '_redact-all-values',
            Module::ID
        );
    }

    public function isLogFrontendEnabled(): bool
    {
        return $this->moduleSettingPort->getBoolean(
            Module::ID . '_log-frontend',
            Module::ID
        );
    }

    public function isLogAdminEnabled(): bool
    {
        return $this->moduleSettingPort->getBoolean(
            Module::ID . '_log-admin',
            Module::ID
        );
    }
}
