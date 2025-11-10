<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Logger\Security;

use OxidSupport\RequestLogger\Logger\Security\SensitiveDataRedactor;
use OxidSupport\RequestLogger\Shop\Facade\ModuleSettingFacadeInterface;
use PHPUnit\Framework\TestCase;

class SensitiveDataRedactorTest extends TestCase
{
    private ModuleSettingFacadeInterface $moduleSettingFacade;
    private SensitiveDataRedactor $redactor;

    protected function setUp(): void
    {
        $this->moduleSettingFacade = $this->createMock(ModuleSettingFacadeInterface::class);
        $this->redactor = new SensitiveDataRedactor($this->moduleSettingFacade);
    }

    // Tests for "redact all values" mode (enabled)

    public function testRedactAllValuesMode_RedactsAllValues(): void
    {
        $this->moduleSettingFacade
            ->method('isRedactAllValuesEnabled')
            ->willReturn(true);

        $input = [
            'username' => 'john',
            'email' => 'john@example.com',
        ];

        $result = $this->redactor->redact($input);

        $this->assertSame('[redacted]', $result['username']);
        $this->assertSame('[redacted]', $result['email']);
    }

    public function testRedactAllValuesMode_KeepsKeys(): void
    {
        $this->moduleSettingFacade
            ->method('isRedactAllValuesEnabled')
            ->willReturn(true);

        $input = [
            'username' => 'john',
            'password' => 'secret123',
            'email' => 'john@example.com',
        ];

        $result = $this->redactor->redact($input);

        $this->assertArrayHasKey('username', $result);
        $this->assertArrayHasKey('password', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertSame('[redacted]', $result['username']);
        $this->assertSame('[redacted]', $result['password']);
        $this->assertSame('[redacted]', $result['email']);
    }

    public function testRedactAllValuesMode_RedactsArrayValues(): void
    {
        $this->moduleSettingFacade
            ->method('isRedactAllValuesEnabled')
            ->willReturn(true);

        $input = [
            'data' => ['key' => 'value', 'nested' => ['foo' => 'bar']],
        ];

        $result = $this->redactor->redact($input);

        $this->assertSame('[redacted]', $result['data']);
    }

    public function testRedactAllValuesMode_WithEmptyArray(): void
    {
        $this->moduleSettingFacade
            ->method('isRedactAllValuesEnabled')
            ->willReturn(true);

        $input = [];

        $result = $this->redactor->redact($input);

        $this->assertSame([], $result);
    }

    public function testRedactAllValuesMode_DoesNotRedactClParameter(): void
    {
        $this->moduleSettingFacade
            ->method('isRedactAllValuesEnabled')
            ->willReturn(true);

        $input = [
            'cl' => 'navigation',
            'username' => 'john',
            'password' => 'secret',
        ];

        $result = $this->redactor->redact($input);

        $this->assertSame('navigation', $result['cl']);
        $this->assertSame('[redacted]', $result['username']);
        $this->assertSame('[redacted]', $result['password']);
    }

    public function testRedactAllValuesMode_DoesNotRedactFncParameter(): void
    {
        $this->moduleSettingFacade
            ->method('isRedactAllValuesEnabled')
            ->willReturn(true);

        $input = [
            'fnc' => 'logout',
            'cl' => 'account',
            'token' => 'abc123',
        ];

        $result = $this->redactor->redact($input);

        $this->assertSame('logout', $result['fnc']);
        $this->assertSame('account', $result['cl']);
        $this->assertSame('[redacted]', $result['token']);
    }

    public function testRedactAllValuesMode_WithClAndFncAndOtherParams(): void
    {
        $this->moduleSettingFacade
            ->method('isRedactAllValuesEnabled')
            ->willReturn(true);

        $input = [
            'cl' => 'navigation',
            'fnc' => 'render',
            'sid' => 'session123',
            'stoken' => 'token456',
            'user_id' => '789',
        ];

        $result = $this->redactor->redact($input);

        // cl and fnc should not be redacted
        $this->assertSame('navigation', $result['cl']);
        $this->assertSame('render', $result['fnc']);

        // Other params should be redacted
        $this->assertSame('[redacted]', $result['sid']);
        $this->assertSame('[redacted]', $result['stoken']);
        $this->assertSame('[redacted]', $result['user_id']);
    }

    // Tests for "blocklist only" mode (disabled)

    public function testBlocklistMode_WithEmptyBlocklistReturnsUnchangedValues(): void
    {
        $this->moduleSettingFacade
            ->method('isRedactAllValuesEnabled')
            ->willReturn(false);
        $this->moduleSettingFacade
            ->method('getRedactItems')
            ->willReturn([]);

        $input = [
            'username' => 'john',
            'email' => 'john@example.com',
        ];

        $result = $this->redactor->redact($input);

        $this->assertSame($input, $result);
    }

    public function testBlocklistMode_RedactsOnlyBlocklistedKeys(): void
    {
        $this->moduleSettingFacade
            ->method('isRedactAllValuesEnabled')
            ->willReturn(false);
        $this->moduleSettingFacade
            ->method('getRedactItems')
            ->willReturn(['password', 'token']);

        $input = [
            'username' => 'john',
            'password' => 'secret123',
            'email' => 'john@example.com',
        ];

        $result = $this->redactor->redact($input);

        $this->assertSame('john', $result['username']);
        $this->assertSame('[redacted]', $result['password']);
        $this->assertSame('john@example.com', $result['email']);
    }

    public function testBlocklistMode_IsCaseInsensitiveForKeys(): void
    {
        $this->moduleSettingFacade
            ->method('isRedactAllValuesEnabled')
            ->willReturn(false);
        $this->moduleSettingFacade
            ->method('getRedactItems')
            ->willReturn(['PASSWORD']);

        $input = [
            'password' => 'secret123',
            'Password' => 'another',
        ];

        $result = $this->redactor->redact($input);

        $this->assertSame('[redacted]', $result['password']);
        $this->assertSame('[redacted]', $result['Password']);
    }

    public function testBlocklistMode_ConvertsArrayToJson(): void
    {
        $this->moduleSettingFacade
            ->method('isRedactAllValuesEnabled')
            ->willReturn(false);
        $this->moduleSettingFacade
            ->method('getRedactItems')
            ->willReturn([]);

        $input = [
            'data' => ['key' => 'value', 'nested' => ['foo' => 'bar']],
        ];

        $result = $this->redactor->redact($input);

        $this->assertIsString($result['data']);
        $this->assertJson($result['data']);
        $decoded = json_decode($result['data'], true);
        $this->assertSame(['key' => 'value', 'nested' => ['foo' => 'bar']], $decoded);
    }

    public function testBlocklistMode_ConvertsObjectToJson(): void
    {
        $this->moduleSettingFacade
            ->method('isRedactAllValuesEnabled')
            ->willReturn(false);
        $this->moduleSettingFacade
            ->method('getRedactItems')
            ->willReturn([]);

        $obj = new \stdClass();
        $obj->name = 'test';
        $obj->value = 42;

        $input = ['object' => $obj];

        $result = $this->redactor->redact($input);

        $this->assertIsString($result['object']);
        $this->assertJson($result['object']);
        $decoded = json_decode($result['object'], true);
        $this->assertSame(['name' => 'test', 'value' => 42], $decoded);
    }

    public function testBlocklistMode_PreservesScalarTypes(): void
    {
        $this->moduleSettingFacade
            ->method('isRedactAllValuesEnabled')
            ->willReturn(false);
        $this->moduleSettingFacade
            ->method('getRedactItems')
            ->willReturn([]);

        $input = [
            'string' => 'text',
            'int' => 42,
            'float' => 3.14,
            'bool' => true,
            'null' => null,
        ];

        $result = $this->redactor->redact($input);

        $this->assertSame('text', $result['string']);
        $this->assertSame(42, $result['int']);
        $this->assertSame(3.14, $result['float']);
        $this->assertSame(true, $result['bool']);
        $this->assertNull($result['null']);
    }

    public function testBlocklistMode_WithMultipleBlocklistedKeys(): void
    {
        $this->moduleSettingFacade
            ->method('isRedactAllValuesEnabled')
            ->willReturn(false);
        $this->moduleSettingFacade
            ->method('getRedactItems')
            ->willReturn(['password', 'token', 'api_key', 'secret']);

        $input = [
            'username' => 'john',
            'password' => 'secret123',
            'token' => 'abc123',
            'email' => 'john@example.com',
            'api_key' => 'key123',
        ];

        $result = $this->redactor->redact($input);

        $this->assertSame('john', $result['username']);
        $this->assertSame('[redacted]', $result['password']);
        $this->assertSame('[redacted]', $result['token']);
        $this->assertSame('john@example.com', $result['email']);
        $this->assertSame('[redacted]', $result['api_key']);
    }

    public function testBlocklistMode_WithNumericKeys(): void
    {
        $this->moduleSettingFacade
            ->method('isRedactAllValuesEnabled')
            ->willReturn(false);
        $this->moduleSettingFacade
            ->method('getRedactItems')
            ->willReturn(['0']);

        $input = [
            0 => 'value0',
            1 => 'value1',
        ];

        $result = $this->redactor->redact($input);

        $this->assertSame('[redacted]', $result['0']);
        $this->assertSame('value1', $result['1']);
    }

    public function testBlocklistMode_PreservesJsonEncoding(): void
    {
        $this->moduleSettingFacade
            ->method('isRedactAllValuesEnabled')
            ->willReturn(false);
        $this->moduleSettingFacade
            ->method('getRedactItems')
            ->willReturn([]);

        $input = [
            'unicode' => ['text' => 'Hello 世界'],
            'slashes' => ['url' => 'https://example.com/path'],
        ];

        $result = $this->redactor->redact($input);

        $this->assertStringContainsString('世界', $result['unicode']);
        $this->assertStringContainsString('https://example.com/path', $result['slashes']);
    }
}
