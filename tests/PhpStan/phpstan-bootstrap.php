<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

// Create class alias for NavigationController_parent
class_alias(
    \OxidEsales\Eshop\Application\Controller\Admin\NavigationController::class,
    \OxidSupport\Heartbeat\Shared\Controller\Admin\NavigationController_parent::class
);

// Create class alias for the User extension parent (OXS-3060 service-user override)
class_alias(
    \OxidEsales\Eshop\Application\Model\User::class,
    \OxidSupport\Heartbeat\Shop\Extend\Application\Model\User_parent::class
);

// Bridge ModuleSettingBridgeInterface namespace difference between OXID 6.5 and 7.x
$ns = 'OxidEsales\EshopCommunity\Internal\Framework\Module\\';
$newInterface = $ns . 'Setting\Bridge\ModuleSettingBridgeInterface';
$oldInterface = $ns . 'Configuration\Bridge\ModuleSettingBridgeInterface';

if (!interface_exists($newInterface, false) && interface_exists($oldInterface)) {
    class_alias($oldInterface, $newInterface);
}
