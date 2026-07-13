<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiUser\Controller\GraphQL;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidSupport\Heartbeat\Module\Module;
use OxidSupport\Heartbeat\Component\ApiUser\Exception\InvalidTokenException;
use OxidSupport\Heartbeat\Component\ApiUser\Exception\PasswordTooShortException;
use OxidSupport\Heartbeat\Component\ApiUser\Exception\SetPasswordFailedException;
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserServiceInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserStatusServiceInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\TokenInvalidatorInterface;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Right;

final class PasswordController
{
    public function __construct(
        private ApiUserServiceInterface $apiUserService,
        private ModuleSettingServiceInterface $moduleSettingService,
        private TokenInvalidatorInterface $tokenInvalidator,
        private ApiUserStatusServiceInterface $apiUserStatusService,
    ) {
    }

    /**
     * Set the password for the Heartbeat API user.
     * Requires a valid setup token. Token is invalidated after use.
     */
    #[Mutation]
    public function heartbeatSetPassword(string $token, string $password): bool
    {
        // No separate "setup available" check: validateToken already throws the
        // generic InvalidTokenException for both an empty stored token and a
        // wrong token, so an unauthenticated caller cannot tell whether a setup
        // is pending (closes the setup-status oracle).
        $this->validateToken($token);
        $this->validatePassword($password);

        // Defense in depth for EE subshops: once this shop's service user has a
        // usable password, setup is complete and no token may set it again, even
        // one that matches the stored value (e.g. a token inherited from the base
        // shop that onActivate has not cleared yet). Same generic error as an
        // invalid token, so this adds no setup-status oracle. See OXS-3103.
        if ($this->apiUserStatusService->isApiUserPasswordSet()) {
            throw new InvalidTokenException();
        }

        // Security: Clear token BEFORE setting password to prevent race conditions (TOCTOU)
        // This ensures a second concurrent request with the same token will fail validation
        $this->moduleSettingService->saveString(Module::SETTING_APIUSER_SETUP_TOKEN, '', Module::ID);

        // OXS-3068: the token clear above and the password set below are not transactional.
        // If setPasswordForApiUser throws (e.g. an internal token-table write hits a missing
        // migration), the cleared token would be gone while the password stays unset, locking
        // the service user out with no way to retry. Restore the token on failure so the setup
        // stays retryable. The provided token equals the stored one (validateToken/hash_equals),
        // so it can be restored verbatim.
        try {
            $this->apiUserService->setPasswordForApiUser($password);
        } catch (\Throwable $e) {
            $this->moduleSettingService->saveString(Module::SETTING_APIUSER_SETUP_TOKEN, $token, Module::ID);
            throw new SetPasswordFailedException($e);
        }

        return true;
    }

    /**
     * Revoke all JWTs of the heartbeat-api service user (leak-response kill
     * switch). Callable by the service user itself so OXID Support can revoke a
     * leaked token remotely. It does not touch the password, and the service
     * user cannot reset the password, so a stolen token cannot be used to
     * re-establish access after this call.
     *
     * @return int number of tokens deleted
     */
    #[Mutation]
    #[Logged]
    #[Right('OXSHEARTBEAT_TOKEN_INVALIDATE')]
    public function heartbeatInvalidateTokens(): int
    {
        return $this->tokenInvalidator->invalidateForApiUser();
    }

    private function validateToken(string $token): void
    {
        try {
            $storedToken = (string) $this->moduleSettingService->getString(
                Module::SETTING_APIUSER_SETUP_TOKEN,
                Module::ID
            );
        } catch (\Throwable) {
            throw new InvalidTokenException();
        }

        // Security: Use constant-time comparison to prevent timing attacks
        if (empty($storedToken) || !hash_equals($storedToken, $token)) {
            throw new InvalidTokenException();
        }
    }

    private function validatePassword(string $password): void
    {
        // 12 char minimum for a service account with full API access.
        if (strlen($password) < 12) {
            throw new PasswordTooShortException();
        }
    }
}
