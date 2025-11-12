<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Shop\Facade;

use OxidSupport\RequestLogger\Shop\Compatibility\ModuleSettings\ModuleSettingsPort;
use OxidSupport\RequestLogger\Shop\Facade\ModuleSettingFacade;
use OxidSupport\RequestLogger\Shop\Facade\ModuleSettingFacadeInterface;
use PHPUnit\Framework\TestCase;

class ModuleSettingFacadeTest extends TestCase
{
    private ModuleSettingsPort $moduleSettingsPort;
    private ModuleSettingFacade $facade;

    protected function setUp(): void
    {
        $this->moduleSettingsPort = $this->createMock(ModuleSettingsPort::class);
        $this->facade = new ModuleSettingFacade($this->moduleSettingsPort);
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(ModuleSettingFacadeInterface::class, $this->facade);
    }

    public function testGetLogLevelCallsModuleSettingsPortWithCorrectParameters(): void
    {
        $this->moduleSettingsPort
            ->expects($this->once())
            ->method('getString')
            ->with('oxsrequestlogger_log-level', 'oxsrequestlogger')
            ->willReturn('debug');

        $result = $this->facade->getLogLevel();

        $this->assertSame('debug', $result);
    }

    public function testGetLogLevelReturnsString(): void
    {
        $this->moduleSettingsPort
            ->expects($this->once())
            ->method('getString')
            ->willReturn('info');

        $result = $this->facade->getLogLevel();

        $this->assertIsString($result);
    }

    public function testGetLogLevelWithDifferentLevels(): void
    {
        $this->moduleSettingsPort
            ->expects($this->once())
            ->method('getString')
            ->willReturn('warning');

        $result = $this->facade->getLogLevel();

        $this->assertSame('warning', $result);
    }

    public function testGetRedactItemsCallsModuleSettingsPortWithCorrectParameters(): void
    {
        $this->moduleSettingsPort
            ->expects($this->once())
            ->method('getCollection')
            ->with('oxsrequestlogger_redact', 'oxsrequestlogger')
            ->willReturn(['password', 'token']);

        $result = $this->facade->getRedactItems();

        $this->assertSame(['password', 'token'], $result);
    }

    public function testGetRedactItemsReturnsArray(): void
    {
        $this->moduleSettingsPort
            ->expects($this->once())
            ->method('getCollection')
            ->willReturn(['api_key', 'secret']);

        $result = $this->facade->getRedactItems();

        $this->assertIsArray($result);
    }

    public function testGetRedactItemsWithEmptyArray(): void
    {
        $this->moduleSettingsPort
            ->expects($this->once())
            ->method('getCollection')
            ->willReturn([]);

        $result = $this->facade->getRedactItems();

        $this->assertSame([], $result);
    }

    public function testGetRedactItemsWithMultipleItems(): void
    {
        $items = ['password', 'token', 'api_key', 'secret', 'auth_token'];

        $this->moduleSettingsPort
            ->expects($this->once())
            ->method('getCollection')
            ->willReturn($items);

        $result = $this->facade->getRedactItems();

        $this->assertCount(5, $result);
        $this->assertSame($items, $result);
    }

    public function testMultipleCallsToGetLogLevel(): void
    {
        $this->moduleSettingsPort
            ->expects($this->exactly(3))
            ->method('getString')
            ->willReturnOnConsecutiveCalls('debug', 'info', 'error');

        $result1 = $this->facade->getLogLevel();
        $result2 = $this->facade->getLogLevel();
        $result3 = $this->facade->getLogLevel();

        $this->assertSame('debug', $result1);
        $this->assertSame('info', $result2);
        $this->assertSame('error', $result3);
    }

    public function testMultipleCallsToGetRedactItems(): void
    {
        $this->moduleSettingsPort
            ->expects($this->exactly(2))
            ->method('getCollection')
            ->willReturnOnConsecutiveCalls(['password'], ['token', 'secret']);

        $result1 = $this->facade->getRedactItems();
        $result2 = $this->facade->getRedactItems();

        $this->assertSame(['password'], $result1);
        $this->assertSame(['token', 'secret'], $result2);
    }

    public function testGetLogLevelUsesModuleIdInSettingName(): void
    {
        $this->moduleSettingsPort
            ->expects($this->once())
            ->method('getString')
            ->with(
                $this->callback(function($arg) {
                    return strpos($arg, 'oxsrequestlogger_') === 0;
                }),
                'oxsrequestlogger'
            )
            ->willReturn('info');

        $this->facade->getLogLevel();
    }

    public function testGetRedactItemsUsesModuleIdInSettingName(): void
    {
        $this->moduleSettingsPort
            ->expects($this->once())
            ->method('getCollection')
            ->with(
                $this->callback(function($arg) {
                    return strpos($arg, 'oxsrequestlogger_') === 0;
                }),
                'oxsrequestlogger'
            )
            ->willReturn([]);

        $this->facade->getRedactItems();
    }
}
