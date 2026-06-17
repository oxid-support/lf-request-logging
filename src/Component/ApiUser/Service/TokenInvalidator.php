<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiUser\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Exception\UserNotFoundException;
use OxidSupport\Heartbeat\Module\Module;

final class TokenInvalidator implements TokenInvalidatorInterface
{
    private QueryBuilderFactoryInterface $queryBuilderFactory;

    public function __construct(QueryBuilderFactoryInterface $queryBuilderFactory)
    {
        $this->queryBuilderFactory = $queryBuilderFactory;
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
}
