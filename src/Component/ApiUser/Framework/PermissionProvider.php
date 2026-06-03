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
        // Both groups intentionally kept here. Password reset and token
        // invalidation are recovery operations that the shop admin must be
        // able to trigger even when the service account is unavailable.
        // For routine settings reads/writes oxidadmin is removed in OXS-3050.
        // See OXS-3050 for the broader permission refinement plan.
        return [
            'oxsheartbeat_api' => [
                'OXSHEARTBEAT_PASSWORD_RESET',
                'OXSHEARTBEAT_TOKEN_INVALIDATE',
            ],
            'oxidadmin' => [
                'OXSHEARTBEAT_PASSWORD_RESET',
                'OXSHEARTBEAT_TOKEN_INVALIDATE',
            ],
        ];
    }
}
