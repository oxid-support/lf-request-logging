<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\ApiUser\Service;

use Doctrine\DBAL\Query\QueryBuilder;
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserShopScope;
use OxidSupport\Heartbeat\Shop\Facade\ShopFacadeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The single behavioral test of the api-user shop-scoping rule. All services
 * delegate here, so the mall-users branching is pinned once. See OXS-3046.
 */
#[CoversClass(ApiUserShopScope::class)]
final class ApiUserShopScopeTest extends TestCase
{
    public function testRestrictsToCurrentShopWhenMallUsersDisabled(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('andWhere')->with('OXSHOPID = :shopId')->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameter')->with('shopId', 3)->willReturnSelf();

        $shopFacade = $this->createStub(ShopFacadeInterface::class);
        $shopFacade->method('areMallUsersEnabled')->willReturn(false);
        $shopFacade->method('getShopId')->willReturn(3);

        (new ApiUserShopScope($shopFacade))->restrictToCurrentShop($queryBuilder);
    }

    public function testDoesNotRestrictWhenMallUsersEnabled(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->never())->method('andWhere');
        $queryBuilder->expects($this->never())->method('setParameter');

        $shopFacade = $this->createStub(ShopFacadeInterface::class);
        $shopFacade->method('areMallUsersEnabled')->willReturn(true);

        (new ApiUserShopScope($shopFacade))->restrictToCurrentShop($queryBuilder);
    }
}
