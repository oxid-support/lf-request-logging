<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiUser\Service;

interface SetupTokenServiceInterface
{
    /**
     * Reconcile this shop's setup token with its service-user password state:
     * generate a fresh per-shop token while the password is not yet set, and
     * clear any (possibly inherited) token once the password is set.
     */
    public function ensureSetupToken(): void;
}
