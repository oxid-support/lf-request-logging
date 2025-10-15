<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Module;

use OxidSupport\RequestLogger\Module\Module;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{
    public function testModuleIdConstantExists(): void
    {
        $this->assertTrue(defined(Module::class . '::ID'));
    }

    public function testModuleIdIsString(): void
    {
        $this->assertIsString(Module::ID);
    }

    public function testModuleIdIsNotEmpty(): void
    {
        $this->assertNotEmpty(Module::ID);
    }

    public function testModuleIdHasExpectedValue(): void
    {
        $this->assertSame('oxsrequestlogger', Module::ID);
    }

    public function testModuleIdIsLowercase(): void
    {
        $this->assertSame(strtolower(Module::ID), Module::ID);
    }

    public function testModuleIdContainsNoSpaces(): void
    {
        $this->assertStringNotContainsString(' ', Module::ID);
    }

    public function testModuleIdIsAlphanumeric(): void
    {
        $this->assertMatchesRegularExpression('/^[a-z0-9]+$/', Module::ID);
    }

    public function testModuleClassIsFinal(): void
    {
        $reflection = new \ReflectionClass(Module::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function testModuleClassHasNoPublicMethods(): void
    {
        $reflection = new \ReflectionClass(Module::class);
        $publicMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        $this->assertCount(0, $publicMethods);
    }
}
