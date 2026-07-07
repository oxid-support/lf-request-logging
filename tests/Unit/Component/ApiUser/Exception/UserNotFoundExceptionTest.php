<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\ApiUser\Exception;

use OxidSupport\Heartbeat\Component\ApiUser\Exception\UserNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserNotFoundException::class)]
final class UserNotFoundExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $exception = new UserNotFoundException();

        $this->assertEquals('API user not found.', $exception->getMessage());
    }

    public function testGetCategoryReturnsNotfound(): void
    {
        $exception = new UserNotFoundException();

        $this->assertEquals('notfound', $exception->getCategory());
    }

    public function testClassIsFinal(): void
    {
        $reflection = new \ReflectionClass(UserNotFoundException::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function testConstructorHasNoParameters(): void
    {
        $reflection = new \ReflectionClass(UserNotFoundException::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertCount(0, $constructor->getParameters());
    }

    public function testCanBeThrownAndCaught(): void
    {
        $this->expectException(UserNotFoundException::class);

        throw new UserNotFoundException();
    }
}
