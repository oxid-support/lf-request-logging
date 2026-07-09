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
 */
final class ApiUserStatusService implements ApiUserStatusServiceInterface
{
    public function __construct(
        private QueryBuilderFactoryInterface $queryBuilderFactory
    ) {
    }

    public function isApiUserCreated(): bool
    {
        try {
            $queryBuilder = $this->queryBuilderFactory->create();
            $result = $queryBuilder
                ->select('COUNT(*)')
                ->from('oxuser')
                ->where('OXUSERNAME = :email')
                ->setParameter('email', Module::API_USER_EMAIL)
                ->execute();

            return (int) $result->fetchOne() > 0; // @phpstan-ignore method.nonObject
        } catch (\Exception) {
            return false;
        }
    }

    public function isApiUserPasswordSet(): bool
    {
        try {
            $queryBuilder = $this->queryBuilderFactory->create();
            $result = $queryBuilder
                ->select('OXPASSWORD')
                ->from('oxuser')
                ->where('OXUSERNAME = :email')
                ->setParameter('email', Module::API_USER_EMAIL)
                ->execute();

            $row = $result->fetchAssociative(); // @phpstan-ignore method.nonObject

            if (!$row) {
                return false;
            }

            $password = $row['OXPASSWORD'] ?? '';

            // Password is set if it's a valid bcrypt hash (starts with $2y$)
            // The placeholder '---' or empty string means password not set
            return !empty($password) && str_starts_with($password, '$2y$');
        } catch (\Exception) {
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
