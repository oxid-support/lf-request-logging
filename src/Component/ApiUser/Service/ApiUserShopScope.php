<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiUser\Service;

use Doctrine\DBAL\Query\QueryBuilder;
use OxidSupport\Heartbeat\Shop\Facade\ShopFacadeInterface;

/**
 * Single source of the api service user's shop-scoping rule, so it is not
 * duplicated across the services that look the user up. See OXS-3046.
 */
final class ApiUserShopScope implements ApiUserShopScopeInterface
{
    public function __construct(
        private readonly ShopFacadeInterface $shopFacade,
    ) {
    }

    public function restrictToCurrentShop(QueryBuilder $queryBuilder): void
    {
        if (!$this->shopFacade->areMallUsersEnabled()) {
            $queryBuilder
                ->andWhere('OXSHOPID = :shopId')
                ->setParameter('shopId', $this->shopFacade->getShopId());
        }
    }
}
