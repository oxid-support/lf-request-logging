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
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserShopScopeInterface;
use OxidSupport\Heartbeat\Shop\Facade\ShopFacadeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The shop scoping itself is covered by ApiUserShopScopeTest; here we pin the
 * lookup's return handling and that it delegates scoping to that service. The
 * DB-touching create path (oxNew User/Groups, save, addToGroup) is covered by
 * the integration test. See OXS-3046.
 */
#[CoversClass(ApiUserProvisioningService::class)]
final class ApiUserProvisioningServiceTest extends TestCase
{
    public function testFindApiUserIdReturnsIdWhenRowExists(): void
    {
        $id = $this->invokeFindApiUserId('existing-id', $this->createStub(ApiUserShopScopeInterface::class));
        $this->assertSame('existing-id', $id);
    }

    public function testFindApiUserIdReturnsNullWhenNoRow(): void
    {
        $id = $this->invokeFindApiUserId(false, $this->createStub(ApiUserShopScopeInterface::class));
        $this->assertNull($id);
    }

    public function testFindApiUserIdDelegatesShopScoping(): void
    {
        $shopScope = $this->createMock(ApiUserShopScopeInterface::class);
        $shopScope->expects($this->once())->method('restrictToCurrentShop');

        $this->invokeFindApiUserId('x', $shopScope);
    }

    /**
     * @param string|false $fetchOne
     */
    private function invokeFindApiUserId($fetchOne, ApiUserShopScopeInterface $shopScope): ?string
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchOne')->willReturn($fetchOne);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('execute')->willReturn($result);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        $sut = new ApiUserProvisioningService(
            $queryBuilderFactory,
            $this->createStub(ShopFacadeInterface::class),
            $shopScope,
        );

        $method = new \ReflectionMethod($sut, 'findApiUserId');
        $method->setAccessible(true);

        /** @var string|null $id */
        $id = $method->invoke($sut);

        return $id;
    }
}
