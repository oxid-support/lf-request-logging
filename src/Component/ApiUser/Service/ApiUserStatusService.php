<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiUser\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidSupport\Heartbeat\Module\Module;

/**
 * Service to check the setup status of the API user.
 * Checks actual database state rather than assuming based on module activation.
 *
 * Shop-scope aware via ApiUserShopScope, consistent with the other api-user
 * services: with mall users off the service user is per subshop, so the status
 * must reflect the CURRENT shop's row, not any row with that username. See
 * OXS-3046.
 */
final class ApiUserStatusService implements ApiUserStatusServiceInterface
{
    private QueryBuilderFactoryInterface $queryBuilderFactory;
    private ApiUserShopScopeInterface $apiUserShopScope;

    public function __construct(
        QueryBuilderFactoryInterface $queryBuilderFactory,
        ApiUserShopScopeInterface $apiUserShopScope
    ) {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->apiUserShopScope = $apiUserShopScope;
    }

    public function isApiUserCreated(): bool
    {
        try {
            $queryBuilder = $this->queryBuilderFactory->create();
            $queryBuilder
                ->select('COUNT(*)')
                ->from('oxuser')
                ->where('OXUSERNAME = :email')
                ->setParameter('email', Module::API_USER_EMAIL);
            $this->apiUserShopScope->restrictToCurrentShop($queryBuilder);

            return (int) $queryBuilder->execute()->fetchOne() > 0; // @phpstan-ignore method.nonObject
        } catch (\Exception $e) {
            return false;
        }
    }

    public function isApiUserPasswordSet(): bool
    {
        try {
            $queryBuilder = $this->queryBuilderFactory->create();
            $queryBuilder
                ->select('OXPASSWORD')
                ->from('oxuser')
                ->where('OXUSERNAME = :email')
                ->setParameter('email', Module::API_USER_EMAIL);
            $this->apiUserShopScope->restrictToCurrentShop($queryBuilder);

            $row = $queryBuilder->execute()->fetchAssociative(); // @phpstan-ignore method.nonObject

            if (!$row) {
                return false;
            }

            $password = $row['OXPASSWORD'] ?? '';

            // Password is set if it's a valid bcrypt hash (starts with $2y$).
            // The placeholder (random hex) or empty string means password not set.
            return !empty($password) && strpos($password, '$2y$') === 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function isSetupComplete(): bool
    {
        // The api user is created on module activation, so its existence is the
        // seeded-state signal; there is no separate migration step. See OXS-3046.
        return $this->isApiUserCreated()
            && $this->isApiUserPasswordSet();
    }
}
