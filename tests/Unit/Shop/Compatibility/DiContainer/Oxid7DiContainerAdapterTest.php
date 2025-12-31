<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Shop\Compatibility\DiContainer;

use OxidSupport\RequestLogger\Shop\Compatibility\DiContainer\DiContainerPort;
use OxidSupport\RequestLogger\Shop\Compatibility\DiContainer\Oxid7DiContainerAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

#[CoversClass(Oxid7DiContainerAdapter::class)]
class Oxid7DiContainerAdapterTest extends TestCase
{
    public function testImplementsDiContainerPort(): void
    {
        $this->assertTrue(
            is_subclass_of(Oxid7DiContainerAdapter::class, DiContainerPort::class)
        );
    }

    public function testClassIsNotAbstract(): void
    {
        $reflection = new ReflectionClass(Oxid7DiContainerAdapter::class);
        $this->assertFalse($reflection->isAbstract());
    }

    public function testClassIsNotFinal(): void
    {
        $reflection = new ReflectionClass(Oxid7DiContainerAdapter::class);
        $this->assertFalse($reflection->isFinal());
    }

    public function testHasNoConstructor(): void
    {
        $reflection = new ReflectionClass(Oxid7DiContainerAdapter::class);
        $constructor = $reflection->getConstructor();
        $this->assertNull($constructor);
    }

    public function testHasGetMethod(): void
    {
        $this->assertTrue(method_exists(Oxid7DiContainerAdapter::class, 'get'));
    }

    public function testGetMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Oxid7DiContainerAdapter::class, 'get');
        $this->assertTrue($method->isPublic());
    }

    public function testGetMethodIsNotStatic(): void
    {
        $method = new ReflectionMethod(Oxid7DiContainerAdapter::class, 'get');
        $this->assertFalse($method->isStatic());
    }

    public function testGetMethodAcceptsStringParameter(): void
    {
        $method = new ReflectionMethod(Oxid7DiContainerAdapter::class, 'get');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('interface', $params[0]->getName());
        $this->assertSame('string', $params[0]->getType()->getName());
    }

    public function testGetMethodHasNoReturnTypeHint(): void
    {
        $method = new ReflectionMethod(Oxid7DiContainerAdapter::class, 'get');
        $returnType = $method->getReturnType();
        // The method returns mixed (no explicit type hint)
        $this->assertNull($returnType);
    }

    public function testClassHasCorrectNamespace(): void
    {
        $reflection = new ReflectionClass(Oxid7DiContainerAdapter::class);
        $this->assertSame(
            'OxidSupport\RequestLogger\Shop\Compatibility\DiContainer',
            $reflection->getNamespaceName()
        );
    }

    public function testCanBeInstantiated(): void
    {
        $adapter = new Oxid7DiContainerAdapter();
        $this->assertInstanceOf(Oxid7DiContainerAdapter::class, $adapter);
    }

    public function testGetMethodDelegatesToContainerFacade(): void
    {
        // This test requires full OXID shop context (oxNew function, etc.)
        // which is not available in unit test environment.
        // The integration is tested through the Factory tests.
        $this->markTestSkipped('Requires full OXID shop context - integration test');
    }
}
