<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\RequestLogger\Core;

use OxidEsales\EshopCommunity\Internal\Container\ContainerBuilderFactory;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserProvisioningServiceInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\SetupTokenServiceInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\TokenInvalidatorInterface;
use OxidSupport\Heartbeat\Module\Module;
use Psr\Container\ContainerInterface;

final class ModuleEvents
{
    /**
     * Called on module activation. Seeds the api user, its group and the group
     * membership for the current shop, then reconciles the setup token for that
     * shop (delegated to SetupTokenService). See OXS-3046 / OXS-3103.
     */
    public static function onActivate(): void
    {
        // Module activation intentionally does NOT run database migrations
        // (schema is an operator/pipeline concern, see OXS-3066). The module
        // ships no migrations anymore; the api user, its group and the group
        // membership are seeded below in the current shop context. See OXS-3046.
        // For the same reason the views are deliberately not regenerated here:
        // the module changes no schema, so there is nothing for DbMetaDataHandler
        // to pick up, and the call cost every activation a view rebuild per shop.
        // See OXS-3375.
        // The caches are not cleared here either: the core already invalidates
        // template, language, menu and module-variable caches on this very event,
        // right before it calls this hook (DispatchLegacyEventsSubscriber and
        // InvalidateModuleCacheEventSubscriber on FinalizingModuleActivationEvent),
        // and it clears more than the module ever did. See OXS-3376.

        $container = self::buildContainerWithModuleServices();

        // Create the api group, the service user and the group membership for
        // the current shop. Idempotent, runs on every activation, and replaces
        // the former data-seeding migration. This is the single creation path;
        // there is no migration to run first. See OXS-3046.
        $container->get(ApiUserProvisioningServiceInterface::class)->ensureApiUser();

        // Reconcile the setup token with this shop's service-user password:
        // a fresh per-shop token while the password is unset, cleared once it is
        // set. Shop-scoped, so EE subshops never share or retain the base shop's
        // inherited token (the only gate on the unauthenticated
        // heartbeatSetPassword mutation). See OXS-3103.
        $container->get(SetupTokenServiceInterface::class)->ensureSetupToken();
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

    /**
     * Compiles a fresh container from the current generated_services.yaml instead of
     * asking ContainerFactory for one.
     *
     * The activating request booted with the container that was cached while this
     * module was still inactive, so that container has none of the module's services.
     * The core resets ContainerFactory once the module's services.yaml is registered,
     * but the reset only deletes the cache file: FilesystemContainerCache::get() loads
     * the file with include_once, so when a concurrent request rewrites it before this
     * hook runs, this process gets a new instance of the stale ProjectServiceContainer
     * class declared at boot, and
     * "You have requested a non-existent service" follows. ContainerFactory::resetContainer()
     * right before getContainer() only narrows that window. Compiling here reads the
     * yaml directly and never touches the cache file or that class.
     */
    private static function buildContainerWithModuleServices(): ContainerInterface
    {
        $container = (new ContainerBuilderFactory())->create()->getContainer();
        $container->compile();

        return $container;
    }
}
