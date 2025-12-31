<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Shop\Compatibility\ModuleSettings;

use OxidSupport\RequestLogger\Shop\Compatibility\ModuleSettings\ModuleSettingsPort;
use OxidSupport\RequestLogger\Shop\Compatibility\ModuleSettings\Oxid6ModuleSettingsAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

#[CoversClass(Oxid6ModuleSettingsAdapter::class)]
class Oxid6ModuleSettingsAdapterTest extends TestCase
{
    private const BRIDGE_INTERFACE = 'OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface';

    private $bridgeMock;
    private Oxid6ModuleSettingsAdapter $adapter;

    protected function setUp(): void
    {
        if (!interface_exists(self::BRIDGE_INTERFACE)) {
            $this->markTestSkipped('OXID 6 ModuleSettingBridgeInterface not available');
        }

        $this->bridgeMock = $this->createMock(self::BRIDGE_INTERFACE);
        $this->adapter = new Oxid6ModuleSettingsAdapter($this->bridgeMock);
    }

    public function testImplementsModuleSettingsPort(): void
    {
        $this->assertInstanceOf(ModuleSettingsPort::class, $this->adapter);
    }

    public function testClassIsNotAbstract(): void
    {
        $reflection = new ReflectionClass(Oxid6ModuleSettingsAdapter::class);
        $this->assertFalse($reflection->isAbstract());
    }

    public function testClassIsNotFinal(): void
    {
        $reflection = new ReflectionClass(Oxid6ModuleSettingsAdapter::class);
        $this->assertFalse($reflection->isFinal());
    }

    public function testHasConstructor(): void
    {
        $reflection = new ReflectionClass(Oxid6ModuleSettingsAdapter::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
    }

    public function testConstructorAcceptsModuleSettingBridgeInterface(): void
    {
        $constructor = new ReflectionMethod(Oxid6ModuleSettingsAdapter::class, '__construct');
        $params = $constructor->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('bridge', $params[0]->getName());
        $this->assertSame(self::BRIDGE_INTERFACE, $params[0]->getType()->getName());
    }

    public function testGetIntegerDelegatesToBridge(): void
    {
        $this->bridgeMock
            ->expects($this->once())
            ->method('get')
            ->with('testModule', 'testSetting')
            ->willReturn(42);

        $result = $this->adapter->getInteger('testSetting', 'testModule');

        $this->assertSame(42, $result);
    }

    public function testGetFloatDelegatesToBridge(): void
    {
        $this->bridgeMock
            ->expects($this->once())
            ->method('get')
            ->with('testModule', 'testSetting')
            ->willReturn(3.14);

        $result = $this->adapter->getFloat('testSetting', 'testModule');

        $this->assertSame(3.14, $result);
    }

    public function testGetStringDelegatesToBridge(): void
    {
        $this->bridgeMock
            ->expects($this->once())
            ->method('get')
            ->with('testSetting', 'testModule')
            ->willReturn('testValue');

        $result = $this->adapter->getString('testSetting', 'testModule');

        $this->assertSame('testValue', $result);
    }

    public function testGetBooleanDelegatesToBridge(): void
    {
        $this->bridgeMock
            ->expects($this->once())
            ->method('get')
            ->with('testSetting', 'testModule')
            ->willReturn(true);

        $result = $this->adapter->getBoolean('testSetting', 'testModule');

        $this->assertTrue($result);
    }

    public function testGetCollectionDelegatesToBridge(): void
    {
        $expectedCollection = ['item1', 'item2', 'item3'];

        $this->bridgeMock
            ->expects($this->once())
            ->method('get')
            ->with('testSetting', 'testModule')
            ->willReturn($expectedCollection);

        $result = $this->adapter->getCollection('testSetting', 'testModule');

        $this->assertSame($expectedCollection, $result);
    }

    public function testSaveIntegerDelegatesToBridge(): void
    {
        $this->bridgeMock
            ->expects($this->once())
            ->method('save')
            ->with('testSetting', 42, 'testModule');

        $this->adapter->saveInteger('testSetting', 42, 'testModule');
    }

    public function testSaveFloatDelegatesToBridge(): void
    {
        $this->bridgeMock
            ->expects($this->once())
            ->method('save')
            ->with('testSetting', 3.14, 'testModule');

        $this->adapter->saveFloat('testSetting', 3.14, 'testModule');
    }

    public function testSaveStringDelegatesToBridge(): void
    {
        $this->bridgeMock
            ->expects($this->once())
            ->method('save')
            ->with('testSetting', 'testValue', 'testModule');

        $this->adapter->saveString('testSetting', 'testValue', 'testModule');
    }

    public function testSaveBooleanDelegatesToBridge(): void
    {
        $this->bridgeMock
            ->expects($this->once())
            ->method('save')
            ->with('testSetting', true, 'testModule');

        $this->adapter->saveBoolean('testSetting', true, 'testModule');
    }

    public function testSaveCollectionDelegatesToBridge(): void
    {
        $collection = ['item1', 'item2'];

        $this->bridgeMock
            ->expects($this->once())
            ->method('save')
            ->with('testSetting', $collection, 'testModule');

        $this->adapter->saveCollection('testSetting', $collection, 'testModule');
    }

    public function testExistsReturnsTrueWhenValueNotNull(): void
    {
        $this->bridgeMock
            ->expects($this->once())
            ->method('get')
            ->with('testSetting', 'testModule')
            ->willReturn('someValue');

        $result = $this->adapter->exists('testSetting', 'testModule');

        $this->assertTrue($result);
    }

    public function testExistsReturnsFalseWhenValueIsNull(): void
    {
        $this->bridgeMock
            ->expects($this->once())
            ->method('get')
            ->with('testSetting', 'testModule')
            ->willReturn(null);

        $result = $this->adapter->exists('testSetting', 'testModule');

        $this->assertFalse($result);
    }

    public function testGetIntegerReturnsInt(): void
    {
        $method = new ReflectionMethod(Oxid6ModuleSettingsAdapter::class, 'getInteger');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('int', $returnType->getName());
    }

    public function testGetFloatReturnsFloat(): void
    {
        $method = new ReflectionMethod(Oxid6ModuleSettingsAdapter::class, 'getFloat');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('float', $returnType->getName());
    }

    public function testGetStringReturnsString(): void
    {
        $method = new ReflectionMethod(Oxid6ModuleSettingsAdapter::class, 'getString');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('string', $returnType->getName());
    }

    public function testGetBooleanReturnsBool(): void
    {
        $method = new ReflectionMethod(Oxid6ModuleSettingsAdapter::class, 'getBoolean');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('bool', $returnType->getName());
    }

    public function testGetCollectionReturnsArray(): void
    {
        $method = new ReflectionMethod(Oxid6ModuleSettingsAdapter::class, 'getCollection');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('array', $returnType->getName());
    }

    public function testExistsReturnsBool(): void
    {
        $method = new ReflectionMethod(Oxid6ModuleSettingsAdapter::class, 'exists');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('bool', $returnType->getName());
    }

    public function testHasPrivateBridgeProperty(): void
    {
        $reflection = new ReflectionClass(Oxid6ModuleSettingsAdapter::class);
        $property = $reflection->getProperty('bridge');
        $this->assertTrue($property->isPrivate());
    }

    public function testClassHasCorrectNamespace(): void
    {
        $reflection = new ReflectionClass(Oxid6ModuleSettingsAdapter::class);
        $this->assertSame(
            'OxidSupport\RequestLogger\Shop\Compatibility\ModuleSettings',
            $reflection->getNamespaceName()
        );
    }
}
