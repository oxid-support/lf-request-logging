<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\RequestLogger\Service\Remote;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;
use OxidSupport\Heartbeat\Component\RequestLogger\DataType\SettingType;
use OxidSupport\Heartbeat\Module\Module as RequestLoggerModule;

/**
 * OXID 6.5 implementation using ModuleSettingBridgeInterface directly
 * (graphql-configuration-access is not available for OXID 6.x).
 */
final class SettingService implements SettingServiceInterface
{
    private const SETTING_LOG_LEVEL = RequestLoggerModule::SETTING_REQUESTLOGGER_LOG_LEVEL;
    private const SETTING_LOG_FRONTEND = RequestLoggerModule::SETTING_REQUESTLOGGER_LOG_FRONTEND;
    private const SETTING_LOG_ADMIN = RequestLoggerModule::SETTING_REQUESTLOGGER_LOG_ADMIN;
    private const SETTING_REDACT = RequestLoggerModule::SETTING_REQUESTLOGGER_REDACT_FIELDS;
    private const SETTING_REDACT_ALL_VALUES = RequestLoggerModule::SETTING_REQUESTLOGGER_REDACT_ALL_VALUES;
    private const SETTING_ACTIVE = RequestLoggerModule::SETTING_REQUESTLOGGER_ACTIVE;

    /** @var array<string, string> Setting name => type mapping for getAllSettings() */
    private const SETTINGS_MAP = [
        self::SETTING_LOG_LEVEL => 'select',
        self::SETTING_LOG_FRONTEND => 'bool',
        self::SETTING_LOG_ADMIN => 'bool',
        self::SETTING_REDACT => 'aarr',
        self::SETTING_REDACT_ALL_VALUES => 'bool',
        self::SETTING_ACTIVE => 'bool',
    ];

    private ModuleSettingBridgeInterface $moduleSettingService;

    public function __construct(ModuleSettingBridgeInterface $moduleSettingService)
    {
        $this->moduleSettingService = $moduleSettingService;
    }

    public function getLogLevel(): string
    {
        return (string) $this->moduleSettingService->get(
            self::SETTING_LOG_LEVEL,
            RequestLoggerModule::ID
        );
    }

    public function setLogLevel(string $value): string
    {
        $this->moduleSettingService->save(
            self::SETTING_LOG_LEVEL,
            $value,
            RequestLoggerModule::ID
        );
        return $value;
    }

    public function isLogFrontendEnabled(): bool
    {
        return (bool) $this->moduleSettingService->get(
            self::SETTING_LOG_FRONTEND,
            RequestLoggerModule::ID
        );
    }

    public function setLogFrontendEnabled(bool $value): bool
    {
        $this->moduleSettingService->save(
            self::SETTING_LOG_FRONTEND,
            $value,
            RequestLoggerModule::ID
        );
        return $value;
    }

    public function isLogAdminEnabled(): bool
    {
        return (bool) $this->moduleSettingService->get(
            self::SETTING_LOG_ADMIN,
            RequestLoggerModule::ID
        );
    }

    public function setLogAdminEnabled(bool $value): bool
    {
        $this->moduleSettingService->save(
            self::SETTING_LOG_ADMIN,
            $value,
            RequestLoggerModule::ID
        );
        return $value;
    }

    public function getRedactItems(): string
    {
        $value = $this->moduleSettingService->get(
            self::SETTING_REDACT,
            RequestLoggerModule::ID
        );

        if (is_array($value)) {
            return json_encode($value) ?: '[]';
        }

        return is_string($value) ? $value : '[]';
    }

    public function isRedactAllValuesEnabled(): bool
    {
        return (bool) $this->moduleSettingService->get(
            self::SETTING_REDACT_ALL_VALUES,
            RequestLoggerModule::ID
        );
    }

    public function setRedactAllValuesEnabled(bool $value): bool
    {
        $this->moduleSettingService->save(
            self::SETTING_REDACT_ALL_VALUES,
            $value,
            RequestLoggerModule::ID
        );
        return $value;
    }

    /**
     * @return SettingType[]
     */
    public function getAllSettings(): array
    {
        $settings = [];
        foreach (self::SETTINGS_MAP as $name => $type) {
            $settings[] = new SettingType($name, $type);
        }
        return $settings;
    }
}
