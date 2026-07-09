<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\RequestLogger\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\ModuleConfiguration;
use OxidSupport\Heartbeat\Module\Module;

class ModuleConfigController extends ModuleConfiguration
{
    private const GRAPHQL_BASE_MODULE_ID = 'oe_graphql_base';
    private const CONFIG_ACCESS_MODULE_ID = 'oe_graphql_configuration_access';

    public function isModuleActivated(): bool
    {
        if ($this->getCurrentModuleId() !== Module::ID) {
            return false;
        }

        return $this->isModuleActive(Module::ID);
    }

    public function isGraphqlBaseActivated(): bool
    {
        return $this->isModuleActive(self::GRAPHQL_BASE_MODULE_ID);
    }

    public function isConfigAccessActivated(): bool
    {
        return $this->isModuleActive(self::CONFIG_ACCESS_MODULE_ID);
    }

    private function isModuleActive(string $moduleId): bool
    {
        try {
            /** @var \OxidEsales\Eshop\Core\Module\Module $module */
            $module = oxNew(\OxidEsales\Eshop\Core\Module\Module::class);
            $module->load($moduleId);
            return $module->isActive();
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function getCurrentModuleId(): string
    {
        return $this->getEditObjectId();
    }
}
