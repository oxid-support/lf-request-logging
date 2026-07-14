<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\LogSender\Service;

/**
 * Marker for log path providers whose files are installation-wide, i.e. shared
 * by every subshop rather than scoped to one shop (e.g. the core oxideshop.log).
 *
 * A separate marker interface, not a method on LogPathProviderInterface, so that
 * third-party providers implementing the public provider interface do not break.
 * Installation-wide sources are only exposed to the base shop's service user, so
 * a subshop's service user cannot read cross-shop log data. See OXS-3132.
 */
interface InstallationWideLogPathProviderInterface
{
}
