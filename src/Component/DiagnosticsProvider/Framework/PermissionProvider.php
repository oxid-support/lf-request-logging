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
        return [
            // Dedicated diagnostics right, separate from LOG_SENDER_VIEW, so
            // "read diagnostics" and "read logs" can be granted independently.
            'oxsheartbeat_api' => [
                'DIAGNOSTICS_VIEW',
            ],
            'oxidadmin' => [
                'DIAGNOSTICS_VIEW',
            ],
        ];
    }
}
