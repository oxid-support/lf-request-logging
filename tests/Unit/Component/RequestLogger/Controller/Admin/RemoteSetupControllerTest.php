<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\RequestLogger\Controller\Admin;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserStatusServiceInterface;
use OxidSupport\Heartbeat\Component\RequestLogger\Controller\Admin\RemoteSetupController;
use OxidSupport\Heartbeat\Module\Module;
use OxidSupport\Heartbeat\Shared\Controller\Admin\ComponentControllerInterface;
use OxidSupport\Heartbeat\Shared\Controller\Admin\TogglableComponentInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RemoteSetupController::class)]
final class RemoteSetupControllerTest extends TestCase
{
    private const SETTING_COMPONENT_ACTIVE = Module::SETTING_REQUESTLOGGER_ACTIVE;

    public function testTemplateIsCorrectlySet(): void
    {
        $reflection = new \ReflectionClass(RemoteSetupController::class);
        $property = $reflection->getProperty('_sThisTemplate');

        $this->assertSame(
            'heartbeat_requestlogger_setup.tpl',
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

        $controller = $this->createControllerWithMocks(moduleSettingService: $moduleSettingService);

        $this->assertSame($expectedValue, $controller->isComponentActive());
    }

    public static function componentActiveDataProvider(): array
    {
        return [
            'component is active' => [true],
            'component is inactive' => [false],
        ];
    }

    public function testToggleComponentFromActiveToInactiveWhenCanToggle(): void
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

        $apiUserStatusService = $this->createMock(ApiUserStatusServiceInterface::class);
        $apiUserStatusService
            ->method('isSetupComplete')
            ->willReturn(true);

        $controller = $this->createControllerWithMocks(
            moduleSettingService: $moduleSettingService,
            apiUserStatusService: $apiUserStatusService
        );
        $controller->toggleComponent();
    }

    public function testToggleComponentFromInactiveToActiveWhenCanToggle(): void
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

        $apiUserStatusService = $this->createMock(ApiUserStatusServiceInterface::class);
        $apiUserStatusService
            ->method('isSetupComplete')
            ->willReturn(true);

        $controller = $this->createControllerWithMocks(
            moduleSettingService: $moduleSettingService,
            apiUserStatusService: $apiUserStatusService
        );
        $controller->toggleComponent();
    }

    public function testToggleComponentDoesNothingWhenCannotToggle(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingBridgeInterface::class);
        $moduleSettingService
            ->expects($this->never())
            ->method('save');

        $apiUserStatusService = $this->createMock(ApiUserStatusServiceInterface::class);
        $apiUserStatusService
            ->method('isSetupComplete')
            ->willReturn(false);

        $controller = $this->createControllerWithMocks(
            moduleSettingService: $moduleSettingService,
            apiUserStatusService: $apiUserStatusService
        );
        $controller->toggleComponent();
    }

    public function testIsApiUserSetupCompleteReturnsTrueWhenComplete(): void
    {
        $apiUserStatusService = $this->createMock(ApiUserStatusServiceInterface::class);
        $apiUserStatusService
            ->expects($this->once())
            ->method('isSetupComplete')
            ->willReturn(true);

        $controller = $this->createControllerWithMocks(apiUserStatusService: $apiUserStatusService);

        $this->assertTrue($controller->isApiUserSetupComplete());
    }

    public function testIsApiUserSetupCompleteReturnsFalseWhenNotComplete(): void
    {
        $apiUserStatusService = $this->createMock(ApiUserStatusServiceInterface::class);
        $apiUserStatusService
            ->expects($this->once())
            ->method('isSetupComplete')
            ->willReturn(false);

        $controller = $this->createControllerWithMocks(apiUserStatusService: $apiUserStatusService);

        $this->assertFalse($controller->isApiUserSetupComplete());
    }

    public function testIsApiUserSetupCompleteReturnsFalseOnException(): void
    {
        $apiUserStatusService = $this->createMock(ApiUserStatusServiceInterface::class);
        $apiUserStatusService
            ->expects($this->once())
            ->method('isSetupComplete')
            ->willThrowException(new \Exception('Service error'));

        $controller = $this->createControllerWithMocks(apiUserStatusService: $apiUserStatusService);

        $this->assertFalse($controller->isApiUserSetupComplete());
    }

    public function testIsConfigAccessActivatedAlwaysReturnsTrue(): void
    {
        $controller = $this->createControllerWithMocks();

        $this->assertTrue($controller->isConfigAccessActivated());
    }

    public function testCanToggleReturnsTrueWhenApiUserSetupComplete(): void
    {
        $apiUserStatusService = $this->createMock(ApiUserStatusServiceInterface::class);
        $apiUserStatusService
            ->method('isSetupComplete')
            ->willReturn(true);

        $controller = $this->createControllerWithMocks(apiUserStatusService: $apiUserStatusService);

        $this->assertTrue($controller->canToggle());
    }

    public function testCanToggleReturnsFalseWhenApiUserNotSetUp(): void
    {
        $apiUserStatusService = $this->createMock(ApiUserStatusServiceInterface::class);
        $apiUserStatusService
            ->method('isSetupComplete')
            ->willReturn(false);

        $controller = $this->createControllerWithMocks(apiUserStatusService: $apiUserStatusService);

        $this->assertFalse($controller->canToggle());
    }

    public function testGetStatusClassReturnsWarningWhenApiUserNotSetUp(): void
    {
        $apiUserStatusService = $this->createMock(ApiUserStatusServiceInterface::class);
        $apiUserStatusService
            ->method('isSetupComplete')
            ->willReturn(false);

        $controller = $this->createControllerWithMocks(apiUserStatusService: $apiUserStatusService);

        $this->assertSame(ComponentControllerInterface::STATUS_CLASS_WARNING, $controller->getStatusClass());
    }

    public function testGetStatusClassReturnsActiveWhenApiUserSetUpAndComponentActive(): void
    {
        $apiUserStatusService = $this->createMock(ApiUserStatusServiceInterface::class);
        $apiUserStatusService
            ->method('isSetupComplete')
            ->willReturn(true);

        $moduleSettingService = $this->createMock(ModuleSettingBridgeInterface::class);
        $moduleSettingService
            ->method('get')
            ->with(self::SETTING_COMPONENT_ACTIVE, Module::ID)
            ->willReturn(true);

        $controller = $this->createControllerWithMocks(
            moduleSettingService: $moduleSettingService,
            apiUserStatusService: $apiUserStatusService
        );

        $this->assertSame(ComponentControllerInterface::STATUS_CLASS_ACTIVE, $controller->getStatusClass());
    }

    public function testGetStatusClassReturnsInactiveWhenApiUserSetUpAndComponentInactive(): void
    {
        $apiUserStatusService = $this->createMock(ApiUserStatusServiceInterface::class);
        $apiUserStatusService
            ->method('isSetupComplete')
            ->willReturn(true);

        $moduleSettingService = $this->createMock(ModuleSettingBridgeInterface::class);
        $moduleSettingService
            ->method('get')
            ->with(self::SETTING_COMPONENT_ACTIVE, Module::ID)
            ->willReturn(false);

        $controller = $this->createControllerWithMocks(
            moduleSettingService: $moduleSettingService,
            apiUserStatusService: $apiUserStatusService
        );

        $this->assertSame(ComponentControllerInterface::STATUS_CLASS_INACTIVE, $controller->getStatusClass());
    }

    public function testGetStatusTextKeyReturnsWarningKeyWhenApiUserNotSetUp(): void
    {
        $apiUserStatusService = $this->createMock(ApiUserStatusServiceInterface::class);
        $apiUserStatusService
            ->method('isSetupComplete')
            ->willReturn(false);

        $controller = $this->createControllerWithMocks(apiUserStatusService: $apiUserStatusService);

        $this->assertSame('OXSHEARTBEAT_REQUESTLOGGER_REMOTE_STATUS_WARNING', $controller->getStatusTextKey());
    }

    public function testGetStatusTextKeyReturnsActiveKeyWhenApiUserSetUpAndActive(): void
    {
        $apiUserStatusService = $this->createMock(ApiUserStatusServiceInterface::class);
        $apiUserStatusService
            ->method('isSetupComplete')
            ->willReturn(true);

        $moduleSettingService = $this->createMock(ModuleSettingBridgeInterface::class);
        $moduleSettingService
            ->method('get')
            ->with(self::SETTING_COMPONENT_ACTIVE, Module::ID)
            ->willReturn(true);

        $controller = $this->createControllerWithMocks(
            moduleSettingService: $moduleSettingService,
            apiUserStatusService: $apiUserStatusService
        );

        $this->assertSame('OXSHEARTBEAT_LF_STATUS_ACTIVE', $controller->getStatusTextKey());
    }

    public function testImplementsTogglableComponentInterface(): void
    {
        $this->assertTrue(
            is_subclass_of(RemoteSetupController::class, TogglableComponentInterface::class)
        );
    }

    public function testImplementsComponentControllerInterface(): void
    {
        $this->assertTrue(
            is_subclass_of(RemoteSetupController::class, ComponentControllerInterface::class)
        );
    }

    private function createControllerWithMocks(
        ?ModuleSettingBridgeInterface $moduleSettingService = null,
        ?ApiUserStatusServiceInterface $apiUserStatusService = null,
    ): RemoteSetupController {
        $controller = $this->getMockBuilder(RemoteSetupController::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getModuleSettingService',
                'getApiUserStatusService',
            ])
            ->getMock();

        $controller
            ->method('getModuleSettingService')
            ->willReturn($moduleSettingService ?? $this->createStub(ModuleSettingBridgeInterface::class));

        $controller
            ->method('getApiUserStatusService')
            ->willReturn($apiUserStatusService ?? $this->createStub(ApiUserStatusServiceInterface::class));

        return $controller;
    }
}
