<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Shop\Facade;

use OxidSupport\RequestLogger\Shop\Facade\ShopFacade;
use OxidSupport\RequestLogger\Shop\Facade\ShopFacadeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

#[CoversClass(ShopFacade::class)]
class ShopFacadeTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $this->assertTrue(
            is_subclass_of(ShopFacade::class, ShopFacadeInterface::class)
        );
    }

    public function testClassIsNotAbstract(): void
    {
        $reflection = new ReflectionClass(ShopFacade::class);
        $this->assertFalse($reflection->isAbstract());
    }

    public function testClassIsNotFinal(): void
    {
        $reflection = new ReflectionClass(ShopFacade::class);
        $this->assertFalse($reflection->isFinal());
    }

    public function testHasGetShopIdMethod(): void
    {
        $this->assertTrue(method_exists(ShopFacade::class, 'getShopId'));
    }

    public function testGetShopIdReturnsInt(): void
    {
        $method = new ReflectionMethod(ShopFacade::class, 'getShopId');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('int', $returnType->getName());
    }

    public function testHasGetShopUrlMethod(): void
    {
        $this->assertTrue(method_exists(ShopFacade::class, 'getShopUrl'));
    }

    public function testGetShopUrlReturnsNullableString(): void
    {
        $method = new ReflectionMethod(ShopFacade::class, 'getShopUrl');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('string', $returnType->getName());
        $this->assertTrue($returnType->allowsNull());
    }

    public function testHasGetLogsPathMethod(): void
    {
        $this->assertTrue(method_exists(ShopFacade::class, 'getLogsPath'));
    }

    public function testGetLogsPathReturnsString(): void
    {
        $method = new ReflectionMethod(ShopFacade::class, 'getLogsPath');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('string', $returnType->getName());
        $this->assertFalse($returnType->allowsNull());
    }

    public function testHasGetShopVersionMethod(): void
    {
        $this->assertTrue(method_exists(ShopFacade::class, 'getShopVersion'));
    }

    public function testGetShopVersionReturnsString(): void
    {
        $method = new ReflectionMethod(ShopFacade::class, 'getShopVersion');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('string', $returnType->getName());
    }

    public function testHasGetShopEditionMethod(): void
    {
        $this->assertTrue(method_exists(ShopFacade::class, 'getShopEdition'));
    }

    public function testGetShopEditionReturnsString(): void
    {
        $method = new ReflectionMethod(ShopFacade::class, 'getShopEdition');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('string', $returnType->getName());
    }

    public function testHasGetLanguageAbbreviationMethod(): void
    {
        $this->assertTrue(method_exists(ShopFacade::class, 'getLanguageAbbreviation'));
    }

    public function testGetLanguageAbbreviationReturnsString(): void
    {
        $method = new ReflectionMethod(ShopFacade::class, 'getLanguageAbbreviation');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('string', $returnType->getName());
    }

    public function testHasGetSessionIdMethod(): void
    {
        $this->assertTrue(method_exists(ShopFacade::class, 'getSessionId'));
    }

    public function testGetSessionIdReturnsNullableString(): void
    {
        $method = new ReflectionMethod(ShopFacade::class, 'getSessionId');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('string', $returnType->getName());
        $this->assertTrue($returnType->allowsNull());
    }

    public function testHasGetUserIdMethod(): void
    {
        $this->assertTrue(method_exists(ShopFacade::class, 'getUserId'));
    }

    public function testGetUserIdReturnsNullableString(): void
    {
        $method = new ReflectionMethod(ShopFacade::class, 'getUserId');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('string', $returnType->getName());
        $this->assertTrue($returnType->allowsNull());
    }

    public function testHasGetUsernameMethod(): void
    {
        $this->assertTrue(method_exists(ShopFacade::class, 'getUsername'));
    }

    public function testGetUsernameReturnsNullableString(): void
    {
        $method = new ReflectionMethod(ShopFacade::class, 'getUsername');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('string', $returnType->getName());
        $this->assertTrue($returnType->allowsNull());
    }

    public function testHasGetRequestParameterMethod(): void
    {
        $this->assertTrue(method_exists(ShopFacade::class, 'getRequestParameter'));
    }

    public function testGetRequestParameterReturnsNullableString(): void
    {
        $method = new ReflectionMethod(ShopFacade::class, 'getRequestParameter');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('string', $returnType->getName());
        $this->assertTrue($returnType->allowsNull());
    }

    public function testGetRequestParameterAcceptsStringParameter(): void
    {
        $method = new ReflectionMethod(ShopFacade::class, 'getRequestParameter');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('name', $params[0]->getName());
        $this->assertSame('string', $params[0]->getType()->getName());
    }

    public function testHasGetLoggerMethod(): void
    {
        $this->assertTrue(method_exists(ShopFacade::class, 'getLogger'));
    }

    public function testGetLoggerReturnsLoggerInterface(): void
    {
        $method = new ReflectionMethod(ShopFacade::class, 'getLogger');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('Psr\Log\LoggerInterface', $returnType->getName());
    }

    public function testHasIsAdminMethod(): void
    {
        $this->assertTrue(method_exists(ShopFacade::class, 'isAdmin'));
    }

    public function testIsAdminReturnsBool(): void
    {
        $method = new ReflectionMethod(ShopFacade::class, 'isAdmin');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('bool', $returnType->getName());
    }

    public function testHasPrivateGetConfigMethod(): void
    {
        $method = new ReflectionMethod(ShopFacade::class, 'getConfig');
        $this->assertTrue($method->isPrivate());
    }

    public function testHasPrivateGetSessionMethod(): void
    {
        $method = new ReflectionMethod(ShopFacade::class, 'getSession');
        $this->assertTrue($method->isPrivate());
    }

    public function testHasPrivateGetRequestMethod(): void
    {
        $method = new ReflectionMethod(ShopFacade::class, 'getRequest');
        $this->assertTrue($method->isPrivate());
    }

    public function testAllPublicMethodsAreInInterface(): void
    {
        $facadeReflection = new ReflectionClass(ShopFacade::class);
        $interfaceReflection = new ReflectionClass(ShopFacadeInterface::class);

        $interfaceMethods = array_map(
            fn($m) => $m->getName(),
            $interfaceReflection->getMethods()
        );

        $publicMethods = array_filter(
            $facadeReflection->getMethods(ReflectionMethod::IS_PUBLIC),
            fn($m) => $m->getDeclaringClass()->getName() === ShopFacade::class
        );

        foreach ($publicMethods as $method) {
            $this->assertContains(
                $method->getName(),
                $interfaceMethods,
                "Public method {$method->getName()} is not defined in interface"
            );
        }
    }

    public function testHasNoConstructor(): void
    {
        $reflection = new ReflectionClass(ShopFacade::class);
        $constructor = $reflection->getConstructor();
        $this->assertNull($constructor);
    }

    public function testClassHasCorrectNamespace(): void
    {
        $reflection = new ReflectionClass(ShopFacade::class);
        $this->assertSame(
            'OxidSupport\RequestLogger\Shop\Facade',
            $reflection->getNamespaceName()
        );
    }
}
