<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\RequestLogger\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidSupport\Heartbeat\Module\Module;

class ModuleConfigController extends ModuleConfiguration
{
    private ?ContextInterface $context = null;
    private ?ShopConfigurationDaoInterface $shopConfigurationDao = null;

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
            $shopConfiguration = $this->getShopConfigurationDao()->get(
                $this->getContext()->getCurrentShopId()
            );
            return $shopConfiguration
                ->getModuleConfiguration($moduleId)
                ->isActivated();
        } catch (\Exception) {
            return false;
        }
    }

    protected function getCurrentModuleId(): string
    {
        return $this->getEditObjectId();
    }

    private function getContext(): ContextInterface
    {
        if ($this->context === null) {
            $this->context = ContainerFactory::getInstance()
                ->getContainer()
                ->get(ContextInterface::class);
        }
        return $this->context; // @phpstan-ignore return.type
    }

    private function getShopConfigurationDao(): ShopConfigurationDaoInterface
    {
        if ($this->shopConfigurationDao === null) {
            $this->shopConfigurationDao = ContainerFactory::getInstance()
                ->getContainer()
                ->get(ShopConfigurationDaoInterface::class);
        }
        return $this->shopConfigurationDao; // @phpstan-ignore return.type
    }
}
