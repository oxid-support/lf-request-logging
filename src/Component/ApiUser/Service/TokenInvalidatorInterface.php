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
 * Both methods only ever touch the heartbeat-api user's tokens: the id-based
 * variant verifies the given id belongs to that user before deleting, so no
 * caller can wipe another user's tokens through this service. See OXS-3054.
 */
interface TokenInvalidatorInterface
{
    /**
     * Invalidates tokens for the heartbeat-api user resolved via the current
     * shop scope (per-subshop user under EE mall-users-off).
     *
     * @return int number of tokens deleted
     */
    public function invalidateForApiUser(): int;

    /**
     * Invalidates tokens for the exact heartbeat-api user row identified by
     * $userId, regardless of the current shop context. Used by the User-model
     * save hook, which must invalidate the row that was actually saved, not a
     * scope-resolved one (a malladmin may edit a foreign subshop's service user
     * from another shop's context). No-op resolution to a foreign row cannot
     * leak: the id is verified to belong to the heartbeat-api user. See OXS-3133.
     *
     * @return int number of tokens deleted
     */
    public function invalidateForUserId(string $userId): int;
}
