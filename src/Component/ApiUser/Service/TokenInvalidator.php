<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiUser\Service;

use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Exception\UserNotFoundException;
use OxidSupport\Heartbeat\Module\Module;
use OxidSupport\Heartbeat\Shop\Facade\ShopFacadeInterface;

final class TokenInvalidator implements TokenInvalidatorInterface
{
    public function __construct(
        private readonly QueryBuilderFactoryInterface $queryBuilderFactory,
        private readonly ShopFacadeInterface $shopFacade,
    ) {
    }

    public function invalidateForApiUser(): int
    {
        // Look up the heartbeat-api user id directly via SQL. We do not depend on
        // ApiUserService here because ApiUserService itself injects this invalidator
        // and a circular dependency would result. See OXS-3054.
        $userIdQb = $this->queryBuilderFactory->create();
        $userIdQb
            ->select('OXID')
            ->from('oxuser')
            ->where('OXUSERNAME = :email')
            ->setParameter('email', Module::API_USER_EMAIL);
        $this->scopeToCurrentShop($userIdQb);

        $userId = $userIdQb->execute()->fetchOne(); // @phpstan-ignore method.nonObject

        if (!$userId) {
            throw new UserNotFoundException();
        }

        // Direct DELETE on oegraphqltoken. We intentionally bypass graphql-base's
        // TokenAdministration::customerTokensDelete because that one requires a
        // GraphQL request context (authenticated UserInterface) which we do not
        // have here. The oegraphqltoken table has been stable across graphql-base
        // v6 through v12. See OXS-3054.
        $deleteQb = $this->queryBuilderFactory->create();
        $deleteQb
            ->delete('oegraphqltoken')
            ->where('OXUSERID = :userId')
            ->setParameter('userId', $userId);

        $result = $deleteQb->execute();

        return is_object($result) ? (int) $result->rowCount() : (int) $result;
    }

    /**
     * With mall users off the service user is per subshop, so invalidation must
     * target THIS shop's row (its tokens), not another shop's. With mall users
     * on the single shared row applies. See OXS-3046.
     */
    private function scopeToCurrentShop(QueryBuilder $queryBuilder): void
    {
        if (!$this->shopFacade->areMallUsersEnabled()) {
            $queryBuilder
                ->andWhere('OXSHOPID = :shopId')
                ->setParameter('shopId', $this->shopFacade->getShopId());
        }
    }
}
