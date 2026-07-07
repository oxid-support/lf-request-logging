<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\DiagnosticsProvider\Controller\GraphQL;

use OxidSupport\Heartbeat\Component\DiagnosticsProvider\Controller\GraphQL\DiagnosticsController;
use OxidSupport\Heartbeat\Component\DiagnosticsProvider\DataType\DiagnosticsType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(DiagnosticsController::class)]
final class DiagnosticsControllerTest extends TestCase
{
    public function testClassIsFinal(): void
    {
        $reflection = new ReflectionClass(DiagnosticsController::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function testDiagnosticsMethodExists(): void
    {
        $reflection = new ReflectionClass(DiagnosticsController::class);

        $this->assertTrue($reflection->hasMethod('diagnostics'));
    }

    public function testDiagnosticsIsPublic(): void
    {
        $reflection = new ReflectionClass(DiagnosticsController::class);
        $method = $reflection->getMethod('diagnostics');

        $this->assertTrue($method->isPublic());
    }

    public function testDiagnosticsReturnsNullableDiagnosticsType(): void
    {
        $reflection = new ReflectionClass(DiagnosticsController::class);
        $method = $reflection->getMethod('diagnostics');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals(DiagnosticsType::class, $returnType->getName());
        $this->assertTrue($returnType->allowsNull());
    }

    public function testDiagnosticsHasQueryAnnotation(): void
    {
        $method = (new ReflectionClass(DiagnosticsController::class))->getMethod('diagnostics');

        $this->assertStringContainsString('@Query', $method->getDocComment());
    }

    public function testDiagnosticsHasLoggedAnnotation(): void
    {
        $method = (new ReflectionClass(DiagnosticsController::class))->getMethod('diagnostics');

        $this->assertStringContainsString('@Logged', $method->getDocComment());
    }

    public function testDiagnosticsHasRightAnnotation(): void
    {
        $method = (new ReflectionClass(DiagnosticsController::class))->getMethod('diagnostics');

        $this->assertStringContainsString('@Right', $method->getDocComment());
    }

    public function testDiagnosticsUsesDedicatedDiagnosticsRight(): void
    {
        // Diagnostics must be gated by its own right, not the LogSender one,
        // so "read diagnostics" and "read logs" are separable privileges.
        $method = (new ReflectionClass(DiagnosticsController::class))->getMethod('diagnostics');
        $doc = (string) $method->getDocComment();

        $this->assertStringContainsString('DIAGNOSTICS_VIEW', $doc);
        $this->assertStringNotContainsString('LOG_SENDER_VIEW', $doc);
    }

    public function testDiagnosticsHasNoParameters(): void
    {
        $reflection = new ReflectionClass(DiagnosticsController::class);
        $method = $reflection->getMethod('diagnostics');

        $this->assertCount(0, $method->getParameters());
    }

    public function testConstructorHasTwoParameters(): void
    {
        $reflection = new ReflectionClass(DiagnosticsController::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertCount(2, $constructor->getParameters());
    }

    public function testConstructorHasDiagnosticsProviderParameter(): void
    {
        $reflection = new ReflectionClass(DiagnosticsController::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();

        $parameterNames = array_map(fn($p) => $p->getName(), $parameters);
        $this->assertContains('diagnosticsProvider', $parameterNames);
    }

    public function testConstructorHasStatusServiceParameter(): void
    {
        $reflection = new ReflectionClass(DiagnosticsController::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();

        $parameterNames = array_map(fn($p) => $p->getName(), $parameters);
        $this->assertContains('statusService', $parameterNames);
    }
}
