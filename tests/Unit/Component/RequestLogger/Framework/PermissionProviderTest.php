<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\RequestLogger\Framework;

use OxidEsales\GraphQL\Base\Framework\PermissionProviderInterface;
use OxidSupport\Heartbeat\Component\RequestLogger\Framework\PermissionProvider;
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
        $permissions = $provider->getPermissions();

        $this->assertIsArray($permissions);
    }

    public function testGetPermissionsContainsApiUserGroup(): void
    {
        $provider = new PermissionProvider();
        $permissions = $provider->getPermissions();

        $this->assertArrayHasKey('oxsheartbeat_api', $permissions);
    }

    public function testApiUserGroupHasViewPermission(): void
    {
        $provider = new PermissionProvider();
        $permissions = $provider->getPermissions();

        $this->assertContains('REQUEST_LOGGER_VIEW', $permissions['oxsheartbeat_api']);
    }

    public function testApiUserGroupHasChangePermission(): void
    {
        $provider = new PermissionProvider();
        $permissions = $provider->getPermissions();

        $this->assertContains('REQUEST_LOGGER_CHANGE', $permissions['oxsheartbeat_api']);
    }

    public function testApiUserGroupHasExactlyTwoPermissions(): void
    {
        $provider = new PermissionProvider();
        $permissions = $provider->getPermissions();

        $this->assertCount(2, $permissions['oxsheartbeat_api']);
    }

    /**
     * Support-only: the shop admin group must not be granted these GraphQL rights.
     * Admins configure via the backend UI, not the API (least privilege, OXS-3050).
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
        $permissions = $provider->getPermissions();

        $this->assertSame(['oxsheartbeat_api'], array_keys($permissions));
    }
}
