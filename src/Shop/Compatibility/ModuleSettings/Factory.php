<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Shop\Compatibility\ModuleSettings;

use OxidSupport\RequestLogger\Shop\Compatibility\DiContainer\DiContainerPort;
use RuntimeException;

class Factory
{
    public function __construct(
        private DiContainerPort $container
    ) {}

    public function create(): ModuleSettingsPort
    {
        // v7
        $v7 = 'OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface';
        if (interface_exists($v7)) {
            return new Oxid7ModuleSettingsAdapter($this->container->get($v7));
        }

        // v6
        $v6 = 'OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Service\ModuleSettingServiceInterface';
        if (interface_exists($v6)) {
            return new Oxid6ModuleSettingsAdapter($this->container->get($v6));
        }

        throw new RuntimeException('No suitable module settings API found (6.2/7).');
    }
}
