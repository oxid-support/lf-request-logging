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
use OxidSupport\Heartbeat\Component\ApiUser\Exception\SetupNotAvailableException;
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserServiceInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\TokenGeneratorInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\TokenInvalidatorInterface;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Right;

final class PasswordController
{
    public function __construct(
        private ApiUserServiceInterface $apiUserService,
        private ModuleSettingServiceInterface $moduleSettingService,
        private TokenGeneratorInterface $tokenGenerator,
        private TokenInvalidatorInterface $tokenInvalidator,
    ) {
    }

    /**
     * Set the password for the Heartbeat API user.
     * Requires a valid setup token. Token is invalidated after use.
     */
    #[Mutation]
    public function heartbeatSetPassword(string $token, string $password): bool
    {
        $this->assertSetupAvailable();
        $this->validateToken($token);
        $this->validatePassword($password);

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
     * Invalidate all JWTs of the heartbeat-api service user without resetting
     * the password. Useful when the service password should remain intact and
     * the existing JWTs should still be revoked. See OXS-3059.
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

    /**
     * Reset the password for the Heartbeat API user to a placeholder value.
     * This generates a new setup token that can be used with heartbeatSetPassword.
     * Requires admin authentication.
     */
    #[Mutation]
    #[Logged]
    #[Right('OXSHEARTBEAT_PASSWORD_RESET')]
    public function heartbeatResetPassword(): string
    {
        // Generate new setup token
        $token = $this->tokenGenerator->generate();

        // Delegate to service
        $this->apiUserService->resetPasswordForApiUser();

        // Save token
        $this->moduleSettingService->saveString(Module::SETTING_APIUSER_SETUP_TOKEN, $token, Module::ID);

        return $token;
    }

    /**
     * Assert that setup is available (token exists).
     */
    private function assertSetupAvailable(): void
    {
        try {
            $storedToken = (string) $this->moduleSettingService->getString(
                Module::SETTING_APIUSER_SETUP_TOKEN,
                Module::ID
            );
        } catch (\Throwable) {
            throw new SetupNotAvailableException();
        }

        if (empty($storedToken)) {
            throw new SetupNotAvailableException();
        }
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
        if (strlen($password) < 8) {
            throw new PasswordTooShortException();
        }
    }
}
