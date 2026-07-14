<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\DiagnosticsProvider\Service;

use OxidEsales\Eshop\Application\Model\Diagnostics;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface;
use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\Eshop\Core\Registry;

class DiagnosticsProvider implements DiagnosticsProviderInterface
{
    private ?Diagnostics $diagnostics = null;

    public function __construct(
        private readonly ShopConfigurationDaoBridgeInterface $shopConfigurationDaoBridge
    ) {
    }

    public function getDiagnosticsModel(): Diagnostics
    {
        if ($this->diagnostics) {
            return $this->diagnostics;
        }
        $this->diagnostics = oxNew(Diagnostics::class);
        return $this->diagnostics;
    }

    /**
     * @return array<string, Module>
     */
    public function getModuleList(): array
    {
        $shopConfiguration = $this->shopConfigurationDaoBridge->get();

        $modules = [];

        foreach ($shopConfiguration->getModuleConfigurations() as $moduleConfiguration) {
            $module = oxNew(Module::class);
            $module->load($moduleConfiguration->getId());
            $modules[$moduleConfiguration->getId()] = $module;
        }

        return $modules;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDiagnostics(): array
    {
        $aResults = [];
        $oDiagnostics = $this->getDiagnosticsModel();
        $oSysReq = oxNew(\OxidEsales\Eshop\Core\SystemRequirements::class);

        // Shop-aware URL: getShopUrl() resolves the current subshop's oxshops.OXURL,
        // whereas the sShopURL config param falls back to the installation base URL
        // for a subshop. See OXS-3134.
        $oDiagnostics->setShopLink(Registry::getConfig()->getShopUrl());
        $oDiagnostics->setEdition(Registry::getConfig()->getFullEdition());
        $oDiagnostics->setVersion(
            oxNew(\OxidEsales\Eshop\Core\ShopVersion::class)->getVersion()
        );

        // aShopDetails row counts come from the core Diagnostics model, which counts
        // the raw oxarticles/oxuser/oxshops tables, not shop views: the figures are
        // installation-wide, not per subshop. Left as-is on purpose; scoping them to
        // shop views or relabelling the section in the API would change the GraphQL
        // contract shared with the dashboard and must be coordinated there. See OXS-3134.
        $aResults["aShopDetails"]   = $oDiagnostics->getShopDetails();

        $aResults["aModuleList"] = $this->getModuleList();

        $aResults['aInfo'] = $oSysReq->getSystemInfo();
        $aResults['aCollations'] = $oSysReq->checkCollation();


        $aResults['aPhpConfigparams'] = $oDiagnostics->getPhpSelection();

        $aResults['sPhpDecoder'] = $oDiagnostics->getPhpDecoder();

        $aResults['aServerInfo'] = $oDiagnostics->getServerInfo();

        return $aResults;
    }
}
