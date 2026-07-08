<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\LogSender\Framework;

use OxidEsales\GraphQL\Base\Framework\PermissionProviderInterface;
use OxidSupport\Heartbeat\Component\LogSender\Framework\PermissionProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PermissionProvider::class)]
final class PermissionProviderTest extends TestCase
{
    public function testImplementsPermissionProviderInterface(): void
    {
        $provider = new PermissionProvider();

        $this->assertInstanceOf(PermissionProviderInterface::class, $provider);
    }

    public function testGetPermissionsReturnsArray(): void
    {
        $provider = new PermissionProvider();

        $this->assertIsArray($provider->getPermissions());
    }

    public function testGetPermissionsContainsApiUserGroup(): void
    {
        $provider = new PermissionProvider();
        $permissions = $provider->getPermissions();

        $this->assertArrayHasKey('oxsheartbeat_api', $permissions);
    }

    public function testApiUserGroupHasLogSenderViewPermission(): void
    {
        $provider = new PermissionProvider();
        $permissions = $provider->getPermissions();

        $this->assertContains('LOG_SENDER_VIEW', $permissions['oxsheartbeat_api']);
    }

    public function testApiUserGroupHasExactlyOnePermission(): void
    {
        $provider = new PermissionProvider();
        $permissions = $provider->getPermissions();

        $this->assertCount(1, $permissions['oxsheartbeat_api']);
    }

    /**
     * Support-only: the shop admin group must not be granted this GraphQL right.
     * Admins use the backend UI, not the API (least privilege, OXS-3050).
     */
    public function testAdminGroupIsNotGranted(): void
    {
        $provider = new PermissionProvider();
        $permissions = $provider->getPermissions();

        $this->assertArrayNotHasKey('oxidadmin', $permissions);
    }

    public function testGetPermissionsReturnsOnlyApiUserGroup(): void
    {
        $provider = new PermissionProvider();

        $this->assertSame(['oxsheartbeat_api'], array_keys($provider->getPermissions()));
    }

    public function testClassIsFinal(): void
    {
        $reflection = new \ReflectionClass(PermissionProvider::class);

        $this->assertTrue($reflection->isFinal());
    }
}
