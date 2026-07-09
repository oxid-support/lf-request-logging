<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiUser\Service;

/**
 * Provisions the Heartbeat API service user, its group and the group
 * membership for the current shop. Idempotent: safe to call on every
 * module activation.
 */
interface ApiUserProvisioningServiceInterface
{
    /**
     * Ensure the api group, the api service user and the user's membership in
     * the api group exist for the current shop. A no-op if everything is
     * already in place.
     */
    public function ensureApiUser(): void;
}
