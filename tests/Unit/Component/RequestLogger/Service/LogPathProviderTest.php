<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\RequestLogger\Service;

use OxidSupport\Heartbeat\Component\RequestLogger\Service\LogPathProvider;
use OxidSupport\Heartbeat\Shop\Facade\ModuleSettingFacadeInterface;
use OxidSupport\Heartbeat\Shop\Facade\ShopFacadeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LogPathProvider::class)]
final class LogPathProviderTest extends TestCase
{
    /**
     * The read path (LogSender source) is scoped to the current shop's
     * subdirectory, matching where LoggerFactory writes. See OXS-3130.
     */
    public function testReturnsCurrentShopSubdirectory(): void
    {
        $paths = $this->makeProvider('/var/www/log/', 1)->getLogPaths();

        $this->assertCount(1, $paths);
        $this->assertSame('/var/www/log/oxs-request-logger/1/', $paths[0]->path);
    }

    /**
     * Two shops resolve to two different directories, so a subshop's service
     * user never reads another subshop's request logs. See OXS-3130.
     */
    public function testDifferentShopsGetDifferentDirectories(): void
    {
        $shop1 = $this->makeProvider('/var/www/log/', 1)->getLogPaths()[0]->path;
        $shop3 = $this->makeProvider('/var/www/log/', 3)->getLogPaths()[0]->path;

        $this->assertNotSame($shop1, $shop3);
        $this->assertStringEndsWith('/oxs-request-logger/1/', $shop1);
        $this->assertStringEndsWith('/oxs-request-logger/3/', $shop3);
    }

    private function makeProvider(string $logsPath, int $shopId): LogPathProvider
    {
        $shopFacade = $this->createMock(ShopFacadeInterface::class);
        $shopFacade->method('getLogsPath')->willReturn($logsPath);
        $shopFacade->method('getShopId')->willReturn($shopId);

        return new LogPathProvider($shopFacade, $this->createMock(ModuleSettingFacadeInterface::class));
    }
}
