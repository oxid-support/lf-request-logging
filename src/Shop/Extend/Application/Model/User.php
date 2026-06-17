<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Shop\Extend\Application\Model;

use OxidEsales\Eshop\Application\Model\User as EshopUser;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidSupport\Heartbeat\Component\ApiUser\Service\TokenInvalidatorInterface;
use OxidSupport\Heartbeat\Module\Module;

/**
 * Override of OXID's User model to invalidate Heartbeat JWTs whenever a
 * security-relevant field of the heartbeat-api service user changes in the
 * OXID admin area (password, login-email, active flag). Cosmetic edits
 * like first/last name or address fields do not affect the session, so we
 * only invalidate on the fields that actually authorise the session.
 * See OXS-3060.
 *
 * Group membership lives in oxobject2group, not on this model. A pure
 * group change is not covered here; OXS-3060-Followup would catch that.
 *
 * @mixin EshopUser
 */
// phpcs:disable PSR1.Files.SideEffects
class User extends User_parent
{
    /**
     * Fields whose change requires immediate JWT invalidation for the
     * heartbeat-api service user. Anything outside this list (first name,
     * address, etc.) is treated as cosmetic and does not touch sessions.
     */
    private const SECURITY_FIELDS = ['oxpassword', 'oxusername', 'oxactive'];

    public function save()
    {
        $shouldInvalidate = $this->isHeartbeatApiUser()
            && $this->hasSecurityRelevantChange();

        $result = parent::save();

        if ($shouldInvalidate) {
            $this->invalidateHeartbeatTokens();
        }

        return $result;
    }

    private function isHeartbeatApiUser(): bool
    {
        $field = 'oxuser__oxusername';
        if (!isset($this->$field) || $this->$field === null) {
            return false;
        }

        $value = $this->$field->value ?? '';

        return $value === Module::API_USER_EMAIL;
    }

    /**
     * Compares the in-memory state of this user to its persisted state.
     * Returns true only if at least one security-relevant column will change
     * on the next parent::save().
     */
    private function hasSecurityRelevantChange(): bool
    {
        $id = $this->getId();
        if (!$id) {
            // Creating the user (no row in DB yet). The activation hook may
            // create the service account on first run; that path is fine.
            return false;
        }

        $oldUser = oxNew(EshopUser::class);
        if (!$oldUser->load($id)) {
            return false;
        }

        foreach (self::SECURITY_FIELDS as $field) {
            $key = 'oxuser__' . $field;
            $oldValue = isset($oldUser->$key) ? (string) ($oldUser->$key->value ?? '') : '';
            $newValue = isset($this->$key) ? (string) ($this->$key->value ?? '') : '';
            if ($oldValue !== $newValue) {
                return true;
            }
        }

        return false;
    }

    private function invalidateHeartbeatTokens(): void
    {
        try {
            $container = ContainerFactory::getInstance()->getContainer();
            $invalidator = $container->get(TokenInvalidatorInterface::class);
            $invalidator->invalidateForApiUser();
        } catch (\Throwable $e) {
            // Swallow: container may not be fully bootstrapped during early
            // save calls (e.g. user creation in the activation hook), and we
            // must never block the parent save. Tokens become useless on the
            // next request anyway because graphql-base re-checks credentials.
        }
    }
}
