<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\RequestLogger\Service\Remote;

use OxidEsales\GraphQL\ConfigurationAccess\Module\Service\ModuleSettingServiceInterface;
use OxidEsales\GraphQL\ConfigurationAccess\Shared\DataType\BooleanSetting;
use OxidEsales\GraphQL\ConfigurationAccess\Shared\DataType\StringSetting;
use OxidEsales\GraphQL\ConfigurationAccess\Shared\DataType\SettingType as ConfigAccessSettingType;
use OxidSupport\Heartbeat\Component\RequestLogger\DataType\SettingType;
use OxidSupport\Heartbeat\Module\Module as RequestLoggerModule;
use OxidSupport\Heartbeat\Component\RequestLogger\Service\Remote\SettingService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SettingService::class)]
final class SettingServiceTest extends TestCase
{
    private const SETTING_LOG_LEVEL = RequestLoggerModule::SETTING_REQUESTLOGGER_LOG_LEVEL;
    private const SETTING_LOG_FRONTEND = RequestLoggerModule::SETTING_REQUESTLOGGER_LOG_FRONTEND;
    private const SETTING_LOG_ADMIN = RequestLoggerModule::SETTING_REQUESTLOGGER_LOG_ADMIN;
    private const SETTING_REDACT = RequestLoggerModule::SETTING_REQUESTLOGGER_REDACT_FIELDS;
    private const SETTING_REDACT_ALL_VALUES = RequestLoggerModule::SETTING_REQUESTLOGGER_REDACT_ALL_VALUES;

    public function testGetLogLevelReturnsString(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('getStringSetting')
            ->with(self::SETTING_LOG_LEVEL, RequestLoggerModule::ID)
            ->willReturn(new StringSetting(self::SETTING_LOG_LEVEL, 'standard'));

        $result = $this->getSut(moduleSettingService: $moduleSettingService)->getLogLevel();

        $this->assertSame('standard', $result);
    }

    public function testSetLogLevelSavesAndReturnsNewValue(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('changeStringSetting')
            ->with(self::SETTING_LOG_LEVEL, 'detailed', RequestLoggerModule::ID)
            ->willReturn(new StringSetting(self::SETTING_LOG_LEVEL, 'detailed'));

        $result = $this->getSut(moduleSettingService: $moduleSettingService)->setLogLevel('detailed');

        $this->assertSame('detailed', $result);
    }

    public function testIsLogFrontendEnabledReturnsBool(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('getBooleanSetting')
            ->with(self::SETTING_LOG_FRONTEND, RequestLoggerModule::ID)
            ->willReturn(new BooleanSetting(self::SETTING_LOG_FRONTEND, true));

        $result = $this->getSut(moduleSettingService: $moduleSettingService)->isLogFrontendEnabled();

        $this->assertTrue($result);
    }

    public function testSetLogFrontendEnabledSavesAndReturnsNewValue(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('changeBooleanSetting')
            ->with(self::SETTING_LOG_FRONTEND, false, RequestLoggerModule::ID)
            ->willReturn(new BooleanSetting(self::SETTING_LOG_FRONTEND, false));

        $result = $this->getSut(moduleSettingService: $moduleSettingService)->setLogFrontendEnabled(false);

        $this->assertFalse($result);
    }

    public function testIsLogAdminEnabledReturnsBool(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('getBooleanSetting')
            ->with(self::SETTING_LOG_ADMIN, RequestLoggerModule::ID)
            ->willReturn(new BooleanSetting(self::SETTING_LOG_ADMIN, false));

        $result = $this->getSut(moduleSettingService: $moduleSettingService)->isLogAdminEnabled();

        $this->assertFalse($result);
    }

    public function testSetLogAdminEnabledSavesAndReturnsNewValue(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('changeBooleanSetting')
            ->with(self::SETTING_LOG_ADMIN, true, RequestLoggerModule::ID)
            ->willReturn(new BooleanSetting(self::SETTING_LOG_ADMIN, true));

        $result = $this->getSut(moduleSettingService: $moduleSettingService)->setLogAdminEnabled(true);

        $this->assertTrue($result);
    }

    public function testGetRedactItemsReturnsJsonEncodedString(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('getCollectionSetting')
            ->with(self::SETTING_REDACT, RequestLoggerModule::ID)
            ->willReturn(new StringSetting(self::SETTING_REDACT, '["password","secret","token"]'));

        $result = $this->getSut(moduleSettingService: $moduleSettingService)->getRedactItems();

        $this->assertSame('["password","secret","token"]', $result);
    }

    public function testIsRedactAllValuesEnabledReturnsBool(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('getBooleanSetting')
            ->with(self::SETTING_REDACT_ALL_VALUES, RequestLoggerModule::ID)
            ->willReturn(new BooleanSetting(self::SETTING_REDACT_ALL_VALUES, false));

        $result = $this->getSut(moduleSettingService: $moduleSettingService)->isRedactAllValuesEnabled();

        $this->assertFalse($result);
    }

    public function testSetRedactAllValuesEnabledSavesAndReturnsNewValue(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('changeBooleanSetting')
            ->with(self::SETTING_REDACT_ALL_VALUES, true, RequestLoggerModule::ID)
            ->willReturn(new BooleanSetting(self::SETTING_REDACT_ALL_VALUES, true));

        $result = $this->getSut(moduleSettingService: $moduleSettingService)->setRedactAllValuesEnabled(true);

        $this->assertTrue($result);
    }

    public function testGetAllSettingsReturnsAllSettingTypes(): void
    {
        $configAccessSettings = [
            new ConfigAccessSettingType(self::SETTING_LOG_LEVEL, 'select'),
            new ConfigAccessSettingType(self::SETTING_LOG_FRONTEND, 'bool'),
            new ConfigAccessSettingType(self::SETTING_LOG_ADMIN, 'bool'),
            new ConfigAccessSettingType(self::SETTING_REDACT, 'arr'),
            new ConfigAccessSettingType(self::SETTING_REDACT_ALL_VALUES, 'bool'),
        ];

        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('getSettingsList')
            ->with(RequestLoggerModule::ID)
            ->willReturn($configAccessSettings);

        $result = $this->getSut(moduleSettingService: $moduleSettingService)->getAllSettings();

        $this->assertCount(5, $result);
        $this->assertContainsOnlyInstancesOf(SettingType::class, $result);

        $names = array_map(fn (SettingType $s) => $s->getName(), $result);

        $this->assertContains(self::SETTING_LOG_LEVEL, $names);
        $this->assertContains(self::SETTING_LOG_FRONTEND, $names);
        $this->assertContains(self::SETTING_LOG_ADMIN, $names);
        $this->assertContains(self::SETTING_REDACT, $names);
        $this->assertContains(self::SETTING_REDACT_ALL_VALUES, $names);
    }

    public function testGetAllSettingsReturnsCorrectTypes(): void
    {
        $configAccessSettings = [
            new ConfigAccessSettingType(self::SETTING_LOG_LEVEL, 'select'),
            new ConfigAccessSettingType(self::SETTING_LOG_FRONTEND, 'bool'),
            new ConfigAccessSettingType(self::SETTING_LOG_ADMIN, 'bool'),
            new ConfigAccessSettingType(self::SETTING_REDACT, 'arr'),
            new ConfigAccessSettingType(self::SETTING_REDACT_ALL_VALUES, 'bool'),
        ];

        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('getSettingsList')
            ->with(RequestLoggerModule::ID)
            ->willReturn($configAccessSettings);

        $result = $this->getSut(moduleSettingService: $moduleSettingService)->getAllSettings();

        $settingsByName = [];
        foreach ($result as $setting) {
            $settingsByName[$setting->getName()] = $setting->getType();
        }

        $this->assertSame('select', $settingsByName[self::SETTING_LOG_LEVEL]);
        $this->assertSame('bool', $settingsByName[self::SETTING_LOG_FRONTEND]);
        $this->assertSame('bool', $settingsByName[self::SETTING_LOG_ADMIN]);
        $this->assertSame('arr', $settingsByName[self::SETTING_REDACT]);
        $this->assertSame('bool', $settingsByName[self::SETTING_REDACT_ALL_VALUES]);
    }

    private function getSut(
        ?ModuleSettingServiceInterface $moduleSettingService = null,
    ): SettingService {
        return new SettingService(
            moduleSettingService: $moduleSettingService ?? $this->createStub(ModuleSettingServiceInterface::class),
        );
    }
}
