<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Shop\Compatibility\ModuleSettings;

use OxidSupport\RequestLogger\Shop\Compatibility\DiContainer\DiContainerPort;
use OxidSupport\RequestLogger\Shop\Compatibility\ModuleSettings\Factory;
use OxidSupport\RequestLogger\Shop\Compatibility\ModuleSettings\ModuleSettingsPort;
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

    public function testHasConstructor(): void
    {
        $reflection = new ReflectionClass(Factory::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
    }

    public function testConstructorAcceptsDiContainerPort(): void
    {
        $constructor = new ReflectionMethod(Factory::class, '__construct');
        $params = $constructor->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('container', $params[0]->getName());
        $this->assertSame(DiContainerPort::class, $params[0]->getType()->getName());
    }

    public function testHasCreateMethod(): void
    {
        $this->assertTrue(method_exists(Factory::class, 'create'));
    }

    public function testCreateMethodReturnsModuleSettingsPort(): void
    {
        $method = new ReflectionMethod(Factory::class, 'create');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame(ModuleSettingsPort::class, $returnType->getName());
    }

    public function testCreateMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Factory::class, 'create');
        $this->assertTrue($method->isPublic());
    }

    public function testCreateMethodIsNotStatic(): void
    {
        $method = new ReflectionMethod(Factory::class, 'create');
        $this->assertFalse($method->isStatic());
    }

    public function testCreateMethodTakesNoParameters(): void
    {
        $method = new ReflectionMethod(Factory::class, 'create');
        $this->assertCount(0, $method->getParameters());
    }

    public function testHasPrivateContainerProperty(): void
    {
        $reflection = new ReflectionClass(Factory::class);
        $property = $reflection->getProperty('container');
        $this->assertTrue($property->isPrivate());
    }

    public function testContainerPropertyHasCorrectType(): void
    {
        $reflection = new ReflectionClass(Factory::class);
        $property = $reflection->getProperty('container');
        $type = $property->getType();
        $this->assertNotNull($type);
        $this->assertSame(DiContainerPort::class, $type->getName());
    }

    public function testClassHasCorrectNamespace(): void
    {
        $reflection = new ReflectionClass(Factory::class);
        $this->assertSame(
            'OxidSupport\RequestLogger\Shop\Compatibility\ModuleSettings',
            $reflection->getNamespaceName()
        );
    }

    public function testCanBeInstantiated(): void
    {
        $containerMock = $this->createMock(DiContainerPort::class);
        $factory = new Factory($containerMock);
        $this->assertInstanceOf(Factory::class, $factory);
    }

    public function testCreateReturnsModuleSettingsPortInstance(): void
    {
        $v7Interface = 'OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface';

        if (!interface_exists($v7Interface)) {
            $this->markTestSkipped('OXID 7 ModuleSettingServiceInterface not available');
        }

        $serviceInterfaceMock = $this->createMock($v7Interface);
        $containerMock = $this->createMock(DiContainerPort::class);
        $containerMock->method('get')
            ->with($v7Interface)
            ->willReturn($serviceInterfaceMock);

        $factory = new Factory($containerMock);
        $result = $factory->create();

        $this->assertInstanceOf(ModuleSettingsPort::class, $result);
    }
}
