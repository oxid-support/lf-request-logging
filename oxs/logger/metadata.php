<?php
declare(strict_types=1);

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidSupport\Logger\Shop\Core\FrontendControllerExtended;
use OxidSupport\Logger\Shop\Core\ShopControlExtend;

$sMetadataVersion = '2.1';

$aModule = [
    'id' => \OxidSupport\Logger\Bootstrap\Module::ID,
    'title' => 'Minimal Invasive Massive Logging',
    'description' => 'PSR-3 Logging mit Request-Kontext, Error/Exception/Shutdown Hooks',
    'version' => '1.0.0',
    'author' => 'support@oxid-esales.com',
    'extend' => [
        OxidEsales\Eshop\Core\ShopControl::class => ShopControlExtend::class,
        FrontendController::class => FrontendControllerExtended::class,
    ],
    'events' => [
        'onActivate'    => 'OxidSupport\\Logger\\Bootstrap\\HandlerRegistrar::onActivate',
        'onDeactivate'  => 'OxidSupport\\Logger\\Bootstrap\\HandlerRegistrar::onDeactivate',
    ],
];
