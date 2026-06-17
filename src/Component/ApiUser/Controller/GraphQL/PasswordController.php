<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiUser\Controller\GraphQL;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;
use OxidSupport\Heartbeat\Module\Module;
use OxidSupport\Heartbeat\Component\ApiUser\Exception\InvalidTokenException;
use OxidSupport\Heartbeat\Component\ApiUser\Exception\PasswordTooShortException;
use OxidSupport\Heartbeat\Component\ApiUser\Exception\SetupNotAvailableException;
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserServiceInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\TokenGeneratorInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\TokenInvalidatorInterface;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Right;

final class PasswordController
{
    private ApiUserServiceInterface $apiUserService;
    private ModuleSettingBridgeInterface $moduleSettingService;
    private TokenGeneratorInterface $tokenGenerator;
    private TokenInvalidatorInterface $tokenInvalidator;

    public function __construct(
        ApiUserServiceInterface $apiUserService,
        ModuleSettingBridgeInterface $moduleSettingService,
        TokenGeneratorInterface $tokenGenerator,
        TokenInvalidatorInterface $tokenInvalidator
    ) {
        $this->apiUserService = $apiUserService;
        $this->moduleSettingService = $moduleSettingService;
        $this->tokenGenerator = $tokenGenerator;
        $this->tokenInvalidator = $tokenInvalidator;
    }

    /**
     * Set the password for the Heartbeat API user.
     * Requires a valid setup token. Token is invalidated after use.
     *
     * @Mutation
     */
    public function heartbeatSetPassword(string $token, string $password): bool
    {
        $this->assertSetupAvailable();
        $this->validateToken($token);
        $this->validatePassword($password);

        // Security: Clear token BEFORE setting password to prevent race conditions (TOCTOU)
        // This ensures a second concurrent request with the same token will fail validation
        $this->moduleSettingService->save(Module::SETTING_APIUSER_SETUP_TOKEN, '', Module::ID);

        // Delegate to service
        $this->apiUserService->setPasswordForApiUser($password);

        return true;
    }

    /**
     * Reset the password for the Heartbeat API user to a placeholder value.
     * This generates a new setup token that can be used with heartbeatSetPassword.
     * Requires admin authentication.
     *
     * @Mutation
     * @Logged
     * @Right(name="OXSHEARTBEAT_PASSWORD_RESET")
     */
    public function heartbeatResetPassword(): string
    {
        // Generate new setup token
        $token = $this->tokenGenerator->generate();

        // Delegate to service
        $this->apiUserService->resetPasswordForApiUser();

        // Save token
        $this->moduleSettingService->save(Module::SETTING_APIUSER_SETUP_TOKEN, $token, Module::ID);

        return $token;
    }

    /**
     * Terminate all active sessions of the Heartbeat API user without resetting
     * its password. Emergency revocation endpoint. See OXS-3059.
     *
     * @Mutation
     * @Logged
     * @Right(name="OXSHEARTBEAT_TOKEN_INVALIDATE")
     *
     * @return int number of tokens deleted
     */
    public function heartbeatInvalidateTokens(): int
    {
        return $this->tokenInvalidator->invalidateForApiUser();
    }

    /**
     * Assert that setup is available (token exists).
     */
    private function assertSetupAvailable(): void
    {
        try {
            $storedToken = (string) $this->moduleSettingService->get(
                Module::SETTING_APIUSER_SETUP_TOKEN,
                Module::ID
            );
        } catch (\Throwable $e) {
            throw new SetupNotAvailableException();
        }

        if (empty($storedToken)) {
            throw new SetupNotAvailableException();
        }
    }

    private function validateToken(string $token): void
    {
        try {
            $storedToken = (string) $this->moduleSettingService->get(
                Module::SETTING_APIUSER_SETUP_TOKEN,
                Module::ID
            );
        } catch (\Throwable $e) {
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
