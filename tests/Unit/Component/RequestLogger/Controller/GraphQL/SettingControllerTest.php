<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\RequestLogger\Controller\GraphQL;

use OxidSupport\Heartbeat\Component\RequestLogger\DataType\SettingType;
use OxidSupport\Heartbeat\Component\RequestLogger\Controller\GraphQL\SettingController;
use OxidSupport\Heartbeat\Component\RequestLogger\Service\Remote\RemoteComponentStatusServiceInterface;
use OxidSupport\Heartbeat\Component\RequestLogger\Service\Remote\SettingServiceInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SettingController::class)]
final class SettingControllerTest extends TestCase
{
    public function testRequestLoggerSettingsReturnsAllSettings(): void
    {
        $expectedSettings = [
            new SettingType('oxsheartbeat_log-level', 'select'),
            new SettingType('oxsheartbeat_log-frontend', 'bool'),
        ];

        $settingService = $this->createMock(SettingServiceInterface::class);
        $settingService
            ->expects($this->once())
            ->method('getAllSettings')
            ->willReturn($expectedSettings);

        $result = $this->getSut(settingService: $settingService)->requestLoggerSettings();

        $this->assertSame($expectedSettings, $result);
    }

    public function testRequestLoggerLogLevelReturnsString(): void
    {
        $settingService = $this->createMock(SettingServiceInterface::class);
        $settingService
            ->expects($this->once())
            ->method('getLogLevel')
            ->willReturn('standard');

        $result = $this->getSut(settingService: $settingService)->requestLoggerLogLevel();

        $this->assertSame('standard', $result);
    }

    public function testRequestLoggerLogFrontendReturnsBool(): void
    {
        $settingService = $this->createMock(SettingServiceInterface::class);
        $settingService
            ->expects($this->once())
            ->method('isLogFrontendEnabled')
            ->willReturn(true);

        $result = $this->getSut(settingService: $settingService)->requestLoggerLogFrontend();

        $this->assertTrue($result);
    }

    public function testRequestLoggerLogAdminReturnsBool(): void
    {
        $settingService = $this->createMock(SettingServiceInterface::class);
        $settingService
            ->expects($this->once())
            ->method('isLogAdminEnabled')
            ->willReturn(false);

        $result = $this->getSut(settingService: $settingService)->requestLoggerLogAdmin();

        $this->assertFalse($result);
    }

    public function testRequestLoggerRedactReturnsString(): void
    {
        $settingService = $this->createMock(SettingServiceInterface::class);
        $settingService
            ->expects($this->once())
            ->method('getRedactItems')
            ->willReturn('["password","secret"]');

        $result = $this->getSut(settingService: $settingService)->requestLoggerRedact();

        $this->assertSame('["password","secret"]', $result);
    }

    public function testRequestLoggerRedactAllValuesReturnsBool(): void
    {
        $settingService = $this->createMock(SettingServiceInterface::class);
        $settingService
            ->expects($this->once())
            ->method('isRedactAllValuesEnabled')
            ->willReturn(false);

        $result = $this->getSut(settingService: $settingService)->requestLoggerRedactAllValues();

        $this->assertFalse($result);
    }

    public function testRequestLoggerLogLevelChangeCallsServiceAndReturnsResult(): void
    {
        $settingService = $this->createMock(SettingServiceInterface::class);
        $settingService
            ->expects($this->once())
            ->method('setLogLevel')
            ->with('detailed')
            ->willReturn('detailed');

        $result = $this->getSut(settingService: $settingService)->requestLoggerLogLevelChange('detailed');

        $this->assertSame('detailed', $result);
    }

    public function testRequestLoggerLogFrontendChangeCallsServiceAndReturnsResult(): void
    {
        $settingService = $this->createMock(SettingServiceInterface::class);
        $settingService
            ->expects($this->once())
            ->method('setLogFrontendEnabled')
            ->with(true)
            ->willReturn(true);

        $result = $this->getSut(settingService: $settingService)->requestLoggerLogFrontendChange(true);

        $this->assertTrue($result);
    }

    public function testRequestLoggerLogAdminChangeCallsServiceAndReturnsResult(): void
    {
        $settingService = $this->createMock(SettingServiceInterface::class);
        $settingService
            ->expects($this->once())
            ->method('setLogAdminEnabled')
            ->with(false)
            ->willReturn(false);

        $result = $this->getSut(settingService: $settingService)->requestLoggerLogAdminChange(false);

        $this->assertFalse($result);
    }

    public function testRequestLoggerRedactAllValuesChangeCallsServiceAndReturnsResult(): void
    {
        $settingService = $this->createMock(SettingServiceInterface::class);
        $settingService
            ->expects($this->once())
            ->method('setRedactAllValuesEnabled')
            ->with(true)
            ->willReturn(true);

        $result = $this->getSut(settingService: $settingService)->requestLoggerRedactAllValuesChange(true);

        $this->assertTrue($result);
    }

    private function getSut(
        ?SettingServiceInterface $settingService = null,
        ?RemoteComponentStatusServiceInterface $componentStatusService = null,
    ): SettingController {
        return new SettingController(
            settingService: $settingService ?? $this->createStub(SettingServiceInterface::class),
            componentStatusService: $componentStatusService
                ?? $this->createStub(RemoteComponentStatusServiceInterface::class),
        );
    }
}
