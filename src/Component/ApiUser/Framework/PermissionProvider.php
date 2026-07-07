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
        // The service user may revoke its own JWTs via heartbeatInvalidateTokens
        // (leak-response kill switch). It intentionally holds NO password-reset
        // right: a stolen token cannot rotate the password to re-establish
        // access, and it lacks the password to obtain a fresh token. Password
        // reset is a shop-admin action in the backend UI only.
        return [
            'oxsheartbeat_api' => [
                'OXSHEARTBEAT_TOKEN_INVALIDATE',
            ],
        ];
    }
}
