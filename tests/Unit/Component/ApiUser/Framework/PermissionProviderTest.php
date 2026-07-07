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
        $this->assertInstanceOf(PermissionProviderInterface::class, new PermissionProvider());
    }

    public function testServiceUserMayInvalidateTokens(): void
    {
        // The kill switch (heartbeatInvalidateTokens) is callable by the service
        // user so OXID Support can revoke a leaked token remotely.
        $permissions = (new PermissionProvider())->getPermissions();

        $this->assertContains('OXSHEARTBEAT_TOKEN_INVALIDATE', $permissions['oxsheartbeat_api'] ?? []);
    }

    /**
     * A stolen token must never be able to rotate the password to re-establish
     * access: the service user holds no password-reset right, and the reset
     * mutation is removed entirely.
     */
    public function testServiceUserHasNoPasswordResetRight(): void
    {
        $permissions = (new PermissionProvider())->getPermissions();

        foreach ($permissions as $group => $rights) {
            $this->assertNotContains(
                'OXSHEARTBEAT_PASSWORD_RESET',
                $rights,
                "group $group must not grant password reset"
            );
        }
    }
}
