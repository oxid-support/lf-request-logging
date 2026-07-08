<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\DiagnosticsProvider\Framework;

use OxidEsales\GraphQL\Base\Framework\PermissionProviderInterface;

final class PermissionProvider implements PermissionProviderInterface
{
    public function getPermissions(): array
    {
        // Dedicated diagnostics right, separate from LOG_SENDER_VIEW, so
        // "read diagnostics" and "read logs" can be granted independently.
        // Support-only: only oxsheartbeat_api over GraphQL. Shop admins use the backend UI,
        // so oxidadmin is intentionally not mapped (least privilege, OXS-3050).
        return [
            'oxsheartbeat_api' => [
                'DIAGNOSTICS_VIEW',
            ],
        ];
    }
}
