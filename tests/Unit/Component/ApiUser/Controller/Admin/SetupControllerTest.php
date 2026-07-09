<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\ApiUser\Controller\Admin;

use OxidSupport\Heartbeat\Component\ApiUser\Controller\Admin\SetupController;
use OxidSupport\Heartbeat\Shared\Controller\Admin\AbstractComponentController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetupController::class)]
final class SetupControllerTest extends TestCase
{
    public function testExtendsAbstractComponentController(): void
    {
        $reflection = new \ReflectionClass(SetupController::class);

        $this->assertTrue($reflection->isSubclassOf(AbstractComponentController::class));
    }

    public function testTemplateIsCorrectlySet(): void
    {
        $reflection = new \ReflectionClass(SetupController::class);
        $property = $reflection->getProperty('_sThisTemplate');

        $this->assertEquals('heartbeat_apiuser_setup.tpl', $property->getDefaultValue());
    }

    public function testIsComponentActiveMethodExists(): void
    {
        $reflection = new \ReflectionClass(SetupController::class);

        $this->assertTrue($reflection->hasMethod('isComponentActive'));
    }

    public function testGetStatusTextKeyMethodExists(): void
    {
        $reflection = new \ReflectionClass(SetupController::class);

        $this->assertTrue($reflection->hasMethod('getStatusTextKey'));
    }

    public function testGetSetupTokenMethodExists(): void
    {
        $reflection = new \ReflectionClass(SetupController::class);

        $this->assertTrue($reflection->hasMethod('getSetupToken'));
    }

    public function testIsHeartbeatModuleActivatedMethodExists(): void
    {
        $reflection = new \ReflectionClass(SetupController::class);

        $this->assertTrue($reflection->hasMethod('isHeartbeatModuleActivated'));
    }

    public function testIsGraphqlBaseActivatedMethodExists(): void
    {
        $reflection = new \ReflectionClass(SetupController::class);

        $this->assertTrue($reflection->hasMethod('isGraphqlBaseActivated'));
    }

    public function testIsApiUserCreatedMethodExists(): void
    {
        $reflection = new \ReflectionClass(SetupController::class);

        $this->assertTrue($reflection->hasMethod('isApiUserCreated'));
    }

    public function testIsApiUserPasswordSetMethodExists(): void
    {
        $reflection = new \ReflectionClass(SetupController::class);

        $this->assertTrue($reflection->hasMethod('isApiUserPasswordSet'));
    }

    public function testIsSetupCompleteMethodExists(): void
    {
        $reflection = new \ReflectionClass(SetupController::class);

        $this->assertTrue($reflection->hasMethod('isSetupComplete'));
    }

    public function testResetPasswordMethodExists(): void
    {
        $reflection = new \ReflectionClass(SetupController::class);

        $this->assertTrue($reflection->hasMethod('resetPassword'));
    }

    public function testClassIsNotFinal(): void
    {
        $reflection = new \ReflectionClass(SetupController::class);

        $this->assertFalse($reflection->isFinal());
    }

    public function testAllPublicMethodsReturnCorrectTypes(): void
    {
        $reflection = new \ReflectionClass(SetupController::class);

        $boolMethods = [
            'isComponentActive',
            'isHeartbeatModuleActivated',
            'isGraphqlBaseActivated',
            'isApiUserCreated',
            'isApiUserPasswordSet',
            'isSetupComplete',
        ];

        foreach ($boolMethods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $returnType = $method->getReturnType();
            $this->assertNotNull($returnType, "Method $methodName should have a return type");
            $this->assertEquals('bool', $returnType->getName(), "Method $methodName should return bool");
        }
    }

    public function testGetSetupTokenReturnsString(): void
    {
        $reflection = new \ReflectionClass(SetupController::class);
        $method = $reflection->getMethod('getSetupToken');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    public function testGetStatusTextKeyReturnsString(): void
    {
        $reflection = new \ReflectionClass(SetupController::class);
        $method = $reflection->getMethod('getStatusTextKey');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    public function testResetPasswordReturnsVoid(): void
    {
        $reflection = new \ReflectionClass(SetupController::class);
        $method = $reflection->getMethod('resetPassword');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    public function testHasPrivateApiUserServiceProperty(): void
    {
        $reflection = new \ReflectionClass(SetupController::class);

        $this->assertTrue($reflection->hasProperty('apiUserService'));
        $property = $reflection->getProperty('apiUserService');
        $this->assertTrue($property->isPrivate());
    }

    public function testHasPrivateApiUserStatusServiceProperty(): void
    {
        $reflection = new \ReflectionClass(SetupController::class);

        $this->assertTrue($reflection->hasProperty('apiUserStatusService'));
        $property = $reflection->getProperty('apiUserStatusService');
        $this->assertTrue($property->isPrivate());
    }
}
