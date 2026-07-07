<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Module;

final class Module
{
    public const ID = 'oxsheartbeat';

    // Module version — single source of truth. metadata.php reads this via HeartbeatModule::VERSION.
    public const VERSION = '2.0.4';

    // API version contract
    public const API_VERSION = '1.0.0';

    /** @var string[] List of all GraphQL operations this module provides/supports */
    public const SUPPORTED_OPERATIONS = [
        'token',
        'heartbeatSetPassword',
        'heartbeatResetPassword',
        'heartbeatInvalidateTokens',
        'requestLoggerSettings',
        'requestLoggerLogLevel',
        'requestLoggerLogLevelChange',
        'requestLoggerLogFrontend',
        'requestLoggerLogFrontendChange',
        'requestLoggerLogAdmin',
        'requestLoggerLogAdminChange',
        'requestLoggerRedact',
        'requestLoggerRedactAllValues',
        'requestLoggerRedactAllValuesChange',
        'logSenderSources',
        'logSenderContent',
        'diagnostics',
        'heartbeatApiVersion',
    ];

    // Request Logger component settings
    public const SETTING_REQUESTLOGGER_ACTIVE = self::ID . '_requestlogger_active';
    public const SETTING_REQUESTLOGGER_LOG_LEVEL = self::ID . '_requestlogger_log_level';
    public const SETTING_REQUESTLOGGER_LOG_FRONTEND = self::ID . '_requestlogger_log_frontend';
    public const SETTING_REQUESTLOGGER_LOG_ADMIN = self::ID . '_requestlogger_log_admin';
    public const SETTING_REQUESTLOGGER_REDACT_FIELDS = self::ID . '_requestlogger_redact_fields';
    public const SETTING_REQUESTLOGGER_REDACT_ALL_VALUES = self::ID . '_requestlogger_redact_all_values';

    // API User component settings
    public const SETTING_APIUSER_SETUP_TOKEN = self::ID . '_apiuser_setup_token';

    // Remote component settings
    public const SETTING_REMOTE_ACTIVE = self::ID . '_remote_active';

    // Log Sender component settings
    public const SETTING_LOGSENDER_ACTIVE = self::ID . '_logsender_active';
    public const SETTING_LOGSENDER_STATIC_PATHS = self::ID . '_logsender_static_paths';
    public const SETTING_LOGSENDER_MAX_BYTES = self::ID . '_logsender_max_bytes';
    public const SETTING_LOGSENDER_ENABLED_SOURCES = self::ID . '_logsender_enabled_sources';

    // Diagnostics Provider component settings
    public const SETTING_DIAGNOSTICSPROVIDER_ACTIVE = self::ID . '_diagnosticsprovider_active';

    public const API_USER_EMAIL = 'heartbeat-api@oxid-esales.com';
}
