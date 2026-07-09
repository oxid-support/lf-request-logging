<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiUser\Service;

use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidSupport\Heartbeat\Module\Module;
use OxidSupport\Heartbeat\Shop\Facade\ShopFacadeInterface;

/**
 * Service to check the setup status of the API user.
 * Checks actual database state rather than assuming based on module activation.
 *
 * Shop-scope aware, consistent with ApiUserProvisioningService: with mall users
 * off the service user is per subshop, so the status must reflect the CURRENT
 * shop's row, not any row with that username. Otherwise a subshop would report
 * "setup complete" off another shop's password. With mall users on the single
 * shared row is authoritative for every shop. See OXS-3046.
 */
final class ApiUserStatusService implements ApiUserStatusServiceInterface
{
    private QueryBuilderFactoryInterface $queryBuilderFactory;
    private ShopFacadeInterface $shopFacade;

    public function __construct(
        QueryBuilderFactoryInterface $queryBuilderFactory,
        ShopFacadeInterface $shopFacade
    ) {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->shopFacade = $shopFacade;
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
            $this->scopeToCurrentShop($queryBuilder);

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
            $this->scopeToCurrentShop($queryBuilder);

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

    /**
     * Constrain a `oxuser` lookup to the current shop when mall users are off
     * (users are per subshop then). With mall users on the single shared row
     * applies to every shop, so no constraint is added. See OXS-3046.
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
