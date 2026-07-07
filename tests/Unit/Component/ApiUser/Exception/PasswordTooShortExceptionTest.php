<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\ApiUser\Exception;

use OxidSupport\Heartbeat\Component\ApiUser\Exception\PasswordTooShortException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PasswordTooShortException::class)]
final class PasswordTooShortExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $exception = new PasswordTooShortException();

        $this->assertEquals('Password must be at least 12 characters long.', $exception->getMessage());
    }

    public function testGetCategoryReturnsValidation(): void
    {
        $exception = new PasswordTooShortException();

        $this->assertEquals('validation', $exception->getCategory());
    }

    public function testClassIsFinal(): void
    {
        $reflection = new \ReflectionClass(PasswordTooShortException::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function testConstructorHasNoParameters(): void
    {
        $reflection = new \ReflectionClass(PasswordTooShortException::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertCount(0, $constructor->getParameters());
    }

    public function testCanBeThrownAndCaught(): void
    {
        $this->expectException(PasswordTooShortException::class);

        throw new PasswordTooShortException();
    }
}
