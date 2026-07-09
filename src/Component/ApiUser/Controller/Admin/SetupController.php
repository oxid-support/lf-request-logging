<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiUser\Controller\Admin;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidSupport\Heartbeat\Module\Module;
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserServiceInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserStatusServiceInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\TokenInvalidatorInterface;
use OxidSupport\Heartbeat\Shared\Controller\Admin\AbstractComponentController;

/**
 * API User setup controller for the Heartbeat.
 * Displays the setup workflow for API user configuration.
 */
class SetupController extends AbstractComponentController
{
    protected $_sThisTemplate = 'heartbeat_apiuser_setup.tpl';

    private const GRAPHQL_BASE_MODULE_ID = 'oe_graphql_base';

    private ?ApiUserServiceInterface $apiUserService = null;
    private ?ApiUserStatusServiceInterface $apiUserStatusService = null;
    private ?TokenInvalidatorInterface $tokenInvalidator = null;

    /**
     * API User is "active" when setup is complete.
     */
    public function isComponentActive(): bool
    {
        return $this->isSetupComplete();
    }

    /**
     * Custom status text: setup required when not complete.
     */
    public function getStatusTextKey(): string
    {
        return $this->isSetupComplete()
            ? 'OXSHEARTBEAT_APIUSER_STATUS_READY'
            : 'OXSHEARTBEAT_APIUSER_STATUS_SETUP_REQUIRED';
    }

    /**
     * Get the setup token.
     */
    public function getSetupToken(): string
    {
        return (string) $this->getModuleSettingService()->get(
            Module::SETTING_APIUSER_SETUP_TOKEN,
            Module::ID
        );
    }

    /**
     * Check if the Heartbeat module is activated.
     */
    public function isHeartbeatModuleActivated(): bool
    {
        return $this->isModuleActivated(Module::ID);
    }

    /**
     * Check if GraphQL Base module is activated.
     */
    public function isGraphqlBaseActivated(): bool
    {
        return $this->isModuleActivated(self::GRAPHQL_BASE_MODULE_ID);
    }

    /**
     * Check if the API user exists.
     */
    public function isApiUserCreated(): bool
    {
        try {
            return $this->getApiUserStatusService()->isApiUserCreated();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if the API user password is set (setup complete).
     */
    public function isApiUserPasswordSet(): bool
    {
        try {
            return $this->getApiUserStatusService()->isApiUserPasswordSet();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if the complete setup is done.
     */
    public function isSetupComplete(): bool
    {
        try {
            return $this->getApiUserStatusService()->isSetupComplete();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Reset the API user password (regenerate setup token).
     */
    public function resetPassword(): void
    {
        // Generate a new setup token
        $newToken = bin2hex(random_bytes(32));

        // Reset the API user password to placeholder
        try {
            $this->getApiUserService()->resetPasswordForApiUser();
        } catch (\Exception $e) {
            // User might not exist yet, ignore
        }

        $this->getModuleSettingService()->save(
            Module::SETTING_APIUSER_SETUP_TOKEN,
            $newToken,
            Module::ID
        );
    }

    protected function getApiUserService(): ApiUserServiceInterface
    {
        if ($this->apiUserService === null) {
            $this->apiUserService = ContainerFactory::getInstance()
                ->getContainer()
                ->get(ApiUserServiceInterface::class);
        }
        return $this->apiUserService; // @phpstan-ignore return.type
    }

    protected function getApiUserStatusService(): ApiUserStatusServiceInterface
    {
        if ($this->apiUserStatusService === null) {
            $this->apiUserStatusService = ContainerFactory::getInstance()
                ->getContainer()
                ->get(ApiUserStatusServiceInterface::class);
        }
        return $this->apiUserStatusService; // @phpstan-ignore return.type
    }

    /**
     * Invalidate all JWTs of the heartbeat-api service user without resetting
     * the password. Used when the admin suspects a token leak but wants to keep
     * the existing service password. See OXS-3058.
     */
    public function invalidateTokens(): void
    {
        try {
            $this->getTokenInvalidator()->invalidateForApiUser();
        } catch (\Exception $e) {
            // Swallow: service user might not exist yet. The button is only
            // exposed after setup is complete, so this is defensive only.
        }
    }

    protected function getTokenInvalidator(): TokenInvalidatorInterface
    {
        if ($this->tokenInvalidator === null) {
            $this->tokenInvalidator = ContainerFactory::getInstance()
                ->getContainer()
                ->get(TokenInvalidatorInterface::class);
        }
        return $this->tokenInvalidator; // @phpstan-ignore return.type
    }
}
