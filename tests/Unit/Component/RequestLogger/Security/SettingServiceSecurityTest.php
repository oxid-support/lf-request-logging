<?php

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\RequestLogger\Security;

use OxidEsales\GraphQL\ConfigurationAccess\Module\Service\ModuleSettingServiceInterface;
use OxidEsales\GraphQL\ConfigurationAccess\Shared\DataType\BooleanSetting;
use OxidEsales\GraphQL\ConfigurationAccess\Shared\DataType\StringSetting;
use OxidEsales\GraphQL\ConfigurationAccess\Shared\DataType\SettingType as ConfigAccessSettingType;
use OxidSupport\Heartbeat\Component\RequestLogger\Service\Remote\SettingService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Security tests for SettingService
 * Tests various attack vectors against the settings management
 */
#[CoversClass(SettingService::class)]
class SettingServiceSecurityTest extends TestCase
{
    private ModuleSettingServiceInterface $moduleSettingService;
    private SettingService $service;

    protected function setUp(): void
    {
        $this->moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);
        $this->service = new SettingService($this->moduleSettingService);
    }

    // Note: setRedactItems and its JSON validation were removed from the remote
    // SettingService on purpose. The redact field list is shop admin only and is
    // written exclusively via the admin settings form (SettingsController::save()).

    // ===========================================
    // LOG LEVEL INJECTION TESTS
    // ===========================================

    #[DataProvider('maliciousLogLevelProvider')]
    public function testMaliciousLogLevelInputs(string $maliciousLogLevel): void
    {
        // Log level is just saved as string, no execution
        // The actual validation happens in the log framework
        $this->moduleSettingService
            ->expects($this->once())
            ->method('changeStringSetting')
            ->willReturn(new StringSetting('test', $maliciousLogLevel));

        $result = $this->service->setLogLevel($maliciousLogLevel);
        $this->assertSame($maliciousLogLevel, $result);
    }

    public static function maliciousLogLevelProvider(): array
    {
        return [
            'sql_injection' => ["' OR '1'='1"],
            'xss_attempt' => ['<script>alert(1)</script>'],
            'command_injection' => ['debug; rm -rf /'],
            'path_traversal' => ['../../../etc/passwd'],
            'null_byte' => ["debug\x00malicious"],
            'very_long_string' => [str_repeat('a', 10000)],
        ];
    }

    // ===========================================
    // BOOLEAN INJECTION TESTS
    // ===========================================

    public function testBooleanSettingsOnlyAcceptBooleans(): void
    {
        // PHP type system enforces boolean type
        // These tests verify the interface
        $this->moduleSettingService
            ->expects($this->once())
            ->method('changeBooleanSetting')
            ->willReturn(new BooleanSetting('test', true));

        $result = $this->service->setLogFrontendEnabled(true);
        $this->assertTrue($result);
    }

    // ===========================================
    // DATA EXPOSURE TESTS
    // ===========================================

    public function testGetRedactItemsReturnsValidJson(): void
    {
        $jsonValue = '["password","credit_card","ssn"]';

        $this->moduleSettingService
            ->method('getCollectionSetting')
            ->willReturn(new StringSetting('test', $jsonValue));

        $result = $this->service->getRedactItems();

        // Verify output is valid JSON
        $this->assertJson($result);

        // Verify no PHP serialization is used (security risk)
        $this->assertStringNotContainsString('O:', $result); // No object serialization
        $this->assertStringNotContainsString('a:', $result); // No array serialization (PHP format)
    }

    public function testGetAllSettingsDoesNotExposeValues(): void
    {
        $configAccessSettings = [
            new ConfigAccessSettingType('setting1', 'bool'),
            new ConfigAccessSettingType('setting2', 'string'),
        ];

        $this->moduleSettingService
            ->method('getSettingsList')
            ->willReturn($configAccessSettings);

        // getAllSettings should only return setting names and types, not values
        $settings = $this->service->getAllSettings();

        foreach ($settings as $setting) {
            // Verify no actual values are exposed
            $this->assertObjectNotHasProperty('value', $setting);
        }
    }
}
