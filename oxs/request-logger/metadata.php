<?php
declare(strict_types=1);

use OxidEsales\Eshop\Core\ShopControl;
use OxidSupport\RequestLogger\Module\Module as LoggingFrameworkModule;


$sMetadataVersion = '2.1';

$aModule = [
    'id' => LoggingFrameworkModule::ID,
    'title' => 'Minimal Invasive Massive Logging',
    'description' => 'PSR-3 Logging mit Request-Kontext, Error/Exception/Shutdown Hooks',
    'version' => '1.0.0',
    'author' => 'support@oxid-esales.com',
    'extend' => [
        ShopControl::class => \OxidSupport\RequestLogger\Shop\Extend\Core\ShopControl::class,
    ]
];
