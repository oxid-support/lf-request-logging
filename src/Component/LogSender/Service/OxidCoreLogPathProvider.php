<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\LogSender\Service;

use OxidSupport\Heartbeat\Component\LogSender\DataType\LogPath;
use OxidSupport\Heartbeat\Component\LogSender\DataType\LogPathType;
use OxidSupport\Heartbeat\Shop\Facade\ShopFacadeInterface;

/**
 * Built-in provider for OXID's core log file (oxideshop.log).
 *
 * This provider is always active and dynamically determines the log path
 * based on the shop's configuration (sShopDir from config.inc.php).
 */
final class OxidCoreLogPathProvider implements
    LogPathProviderInterface,
    InstallationWideLogPathProviderInterface
{
    private const LOG_FILENAME = 'oxideshop.log';

    public function __construct(
        private readonly ShopFacadeInterface $shopFacade,
    ) {
    }

    public function getLogPaths(): array
    {
        $logDirectory = $this->shopFacade->getLogsPath();

        return [
            new LogPath(
                path: $logDirectory . self::LOG_FILENAME,
                type: LogPathType::FILE,
                name: 'OXID eShop Log',
                description: 'Core application log file (errors, warnings, debug). Installation-wide: '
                    . 'one shared file for all subshops, not scoped to a single shop. See OXS-3130.',
            ),
        ];
    }

    public function getProviderId(): string
    {
        return 'oxid_core';
    }

    public function getProviderName(): string
    {
        return 'OXID Core';
    }

    public function getProviderDescription(): string
    {
        return 'OXID eShop core log file (oxideshop.log). Installation-wide, covers all subshops.';
    }

    public function isActive(): bool
    {
        // Always active - this is a core log that should always be available
        return true;
    }
}
