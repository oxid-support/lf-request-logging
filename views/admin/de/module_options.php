<?php

declare(strict_types=1);

use OxidSupport\RequestLogger\Module\Module as RequestLoggerModule;

$aLang = [
    'charset' => 'UTF-8',
    'SHOP_MODULE_GROUP_' . RequestLoggerModule::ID . '_main' => 'Einstellungen',
    'SHOP_MODULE_' . RequestLoggerModule::ID . '_log-level' => 'Log Level',
    'SHOP_MODULE_' . RequestLoggerModule::ID . '_log-level_info' => 'INFO',
    'SHOP_MODULE_' . RequestLoggerModule::ID . '_log-level_debug' => 'DEBUG',
    'SHOP_MODULE_' . RequestLoggerModule::ID . '_log-frontend' => 'Frontend-Anfragen protokollieren',
    'SHOP_MODULE_' . RequestLoggerModule::ID . '_log-admin' => 'Admin-Anfragen protokollieren',
    'SHOP_MODULE_' . RequestLoggerModule::ID . '_redact' => 'Zensieren',
];
