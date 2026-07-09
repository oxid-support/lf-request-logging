<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiUser\Service;

use OxidEsales\Eshop\Application\Model\Groups;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidSupport\Heartbeat\Module\Module;
use OxidSupport\Heartbeat\Shop\Facade\ShopFacadeInterface;

/**
 * Creates the Heartbeat API group, the service user and the group membership
 * on module activation, in the current shop context.
 *
 * This replaces the former data-seeding migration, which hardcoded the service
 * user's OXSHOPID = 1 and used md5-based record ids. A migration runs once and is
 * tracked globally, so it cannot reflect the shop it is installed on.
 *
 * Multishop (EE): the oxuser unique key is (OXUSERNAME, OXSHOPID), so the account
 * model depends on `blMallUsers`:
 *  - mall users on  -> one shared user row serves every subshop (login resolves
 *    it shop-agnostically), so the existence check is global.
 *  - mall users off -> users are per subshop; a row from another shop must not be
 *    reused, so we require a row for the current shop and create one per subshop
 *    activation.
 * See OXS-3046 / OXS-3103.
 */
final class ApiUserProvisioningService implements ApiUserProvisioningServiceInterface
{
    private const GROUP_ID = 'oxsheartbeat_api';
    private const GROUP_TITLE = 'Heartbeat API';

    private QueryBuilderFactoryInterface $queryBuilderFactory;
    private ShopFacadeInterface $shopFacade;

    public function __construct(
        QueryBuilderFactoryInterface $queryBuilderFactory,
        ShopFacadeInterface $shopFacade
    ) {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->shopFacade = $shopFacade;
    }

    public function ensureApiUser(): void
    {
        $this->ensureGroup();
        $user = $this->ensureUser();

        // Idempotent via the model's inGroup() guard. One membership row per user
        // row is enough: the shop core looks up group membership shop-agnostically
        // (User::getUserGroups() does not filter oxobject2group by shop), so the
        // permission checks do not depend on this row's OXSHOPID. See OXS-3046.
        $user->addToGroup(self::GROUP_ID);
    }

    private function ensureGroup(): void
    {
        /** @var Groups $group */
        $group = oxNew(Groups::class);
        if ($group->load(self::GROUP_ID)) {
            return;
        }

        $group->setId(self::GROUP_ID);
        $group->assign([
            'oxactive' => 1,
            'oxtitle' => self::GROUP_TITLE,
            'oxtitle_1' => self::GROUP_TITLE,
        ]);
        $group->save();
    }

    private function ensureUser(): User
    {
        /** @var User $user */
        $user = oxNew(User::class);

        $userId = $this->findApiUserId();
        if ($userId !== null && $user->load($userId)) {
            return $user;
        }

        // Service account without a usable password: the placeholder is a random
        // hex string that can never satisfy a bcrypt verification. The operator
        // sets a real password via the setup token. OXID from the shop's own id
        // generator, not a hand-rolled md5. See OXS-3046.
        $user->setId(Registry::getUtilsObject()->generateUId());
        $user->assign([
            'oxactive' => 1,
            'oxrights' => 'user',
            'oxshopid' => $this->shopFacade->getShopId(),
            'oxusername' => Module::API_USER_EMAIL,
            'oxpassword' => bin2hex(random_bytes(32)),
            'oxpasssalt' => '',
            'oxfname' => 'Heartbeat',
            'oxlname' => 'API User',
            'oxaddinfo' => 'Service user for Heartbeat GraphQL API. Created by oxsheartbeat module.',
        ]);
        $user->save();

        return $user;
    }

    /**
     * Finds the existing api service user relevant for the current shop scope,
     * honouring the shop's `blMallUsers` setting (see class docblock). Returns
     * null if no matching row exists and one must be created.
     *
     * With mall users on the lookup is global and returns any matching row; if a
     * shop was switched from mall-users-off (leaving one row per subshop), which
     * row is returned is unspecified. That transition is out of scope here (the
     * module never creates such duplicates itself); see OXS-3103.
     */
    private function findApiUserId(): ?string
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->select('OXID')
            ->from('oxuser')
            ->where('OXUSERNAME = :email')
            ->setParameter('email', Module::API_USER_EMAIL);

        if (!$this->shopFacade->areMallUsersEnabled()) {
            $queryBuilder
                ->andWhere('OXSHOPID = :shopId')
                ->setParameter('shopId', $this->shopFacade->getShopId());
        }

        $userId = $queryBuilder->execute()->fetchOne(); // @phpstan-ignore method.nonObject

        return ($userId === false || $userId === null) ? null : (string) $userId;
    }
}
