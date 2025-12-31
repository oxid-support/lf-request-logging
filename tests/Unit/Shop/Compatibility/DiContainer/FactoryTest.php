<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Shop\Compatibility\DiContainer;

use OxidSupport\RequestLogger\Shop\Compatibility\DiContainer\DiContainerPort;
use OxidSupport\RequestLogger\Shop\Compatibility\DiContainer\Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

#[CoversClass(Factory::class)]
class FactoryTest extends TestCase
{
    public function testClassIsNotAbstract(): void
    {
        $reflection = new ReflectionClass(Factory::class);
        $this->assertFalse($reflection->isAbstract());
    }

    public function testClassIsNotFinal(): void
    {
        $reflection = new ReflectionClass(Factory::class);
        $this->assertFalse($reflection->isFinal());
    }

    public function testHasNoConstructor(): void
    {
        $reflection = new ReflectionClass(Factory::class);
        $constructor = $reflection->getConstructor();
        $this->assertNull($constructor);
    }

    public function testHasCreateMethod(): void
    {
        $this->assertTrue(method_exists(Factory::class, 'create'));
    }

    public function testCreateMethodReturnsDiContainerPort(): void
    {
        $method = new ReflectionMethod(Factory::class, 'create');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame(DiContainerPort::class, $returnType->getName());
    }

    public function testCreateMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Factory::class, 'create');
        $this->assertTrue($method->isPublic());
    }

    public function testCreateMethodIsStatic(): void
    {
        $method = new ReflectionMethod(Factory::class, 'create');
        $this->assertTrue($method->isStatic());
    }

    public function testCreateMethodTakesNoParameters(): void
    {
        $method = new ReflectionMethod(Factory::class, 'create');
        $this->assertCount(0, $method->getParameters());
    }

    public function testClassHasCorrectNamespace(): void
    {
        $reflection = new ReflectionClass(Factory::class);
        $this->assertSame(
            'OxidSupport\RequestLogger\Shop\Compatibility\DiContainer',
            $reflection->getNamespaceName()
        );
    }

    public function testCreateReturnsDiContainerPortInstance(): void
    {
        $v7Facade = '\OxidEsales\EshopCommunity\Core\Di\ContainerFacade';
        $v6Factory = '\OxidEsales\EshopCommunity\Internal\Container\ContainerFactory';

        $hasV7 = class_exists($v7Facade) && method_exists($v7Facade, 'get');
        $hasV6 = class_exists($v6Factory) && method_exists($v6Factory, 'getInstance');

        if (!$hasV7 && !$hasV6) {
            $this->markTestSkipped('Neither OXID 6 nor OXID 7 container available');
        }

        $result = Factory::create();

        $this->assertInstanceOf(DiContainerPort::class, $result);
    }

    public function testFactoryDetectsOxid7WhenAvailable(): void
    {
        $v7Facade = '\OxidEsales\EshopCommunity\Core\Di\ContainerFacade';

        if (!class_exists($v7Facade) || !method_exists($v7Facade, 'get')) {
            $this->markTestSkipped('OXID 7 ContainerFacade not available');
        }

        $result = Factory::create();

        $this->assertInstanceOf(
            'OxidSupport\RequestLogger\Shop\Compatibility\DiContainer\Oxid7DiContainerAdapter',
            $result
        );
    }

    public function testFactoryUsesOxid6AsCallbackWhenNoOxid7(): void
    {
        $v7Facade = '\OxidEsales\EshopCommunity\Core\Di\ContainerFacade';
        $v6Factory = '\OxidEsales\EshopCommunity\Internal\Container\ContainerFactory';

        $hasV7 = class_exists($v7Facade) && method_exists($v7Facade, 'get');
        $hasV6 = class_exists($v6Factory) && method_exists($v6Factory, 'getInstance');

        if ($hasV7 || !$hasV6) {
            $this->markTestSkipped('Test only applicable when OXID 6 is available without OXID 7');
        }

        $result = Factory::create();

        $this->assertInstanceOf(
            'OxidSupport\RequestLogger\Shop\Compatibility\DiContainer\Oxid6DiContainerAdapter',
            $result
        );
    }
}
