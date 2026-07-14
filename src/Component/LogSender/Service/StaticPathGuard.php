<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\LogSender\Service;

use OxidSupport\Heartbeat\Component\RequestLogger\Service\RequestLogDirectory;
use OxidSupport\Heartbeat\Shop\Facade\ShopFacadeInterface;

final class StaticPathGuard implements StaticPathGuardInterface
{
    /**
     * File types the Log Sender must never serve: it is for log files, not source
     * or configuration. Blocking these blunts the arbitrary-file-read angle even
     * for paths outside the request-log tree. See OXS-3131.
     */
    private const BLOCKED_EXTENSIONS = ['php', 'phtml', 'inc', 'yml', 'yaml', 'env', 'sql', 'key', 'pem'];

    private ShopFacadeInterface $shopFacade;

    public function __construct(ShopFacadeInterface $shopFacade)
    {
        $this->shopFacade = $shopFacade;
    }

    public function isAllowed(string $path): bool
    {
        $trimmed = rtrim($path, '/\\');
        if ($trimmed === '') {
            return false;
        }

        $extension = strtolower(pathinfo($trimmed, PATHINFO_EXTENSION));
        if (in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            return false;
        }

        // Canonicalize to defeat traversal (../). A non-existent path cannot be
        // resolved; fall back to the raw string so a path textually pointing into
        // a foreign shop's tree is still rejected (existence is re-checked at read).
        $resolved = realpath($trimmed);
        $candidate = ($resolved === false ? $trimmed : $resolved) . DIRECTORY_SEPARATOR;

        $requestLogBase = realpath($this->shopFacade->getLogsPath() . RequestLogDirectory::NAME);
        if ($requestLogBase === false) {
            // The per-shop request-log tree does not exist yet: nothing to leak.
            return true;
        }
        $requestLogBase .= DIRECTORY_SEPARATOR;

        // Outside the request-log tree entirely: not a cross-shop concern here.
        if (strpos($candidate, $requestLogBase) !== 0) {
            return true;
        }

        // Inside the request-log tree: allow only the current shop's own subdir,
        // never a sibling subshop's logs. See OXS-3130 / OXS-3131.
        $ownDir = realpath(RequestLogDirectory::forShop($this->shopFacade));
        if ($ownDir === false) {
            return false;
        }
        $ownDir .= DIRECTORY_SEPARATOR;

        return strpos($candidate, $ownDir) === 0;
    }
}
