<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiUser\Service;

use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidSupport\Heartbeat\Module\Module;
use OxidSupport\Heartbeat\Component\ApiUser\Exception\UserNotFoundException;
use OxidSupport\Heartbeat\Shop\Facade\ShopFacadeInterface;

/**
 * Service for API user operations.
 */
final class ApiUserService implements ApiUserServiceInterface
{
    private QueryBuilderFactoryInterface $queryBuilderFactory;
    private TokenInvalidatorInterface $tokenInvalidator;
    private ShopFacadeInterface $shopFacade;

    public function __construct(
        QueryBuilderFactoryInterface $queryBuilderFactory,
        TokenInvalidatorInterface $tokenInvalidator,
        ShopFacadeInterface $shopFacade
    ) {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->tokenInvalidator = $tokenInvalidator;
        $this->shopFacade = $shopFacade;
    }

    public function loadApiUser(User $user): bool
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->select('OXID')
            ->from('oxuser')
            ->where('OXUSERNAME = :email')
            ->setParameter('email', Module::API_USER_EMAIL);
        $this->scopeToCurrentShop($queryBuilder);

        $userId = $queryBuilder->execute()->fetchOne(); // @phpstan-ignore method.nonObject

        if (!$userId) {
            return false;
        }

        return $user->load($userId);
    }

    /**
     * With mall users off the service user is per subshop, so an admin action
     * (set/reset password) must target THIS shop's row, not another shop's.
     * With mall users on the single shared row applies. See OXS-3046.
     */
    private function scopeToCurrentShop(QueryBuilder $queryBuilder): void
    {
        if (!$this->shopFacade->areMallUsersEnabled()) {
            $queryBuilder
                ->andWhere('OXSHOPID = :shopId')
                ->setParameter('shopId', $this->shopFacade->getShopId());
        }
    }

    public function resetPassword(string $userId): void
    {
        $placeholder = bin2hex(random_bytes(32));

        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->update('oxuser')
            ->set('OXPASSWORD', ':placeholder')
            ->set('OXPASSSALT', ':salt')
            ->where('OXID = :userId')
            ->setParameter('placeholder', $placeholder)
            ->setParameter('salt', '')
            ->setParameter('userId', $userId);

        $queryBuilder->execute();
    }

    public function setPasswordForApiUser(string $password): void
    {
        /** @var User $user */
        $user = oxNew(User::class);

        if (!$this->loadApiUser($user)) {
            throw new UserNotFoundException();
        }

        $user->setPassword($password);
        $user->save();
    }

    public function resetPasswordForApiUser(): void
    {
        /** @var User $user */
        $user = oxNew(User::class);

        if (!$this->loadApiUser($user)) {
            throw new UserNotFoundException();
        }

        $this->resetPassword($user->getId());

        // Token invalidation is bundled with the password reset on purpose:
        // a stolen JWT would otherwise remain valid until its exp claim, which
        // defeats the point of a password reset triggered by suspected leak.
        // See OXS-3054.
        $this->tokenInvalidator->invalidateForApiUser();
    }
}
