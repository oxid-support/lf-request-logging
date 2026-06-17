<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiUser\Framework;

use OxidEsales\GraphQL\Base\Framework\PermissionProviderInterface;

final class PermissionProvider implements PermissionProviderInterface
{
    public function getPermissions(): array
    {
        return [
            // Custom user group for Heartbeat API access
            'oxsheartbeat_api' => [
                'OXSHEARTBEAT_PASSWORD_RESET',
                'OXSHEARTBEAT_TOKEN_INVALIDATE',
            ],
            // Also grant permissions to shop admins
            'oxidadmin' => [
                'OXSHEARTBEAT_PASSWORD_RESET',
                'OXSHEARTBEAT_TOKEN_INVALIDATE',
            ],
        ];
    }
}
