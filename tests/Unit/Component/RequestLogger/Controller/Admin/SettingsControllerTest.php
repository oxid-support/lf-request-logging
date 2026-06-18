<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\RequestLogger\Controller\Admin;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;
use OxidSupport\Heartbeat\Component\RequestLogger\Controller\Admin\SettingsController;
use OxidSupport\Heartbeat\Module\Module;
use OxidSupport\Heartbeat\Shared\Controller\Admin\ComponentControllerInterface;
use OxidSupport\Heartbeat\Shared\Controller\Admin\TogglableComponentInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SettingsController::class)]
final class SettingsControllerTest extends TestCase
{
    private const SETTING_COMPONENT_ACTIVE = Module::SETTING_REQUESTLOGGER_ACTIVE;

    public function testTemplateIsCorrectlySet(): void
    {
        $reflection = new \ReflectionClass(SettingsController::class);
        $property = $reflection->getProperty('_sThisTemplate');

        $this->assertSame(
            'heartbeat_requestlogger_settings.tpl',
            $property->getDefaultValue()
        );
    }

    /**
     * @dataProvider componentActiveDataProvider
     */
    #[DataProvider('componentActiveDataProvider')]
    public function testIsComponentActiveReturnsCorrectValue(bool $expectedValue): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingBridgeInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('get')
            ->with(self::SETTING_COMPONENT_ACTIVE, Module::ID)
            ->willReturn($expectedValue);

        $controller = $this->createControllerWithMockedService($moduleSettingService);

        $this->assertSame($expectedValue, $controller->isComponentActive());
    }

    public static function componentActiveDataProvider(): array
    {
        return [
            'component is active' => [true],
            'component is inactive' => [false],
        ];
    }

    public function testToggleComponentFromActiveToInactive(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingBridgeInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('get')
            ->with(self::SETTING_COMPONENT_ACTIVE, Module::ID)
            ->willReturn(true);

        $moduleSettingService
            ->expects($this->once())
            ->method('save')
            ->with(self::SETTING_COMPONENT_ACTIVE, false, Module::ID);

        $controller = $this->createControllerWithMockedService($moduleSettingService);
        $controller->toggleComponent();
    }

    public function testToggleComponentFromInactiveToActive(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingBridgeInterface::class);
        $moduleSettingService
            ->expects($this->once())
            ->method('get')
            ->with(self::SETTING_COMPONENT_ACTIVE, Module::ID)
            ->willReturn(false);

        $moduleSettingService
            ->expects($this->once())
            ->method('save')
            ->with(self::SETTING_COMPONENT_ACTIVE, true, Module::ID);

        $controller = $this->createControllerWithMockedService($moduleSettingService);
        $controller->toggleComponent();
    }

    public function testGetSettingsReturnsCorrectArray(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingBridgeInterface::class);

        $moduleSettingService
            ->method('get')
            ->willReturnMap([
                [self::SETTING_COMPONENT_ACTIVE, Module::ID, true],
                [Module::SETTING_REQUESTLOGGER_LOG_FRONTEND, Module::ID, true],
                [Module::SETTING_REQUESTLOGGER_LOG_ADMIN, Module::ID, false],
                [Module::SETTING_REQUESTLOGGER_REDACT_ALL_VALUES, Module::ID, false],
                [Module::SETTING_REQUESTLOGGER_LOG_LEVEL, Module::ID, 'detailed'],
                [Module::SETTING_REQUESTLOGGER_REDACT_FIELDS, Module::ID, ['password', 'secret']],
            ]);

        $controller = $this->createControllerWithMockedService($moduleSettingService);
        $settings = $controller->getSettings();

        $this->assertTrue($settings['componentActive']);
        $this->assertSame('detailed', $settings['logLevel']);
        $this->assertTrue($settings['logFrontend']);
        $this->assertFalse($settings['logAdmin']);
        $this->assertFalse($settings['redactAllValues']);
        $this->assertSame(['password', 'secret'], $settings['redactFields']);
    }

    public function testCanToggleAlwaysReturnsTrue(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingBridgeInterface::class);
        $controller = $this->createControllerWithMockedService($moduleSettingService);

        $this->assertTrue($controller->canToggle());
    }

    public function testImplementsTogglableComponentInterface(): void
    {
        $this->assertTrue(
            is_subclass_of(SettingsController::class, TogglableComponentInterface::class)
        );
    }

    public function testImplementsComponentControllerInterface(): void
    {
        $this->assertTrue(
            is_subclass_of(SettingsController::class, ComponentControllerInterface::class)
        );
    }

    /**
     * @dataProvider statusClassDataProvider
     */
    #[DataProvider('statusClassDataProvider')]
    public function testGetStatusClassReturnsCorrectValue(bool $isActive, string $expectedClass): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingBridgeInterface::class);
        $moduleSettingService
            ->method('get')
            ->with(self::SETTING_COMPONENT_ACTIVE, Module::ID)
            ->willReturn($isActive);

        $controller = $this->createControllerWithMockedService($moduleSettingService);

        $this->assertSame($expectedClass, $controller->getStatusClass());
    }

    public static function statusClassDataProvider(): array
    {
        return [
            'active returns active class' => [true, 'active'],
            'inactive returns inactive class' => [false, 'inactive'],
        ];
    }

    /**
     * @dataProvider statusTextKeyDataProvider
     */
    #[DataProvider('statusTextKeyDataProvider')]
    public function testGetStatusTextKeyReturnsCorrectValue(bool $isActive, string $expectedKey): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingBridgeInterface::class);
        $moduleSettingService
            ->method('get')
            ->with(self::SETTING_COMPONENT_ACTIVE, Module::ID)
            ->willReturn($isActive);

        $controller = $this->createControllerWithMockedService($moduleSettingService);

        $this->assertSame($expectedKey, $controller->getStatusTextKey());
    }

    public static function statusTextKeyDataProvider(): array
    {
        return [
            'active returns active key' => [true, 'OXSHEARTBEAT_LF_STATUS_ACTIVE'],
            'inactive returns inactive key' => [false, 'OXSHEARTBEAT_LF_STATUS_INACTIVE'],
        ];
    }

    private function createControllerWithMockedService(
        ModuleSettingBridgeInterface $moduleSettingService
    ): SettingsController {
        $controller = $this->getMockBuilder(SettingsController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getModuleSettingService'])
            ->getMock();

        $controller
            ->method('getModuleSettingService')
            ->willReturn($moduleSettingService);

        return $controller;
    }
}
