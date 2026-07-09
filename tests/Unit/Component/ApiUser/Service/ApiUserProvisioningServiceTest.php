<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\ApiUser\Service;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserProvisioningService;
use OxidSupport\Heartbeat\Shop\Facade\ShopFacadeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for the shop-scope decision of the api user lookup.
 *
 * The DB-touching creation path (oxNew User/Groups, save, addToGroup) is covered
 * by the integration test; here we pin the mall-users branching that decides
 * whether the lookup is global or scoped to the current shop. See OXS-3103.
 */
#[CoversClass(ApiUserProvisioningService::class)]
final class ApiUserProvisioningServiceTest extends TestCase
{
    public function testLookupIsScopedToCurrentShopWhenMallUsersDisabled(): void
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchOne')->willReturn('existing-id');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->with('OXUSERNAME = :email')->willReturnSelf();
        // Mall users off: the lookup MUST be constrained to the current shop.
        $queryBuilder->expects($this->once())
            ->method('andWhere')->with('OXSHOPID = :shopId')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('execute')->willReturn($result);

        $shopFacade = $this->createMock(ShopFacadeInterface::class);
        $shopFacade->method('areMallUsersEnabled')->willReturn(false);
        $shopFacade->method('getShopId')->willReturn(3);

        $id = $this->invokeFindApiUserId($queryBuilder, $shopFacade);

        $this->assertSame('existing-id', $id);
    }

    public function testLookupIsGlobalWhenMallUsersEnabled(): void
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchOne')->willReturn(false);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        // Mall users on: one shared row serves all shops, no shop constraint.
        $queryBuilder->expects($this->never())->method('andWhere');
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('execute')->willReturn($result);

        $shopFacade = $this->createMock(ShopFacadeInterface::class);
        $shopFacade->method('areMallUsersEnabled')->willReturn(true);

        $id = $this->invokeFindApiUserId($queryBuilder, $shopFacade);

        $this->assertNull($id);
    }

    private function invokeFindApiUserId(
        QueryBuilder $queryBuilder,
        ShopFacadeInterface $shopFacade
    ): ?string {
        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        $sut = new ApiUserProvisioningService($queryBuilderFactory, $shopFacade);

        $method = new \ReflectionMethod($sut, 'findApiUserId');
        $method->setAccessible(true);

        /** @var string|null $id */
        $id = $method->invoke($sut);

        return $id;
    }
}
