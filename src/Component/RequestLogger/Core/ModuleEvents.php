<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\RequestLogger\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserProvisioningServiceInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserStatusServiceInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\TokenGeneratorInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\TokenInvalidatorInterface;
use OxidSupport\Heartbeat\Module\Module;

final class ModuleEvents
{
    /**
     * Called on module activation.
     * Generates a setup token only if:
     * - Token doesn't exist yet, AND
     * - Password is not yet set (still placeholder)
     *
     * This prevents generating a new token when reactivating after successful setup.
     */
    public static function onActivate(): void
    {
        // Module activation intentionally does NOT run database migrations
        // (schema is an operator/pipeline concern, see OXS-3066). The module
        // ships no migrations anymore; the api user, its group and the group
        // membership are seeded below in the current shop context. See OXS-3046.
        self::regenerateViews();
        self::clearCache();

        $container = ContainerFactory::getInstance()->getContainer();

        // Create the api group, the service user and the group membership for
        // the current shop. Idempotent, runs on every activation, and replaces
        // the former data-seeding migration. This is the single creation path;
        // there is no migration to run first. See OXS-3046.
        $container->get(ApiUserProvisioningServiceInterface::class)->ensureApiUser();

        $moduleSettingService = $container->get(ModuleSettingServiceInterface::class);

        try {
            $currentToken = (string) $moduleSettingService->getString(Module::SETTING_APIUSER_SETUP_TOKEN, Module::ID);
        } catch (\Throwable $e) {
            $currentToken = '';
        }

        if (!empty($currentToken)) {
            return;
        }

        // Shop-aware (mall users): with mall users off the service user is per
        // subshop, so this must check THIS shop's row, not any row with the
        // service-user name. A global check let a subshop skip token generation
        // because another shop's password was already set. See OXS-3046.
        if ($container->get(ApiUserStatusServiceInterface::class)->isApiUserPasswordSet()) {
            return;
        }

        // Use the shared CSPRNG-backed generator, not OXID's md5(uniqid())
        // generateUId(): this token is the only gate on the unauthenticated
        // heartbeatSetPassword mutation.
        $token = $container->get(TokenGeneratorInterface::class)->generate();
        $moduleSettingService->saveString(Module::SETTING_APIUSER_SETUP_TOKEN, $token, Module::ID);
    }

    /**
     * Called on module deactivation.
     * Invalidates all JWTs of the heartbeat-api service user so that no stale
     * token can keep the dormant module accessible from outside. See OXS-3054.
     */
    public static function onDeactivate(): void
    {
        try {
            $container = ContainerFactory::getInstance()->getContainer();
            $tokenInvalidator = $container->get(TokenInvalidatorInterface::class);
            $tokenInvalidator->invalidateForApiUser();
        } catch (\Throwable $e) {
            // Module is being deactivated. Swallow lookup failures (e.g. service
            // not registered, api user missing) because we must not block the
            // deactivation flow itself. The tokens become useless without the
            // module routes anyway.
        }
    }

    private static function regenerateViews(): void
    {
        oxNew(\OxidEsales\Eshop\Core\DbMetaDataHandler::class)->updateViews();
    }

    private static function clearCache(): void
    {
        $tmpDir = realpath(Registry::getConfig()->getConfigParam('sCompileDir'));

        Registry::getUtils()->commitFileCache();

        $files = array_merge(
            glob($tmpDir . '/smarty/*.php') ?: [],
            glob($tmpDir . '/*.txt') ?: []
        );
        array_map('unlink', $files);
    }
}
