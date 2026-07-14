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

    private const LOG_DIRECTORY_NAME = 'oxs-request-logger';

    public function getLogPaths(): array
    {
        // Only the current shop's subdirectory, mirroring how LoggerFactory
        // writes (oxs-request-logger/<shopId>/). This is the single shop-scoping
        // point for the request logs: a subshop's service user only ever sees
        // its own shop's files, never another subshop's. See OXS-3130.
        $logDirectory = $this->shopFacade->getLogsPath()
            . self::LOG_DIRECTORY_NAME . DIRECTORY_SEPARATOR
            . $this->shopFacade->getShopId() . DIRECTORY_SEPARATOR;

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
