<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\RequestLogger\Service\Remote;

use OxidEsales\GraphQL\ConfigurationAccess\Module\Service\ModuleSettingServiceInterface as ConfigAccessSettingService;
use OxidSupport\Heartbeat\Component\RequestLogger\DataType\SettingType;
use OxidSupport\Heartbeat\Module\Module as RequestLoggerModule;

final class SettingService implements SettingServiceInterface
{
    private const SETTING_LOG_LEVEL = RequestLoggerModule::SETTING_REQUESTLOGGER_LOG_LEVEL;
    private const SETTING_LOG_FRONTEND = RequestLoggerModule::SETTING_REQUESTLOGGER_LOG_FRONTEND;
    private const SETTING_LOG_ADMIN = RequestLoggerModule::SETTING_REQUESTLOGGER_LOG_ADMIN;
    private const SETTING_REDACT = RequestLoggerModule::SETTING_REQUESTLOGGER_REDACT_FIELDS;
    private const SETTING_REDACT_ALL_VALUES = RequestLoggerModule::SETTING_REQUESTLOGGER_REDACT_ALL_VALUES;

    public function __construct(
        private readonly ConfigAccessSettingService $moduleSettingService
    ) {
    }

    public function getLogLevel(): string
    {
        return $this->moduleSettingService
            ->getStringSetting(self::SETTING_LOG_LEVEL, RequestLoggerModule::ID)
            ->getValue();
    }

    public function setLogLevel(string $value): string
    {
        return $this->moduleSettingService
            ->changeStringSetting(self::SETTING_LOG_LEVEL, $value, RequestLoggerModule::ID)
            ->getValue();
    }

    public function isLogFrontendEnabled(): bool
    {
        return $this->moduleSettingService
            ->getBooleanSetting(self::SETTING_LOG_FRONTEND, RequestLoggerModule::ID)
            ->getValue();
    }

    public function setLogFrontendEnabled(bool $value): bool
    {
        return $this->moduleSettingService
            ->changeBooleanSetting(self::SETTING_LOG_FRONTEND, $value, RequestLoggerModule::ID)
            ->getValue();
    }

    public function isLogAdminEnabled(): bool
    {
        return $this->moduleSettingService
            ->getBooleanSetting(self::SETTING_LOG_ADMIN, RequestLoggerModule::ID)
            ->getValue();
    }

    public function setLogAdminEnabled(bool $value): bool
    {
        return $this->moduleSettingService
            ->changeBooleanSetting(self::SETTING_LOG_ADMIN, $value, RequestLoggerModule::ID)
            ->getValue();
    }

    public function getRedactItems(): string
    {
        return $this->moduleSettingService
            ->getCollectionSetting(self::SETTING_REDACT, RequestLoggerModule::ID)
            ->getValue();
    }

    public function isRedactAllValuesEnabled(): bool
    {
        return $this->moduleSettingService
            ->getBooleanSetting(self::SETTING_REDACT_ALL_VALUES, RequestLoggerModule::ID)
            ->getValue();
    }

    public function setRedactAllValuesEnabled(bool $value): bool
    {
        return $this->moduleSettingService
            ->changeBooleanSetting(self::SETTING_REDACT_ALL_VALUES, $value, RequestLoggerModule::ID)
            ->getValue();
    }

    /**
     * @return SettingType[]
     */
    public function getAllSettings(): array
    {
        $configAccessSettings = $this->moduleSettingService->getSettingsList(RequestLoggerModule::ID);

        $settings = [];
        foreach ($configAccessSettings as $setting) {
            $settings[] = new SettingType($setting->getName(), $setting->getType());
        }

        return $settings;
    }
}
