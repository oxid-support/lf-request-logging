<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\LogSender\Framework;

use OxidEsales\GraphQL\Base\Framework\PermissionProviderInterface;

final class PermissionProvider implements PermissionProviderInterface
{
    public function getPermissions(): array
    {
        // Support-only operation: only oxsheartbeat_api over GraphQL. Shop admins use the
        // backend UI, not the API, so oxidadmin is intentionally not mapped (least privilege,
        // OXS-3050).
        return [
            'oxsheartbeat_api' => [
                'LOG_SENDER_VIEW',
            ],
        ];
    }
}
