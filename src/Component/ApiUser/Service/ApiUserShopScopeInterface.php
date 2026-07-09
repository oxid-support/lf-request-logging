<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiUser\Service;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Single source of the api service user's shop-scoping rule (EE multishop).
 * Used by every service that looks the user up, so the rule lives in one place.
 */
interface ApiUserShopScopeInterface
{
    /**
     * Restrict an `oxuser` lookup to the current shop when mall users are off
     * (the service user is per subshop then, unique key OXUSERNAME+OXSHOPID).
     * With mall users on the single shared row applies, so no constraint is
     * added. See OXS-3046.
     */
    public function restrictToCurrentShop(QueryBuilder $queryBuilder): void;
}
