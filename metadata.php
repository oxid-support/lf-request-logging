<?php
declare(strict_types=1);

use OxidEsales\Eshop\Core\ShopControl;
use OxidSupport\Heartbeat\Module\Module as HeartbeatModule;

$sMetadataVersion = '2.1';

$aModule = [
    'id' => HeartbeatModule::ID,
    'title' => 'OXS :: Heartbeat',
    'description' => 'This module provides comprehensive logging and monitoring capabilities for OXID eShop.
It includes detailed request logging, capturing what users do inside the shop.
Records key request data such as visited pages, parameters, and context, making user flows and issues traceable.
Includes GraphQL API for remote configuration and activation.',
    'version' => HeartbeatModule::VERSION,
    'author' => 'OXID Support',
    'email' => 'support@oxid-esales.com',
    'url' => 'https://oxid-esales.com',
    'extend' => [
        ShopControl::class => \OxidSupport\Heartbeat\Shop\Extend\Core\ShopControl::class,
        \OxidEsales\Eshop\Application\Controller\Admin\ModuleConfiguration::class =>
            \OxidSupport\Heartbeat\Component\RequestLogger\Controller\Admin\ModuleConfigController::class,
        \OxidEsales\Eshop\Application\Controller\Admin\NavigationController::class =>
            \OxidSupport\Heartbeat\Shared\Controller\Admin\NavigationController::class,
        // Override oxuser to invalidate Heartbeat JWTs when the service user
        // is edited directly in the OXID admin area (Stamm > Benutzer).
        // See OXS-3060.
        \OxidEsales\Eshop\Application\Model\User::class =>
            \OxidSupport\Heartbeat\Shop\Extend\Application\Model\User::class,
    ],
    'controllers' => [
        'heartbeat_requestlogger_settings' =>
            \OxidSupport\Heartbeat\Component\RequestLogger\Controller\Admin\SettingsController::class,
        'heartbeat_apiuser_setup' =>
            \OxidSupport\Heartbeat\Component\ApiUser\Controller\Admin\SetupController::class,
        'heartbeat_requestlogger_password_reset' =>
            \OxidSupport\Heartbeat\Component\RequestLogger\Controller\Admin\PasswordResetController::class,
        'heartbeat_requestlogger_setup' =>
            \OxidSupport\Heartbeat\Component\RequestLogger\Controller\Admin\RemoteSetupController::class,
        'heartbeat_logsender_manage' =>
            \OxidSupport\Heartbeat\Component\LogSender\Controller\Admin\ManageController::class,
        'heartbeat_diagnosticsprovider_manage' =>
            \OxidSupport\Heartbeat\Component\DiagnosticsProvider\Controller\Admin\ManageController::class,
    ],
    'events' => [
        'onActivate' => \OxidSupport\Heartbeat\Component\RequestLogger\Core\ModuleEvents::class . '::onActivate',
        'onDeactivate' => \OxidSupport\Heartbeat\Component\RequestLogger\Core\ModuleEvents::class . '::onDeactivate',
    ],
    'settings' => [
        // Request Logger component settings (hidden - managed via component UI)
        [
            'group' => '',
            'name'  => HeartbeatModule::ID . '_requestlogger_active',
            'type'  => 'bool',
            'value' => false,
        ],
        [
            'group' => '',
            'name'  => HeartbeatModule::ID . '_requestlogger_log_level',
            'type'  => 'select',
            'constraints' => 'standard|detailed',
            'value' => 'standard',
        ],
        [
            'group' => '',
            'name'  => HeartbeatModule::ID . '_requestlogger_log_frontend',
            'type'  => 'bool',
            'value' => false,
        ],
        [
            'group' => '',
            'name'  => HeartbeatModule::ID . '_requestlogger_log_admin',
            'type'  => 'bool',
            'value' => false,
        ],
        [
            'group' => '',
            'name'  => HeartbeatModule::ID . '_requestlogger_redact_fields',
            'type'  => 'arr',
            'value' => [
                'pwd',
                'lgn_pwd',
                'lgn_pwd2',
                'newPassword',
            ],
        ],
        [
            'group' => '',
            'name'  => HeartbeatModule::ID . '_requestlogger_redact_all_values',
            'type'  => 'bool',
            'value' => true,
        ],
        // API User component settings
        [
            'group' => '',
            'name'  => HeartbeatModule::ID . '_apiuser_setup_token',
            'type'  => 'str',
            'value' => '',
        ],
        // Remote component settings
        [
            'group' => '',
            'name'  => HeartbeatModule::ID . '_remote_active',
            'type'  => 'bool',
            'value' => false,
        ],
        // Log Sender component settings
        [
            'group' => '',
            'name'  => HeartbeatModule::ID . '_logsender_active',
            'type'  => 'bool',
            'value' => false,
        ],
        [
            'group' => '',
            'name'  => HeartbeatModule::ID . '_logsender_static_paths',
            'type'  => 'arr',
            'value' => [],
        ],
        [
            'group' => '',
            'name'  => HeartbeatModule::ID . '_logsender_max_bytes',
            'type'  => 'num',
            'value' => 1048576,
        ],
        [
            'group' => '',
            'name'  => HeartbeatModule::ID . '_logsender_enabled_sources',
            'type'  => 'arr',
            'value' => [],
        ],
        // Diagnostics Provider component settings
        [
            'group' => '',
            'name'  => HeartbeatModule::ID . '_diagnosticsprovider_active',
            'type'  => 'bool',
            'value' => false,
        ],
    ],
];
