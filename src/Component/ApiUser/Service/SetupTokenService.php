<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiUser\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidSupport\Heartbeat\Module\Module;

/**
 * Owns the API-user setup-token lifecycle on module activation.
 *
 * The setup token is the only gate on the unauthenticated heartbeatSetPassword
 * mutation, so its state must follow THIS shop's service-user password, never a
 * value inherited from another shop. On EE a freshly created subshop inherits
 * the base shop's module settings (including this token), so the decision must
 * not trust an existing token value:
 *  - password not set  -> generate a fresh per-shop token (ignore any existing
 *    value, which may be the base shop's inherited token);
 *  - password set      -> setup is complete, clear any leftover/inherited token
 *    so it cannot be replayed against heartbeatSetPassword.
 * See OXS-3103.
 */
final class SetupTokenService implements SetupTokenServiceInterface
{
    public function __construct(
        private ApiUserStatusServiceInterface $apiUserStatusService,
        private TokenGeneratorInterface $tokenGenerator,
        private ModuleSettingServiceInterface $moduleSettingService,
    ) {
    }

    public function ensureSetupToken(): void
    {
        // Shop-scoped: isApiUserPasswordSet() checks THIS shop's service-user row
        // (mall users off) or the shared user (mall users on). See OXS-3046.
        if ($this->apiUserStatusService->isApiUserPasswordSet()) {
            $this->moduleSettingService->saveString(Module::SETTING_APIUSER_SETUP_TOKEN, '', Module::ID);
            return;
        }

        $token = $this->tokenGenerator->generate();
        $this->moduleSettingService->saveString(Module::SETTING_APIUSER_SETUP_TOKEN, $token, Module::ID);
    }
}
