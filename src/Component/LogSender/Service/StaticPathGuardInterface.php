<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\LogSender\Service;

/**
 * Validates admin-configured static log paths before they are stored or served.
 *
 * The static-paths feature lets an admin point the Log Sender at arbitrary files.
 * Without validation a subshop admin could point it at another shop's per-shop
 * request logs (OXS-3130) or at a source/config file, turning a per-shop setting
 * into a cross-shop or arbitrary file read for the shop's service user. See OXS-3131.
 */
interface StaticPathGuardInterface
{
    /**
     * Whether the given path may be exposed to the current shop's service user.
     */
    public function isAllowed(string $path): bool;
}
