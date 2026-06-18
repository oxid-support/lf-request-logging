<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\RequestLogger\Controller\GraphQL;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidSupport\Heartbeat\Component\RequestLogger\Controller\GraphQL\SettingController;
use OxidSupport\Heartbeat\Component\RequestLogger\Exception\RemoteComponentDisabledException;
use OxidSupport\Heartbeat\Component\RequestLogger\Service\Remote\RemoteComponentStatusService;
use OxidSupport\Heartbeat\Component\RequestLogger\Service\Remote\RemoteComponentStatusServiceInterface;
use OxidSupport\Heartbeat\Component\RequestLogger\Service\Remote\SettingServiceInterface;
use OxidSupport\Heartbeat\Module\Module;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests that GraphQL controllers block requests when remote component is disabled.
 *
 * When requestlogger_active = false, all GraphQL endpoints should throw RemoteComponentDisabledException.
 * This allows customers to disable external support access while keeping admin configuration accessible.
 */
#[CoversClass(SettingController::class)]
final class RemoteComponentBlockingTest extends TestCase
{
    // ==========================================
    // SettingController Tests
    // ==========================================

    /**
     * @dataProvider settingControllerQueryMethodsProvider
     */
    #[DataProvider('settingControllerQueryMethodsProvider')]
    public function testSettingControllerQueryBlocksWhenDisabled(string $method): void
    {
        $controller = $this->createSettingControllerWithDisabledComponent();

        $this->expectException(RemoteComponentDisabledException::class);
        $controller->$method();
    }

    public static function settingControllerQueryMethodsProvider(): array
    {
        return [
            'requestLoggerSettings' => ['requestLoggerSettings'],
            'requestLoggerLogLevel' => ['requestLoggerLogLevel'],
            'requestLoggerLogFrontend' => ['requestLoggerLogFrontend'],
            'requestLoggerLogAdmin' => ['requestLoggerLogAdmin'],
            'requestLoggerRedact' => ['requestLoggerRedact'],
            'requestLoggerRedactAllValues' => ['requestLoggerRedactAllValues'],
        ];
    }

    public function testSettingControllerLogLevelChangeBlocksWhenDisabled(): void
    {
        $controller = $this->createSettingControllerWithDisabledComponent();

        $this->expectException(RemoteComponentDisabledException::class);
        $controller->requestLoggerLogLevelChange('detailed');
    }

    public function testSettingControllerLogFrontendChangeBlocksWhenDisabled(): void
    {
        $controller = $this->createSettingControllerWithDisabledComponent();

        $this->expectException(RemoteComponentDisabledException::class);
        $controller->requestLoggerLogFrontendChange(true);
    }

    public function testSettingControllerLogAdminChangeBlocksWhenDisabled(): void
    {
        $controller = $this->createSettingControllerWithDisabledComponent();

        $this->expectException(RemoteComponentDisabledException::class);
        $controller->requestLoggerLogAdminChange(true);
    }

    public function testSettingControllerRedactChangeBlocksWhenDisabled(): void
    {
        $controller = $this->createSettingControllerWithDisabledComponent();

        $this->expectException(RemoteComponentDisabledException::class);
        $controller->requestLoggerRedactChange('["password"]');
    }

    public function testSettingControllerRedactAllValuesChangeBlocksWhenDisabled(): void
    {
        $controller = $this->createSettingControllerWithDisabledComponent();

        $this->expectException(RemoteComponentDisabledException::class);
        $controller->requestLoggerRedactAllValuesChange(true);
    }

    // ==========================================
    // Positive Tests - Component Enabled
    // ==========================================

    public function testSettingControllerAllowsQueriesWhenEnabled(): void
    {
        $settingService = $this->createMock(SettingServiceInterface::class);
        $settingService->method('getLogLevel')->willReturn('standard');

        $controller = new SettingController(
            $settingService,
            $this->createEnabledComponentStatusService()
        );

        // Should not throw
        $result = $controller->requestLoggerLogLevel();
        $this->assertSame('standard', $result);
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    private function createDisabledComponentStatusService(): RemoteComponentStatusServiceInterface
    {
        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);
        $moduleSettingService
            ->method('getBoolean')
            ->with(Module::SETTING_REQUESTLOGGER_ACTIVE, Module::ID)
            ->willReturn(false);

        return new RemoteComponentStatusService($moduleSettingService);
    }

    private function createEnabledComponentStatusService(): RemoteComponentStatusServiceInterface
    {
        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);
        $moduleSettingService
            ->method('getBoolean')
            ->with(Module::SETTING_REQUESTLOGGER_ACTIVE, Module::ID)
            ->willReturn(true);

        return new RemoteComponentStatusService($moduleSettingService);
    }

    private function createSettingControllerWithDisabledComponent(): SettingController
    {
        return new SettingController(
            $this->createStub(SettingServiceInterface::class),
            $this->createDisabledComponentStatusService()
        );
    }
}
