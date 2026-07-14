<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\RequestLogger\Service;

use OxidSupport\Heartbeat\Component\LogSender\DataType\LogPath;
use OxidSupport\Heartbeat\Component\LogSender\DataType\LogPathType;
use OxidSupport\Heartbeat\Component\LogSender\Service\LogPathProviderInterface;
use OxidSupport\Heartbeat\Shop\Facade\ModuleSettingFacadeInterface;
use OxidSupport\Heartbeat\Shop\Facade\ShopFacadeInterface;

/**
 * Provides log paths for the Request Logger component.
 *
 * Tagged with 'oxs.heartbeat.provider' to be discovered by LogCollectorService.
 */
class LogPathProvider implements LogPathProviderInterface
{
    public function __construct(
        private readonly ShopFacadeInterface $shopFacade,
        private readonly ModuleSettingFacadeInterface $moduleSettingFacade,
    ) {
    }

    public function getLogPaths(): array
    {
        // Only the current shop's subdirectory, from the shared single-source
        // builder LoggerFactory also writes to. A subshop's service user thus
        // only ever sees its own shop's files, never another subshop's. See OXS-3130.
        $logDirectory = RequestLogDirectory::forShop($this->shopFacade);

        return [
            new LogPath(
                path: $logDirectory,
                type: LogPathType::DIRECTORY,
                name: 'Request Logger Logs',
                description: "Log files containing this shop's recorded requests with correlation IDs",
                filePattern: '*.log',
            ),
        ];
    }

    public function getProviderId(): string
    {
        return 'requestlogger';
    }

    public function getProviderName(): string
    {
        return 'Request Logger';
    }

    public function getProviderDescription(): string
    {
        return 'Provides access to request log files that capture user interactions and shop requests.';
    }

    public function isActive(): bool
    {
        return $this->moduleSettingFacade->isRequestLoggerComponentActive();
    }
}
