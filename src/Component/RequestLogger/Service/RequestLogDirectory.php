<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\RequestLogger\Service;

use OxidSupport\Heartbeat\Shop\Facade\ShopFacadeInterface;

/**
 * Single source of truth for the per-shop request-log directory.
 *
 * The log is WRITTEN by LoggerFactory and SERVED by LogPathProvider (Log
 * Sender); both derive the directory from here so the two can never drift to
 * different locations. Shop-scoped: `<logsPath>/oxs-request-logger/<shopId>/`,
 * so EE subshops never share one flat directory (on CE the shop id is always 1,
 * a no-op). See OXS-3130.
 */
final class RequestLogDirectory
{
    /** Directory-name segment; also the log filename prefix. */
    public const NAME = 'oxs-request-logger';

    public static function forShop(ShopFacadeInterface $shopFacade): string
    {
        return $shopFacade->getLogsPath()
            . self::NAME . DIRECTORY_SEPARATOR
            . $shopFacade->getShopId() . DIRECTORY_SEPARATOR;
    }
}
