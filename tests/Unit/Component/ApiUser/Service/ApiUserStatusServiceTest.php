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
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserStatusService;
use OxidSupport\Heartbeat\Module\Module;
use OxidSupport\Heartbeat\Shop\Facade\ShopFacadeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApiUserStatusService::class)]
final class ApiUserStatusServiceTest extends TestCase
{
    // ===========================================
    // isApiUserCreated() tests
    // ===========================================

    public function testIsApiUserCreatedReturnsTrueWhenUserExists(): void
    {
        $result = $this->createMock(Result::class);
        $result->expects($this->once())
            ->method('fetchOne')
            ->willReturn('1');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())->method('select')->with('COUNT(*)')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('from')->with('oxuser')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('where')->with('OXUSERNAME = :email')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setParameter')
            ->with('email', Module::API_USER_EMAIL)->willReturnSelf();
        $queryBuilder->expects($this->once())->method('execute')->willReturn($result);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($queryBuilder);

        $result = $this->getSut(queryBuilderFactory: $queryBuilderFactory)->isApiUserCreated();

        $this->assertTrue($result);
    }

    public function testIsApiUserCreatedReturnsFalseWhenUserDoesNotExist(): void
    {
        $result = $this->createMock(Result::class);
        $result->expects($this->once())
            ->method('fetchOne')
            ->willReturn('0');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('execute')->willReturn($result);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        $result = $this->getSut(queryBuilderFactory: $queryBuilderFactory)->isApiUserCreated();

        $this->assertFalse($result);
    }

    public function testIsApiUserCreatedReturnsFalseOnException(): void
    {
        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')
            ->willThrowException(new \Exception('Database error'));

        $result = $this->getSut(queryBuilderFactory: $queryBuilderFactory)->isApiUserCreated();

        $this->assertFalse($result);
    }

    // ===========================================
    // isApiUserPasswordSet() tests
    // ===========================================

    public function testIsApiUserPasswordSetReturnsTrueWhenPasswordIsSet(): void
    {
        $result = $this->createMock(Result::class);
        $result->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'OXPASSWORD' => '$2y$10$somehash',
            ]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())->method('select')->with('OXPASSWORD')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('from')->with('oxuser')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('where')->with('OXUSERNAME = :email')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setParameter')
            ->with('email', Module::API_USER_EMAIL)->willReturnSelf();
        $queryBuilder->expects($this->once())->method('execute')->willReturn($result);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->expects($this->once())->method('create')->willReturn($queryBuilder);

        $result = $this->getSut(queryBuilderFactory: $queryBuilderFactory)->isApiUserPasswordSet();

        $this->assertTrue($result);
    }

    public function testIsApiUserPasswordSetReturnsFalseWhenPasswordIsNotBCrypt(): void
    {
        $result = $this->createMock(Result::class);
        $result->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'OXPASSWORD' => 'placeholder_hash_not_bcrypt',
            ]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('execute')->willReturn($result);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        $result = $this->getSut(queryBuilderFactory: $queryBuilderFactory)->isApiUserPasswordSet();

        $this->assertFalse($result);
    }

    public function testIsApiUserPasswordSetReturnsFalseWhenUserNotFound(): void
    {
        $result = $this->createMock(Result::class);
        $result->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn(false);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('execute')->willReturn($result);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        $result = $this->getSut(queryBuilderFactory: $queryBuilderFactory)->isApiUserPasswordSet();

        $this->assertFalse($result);
    }

    public function testIsApiUserPasswordSetReturnsFalseOnException(): void
    {
        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')
            ->willThrowException(new \Exception('Database error'));

        $result = $this->getSut(queryBuilderFactory: $queryBuilderFactory)->isApiUserPasswordSet();

        $this->assertFalse($result);
    }

    // ===========================================
    // isSetupComplete() tests
    //
    // Setup is complete when the api user exists and its password is set.
    // The user is created on module activation, so there is no separate
    // migration-executed condition anymore. See OXS-3046.
    // ===========================================

    public function testIsSetupCompleteReturnsTrueWhenUserCreatedAndPasswordSet(): void
    {
        // First query: user-exists check. Second query: password check.
        $userExistsResult = $this->createMock(Result::class);
        $userExistsResult->method('fetchOne')->willReturn('1');

        $passwordResult = $this->createMock(Result::class);
        $passwordResult->method('fetchAssociative')->willReturn([
            'OXPASSWORD' => '$2y$10$somevalidbcrypthash',
        ]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('execute')
            ->willReturnOnConsecutiveCalls($userExistsResult, $passwordResult);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        $result = $this->getSut(queryBuilderFactory: $queryBuilderFactory)->isSetupComplete();

        $this->assertTrue($result);
    }

    public function testIsSetupCompleteReturnsFalseWhenUserNotCreated(): void
    {
        // User-exists returns false; the password check must not even be reached.
        $userExistsResult = $this->createMock(Result::class);
        $userExistsResult->method('fetchOne')->willReturn('0');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('execute')->willReturn($userExistsResult);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        $result = $this->getSut(queryBuilderFactory: $queryBuilderFactory)->isSetupComplete();

        $this->assertFalse($result);
    }

    public function testIsSetupCompleteReturnsFalseWhenPasswordNotSet(): void
    {
        // User exists, but the password is still the placeholder.
        $userExistsResult = $this->createMock(Result::class);
        $userExistsResult->method('fetchOne')->willReturn('1');

        $passwordResult = $this->createMock(Result::class);
        $passwordResult->method('fetchAssociative')->willReturn([
            'OXPASSWORD' => 'placeholder',
        ]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('execute')
            ->willReturnOnConsecutiveCalls($userExistsResult, $passwordResult);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        $result = $this->getSut(queryBuilderFactory: $queryBuilderFactory)->isSetupComplete();

        $this->assertFalse($result);
    }

    // ===========================================
    // shop scoping (mall users) tests
    //
    // With mall users off the service user is per subshop, so the status lookup
    // must be constrained to the current shop; otherwise a subshop reads another
    // shop's row. With mall users on the single shared row applies. See OXS-3046.
    // ===========================================

    public function testCreatedCheckIsScopedToCurrentShopWhenMallUsersDisabled(): void
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchOne')->willReturn('1');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('andWhere')->with('OXSHOPID = :shopId')->willReturnSelf();
        $queryBuilder->method('execute')->willReturn($result);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        $shopFacade = $this->createStub(ShopFacadeInterface::class);
        $shopFacade->method('areMallUsersEnabled')->willReturn(false);
        $shopFacade->method('getShopId')->willReturn(2);

        $this->assertTrue(
            $this->getSut(queryBuilderFactory: $queryBuilderFactory, shopFacade: $shopFacade)->isApiUserCreated()
        );
    }

    public function testCreatedCheckIsGlobalWhenMallUsersEnabled(): void
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchOne')->willReturn('1');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->expects($this->never())->method('andWhere');
        $queryBuilder->method('execute')->willReturn($result);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        $shopFacade = $this->createStub(ShopFacadeInterface::class);
        $shopFacade->method('areMallUsersEnabled')->willReturn(true);

        $this->assertTrue(
            $this->getSut(queryBuilderFactory: $queryBuilderFactory, shopFacade: $shopFacade)->isApiUserCreated()
        );
    }

    public function testPasswordCheckIsScopedToCurrentShopWhenMallUsersDisabled(): void
    {
        // The live bug: a subshop row with only the placeholder password must
        // report false, and the lookup must be constrained to the current shop
        // (not read another shop's set password). See OXS-3046.
        $result = $this->createMock(Result::class);
        $result->method('fetchAssociative')->willReturn(['OXPASSWORD' => 'placeholder-not-bcrypt']);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('andWhere')->with('OXSHOPID = :shopId')->willReturnSelf();
        $queryBuilder->method('execute')->willReturn($result);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        $shopFacade = $this->createStub(ShopFacadeInterface::class);
        $shopFacade->method('areMallUsersEnabled')->willReturn(false);
        $shopFacade->method('getShopId')->willReturn(2);

        $this->assertFalse(
            $this->getSut(queryBuilderFactory: $queryBuilderFactory, shopFacade: $shopFacade)->isApiUserPasswordSet()
        );
    }

    private function getSut(
        ?QueryBuilderFactoryInterface $queryBuilderFactory = null,
        ?ShopFacadeInterface $shopFacade = null,
    ): ApiUserStatusService {
        if ($shopFacade === null) {
            // Default to mall users ON: a global lookup with no shop constraint,
            // so the existing query expectations below hold unchanged.
            $shopFacade = $this->createStub(ShopFacadeInterface::class);
            $shopFacade->method('areMallUsersEnabled')->willReturn(true);
        }

        return new ApiUserStatusService(
            queryBuilderFactory: $queryBuilderFactory ?? $this->createStub(QueryBuilderFactoryInterface::class),
            shopFacade: $shopFacade,
        );
    }
}
