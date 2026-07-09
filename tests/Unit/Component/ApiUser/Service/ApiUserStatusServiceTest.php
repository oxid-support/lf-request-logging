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
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserShopScopeInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserStatusService;
use OxidSupport\Heartbeat\Module\Module;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The shop scoping itself is covered by ApiUserShopScopeTest; here the scope is
 * a no-op stub, so these tests pin the status logic (created / password / setup).
 */
#[CoversClass(ApiUserStatusService::class)]
final class ApiUserStatusServiceTest extends TestCase
{
    // ===========================================
    // isApiUserCreated() tests
    // ===========================================

    public function testIsApiUserCreatedReturnsTrueWhenUserExists(): void
    {
        $result = $this->createMock(Result::class);
        $result->expects($this->once())->method('fetchOne')->willReturn('1');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())->method('select')->with('COUNT(*)')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('from')->with('oxuser')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('where')->with('OXUSERNAME = :email')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setParameter')
            ->with('email', Module::API_USER_EMAIL)->willReturnSelf();
        $queryBuilder->expects($this->once())->method('execute')->willReturn($result);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->expects($this->once())->method('create')->willReturn($queryBuilder);

        $this->assertTrue($this->getSut(queryBuilderFactory: $queryBuilderFactory)->isApiUserCreated());
    }

    public function testIsApiUserCreatedReturnsFalseWhenUserDoesNotExist(): void
    {
        $result = $this->createMock(Result::class);
        $result->expects($this->once())->method('fetchOne')->willReturn('0');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('execute')->willReturn($result);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        $this->assertFalse($this->getSut(queryBuilderFactory: $queryBuilderFactory)->isApiUserCreated());
    }

    public function testIsApiUserCreatedReturnsFalseOnException(): void
    {
        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willThrowException(new \Exception('Database error'));

        $this->assertFalse($this->getSut(queryBuilderFactory: $queryBuilderFactory)->isApiUserCreated());
    }

    // ===========================================
    // isApiUserPasswordSet() tests
    // ===========================================

    public function testIsApiUserPasswordSetReturnsTrueWhenPasswordIsSet(): void
    {
        $result = $this->createMock(Result::class);
        $result->expects($this->once())->method('fetchAssociative')
            ->willReturn(['OXPASSWORD' => '$2y$10$somehash']);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())->method('select')->with('OXPASSWORD')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('from')->with('oxuser')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('where')->with('OXUSERNAME = :email')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setParameter')
            ->with('email', Module::API_USER_EMAIL)->willReturnSelf();
        $queryBuilder->expects($this->once())->method('execute')->willReturn($result);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->expects($this->once())->method('create')->willReturn($queryBuilder);

        $this->assertTrue($this->getSut(queryBuilderFactory: $queryBuilderFactory)->isApiUserPasswordSet());
    }

    public function testIsApiUserPasswordSetReturnsFalseWhenPasswordIsNotBCrypt(): void
    {
        $result = $this->createMock(Result::class);
        $result->expects($this->once())->method('fetchAssociative')
            ->willReturn(['OXPASSWORD' => 'placeholder_hash_not_bcrypt']);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('execute')->willReturn($result);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        $this->assertFalse($this->getSut(queryBuilderFactory: $queryBuilderFactory)->isApiUserPasswordSet());
    }

    public function testIsApiUserPasswordSetReturnsFalseWhenUserNotFound(): void
    {
        $result = $this->createMock(Result::class);
        $result->expects($this->once())->method('fetchAssociative')->willReturn(false);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('execute')->willReturn($result);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        $this->assertFalse($this->getSut(queryBuilderFactory: $queryBuilderFactory)->isApiUserPasswordSet());
    }

    public function testIsApiUserPasswordSetReturnsFalseOnException(): void
    {
        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willThrowException(new \Exception('Database error'));

        $this->assertFalse($this->getSut(queryBuilderFactory: $queryBuilderFactory)->isApiUserPasswordSet());
    }

    // ===========================================
    // isSetupComplete() tests
    // ===========================================

    public function testIsSetupCompleteReturnsTrueWhenUserCreatedAndPasswordSet(): void
    {
        $userExistsResult = $this->createMock(Result::class);
        $userExistsResult->method('fetchOne')->willReturn('1');

        $passwordResult = $this->createMock(Result::class);
        $passwordResult->method('fetchAssociative')->willReturn(['OXPASSWORD' => '$2y$10$somevalidbcrypthash']);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('execute')->willReturnOnConsecutiveCalls($userExistsResult, $passwordResult);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        $this->assertTrue($this->getSut(queryBuilderFactory: $queryBuilderFactory)->isSetupComplete());
    }

    public function testIsSetupCompleteReturnsFalseWhenUserNotCreated(): void
    {
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

        $this->assertFalse($this->getSut(queryBuilderFactory: $queryBuilderFactory)->isSetupComplete());
    }

    public function testIsSetupCompleteReturnsFalseWhenPasswordNotSet(): void
    {
        $userExistsResult = $this->createMock(Result::class);
        $userExistsResult->method('fetchOne')->willReturn('1');

        $passwordResult = $this->createMock(Result::class);
        $passwordResult->method('fetchAssociative')->willReturn(['OXPASSWORD' => 'placeholder']);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('execute')->willReturnOnConsecutiveCalls($userExistsResult, $passwordResult);

        $queryBuilderFactory = $this->createMock(QueryBuilderFactoryInterface::class);
        $queryBuilderFactory->method('create')->willReturn($queryBuilder);

        $this->assertFalse($this->getSut(queryBuilderFactory: $queryBuilderFactory)->isSetupComplete());
    }

    private function getSut(
        ?QueryBuilderFactoryInterface $queryBuilderFactory = null,
    ): ApiUserStatusService {
        return new ApiUserStatusService(
            queryBuilderFactory: $queryBuilderFactory ?? $this->createStub(QueryBuilderFactoryInterface::class),
            apiUserShopScope: $this->createStub(ApiUserShopScopeInterface::class),
        );
    }
}
