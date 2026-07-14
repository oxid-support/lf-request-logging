<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\RequestLogger\Service;

use OxidSupport\Heartbeat\Component\RequestLogger\Service\RequestLogDirectory;
use OxidSupport\Heartbeat\Shop\Facade\ShopFacadeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RequestLogDirectory::class)]
final class RequestLogDirectoryTest extends TestCase
{
    public function testBuildsPerShopDirectory(): void
    {
        $this->assertSame('/var/www/log/oxs-request-logger/1/', RequestLogDirectory::forShop($this->shop(1)));
    }

    public function testDifferentShopsGetDifferentDirectories(): void
    {
        $this->assertNotSame(
            RequestLogDirectory::forShop($this->shop(1)),
            RequestLogDirectory::forShop($this->shop(2))
        );
    }

    private function shop(int $shopId): ShopFacadeInterface
    {
        $shopFacade = $this->createMock(ShopFacadeInterface::class);
        $shopFacade->method('getLogsPath')->willReturn('/var/www/log/');
        $shopFacade->method('getShopId')->willReturn($shopId);

        return $shopFacade;
    }
}
