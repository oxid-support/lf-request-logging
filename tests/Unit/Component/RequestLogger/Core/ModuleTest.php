<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\RequestLogger\Core;

use OxidSupport\Heartbeat\Module\Module;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Module::class)]
final class ModuleTest extends TestCase
{
    public function testModuleIdConstantIsCorrect(): void
    {
        $this->assertEquals('oxsheartbeat', Module::ID);
    }

    public function testModuleIdConstantIsString(): void
    {
        $this->assertIsString(Module::ID);
    }

    public function testModuleIdConstantIsNotEmpty(): void
    {
        $this->assertNotEmpty(Module::ID);
    }

    public function testSettingApiUserSetupTokenConstantIsCorrect(): void
    {
        $this->assertEquals('oxsheartbeat_apiuser_setup_token', Module::SETTING_APIUSER_SETUP_TOKEN);
    }

    public function testSettingApiUserSetupTokenConstantIsString(): void
    {
        $this->assertIsString(Module::SETTING_APIUSER_SETUP_TOKEN);
    }

    public function testSettingApiUserSetupTokenConstantIsNotEmpty(): void
    {
        $this->assertNotEmpty(Module::SETTING_APIUSER_SETUP_TOKEN);
    }

    public function testSettingApiUserSetupTokenConstantStartsWithModuleId(): void
    {
        $this->assertStringStartsWith(Module::ID, Module::SETTING_APIUSER_SETUP_TOKEN);
    }

    public function testApiUserEmailConstantIsCorrect(): void
    {
        $this->assertEquals('heartbeat-api@oxid-esales.com', Module::API_USER_EMAIL);
    }

    public function testApiUserEmailConstantIsString(): void
    {
        $this->assertIsString(Module::API_USER_EMAIL);
    }

    public function testApiUserEmailConstantIsNotEmpty(): void
    {
        $this->assertNotEmpty(Module::API_USER_EMAIL);
    }

    public function testApiUserEmailConstantIsValidEmailFormat(): void
    {
        $this->assertRegExp('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', Module::API_USER_EMAIL);
    }

    public function testApiUserEmailConstantContainsOxidDomain(): void
    {
        $this->assertStringContainsString('oxid-esales.com', Module::API_USER_EMAIL);
    }

    public function testModuleClassIsFinal(): void
    {
        $reflection = new \ReflectionClass(Module::class);
        $this->assertTrue($reflection->isFinal());
    }

    public function testModuleClassHasNoConstructor(): void
    {
        $reflection = new \ReflectionClass(Module::class);
        $constructor = $reflection->getConstructor();

        // Class should have no explicit constructor (constants only)
        $this->assertNull($constructor);
    }

    public function testModuleClassHasExpectedConstants(): void
    {
        $reflection = new \ReflectionClass(Module::class);
        $constants = $reflection->getConstants();

        // Module has ID + VERSION + API_VERSION + SUPPORTED_OPERATIONS
        // + 6 request logger settings + 1 API user setting
        // + 1 remote setting + 4 log sender settings
        // + 1 diagnostics provider setting + API_USER_EMAIL = 18 constants
        $this->assertCount(18, $constants);
    }

    public function testAllConstantsArePublic(): void
    {
        $reflection = new \ReflectionClass(Module::class);
        $constants = $reflection->getReflectionConstants();

        foreach ($constants as $constant) {
            $this->assertTrue($constant->isPublic());
        }
    }
}
