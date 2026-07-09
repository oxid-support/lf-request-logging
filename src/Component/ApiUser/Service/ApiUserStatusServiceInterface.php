<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiUser\Service;

/**
 * Interface for API User status service.
 * Checks whether the API user is properly set up.
 */
interface ApiUserStatusServiceInterface
{
    /**
     * Check if the API user exists in the database.
     */
    public function isApiUserCreated(): bool;

    /**
     * Check if the API user has a valid password set.
     * Returns true if password is NOT the placeholder (setup completed).
     */
    public function isApiUserPasswordSet(): bool;

    /**
     * Check if the complete API User setup is done.
     * This means: user created AND password set.
     */
    public function isSetupComplete(): bool;
}
