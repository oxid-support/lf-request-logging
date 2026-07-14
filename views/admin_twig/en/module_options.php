<?php

declare(strict_types=1);

$aLang = [
    'charset' => 'UTF-8',

    // Heartbeat Navigation
    'mxheartbeat' => 'OXS :: Heartbeat',
    'mxheartbeat_apiuser' => 'API User',
    'tbclheartbeat_apiuser_setup' => 'Setup',
    'mxheartbeat_requestlogger' => 'Request Logger',
    'tbclheartbeat_requestlogger_settings' => 'Settings',
    'mxheartbeat_logsender' => 'Log Sender',
    'tbclheartbeat_logsender_manage' => 'Manage',
    'mxheartbeat_diagnosticsprovider' => 'Diagnostics Provider',
    'tbclheartbeat_diagnosticsprovider_manage' => 'Manage',

    // Heartbeat Component Status
    'OXSHEARTBEAT_LF_STATUS_ACTIVE' => 'Active',
    'OXSHEARTBEAT_LF_STATUS_INACTIVE' => 'Inactive',
    'OXSHEARTBEAT_LF_COMPONENT_ACTIVATION' => 'Activate Component',
    'OXSHEARTBEAT_LF_COMPONENT_ACTIVATION_DESC' => 'Toggle this component on or off.',

    // Heartbeat Request Logger
    'OXSHEARTBEAT_LF_REQUESTLOGGER_TITLE' => 'Request Logger',
    'OXSHEARTBEAT_LF_REQUESTLOGGER_DESC' => 'Configures the logging of shop requests for error analysis.',

    // Heartbeat Request Logger Settings
    'OXSHEARTBEAT_LF_SETTINGS_ACTIVATION' => 'Activation',
    'OXSHEARTBEAT_LF_SETTINGS_LOGGING' => 'Logging',
    'OXSHEARTBEAT_LF_SETTINGS_REDACTION' => 'Redaction',
    'OXSHEARTBEAT_LF_SETTINGS_LOG_FRONTEND' => 'Log Frontend',
    'OXSHEARTBEAT_LF_SETTINGS_LOG_FRONTEND_HELP' => 'Enables logging of frontend requests.',
    'OXSHEARTBEAT_LF_SETTINGS_LOG_ADMIN' => 'Log Admin',
    'OXSHEARTBEAT_LF_SETTINGS_LOG_ADMIN_HELP' => 'Enables logging of admin requests.',
    'OXSHEARTBEAT_LF_SETTINGS_DETAILED_LOGGING' => 'Detailed Logging',
    'OXSHEARTBEAT_LF_SETTINGS_DETAILED_LOGGING_HELP' => 'Enables extended logging with more details.',
    'OXSHEARTBEAT_LF_SETTINGS_REDACT_ALL' => 'Redact All Values',
    'OXSHEARTBEAT_LF_SETTINGS_REDACT_ALL_HELP' => 'Redacts all parameter values in the log.',
    'OXSHEARTBEAT_LF_SETTINGS_REDACT_FIELDS' => 'Redact Fields',
    'OXSHEARTBEAT_LF_SETTINGS_REDACT_FIELDS_HELP' => 'List of field names (one per line) whose values should be redacted.',
    'OXSHEARTBEAT_LF_SETTINGS_SAVE' => 'Save',

    // Heartbeat Remote
    'OXSHEARTBEAT_LF_REMOTE_TITLE' => 'Request Logger Remote',
    'OXSHEARTBEAT_LF_REMOTE_DESC' => 'Allows OXID Support to configure the Request Logger remotely.',

    // ==========================================================================
    // Module Config Tab
    // ==========================================================================
    'OXSHEARTBEAT_MODULE_CONFIG_HINT' => 'Settings and setup for this module are in the left menu under "OXS :: Heartbeat", specifically the entry',
    'OXSHEARTBEAT_MODULE_CONFIG_HINT_LINK' => 'API User',

    // ==========================================================================
    // API User Component
    // ==========================================================================
    'OXSHEARTBEAT_APIUSER_TITLE' => 'API User',
    'OXSHEARTBEAT_APIUSER_DESC' => 'Manages the API user for remote access to Heartbeat.',
    'OXSHEARTBEAT_APIUSER_STATUS_READY' => 'Active',
    'OXSHEARTBEAT_APIUSER_STATUS_SETUP_REQUIRED' => 'Setup Required',
    'OXSHEARTBEAT_APIUSER_INFO_TITLE' => 'Important',
    'OXSHEARTBEAT_APIUSER_INFO_TEXT' => 'The API User is required for all components that need remote access (e.g., Request Logger Remote). Set this up first.',

    // API User Setup Workflow
    'OXSHEARTBEAT_APIUSER_SETUP_TITLE' => 'Setup Workflow',
    'OXSHEARTBEAT_APIUSER_STEP_INSTALL' => 'Module installed',
    'OXSHEARTBEAT_APIUSER_STEP_GRAPHQL_BASE' => 'GraphQL Base module activated',
    'OXSHEARTBEAT_APIUSER_STEP_GRAPHQL_BASE_DESC' => 'Activate with: ./vendor/bin/oe-console oe:module:activate oe_graphql_base',
    'OXSHEARTBEAT_APIUSER_STEP_CONFIG_ACCESS' => 'GraphQL Configuration Access module activated',
    'OXSHEARTBEAT_APIUSER_STEP_CONFIG_ACCESS_DESC' => 'Activate with: ./vendor/bin/oe-console oe:module:activate oe_graphql_configuration_access',
    'OXSHEARTBEAT_APIUSER_STEP_ACTIVATE' => 'Heartbeat module activated',
    'OXSHEARTBEAT_APIUSER_STEP_SEND_TOKEN' => 'Send setup token to OXID Support',
    'OXSHEARTBEAT_APIUSER_STEP_SEND_TOKEN_DESC' => 'Copy the token below and send it via email to support@oxid-esales.com',
    'OXSHEARTBEAT_APIUSER_STEP_WAIT_SUPPORT' => 'Wait for OXID Support to activate API access',
    'OXSHEARTBEAT_APIUSER_PREREQUISITES_WARNING' => 'Important: Without the GraphQL Base module, support cannot use the token!',
    'OXSHEARTBEAT_APIUSER_COPIED' => 'Copied!',
    'OXSHEARTBEAT_APIUSER_SETUP_COMPLETE_TITLE' => 'API User Set Up',
    'OXSHEARTBEAT_APIUSER_SETUP_COMPLETE_TEXT' => 'The API User has been successfully configured. Components like Request Logger Remote can now be activated.',

    // API User Reset
    'OXSHEARTBEAT_APIUSER_RESET_TITLE' => 'Reset API Access',
    'OXSHEARTBEAT_APIUSER_RESET_DESCRIPTION' => 'This action resets the password of the API user and generates a new setup token. Use this only if remote access needs to be set up again.',
    'OXSHEARTBEAT_APIUSER_WARNING_1' => 'The current API password will be invalidated',
    'OXSHEARTBEAT_APIUSER_WARNING_2' => 'All existing remote sessions will be terminated immediately',
    'OXSHEARTBEAT_APIUSER_WARNING_3' => 'OXID Support will lose access until a new token is provided and a new password is set',
    'OXSHEARTBEAT_APIUSER_WARNING_4' => 'You must send the new token to OXID Support to restore access',
    'OXSHEARTBEAT_APIUSER_CONFIRM_RESET' => 'I understand the consequences and want to reset the password',
    'OXSHEARTBEAT_APIUSER_CONFIRM_DIALOG' => 'Are you absolutely sure? This will immediately revoke all remote access!',
    'OXSHEARTBEAT_APIUSER_RESET_BUTTON' => 'Reset Password & Generate New Token',

    // API User Token Invalidation (OXS-3058)
    'OXSHEARTBEAT_APIUSER_INVALIDATE_TITLE' => 'Terminate API Sessions',
    'OXSHEARTBEAT_APIUSER_INVALIDATE_DESCRIPTION' => 'Terminates all active sessions (JWTs) of the API user immediately. The service password and the setup token stay unchanged. OXID Support can log in again right after.',
    'OXSHEARTBEAT_APIUSER_INVALIDATE_WARNING_1' => 'All currently active remote sessions of OXID Support will be terminated immediately',
    'OXSHEARTBEAT_APIUSER_INVALIDATE_WARNING_2' => 'OXID Support must log in again before remote operations can continue',
    'OXSHEARTBEAT_APIUSER_INVALIDATE_WARNING_3' => 'Any in-flight remote operation (log download, configuration change) will fail and need to be retried',
    'OXSHEARTBEAT_APIUSER_CONFIRM_INVALIDATE' => 'I want to terminate all active sessions of the API user now',
    'OXSHEARTBEAT_APIUSER_INVALIDATE_BUTTON' => 'Terminate Sessions Now',

    // ==========================================================================
    // Request Logger Remote Component (simplified - API User setup moved out)
    // ==========================================================================
    'OXSHEARTBEAT_REMOTE_STATUS_WARNING' => 'Setup Required',
    'OXSHEARTBEAT_REMOTE_WARNING_TITLE' => 'API User Required',
    'OXSHEARTBEAT_REMOTE_WARNING_TEXT' => 'This component requires a configured API User. Please set up the API User first.',
    'OXSHEARTBEAT_REMOTE_GOTO_APIUSER' => 'Go to API User Setup',
    'OXSHEARTBEAT_REMOTE_CONFIG_ACCESS_REQUIRED_TITLE' => 'GraphQL Configuration Access Required',
    'OXSHEARTBEAT_REMOTE_CONFIG_ACCESS_REQUIRED_TEXT' => 'This component requires the GraphQL Configuration Access module. Please activate it:',
    'OXSHEARTBEAT_REMOTE_READY_TITLE' => 'Remote Access Activated',
    'OXSHEARTBEAT_REMOTE_READY_TEXT' => 'OXID Support can now access the Request Logger settings.',

    // Request Logger - API User Info Notice
    'OXSHEARTBEAT_REQUESTLOGGER_WARNING_TITLE' => 'API User Not Configured',
    'OXSHEARTBEAT_REQUESTLOGGER_WARNING_TEXT' => 'Without an API User, no connection to the Heartbeat dashboard is possible. Local logging works independently.',
    'OXSHEARTBEAT_REQUESTLOGGER_GOTO_APIUSER' => 'Go to API User Setup',

    // ==========================================================================
    // Log Sender Component
    // ==========================================================================
    'OXSHEARTBEAT_LOGSENDER_TITLE' => 'Log Sender',
    'OXSHEARTBEAT_LOGSENDER_DESC' => 'Collects log files and provides them to the Heartbeat Monitor.',
    'OXSHEARTBEAT_LOGSENDER_STATUS_WARNING' => 'Setup Required',
    'OXSHEARTBEAT_LOGSENDER_WARNING_TITLE' => 'API User Required',
    'OXSHEARTBEAT_LOGSENDER_WARNING_TEXT' => 'This component requires a configured API User. Please set up the API User first.',
    'OXSHEARTBEAT_LOGSENDER_GOTO_APIUSER' => 'Go to API User Setup',
    'OXSHEARTBEAT_LOGSENDER_READY_TITLE' => 'Log Sender Activated',
    'OXSHEARTBEAT_LOGSENDER_READY_TEXT' => 'The Heartbeat Monitor can now access the log sources.',
    'OXSHEARTBEAT_LOGSENDER_SOURCES_TITLE' => 'Recognized Log Sources',
    'OXSHEARTBEAT_LOGSENDER_NO_SOURCES' => 'No log sources configured. Register providers via DI tags or configure static paths.',
    'OXSHEARTBEAT_LOGSENDER_HOWTO_TITLE' => 'Adding Log Sources',
    'OXSHEARTBEAT_LOGSENDER_HOWTO_TEXT' => 'There are two ways to register log sources:',
    'OXSHEARTBEAT_LOGSENDER_HOWTO_PROVIDER' => 'DI Tag Provider',
    'OXSHEARTBEAT_LOGSENDER_HOWTO_PROVIDER_DESC' => 'Services implement LogPathProviderInterface and are registered with the tag "oxs.logsender.provider".',
    'OXSHEARTBEAT_LOGSENDER_HOWTO_STATIC' => 'Static Paths',
    'OXSHEARTBEAT_LOGSENDER_HOWTO_STATIC_DESC' => 'Paths are configured directly in the module settings (for third-party logs). These paths are installation-wide and cover all subshops, unlike the per-shop request log.',

    // Log Sender - Static Paths Configuration
    'OXSHEARTBEAT_LOGSENDER_STATIC_TITLE' => 'Static Log Paths',
    'OXSHEARTBEAT_LOGSENDER_STATIC_DESC' => 'Configure additional log files or directories to be monitored here.',
    'OXSHEARTBEAT_LOGSENDER_STATIC_PATHS_LABEL' => 'Log Paths (one path per line)',
    'OXSHEARTBEAT_LOGSENDER_STATIC_PATHS_PLACEHOLDER' => '/var/log/myapp.log
/var/log/custom/',
    'OXSHEARTBEAT_LOGSENDER_STATIC_PATHS_HELP' => 'Enter absolute paths. Paths ending with "/" are treated as directories, all others as files.',
    'OXSHEARTBEAT_LOGSENDER_SAVE' => 'Save',

    // Log Sender - Path Validation
    'OXSHEARTBEAT_LOGSENDER_VALIDATION_TITLE' => 'Path Validation',
    'OXSHEARTBEAT_LOGSENDER_TYPE_FILE' => 'File',
    'OXSHEARTBEAT_LOGSENDER_TYPE_DIRECTORY' => 'Directory',
    'OXSHEARTBEAT_LOGSENDER_ERROR_NOT_FOUND' => 'Path does not exist',
    'OXSHEARTBEAT_LOGSENDER_ERROR_NOT_READABLE' => 'Path not readable (missing permissions)',
    'OXSHEARTBEAT_LOGSENDER_ERROR_TYPE_MISMATCH' => 'Type mismatch',
    'OXSHEARTBEAT_LOGSENDER_ERROR_CANNOT_LIST' => 'Cannot list directory contents',
    'OXSHEARTBEAT_LOGSENDER_EXPECTED' => 'Expected',
    'OXSHEARTBEAT_LOGSENDER_FOUND' => 'Found',
    'OXSHEARTBEAT_LOGSENDER_FILES_FOUND' => 'files found',
    'OXSHEARTBEAT_LOGSENDER_SIZE' => 'Size',
    'OXSHEARTBEAT_LOGSENDER_TOGGLE_SOURCE' => 'Enable/disable log source for sending',
    'OXSHEARTBEAT_LOGSENDER_REFRESH' => 'Refresh',
    'OXSHEARTBEAT_LOGSENDER_REFRESH_TITLE' => 'Reload log sources (clear cache)',
    'OXSHEARTBEAT_LOGSENDER_HOWTO_REFRESH' => 'Refresh Button',
    'OXSHEARTBEAT_LOGSENDER_HOWTO_REFRESH_DESC' => 'Reloads the list of log sources by clearing the DI container cache. Use this if new providers are not showing up.',

    // ==========================================================================
    // Diagnostics Provider Component
    // ==========================================================================
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_TITLE' => 'Diagnostics Provider',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_DESC' => 'Provides shop diagnostic information via GraphQL API.',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_STATUS_WARNING' => 'Setup Required',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_WARNING_TITLE' => 'API User Required',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_WARNING_TEXT' => 'This component requires a configured API User. Please set up the API User first.',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_GOTO_APIUSER' => 'Go to API User Setup',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_READY_TITLE' => 'Diagnostics Provider Activated',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_READY_TEXT' => 'The Heartbeat Monitor can now access diagnostic information.',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_INFO_TITLE' => 'Provided Information',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_INFO_TEXT' => 'This component provides the following diagnostic information via GraphQL API:',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_INFO_SHOPDETAILS' => 'Shop Details (URL, Edition, Version, Statistics)',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_INFO_MODULES' => 'Installed Modules',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_INFO_SYSTEM' => 'System Information',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_INFO_PHP' => 'PHP Configuration',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_INFO_SERVER' => 'Server Information',
];
