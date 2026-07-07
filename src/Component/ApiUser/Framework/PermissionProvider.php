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
            // Password reset and token invalidation are leak-response recovery
            // operations, granted to the shop admin only. The service account
            // (oxsheartbeat_api) intentionally does NOT get them: a leaked
            // service JWT could otherwise call heartbeatResetPassword, read the
            // fresh setup token from the response and re-establish access,
            // defeating the token-invalidation control (OXS-3054/3059).
            'oxsheartbeat_api' => [],
            'oxidadmin' => [
                'OXSHEARTBEAT_PASSWORD_RESET',
                'OXSHEARTBEAT_TOKEN_INVALIDATE',
            ],
        ];
    }
}
