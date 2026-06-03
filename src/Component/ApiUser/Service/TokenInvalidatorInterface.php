<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiUser\Service;

/**
 * Invalidates all graphql-base JWTs for the heartbeat-api service user.
 *
 * Hardcoded to the heartbeat-api user. There is intentionally no userId parameter
 * so no caller can wipe other users' tokens through this service. See OXS-3054.
 */
interface TokenInvalidatorInterface
{
    /**
     * @return int number of tokens deleted
     */
    public function invalidateForApiUser(): int;
}
