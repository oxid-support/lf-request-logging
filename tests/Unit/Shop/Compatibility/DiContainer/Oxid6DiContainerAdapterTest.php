<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Shop\Compatibility\DiContainer;

use OxidSupport\RequestLogger\Shop\Compatibility\DiContainer\DiContainerPort;
use OxidSupport\RequestLogger\Shop\Compatibility\DiContainer\Oxid6DiContainerAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

#[CoversClass(Oxid6DiContainerAdapter::class)]
class Oxid6DiContainerAdapterTest extends TestCase
{
    public function testImplementsDiContainerPort(): void
    {
        $this->assertTrue(
            is_subclass_of(Oxid6DiContainerAdapter::class, DiContainerPort::class)
        );
    }

    public function testClassIsNotAbstract(): void
    {
        $reflection = new ReflectionClass(Oxid6DiContainerAdapter::class);
        $this->assertFalse($reflection->isAbstract());
    }

    public function testClassIsNotFinal(): void
    {
        $reflection = new ReflectionClass(Oxid6DiContainerAdapter::class);
        $this->assertFalse($reflection->isFinal());
    }

    public function testHasNoConstructor(): void
    {
        $reflection = new ReflectionClass(Oxid6DiContainerAdapter::class);
        $constructor = $reflection->getConstructor();
        $this->assertNull($constructor);
    }

    public function testHasGetMethod(): void
    {
        $this->assertTrue(method_exists(Oxid6DiContainerAdapter::class, 'get'));
    }

    public function testGetMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Oxid6DiContainerAdapter::class, 'get');
        $this->assertTrue($method->isPublic());
    }

    public function testGetMethodIsNotStatic(): void
    {
        $method = new ReflectionMethod(Oxid6DiContainerAdapter::class, 'get');
        $this->assertFalse($method->isStatic());
    }

    public function testGetMethodAcceptsStringParameter(): void
    {
        $method = new ReflectionMethod(Oxid6DiContainerAdapter::class, 'get');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('interface', $params[0]->getName());
        $this->assertSame('string', $params[0]->getType()->getName());
    }

    public function testGetMethodHasNoReturnTypeHint(): void
    {
        $method = new ReflectionMethod(Oxid6DiContainerAdapter::class, 'get');
        $returnType = $method->getReturnType();
        // The method returns mixed (no explicit type hint)
        $this->assertNull($returnType);
    }

    public function testClassHasCorrectNamespace(): void
    {
        $reflection = new ReflectionClass(Oxid6DiContainerAdapter::class);
        $this->assertSame(
            'OxidSupport\RequestLogger\Shop\Compatibility\DiContainer',
            $reflection->getNamespaceName()
        );
    }

    public function testCanBeInstantiated(): void
    {
        $adapter = new Oxid6DiContainerAdapter();
        $this->assertInstanceOf(Oxid6DiContainerAdapter::class, $adapter);
    }

    public function testGetMethodDelegatesToContainerFactory(): void
    {
        // This test requires full OXID shop context (oxNew function, etc.)
        // which is not available in unit test environment.
        // The integration is tested through the Factory tests.
        $this->markTestSkipped('Requires full OXID shop context - integration test');
    }
}
