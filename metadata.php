<?php
declare(strict_types=1);

use OxidEsales\Eshop\Core\ShopControl;
use OxidSupport\RequestLogger\Module\Module as RequestLoggerModule;


$sMetadataVersion = '2.1';

$aModule = [
    'id' => RequestLoggerModule::ID,
    'title' => 'Minimal Invasive Massive Logging',
    'description' => 'This module provides detailed request logging for OXID eShop, capturing what users do inside the shop.
It records key request data such as visited pages, parameters, and context, making user flows and issues traceable.',
    'version' => '1.0.0',
    'author' => 'support@oxid-esales.com',
    'extend' => [
        ShopControl::class => \OxidSupport\RequestLogger\Shop\Extend\Core\ShopControl::class,
    ],
    'settings' => [
        [
            'group' => RequestLoggerModule::ID . '_main',
            'name' => RequestLoggerModule::ID . '_log-level',
            'type' => 'select',
            'constraints' => 'debug|info',
            'value' => 'info',
        ],
        [
            'group' => RequestLoggerModule::ID . '_main',
            'name' => RequestLoggerModule::ID . '_log-frontend',
            'type' => 'bool',
            'value' => false,
        ],
        [
            'group' => RequestLoggerModule::ID . '_main',
            'name' => RequestLoggerModule::ID . '_log-admin',
            'type' => 'bool',
            'value' => false,
        ],
        [
            'group' => RequestLoggerModule::ID . '_main',
            'name' => RequestLoggerModule::ID . '_redact',
            'type' => 'arr',
            'value' => [
                'pwd',
                'lgn_pwd',
                'lgn_pwd2',
                'newPassword',
            ],
        ],
    ],
];
