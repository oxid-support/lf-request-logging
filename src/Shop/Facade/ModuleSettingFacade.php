<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Shop\Facade;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidSupport\RequestLogger\Module\Module;

class ModuleSettingFacade implements ModuleSettingFacadeInterface
{
    public function __construct(
        private ModuleSettingServiceInterface $moduleSettingService
    ) {}

    public function getLogLevel(): string
    {
        return (string) $this->moduleSettingService->getString(
            Module::ID . '_log-level',
            Module::ID
        );
    }

    public function getRedactItems(): array
    {
        return
            $this->moduleSettingService->getCollection(
                Module::ID . '_redact',
                Module::ID
            );
    }
}
