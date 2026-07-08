<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\RequestLogger\Framework;

use OxidEsales\GraphQL\Base\Framework\PermissionProviderInterface;

final class PermissionProvider implements PermissionProviderInterface
{
    public function getPermissions(): array
    {
        // Support-only operations: only the oxsheartbeat_api service user may call these
        // over GraphQL. Shop admins configure via the backend UI, not the API, so oxidadmin
        // is intentionally not mapped here (least privilege, OXS-3050).
        return [
            'oxsheartbeat_api' => [
                'REQUEST_LOGGER_VIEW',
                'REQUEST_LOGGER_CHANGE',
            ],
        ];
    }
}
