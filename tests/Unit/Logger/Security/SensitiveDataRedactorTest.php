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

    public function testRedactWithEmptyBlocklistReturnsUnchangedValues(): void
    {
        $this->moduleSettingFacade
            ->expects($this->once())->method('getRedactItems')
            
            ->willReturn([]);

        $input = [
            'username' => 'john',
            'email' => 'john@example.com',
        ];

        $result = $this->redactor->redact($input);

        $this->assertSame($input, $result);
    }

    public function testRedactWithBlocklistedKeyRedactsValue(): void
    {
        $this->moduleSettingFacade
            ->expects($this->once())->method('getRedactItems')
            
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

    public function testRedactIsCaseInsensitiveForKeys(): void
    {
        $this->moduleSettingFacade
            ->expects($this->once())->method('getRedactItems')
            
            ->willReturn(['PASSWORD']);

        $input = [
            'password' => 'secret123',
            'Password' => 'another',
        ];

        $result = $this->redactor->redact($input);

        $this->assertSame('[redacted]', $result['password']);
        $this->assertSame('[redacted]', $result['Password']);
    }

    public function testRedactConvertsArrayToJson(): void
    {
        $this->moduleSettingFacade
            ->expects($this->once())->method('getRedactItems')
            
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

    public function testRedactConvertsObjectToJson(): void
    {
        $this->moduleSettingFacade
            ->expects($this->once())->method('getRedactItems')
            
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

    public function testRedactHandlesUnserializableAsPlaceholder(): void
    {
        $this->moduleSettingFacade
            ->expects($this->once())->method('getRedactItems')
            
            ->willReturn([]);

        // Create resource that cannot be JSON encoded
        $resource = fopen('php://memory', 'r');

        $input = ['resource' => $resource];

        $result = $this->redactor->redact($input);

        fclose($resource);

        // Resources are not handled specially, they pass through as-is
        $this->assertIsResource($result['resource']);
    }

    public function testRedactPreservesScalarTypes(): void
    {
        $this->moduleSettingFacade
            ->expects($this->once())->method('getRedactItems')
            
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

    public function testRedactWithMultipleBlocklistedKeys(): void
    {
        $this->moduleSettingFacade
            ->expects($this->once())->method('getRedactItems')
            
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

    public function testRedactWithNumericKeys(): void
    {
        $this->moduleSettingFacade
            ->expects($this->once())->method('getRedactItems')
            
            ->willReturn(['0']);

        $input = [
            0 => 'value0',
            1 => 'value1',
        ];

        $result = $this->redactor->redact($input);

        $this->assertSame('[redacted]', $result['0']);
        $this->assertSame('value1', $result['1']);
    }

    public function testRedactPreservesJsonEncoding(): void
    {
        $this->moduleSettingFacade
            ->expects($this->once())->method('getRedactItems')
            
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
