<?php

declare(strict_types=1);

$aLang = [
    'charset' => 'UTF-8',

    // Heartbeat Navigation
    'mxheartbeat' => 'OXS :: Heartbeat',
    'mxheartbeat_apiuser' => 'API User',
    'tbclheartbeat_apiuser_setup' => 'Einrichtung',
    'mxheartbeat_requestlogger' => 'Request Logger',
    'tbclheartbeat_requestlogger_settings' => 'Einstellungen',
    'mxheartbeat_logsender' => 'Log Sender',
    'tbclheartbeat_logsender_manage' => 'Verwalten',
    'mxheartbeat_diagnosticsprovider' => 'Diagnostics Provider',
    'tbclheartbeat_diagnosticsprovider_manage' => 'Verwalten',

    // Heartbeat Component Status
    'OXSHEARTBEAT_LF_STATUS_ACTIVE' => 'Aktiv',
    'OXSHEARTBEAT_LF_STATUS_INACTIVE' => 'Inaktiv',
    'OXSHEARTBEAT_LF_COMPONENT_ACTIVATION' => 'Komponente aktivieren',
    'OXSHEARTBEAT_LF_COMPONENT_ACTIVATION_DESC' => 'Schalten Sie diese Komponente ein oder aus.',

    // Heartbeat Request Logger
    'OXSHEARTBEAT_LF_REQUESTLOGGER_TITLE' => 'Request Logger',
    'OXSHEARTBEAT_LF_REQUESTLOGGER_DESC' => 'Konfiguriert die Protokollierung von Shop-Requests zur Fehleranalyse.',

    // Heartbeat Request Logger Settings
    'OXSHEARTBEAT_LF_SETTINGS_ACTIVATION' => 'Aktivierung',
    'OXSHEARTBEAT_LF_SETTINGS_LOGGING' => 'Protokollierung',
    'OXSHEARTBEAT_LF_SETTINGS_REDACTION' => 'Anonymisierung',
    'OXSHEARTBEAT_LF_SETTINGS_LOG_FRONTEND' => 'Frontend protokollieren',
    'OXSHEARTBEAT_LF_SETTINGS_LOG_FRONTEND_HELP' => 'Aktiviert die Protokollierung von Frontend-Anfragen.',
    'OXSHEARTBEAT_LF_SETTINGS_LOG_ADMIN' => 'Admin protokollieren',
    'OXSHEARTBEAT_LF_SETTINGS_LOG_ADMIN_HELP' => 'Aktiviert die Protokollierung von Admin-Anfragen.',
    'OXSHEARTBEAT_LF_SETTINGS_DETAILED_LOGGING' => 'Detailliertes Logging',
    'OXSHEARTBEAT_LF_SETTINGS_DETAILED_LOGGING_HELP' => 'Aktiviert erweiterte Protokollierung mit mehr Details.',
    'OXSHEARTBEAT_LF_SETTINGS_REDACT_ALL' => 'Alle Werte anonymisieren',
    'OXSHEARTBEAT_LF_SETTINGS_REDACT_ALL_HELP' => 'Zensiert alle Parameterwerte im Log.',
    'OXSHEARTBEAT_LF_SETTINGS_REDACT_FIELDS' => 'Felder anonymisieren',
    'OXSHEARTBEAT_LF_SETTINGS_REDACT_FIELDS_HELP' => 'Liste der Feldnamen (einer pro Zeile), deren Werte zensiert werden sollen.',
    'OXSHEARTBEAT_LF_SETTINGS_SAVE' => 'Speichern',

    // Heartbeat Remote
    'OXSHEARTBEAT_LF_REMOTE_TITLE' => 'Request Logger Remote',
    'OXSHEARTBEAT_LF_REMOTE_DESC' => 'Ermöglicht dem OXID Support, den Request Logger aus der Ferne zu konfigurieren.',

    // ==========================================================================
    // API User Component
    // ==========================================================================
    'OXSHEARTBEAT_APIUSER_TITLE' => 'API User',
    'OXSHEARTBEAT_APIUSER_DESC' => 'Verwaltet den API-Benutzer für den Fernzugriff auf Heartbeat.',
    'OXSHEARTBEAT_APIUSER_STATUS_READY' => 'Aktiv',
    'OXSHEARTBEAT_APIUSER_STATUS_SETUP_REQUIRED' => 'Einrichtung erforderlich',
    'OXSHEARTBEAT_APIUSER_INFO_TITLE' => 'Wichtig',
    'OXSHEARTBEAT_APIUSER_INFO_TEXT' => 'Der API User ist erforderlich für alle Komponenten, die Fernzugriff benötigen (z.B. Request Logger Remote). Richten Sie diesen zuerst ein.',

    // API User Setup Workflow
    'OXSHEARTBEAT_APIUSER_SETUP_TITLE' => 'Einrichtungs-Workflow',
    'OXSHEARTBEAT_APIUSER_STEP_INSTALL' => 'Modul installiert',
    'OXSHEARTBEAT_APIUSER_STEP_GRAPHQL_BASE' => 'GraphQL Base Modul aktiviert',
    'OXSHEARTBEAT_APIUSER_STEP_GRAPHQL_BASE_DESC' => 'Aktivieren mit: ./vendor/bin/oe-console oe:module:activate oe_graphql_base',
    'OXSHEARTBEAT_APIUSER_STEP_CONFIG_ACCESS' => 'GraphQL Configuration Access Modul aktiviert',
    'OXSHEARTBEAT_APIUSER_STEP_CONFIG_ACCESS_DESC' => 'Aktivieren mit: ./vendor/bin/oe-console oe:module:activate oe_graphql_configuration_access',
    'OXSHEARTBEAT_APIUSER_STEP_ACTIVATE' => 'Heartbeat Modul aktiviert',
    'OXSHEARTBEAT_APIUSER_STEP_SEND_TOKEN' => 'Setup-Token an OXID Support senden',
    'OXSHEARTBEAT_APIUSER_STEP_SEND_TOKEN_DESC' => 'Kopieren Sie den Token unten und senden Sie ihn per E-Mail an support@oxid-esales.com',
    'OXSHEARTBEAT_APIUSER_STEP_WAIT_SUPPORT' => 'Warten auf OXID Support zur Aktivierung des API-Zugangs',
    'OXSHEARTBEAT_APIUSER_PREREQUISITES_WARNING' => 'Wichtig: Ohne das GraphQL Base Modul kann der Support den Token nicht verwenden!',
    'OXSHEARTBEAT_APIUSER_COPIED' => 'Kopiert!',
    'OXSHEARTBEAT_APIUSER_SETUP_COMPLETE_TITLE' => 'API User eingerichtet',
    'OXSHEARTBEAT_APIUSER_SETUP_COMPLETE_TEXT' => 'Der API User wurde erfolgreich konfiguriert. Komponenten wie Request Logger Remote können nun aktiviert werden.',

    // API User Reset
    'OXSHEARTBEAT_APIUSER_RESET_TITLE' => 'API-Zugang zurücksetzen',
    'OXSHEARTBEAT_APIUSER_RESET_DESCRIPTION' => 'Diese Aktion setzt das Passwort des API-Benutzers zurück und generiert einen neuen Setup-Token. Verwenden Sie dies nur wenn der Fernzugriff neu eingerichten werden muss.',
    'OXSHEARTBEAT_APIUSER_WARNING_1' => 'Das aktuelle API-Passwort wird ungültig',
    'OXSHEARTBEAT_APIUSER_WARNING_2' => 'Alle bestehenden Remote-Sitzungen werden sofort beendet',
    'OXSHEARTBEAT_APIUSER_WARNING_3' => 'OXID Support verliert den Zugriff bis ein neuer Token zur Verfügung gestellt und ein neues Passwort gesetzt wird',
    'OXSHEARTBEAT_APIUSER_WARNING_4' => 'Sie müssen den neuen Token an OXID Support senden, um den Zugriff wiederherzustellen',
    'OXSHEARTBEAT_APIUSER_CONFIRM_RESET' => 'Ich verstehe die Konsequenzen und möchte das Passwort zurücksetzen',
    'OXSHEARTBEAT_APIUSER_CONFIRM_DIALOG' => 'Sind Sie absolut sicher? Dies widerruft sofort allen Fernzugriff!',
    'OXSHEARTBEAT_APIUSER_RESET_BUTTON' => 'Passwort zurücksetzen & neuen Token generieren',

    // API User Token Invalidation (OXS-3058)
    'OXSHEARTBEAT_APIUSER_INVALIDATE_TITLE' => 'API Sessions sofort beenden',
    'OXSHEARTBEAT_APIUSER_INVALIDATE_DESCRIPTION' => 'Beendet alle aktiven Sessions (JWTs) des API-Users sofort. Das Service-Passwort und der Setup-Token bleiben erhalten. OXID Support kann sich danach sofort neu einloggen.',
    'OXSHEARTBEAT_APIUSER_INVALIDATE_WARNING_1' => 'Alle laufenden Remote-Sessions vom OXID Support werden sofort beendet',
    'OXSHEARTBEAT_APIUSER_INVALIDATE_WARNING_2' => 'OXID Support muss sich neu einloggen, bevor weiter remote gearbeitet werden kann',
    'OXSHEARTBEAT_APIUSER_INVALIDATE_WARNING_3' => 'Laufende Aktionen (Log-Download, Konfigurationsänderung) brechen ab und müssen wiederholt werden',
    'OXSHEARTBEAT_APIUSER_CONFIRM_INVALIDATE' => 'Ich möchte alle aktiven Sessions des API-Users jetzt beenden',
    'OXSHEARTBEAT_APIUSER_INVALIDATE_BUTTON' => 'Sessions jetzt beenden',

    // ==========================================================================
    // Request Logger Remote Component (simplified - API User setup moved out)
    // ==========================================================================
    'OXSHEARTBEAT_REMOTE_STATUS_WARNING' => 'Einrichtung erforderlich',
    'OXSHEARTBEAT_REMOTE_WARNING_TITLE' => 'API User erforderlich',
    'OXSHEARTBEAT_REMOTE_WARNING_TEXT' => 'Diese Komponente benötigt einen eingerichteten API User. Bitte richten Sie zuerst den API User ein.',
    'OXSHEARTBEAT_REMOTE_GOTO_APIUSER' => 'Zur API User Einrichtung',
    'OXSHEARTBEAT_REMOTE_CONFIG_ACCESS_REQUIRED_TITLE' => 'GraphQL Configuration Access erforderlich',
    'OXSHEARTBEAT_REMOTE_CONFIG_ACCESS_REQUIRED_TEXT' => 'Diese Komponente benötigt das GraphQL Configuration Access Modul. Bitte aktivieren Sie es:',
    'OXSHEARTBEAT_REMOTE_READY_TITLE' => 'Remote-Zugriff aktiviert',
    'OXSHEARTBEAT_REMOTE_READY_TEXT' => 'Der OXID Support kann nun auf die Request Logger Einstellungen zugreifen.',

    // Request Logger - API User Info Notice
    'OXSHEARTBEAT_REQUESTLOGGER_WARNING_TITLE' => 'API User nicht eingerichtet',
    'OXSHEARTBEAT_REQUESTLOGGER_WARNING_TEXT' => 'Ohne API User ist keine Verbindung zum Heartbeat-Dashboard möglich. Das lokale Logging funktioniert unabhängig.',
    'OXSHEARTBEAT_REQUESTLOGGER_GOTO_APIUSER' => 'Zur API User Einrichtung',

    // ==========================================================================
    // Log Sender Component
    // ==========================================================================
    'OXSHEARTBEAT_LOGSENDER_TITLE' => 'Log Sender',
    'OXSHEARTBEAT_LOGSENDER_DESC' => 'Sammelt Log-Dateien und stellt sie dem Heartbeat Monitor zur Verfügung.',
    'OXSHEARTBEAT_LOGSENDER_STATUS_WARNING' => 'Einrichtung erforderlich',
    'OXSHEARTBEAT_LOGSENDER_WARNING_TITLE' => 'API User erforderlich',
    'OXSHEARTBEAT_LOGSENDER_WARNING_TEXT' => 'Diese Komponente benötigt einen eingerichteten API User. Bitte richten Sie zuerst den API User ein.',
    'OXSHEARTBEAT_LOGSENDER_GOTO_APIUSER' => 'Zur API User Einrichtung',
    'OXSHEARTBEAT_LOGSENDER_READY_TITLE' => 'Log Sender aktiviert',
    'OXSHEARTBEAT_LOGSENDER_READY_TEXT' => 'Der Heartbeat Monitor kann nun auf die Log-Quellen zugreifen.',
    'OXSHEARTBEAT_LOGSENDER_SOURCES_TITLE' => 'Erkannte Log-Quellen',
    'OXSHEARTBEAT_LOGSENDER_NO_SOURCES' => 'Keine Log-Quellen konfiguriert. Registrieren Sie Provider über DI-Tags oder konfigurieren Sie statische Pfade.',
    'OXSHEARTBEAT_LOGSENDER_HOWTO_TITLE' => 'Log-Quellen hinzufügen',
    'OXSHEARTBEAT_LOGSENDER_HOWTO_TEXT' => 'Es gibt zwei Wege, Log-Quellen zu registrieren:',
    'OXSHEARTBEAT_LOGSENDER_HOWTO_PROVIDER' => 'DI Tag Provider',
    'OXSHEARTBEAT_LOGSENDER_HOWTO_PROVIDER_DESC' => 'Services implementieren LogPathProviderInterface und werden mit dem Tag "oxs.logsender.provider" registriert.',
    'OXSHEARTBEAT_LOGSENDER_HOWTO_STATIC' => 'Statische Pfade',
    'OXSHEARTBEAT_LOGSENDER_HOWTO_STATIC_DESC' => 'Pfade werden direkt in den Modul-Settings konfiguriert (für Drittanbieter-Logs). Diese Pfade gelten installationsweit und decken alle Subshops ab, anders als das per-Shop Request-Log.',

    // Log Sender - Static Paths Configuration
    'OXSHEARTBEAT_LOGSENDER_STATIC_TITLE' => 'Statische Log-Pfade',
    'OXSHEARTBEAT_LOGSENDER_STATIC_DESC' => 'Konfigurieren Sie hier zusätzliche Log-Dateien oder -Verzeichnisse, die überwacht werden sollen.',
    'OXSHEARTBEAT_LOGSENDER_STATIC_PATHS_LABEL' => 'Log-Pfade (ein Pfad pro Zeile)',
    'OXSHEARTBEAT_LOGSENDER_STATIC_PATHS_PLACEHOLDER' => '/var/log/myapp.log
/var/log/custom/',
    'OXSHEARTBEAT_LOGSENDER_STATIC_PATHS_HELP' => 'Geben Sie absolute Pfade an. Pfade mit "/" am Ende werden als Verzeichnis behandelt, alle anderen als Datei.',
    'OXSHEARTBEAT_LOGSENDER_SAVE' => 'Speichern',

    // Log Sender - Path Validation
    'OXSHEARTBEAT_LOGSENDER_VALIDATION_TITLE' => 'Pfad-Validierung',
    'OXSHEARTBEAT_LOGSENDER_TYPE_FILE' => 'Datei',
    'OXSHEARTBEAT_LOGSENDER_TYPE_DIRECTORY' => 'Verzeichnis',
    'OXSHEARTBEAT_LOGSENDER_ERROR_NOT_FOUND' => 'Pfad existiert nicht',
    'OXSHEARTBEAT_LOGSENDER_ERROR_NOT_READABLE' => 'Pfad nicht lesbar (fehlende Berechtigungen)',
    'OXSHEARTBEAT_LOGSENDER_ERROR_TYPE_MISMATCH' => 'Typ-Konflikt',
    'OXSHEARTBEAT_LOGSENDER_ERROR_CANNOT_LIST' => 'Verzeichnis kann nicht aufgelistet werden',
    'OXSHEARTBEAT_LOGSENDER_EXPECTED' => 'Erwartet',
    'OXSHEARTBEAT_LOGSENDER_FOUND' => 'Gefunden',
    'OXSHEARTBEAT_LOGSENDER_FILES_FOUND' => 'Dateien gefunden',
    'OXSHEARTBEAT_LOGSENDER_SIZE' => 'Größe',
    'OXSHEARTBEAT_LOGSENDER_TOGGLE_SOURCE' => 'Log-Quelle zum Senden aktivieren/deaktivieren',
    'OXSHEARTBEAT_LOGSENDER_REFRESH' => 'Aktualisieren',
    'OXSHEARTBEAT_LOGSENDER_REFRESH_TITLE' => 'Log-Quellen neu laden (Cache leeren)',
    'OXSHEARTBEAT_LOGSENDER_HOWTO_REFRESH' => 'Aktualisieren-Button',
    'OXSHEARTBEAT_LOGSENDER_HOWTO_REFRESH_DESC' => 'Lädt die Liste der Log-Quellen neu, indem der DI-Container-Cache geleert wird. Nutzen Sie dies, wenn neue Provider nicht angezeigt werden.',

    // ==========================================================================
    // Diagnostics Provider Component
    // ==========================================================================
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_TITLE' => 'Diagnostics Provider',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_DESC' => 'Stellt Shop-Diagnoseinformationen über die GraphQL-API bereit.',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_STATUS_WARNING' => 'Einrichtung erforderlich',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_WARNING_TITLE' => 'API User erforderlich',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_WARNING_TEXT' => 'Diese Komponente benötigt einen eingerichteten API User. Bitte richten Sie zuerst den API User ein.',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_GOTO_APIUSER' => 'Zur API User Einrichtung',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_READY_TITLE' => 'Diagnostics Provider aktiviert',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_READY_TEXT' => 'Der Heartbeat Monitor kann nun auf die Diagnoseinformationen zugreifen.',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_INFO_TITLE' => 'Bereitgestellte Informationen',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_INFO_TEXT' => 'Diese Komponente stellt folgende Diagnoseinformationen über die GraphQL-API bereit:',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_INFO_SHOPDETAILS' => 'Shop-Details (URL, Edition, Version, Statistiken)',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_INFO_MODULES' => 'Installierte Module',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_INFO_SYSTEM' => 'System-Informationen',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_INFO_PHP' => 'PHP-Konfiguration',
    'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_INFO_SERVER' => 'Server-Informationen',
];
