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
    'version' => '1.0.2',
    'author' => 'OXID Support',
    'email' => 'support@oxid-esales.com',
    'url' => 'https://oxid-esales.com',
    'extend' => [
        ShopControl::class => \OxidSupport\Heartbeat\Shop\Extend\Core\ShopControl::class,
        \OxidEsales\Eshop\Application\Controller\Admin\ModuleConfiguration::class =>
            \OxidSupport\Heartbeat\Component\RequestLogger\Controller\Admin\ModuleConfigController::class,
        \OxidEsales\Eshop\Application\Controller\Admin\NavigationController::class =>
            \OxidSupport\Heartbeat\Shared\Controller\Admin\NavigationController::class,
    ],
    'controllers' => [
        // Controller registrations for OXID eShop 6.5
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
    'templates' => [
        'heartbeat_base.tpl' => 'oxid-support/heartbeat/views/admin/tpl/heartbeat_base.tpl',
        'heartbeat_apiuser_setup.tpl' => 'oxid-support/heartbeat/views/admin/tpl/heartbeat_apiuser_setup.tpl',
        'heartbeat_requestlogger_setup.tpl' => 'oxid-support/heartbeat/views/admin/tpl/heartbeat_requestlogger_setup.tpl',
        'heartbeat_requestlogger_settings.tpl' => 'oxid-support/heartbeat/views/admin/tpl/heartbeat_requestlogger_settings.tpl',
        'heartbeat_logsender_manage.tpl' => 'oxid-support/heartbeat/views/admin/tpl/heartbeat_logsender_manage.tpl',
        'heartbeat_diagnosticsprovider_manage.tpl' => 'oxid-support/heartbeat/views/admin/tpl/heartbeat_diagnosticsprovider_manage.tpl',
    ],
    'blocks' => [
        [
            'template' => 'navigation.tpl',
            'block' => 'admin_navigation_menustructure',
            'file' => 'views/admin_smarty/navigation.tpl',
        ],
        [
            'template' => 'module_config.tpl',
            'block' => 'admin_module_config_form',
            'file' => 'views/admin_smarty/module_config.tpl',
        ],
    ],
    'events' => [
        'onActivate' => \OxidSupport\Heartbeat\Component\RequestLogger\Core\ModuleEvents::class . '::onActivate',
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
