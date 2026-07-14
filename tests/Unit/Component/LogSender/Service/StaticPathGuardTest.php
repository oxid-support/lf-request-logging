<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\LogSender\Service;

use OxidSupport\Heartbeat\Component\LogSender\Service\StaticPathGuard;
use OxidSupport\Heartbeat\Component\LogSender\Service\StaticPathGuardInterface;
use OxidSupport\Heartbeat\Shop\Facade\ShopFacadeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(StaticPathGuard::class)]
final class StaticPathGuardTest extends TestCase
{
    private string $logsPath;
    private ShopFacadeInterface&MockObject $shopFacade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logsPath = sys_get_temp_dir() . '/staticpathguard_' . uniqid() . '/';
        // Per-shop request-log tree for shops 1 and 2 (OXS-3130 layout).
        mkdir($this->logsPath . 'oxs-request-logger/1', 0777, true);
        mkdir($this->logsPath . 'oxs-request-logger/2', 0777, true);

        $this->shopFacade = $this->createMock(ShopFacadeInterface::class);
        $this->shopFacade->method('getLogsPath')->willReturn($this->logsPath);
        $this->shopFacade->method('getShopId')->willReturn(1);
    }

    protected function tearDown(): void
    {
        @rmdir($this->logsPath . 'oxs-request-logger/1');
        @rmdir($this->logsPath . 'oxs-request-logger/2');
        @rmdir($this->logsPath . 'oxs-request-logger');
        @rmdir($this->logsPath);
        parent::tearDown();
    }

    private function guard(): StaticPathGuardInterface
    {
        return new StaticPathGuard($this->shopFacade);
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(StaticPathGuardInterface::class, $this->guard());
    }

    public function testAllowsOwnShopRequestLogDirectory(): void
    {
        $this->assertTrue($this->guard()->isAllowed($this->logsPath . 'oxs-request-logger/1/'));
    }

    public function testRejectsSiblingShopRequestLogDirectory(): void
    {
        // The core of OXS-3131: shop 1's service user must not read shop 2's logs.
        $this->assertFalse($this->guard()->isAllowed($this->logsPath . 'oxs-request-logger/2/'));
    }

    public function testRejectsTraversalIntoSiblingShopDirectory(): void
    {
        $traversal = $this->logsPath . 'oxs-request-logger/1/../2/';
        $this->assertFalse($this->guard()->isAllowed($traversal));
    }

    public function testAllowsUnrelatedLogPathOutsideTheRequestLogTree(): void
    {
        $this->assertTrue($this->guard()->isAllowed('/var/log/syslog'));
    }

    public function testRejectsSensitiveFileExtensions(): void
    {
        foreach (['/var/www/config.inc.php', '/etc/app/secrets.yaml', '/home/x/id_rsa.pem'] as $path) {
            $this->assertFalse($this->guard()->isAllowed($path), $path);
        }
    }

    public function testRejectsEmptyPath(): void
    {
        $this->assertFalse($this->guard()->isAllowed(''));
        $this->assertFalse($this->guard()->isAllowed('/'));
    }
}
