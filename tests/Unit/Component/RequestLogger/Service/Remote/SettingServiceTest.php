<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\RequestLogger\Service\Remote;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;
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
    private const SETTING_ACTIVE = RequestLoggerModule::SETTING_REQUESTLOGGER_ACTIVE;

    public function testGetLogLevelReturnsString(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingBridgeInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('get')
            ->with(self::SETTING_LOG_LEVEL, RequestLoggerModule::ID)
            ->willReturn('standard');

        $result = $this->getSut(moduleSettingService: $moduleSettingService)->getLogLevel();

        $this->assertSame('standard', $result);
    }

    public function testSetLogLevelSavesAndReturnsNewValue(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingBridgeInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('save')
            ->with(self::SETTING_LOG_LEVEL, 'detailed', RequestLoggerModule::ID);

        $result = $this->getSut(moduleSettingService: $moduleSettingService)->setLogLevel('detailed');

        $this->assertSame('detailed', $result);
    }

    public function testIsLogFrontendEnabledReturnsBool(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingBridgeInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('get')
            ->with(self::SETTING_LOG_FRONTEND, RequestLoggerModule::ID)
            ->willReturn(true);

        $result = $this->getSut(moduleSettingService: $moduleSettingService)->isLogFrontendEnabled();

        $this->assertTrue($result);
    }

    public function testSetLogFrontendEnabledSavesAndReturnsNewValue(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingBridgeInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('save')
            ->with(self::SETTING_LOG_FRONTEND, false, RequestLoggerModule::ID);

        $result = $this->getSut(moduleSettingService: $moduleSettingService)->setLogFrontendEnabled(false);

        $this->assertFalse($result);
    }

    public function testIsLogAdminEnabledReturnsBool(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingBridgeInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('get')
            ->with(self::SETTING_LOG_ADMIN, RequestLoggerModule::ID)
            ->willReturn(false);

        $result = $this->getSut(moduleSettingService: $moduleSettingService)->isLogAdminEnabled();

        $this->assertFalse($result);
    }

    public function testSetLogAdminEnabledSavesAndReturnsNewValue(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingBridgeInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('save')
            ->with(self::SETTING_LOG_ADMIN, true, RequestLoggerModule::ID);

        $result = $this->getSut(moduleSettingService: $moduleSettingService)->setLogAdminEnabled(true);

        $this->assertTrue($result);
    }

    public function testGetRedactItemsReturnsJsonEncodedStringWhenArrayStored(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingBridgeInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('get')
            ->with(self::SETTING_REDACT, RequestLoggerModule::ID)
            ->willReturn(['password', 'secret', 'token']);

        $result = $this->getSut(moduleSettingService: $moduleSettingService)->getRedactItems();

        $this->assertSame('["password","secret","token"]', $result);
    }

    public function testGetRedactItemsReturnsStringWhenStringStored(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingBridgeInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('get')
            ->with(self::SETTING_REDACT, RequestLoggerModule::ID)
            ->willReturn('["password","secret"]');

        $result = $this->getSut(moduleSettingService: $moduleSettingService)->getRedactItems();

        $this->assertSame('["password","secret"]', $result);
    }

    public function testGetRedactItemsReturnsFallbackForNonStringNonArray(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingBridgeInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('get')
            ->with(self::SETTING_REDACT, RequestLoggerModule::ID)
            ->willReturn(42);

        $result = $this->getSut(moduleSettingService: $moduleSettingService)->getRedactItems();

        $this->assertSame('[]', $result);
    }

    public function testIsRedactAllValuesEnabledReturnsBool(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingBridgeInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('get')
            ->with(self::SETTING_REDACT_ALL_VALUES, RequestLoggerModule::ID)
            ->willReturn(false);

        $result = $this->getSut(moduleSettingService: $moduleSettingService)->isRedactAllValuesEnabled();

        $this->assertFalse($result);
    }

    public function testSetRedactAllValuesEnabledSavesAndReturnsNewValue(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingBridgeInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('save')
            ->with(self::SETTING_REDACT_ALL_VALUES, true, RequestLoggerModule::ID);

        $result = $this->getSut(moduleSettingService: $moduleSettingService)->setRedactAllValuesEnabled(true);

        $this->assertTrue($result);
    }

    public function testGetAllSettingsReturnsAllSettingTypes(): void
    {
        // getAllSettings() uses an internal SETTINGS_MAP, no mock interaction needed
        $result = $this->getSut()->getAllSettings();

        $this->assertCount(6, $result);
        $this->assertContainsOnlyInstancesOf(SettingType::class, $result);

        $names = array_map(fn (SettingType $s) => $s->getName(), $result);

        $this->assertContains(self::SETTING_LOG_LEVEL, $names);
        $this->assertContains(self::SETTING_LOG_FRONTEND, $names);
        $this->assertContains(self::SETTING_LOG_ADMIN, $names);
        $this->assertContains(self::SETTING_REDACT, $names);
        $this->assertContains(self::SETTING_REDACT_ALL_VALUES, $names);
        $this->assertContains(self::SETTING_ACTIVE, $names);
    }

    public function testGetAllSettingsReturnsCorrectTypes(): void
    {
        // getAllSettings() uses an internal SETTINGS_MAP, no mock interaction needed
        $result = $this->getSut()->getAllSettings();

        $settingsByName = [];
        foreach ($result as $setting) {
            $settingsByName[$setting->getName()] = $setting->getType();
        }

        $this->assertSame('select', $settingsByName[self::SETTING_LOG_LEVEL]);
        $this->assertSame('bool', $settingsByName[self::SETTING_LOG_FRONTEND]);
        $this->assertSame('bool', $settingsByName[self::SETTING_LOG_ADMIN]);
        $this->assertSame('aarr', $settingsByName[self::SETTING_REDACT]);
        $this->assertSame('bool', $settingsByName[self::SETTING_REDACT_ALL_VALUES]);
        $this->assertSame('bool', $settingsByName[self::SETTING_ACTIVE]);
    }

    private function getSut(
        ?ModuleSettingBridgeInterface $moduleSettingService = null,
    ): SettingService {
        return new SettingService(
            moduleSettingService: $moduleSettingService ?? $this->createStub(ModuleSettingBridgeInterface::class),
        );
    }
}
