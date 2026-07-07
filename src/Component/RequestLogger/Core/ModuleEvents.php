<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\RequestLogger\Core;

use OxidEsales\DoctrineMigrationWrapper\MigrationsBuilder;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\TokenGeneratorInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\TokenInvalidatorInterface;
use OxidSupport\Heartbeat\Module\Module;
use Symfony\Component\Console\Output\BufferedOutput;

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
        self::executeModuleMigrations();
        self::regenerateViews();
        self::clearCache();

        $container = ContainerFactory::getInstance()->getContainer();
        $moduleSettingService = $container->get(ModuleSettingServiceInterface::class);

        try {
            $currentToken = (string) $moduleSettingService->getString(Module::SETTING_APIUSER_SETUP_TOKEN, Module::ID);
        } catch (\Throwable $e) {
            $currentToken = '';
        }

        if (!empty($currentToken)) {
            return;
        }

        if (self::isPasswordAlreadySet($container)) {
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

    private static function executeModuleMigrations(): void
    {
        $migrations = (new MigrationsBuilder())->build();
        $output = new BufferedOutput();
        $migrations->setOutput($output);
        if ($migrations->execute('migrations:up-to-date', Module::ID)) {
            $migrations->execute('migrations:migrate', Module::ID);
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

    private static function isPasswordAlreadySet(\Psr\Container\ContainerInterface $container): bool
    {
        try {
            $queryBuilderFactory = $container->get(QueryBuilderFactoryInterface::class);
            $queryBuilder = $queryBuilderFactory->create();
            $queryBuilder
                ->select('OXPASSWORD')
                ->from('oxuser')
                ->where('OXUSERNAME = :email')
                ->setParameter('email', Module::API_USER_EMAIL);

            $password = $queryBuilder->execute()->fetchOne();

            return $password && str_starts_with($password, '$');
        } catch (\Throwable $e) {
            return false;
        }
    }
}
