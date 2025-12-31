<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Shop\Compatibility\ModuleSettings;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidSupport\RequestLogger\Shop\Compatibility\ModuleSettings\ModuleSettingsPort;
use OxidSupport\RequestLogger\Shop\Compatibility\ModuleSettings\Oxid7ModuleSettingsAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

#[CoversClass(Oxid7ModuleSettingsAdapter::class)]
class Oxid7ModuleSettingsAdapterTest extends TestCase
{
    private ModuleSettingServiceInterface $serviceMock;
    private Oxid7ModuleSettingsAdapter $adapter;

    protected function setUp(): void
    {
        if (!interface_exists(ModuleSettingServiceInterface::class)) {
            $this->markTestSkipped('OXID 7 ModuleSettingServiceInterface not available');
        }

        $this->serviceMock = $this->createMock(ModuleSettingServiceInterface::class);
        $this->adapter = new Oxid7ModuleSettingsAdapter($this->serviceMock);
    }

    public function testImplementsModuleSettingsPort(): void
    {
        $this->assertInstanceOf(ModuleSettingsPort::class, $this->adapter);
    }

    public function testClassIsNotAbstract(): void
    {
        $reflection = new ReflectionClass(Oxid7ModuleSettingsAdapter::class);
        $this->assertFalse($reflection->isAbstract());
    }

    public function testClassIsNotFinal(): void
    {
        $reflection = new ReflectionClass(Oxid7ModuleSettingsAdapter::class);
        $this->assertFalse($reflection->isFinal());
    }

    public function testHasConstructor(): void
    {
        $reflection = new ReflectionClass(Oxid7ModuleSettingsAdapter::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
    }

    public function testConstructorAcceptsModuleSettingServiceInterface(): void
    {
        $constructor = new ReflectionMethod(Oxid7ModuleSettingsAdapter::class, '__construct');
        $params = $constructor->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('svc', $params[0]->getName());
        $this->assertSame(ModuleSettingServiceInterface::class, $params[0]->getType()->getName());
    }

    public function testGetIntegerDelegatesToService(): void
    {
        $this->serviceMock
            ->expects($this->once())
            ->method('getInteger')
            ->with('testModule', 'testSetting')
            ->willReturn(42);

        $result = $this->adapter->getInteger('testSetting', 'testModule');

        $this->assertSame(42, $result);
    }

    public function testGetFloatDelegatesToService(): void
    {
        $this->serviceMock
            ->expects($this->once())
            ->method('getFloat')
            ->with('testModule', 'testSetting')
            ->willReturn(3.14);

        $result = $this->adapter->getFloat('testSetting', 'testModule');

        $this->assertSame(3.14, $result);
    }

    public function testGetStringDelegatesToService(): void
    {
        $unicodeString = $this->createMock(\Symfony\Component\String\UnicodeString::class);
        $unicodeString->method('__toString')->willReturn('testValue');

        $this->serviceMock
            ->expects($this->once())
            ->method('getString')
            ->with('testSetting', 'testModule')
            ->willReturn($unicodeString);

        $result = $this->adapter->getString('testSetting', 'testModule');

        $this->assertSame('testValue', $result);
    }

    public function testGetBooleanDelegatesToService(): void
    {
        $this->serviceMock
            ->expects($this->once())
            ->method('getBoolean')
            ->with('testSetting', 'testModule')
            ->willReturn(true);

        $result = $this->adapter->getBoolean('testSetting', 'testModule');

        $this->assertTrue($result);
    }

    public function testGetCollectionDelegatesToService(): void
    {
        $expectedCollection = ['item1', 'item2', 'item3'];

        $this->serviceMock
            ->expects($this->once())
            ->method('getCollection')
            ->with('testSetting', 'testModule')
            ->willReturn($expectedCollection);

        $result = $this->adapter->getCollection('testSetting', 'testModule');

        $this->assertSame($expectedCollection, $result);
    }

    public function testSaveIntegerDelegatesToService(): void
    {
        $this->serviceMock
            ->expects($this->once())
            ->method('saveInteger')
            ->with('testSetting', 42, 'testModule');

        $this->adapter->saveInteger('testSetting', 42, 'testModule');
    }

    public function testSaveFloatDelegatesToService(): void
    {
        $this->serviceMock
            ->expects($this->once())
            ->method('saveFloat')
            ->with('testSetting', 3.14, 'testModule');

        $this->adapter->saveFloat('testSetting', 3.14, 'testModule');
    }

    public function testSaveStringDelegatesToService(): void
    {
        $this->serviceMock
            ->expects($this->once())
            ->method('saveString')
            ->with('testSetting', 'testValue', 'testModule');

        $this->adapter->saveString('testSetting', 'testValue', 'testModule');
    }

    public function testSaveBooleanDelegatesToService(): void
    {
        $this->serviceMock
            ->expects($this->once())
            ->method('saveBoolean')
            ->with('testSetting', true, 'testModule');

        $this->adapter->saveBoolean('testSetting', true, 'testModule');
    }

    public function testSaveCollectionDelegatesToService(): void
    {
        $collection = ['item1', 'item2'];

        $this->serviceMock
            ->expects($this->once())
            ->method('saveCollection')
            ->with('testSetting', $collection, 'testModule');

        $this->adapter->saveCollection('testSetting', $collection, 'testModule');
    }

    public function testExistsDelegatesToService(): void
    {
        $this->serviceMock
            ->expects($this->once())
            ->method('exists')
            ->with('testSetting', 'testModule')
            ->willReturn(true);

        $result = $this->adapter->exists('testSetting', 'testModule');

        $this->assertTrue($result);
    }

    public function testExistsReturnsFalseWhenSettingDoesNotExist(): void
    {
        $this->serviceMock
            ->expects($this->once())
            ->method('exists')
            ->with('nonExistentSetting', 'testModule')
            ->willReturn(false);

        $result = $this->adapter->exists('nonExistentSetting', 'testModule');

        $this->assertFalse($result);
    }

    public function testGetIntegerReturnsInt(): void
    {
        $method = new ReflectionMethod(Oxid7ModuleSettingsAdapter::class, 'getInteger');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('int', $returnType->getName());
    }

    public function testGetFloatReturnsFloat(): void
    {
        $method = new ReflectionMethod(Oxid7ModuleSettingsAdapter::class, 'getFloat');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('float', $returnType->getName());
    }

    public function testGetStringReturnsString(): void
    {
        $method = new ReflectionMethod(Oxid7ModuleSettingsAdapter::class, 'getString');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('string', $returnType->getName());
    }

    public function testGetBooleanReturnsBool(): void
    {
        $method = new ReflectionMethod(Oxid7ModuleSettingsAdapter::class, 'getBoolean');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('bool', $returnType->getName());
    }

    public function testGetCollectionReturnsArray(): void
    {
        $method = new ReflectionMethod(Oxid7ModuleSettingsAdapter::class, 'getCollection');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('array', $returnType->getName());
    }

    public function testExistsReturnsBool(): void
    {
        $method = new ReflectionMethod(Oxid7ModuleSettingsAdapter::class, 'exists');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('bool', $returnType->getName());
    }

    public function testHasPrivateSvcProperty(): void
    {
        $reflection = new ReflectionClass(Oxid7ModuleSettingsAdapter::class);
        $property = $reflection->getProperty('svc');
        $this->assertTrue($property->isPrivate());
    }

    public function testClassHasCorrectNamespace(): void
    {
        $reflection = new ReflectionClass(Oxid7ModuleSettingsAdapter::class);
        $this->assertSame(
            'OxidSupport\RequestLogger\Shop\Compatibility\ModuleSettings',
            $reflection->getNamespaceName()
        );
    }
}
