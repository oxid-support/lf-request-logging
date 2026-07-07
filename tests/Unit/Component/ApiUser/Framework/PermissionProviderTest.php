<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\ApiUser\Framework;

use OxidEsales\GraphQL\Base\Framework\PermissionProviderInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Framework\PermissionProvider;
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

    public function testGetPermissionsContainsAdminGroup(): void
    {
        $provider = new PermissionProvider();
        $permissions = $provider->getPermissions();

        $this->assertArrayHasKey('oxidadmin', $permissions);
    }

    public function testAdminGroupHasRecoveryPermissions(): void
    {
        $provider = new PermissionProvider();
        $permissions = $provider->getPermissions();

        $this->assertContains('OXSHEARTBEAT_PASSWORD_RESET', $permissions['oxidadmin']);
        $this->assertContains('OXSHEARTBEAT_TOKEN_INVALIDATE', $permissions['oxidadmin']);
    }

    /**
     * Password reset and token invalidation are leak-response recovery
     * operations. The service account (oxsheartbeat_api) must NOT hold them:
     * a leaked service JWT could otherwise call heartbeatResetPassword, obtain
     * a fresh setup token from the response, and re-establish access, defeating
     * the very token-invalidation control (OXS-3054/3059).
     */
    public function testApiUserGroupHasNoRecoveryPermissions(): void
    {
        $provider = new PermissionProvider();
        $permissions = $provider->getPermissions();

        $apiGroup = $permissions['oxsheartbeat_api'] ?? [];

        $this->assertNotContains('OXSHEARTBEAT_PASSWORD_RESET', $apiGroup);
        $this->assertNotContains('OXSHEARTBEAT_TOKEN_INVALIDATE', $apiGroup);
    }
}
