<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Integration\ApiUser;

use Doctrine\DBAL\Connection;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserServiceInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\TokenInvalidatorInterface;
use OxidSupport\Heartbeat\Component\RequestLogger\Core\ModuleEvents;
use OxidSupport\Heartbeat\Module\Module;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for the token-invalidation feature set
 * (OXS-3054 / 3058 / 3059 / 3060) on the OXID 6.5 line.
 *
 * Runs inside an installed shop (real DB, real DI container). Every path that
 * must drop the heartbeat-api service user's JWTs from oegraphqltoken is
 * exercised at the service / component boundary.
 *
 * Why no graphql-base TokenTestCase here (unlike the 7.x lines): graphql-base v7
 * ships its test base for the phpunit-8.5 era and it does not load cleanly in
 * the installed 6.5 shop. The OXID 6.5 platform dictates phpunit 8.5, so this
 * harness uses a plain TestCase. The GraphQL mutation path (heartbeatInvalidateTokens)
 * is verified functionally over HTTP separately; here the same invalidation is
 * asserted through the TokenInvalidator service the mutation delegates to.
 *
 * Non-destructive: there is no DBAL transaction wrapping because the reset/edit
 * paths write through OXID's legacy DB layer (outside a DBAL transaction). Instead
 * the service user's credentials are captured in setUp and restored in tearDown,
 * and seeded tokens are cleaned up explicitly.
 */
final class TokenInvalidationTest extends TestCase
{
    private const SEED_USERAGENT = 'phpunit-integration';

    private string $apiUserId = '';
    private string $origPassword = '';
    private string $origSalt = '';

    protected function setUp(): void
    {
        parent::setUp();

        $row = $this->connection()->executeQuery(
            'SELECT OXID, OXPASSWORD, OXPASSSALT FROM oxuser WHERE OXUSERNAME = :email',
            ['email' => Module::API_USER_EMAIL]
        )->fetchAssociative();

        $this->assertNotEmpty($row['OXID'] ?? null, 'heartbeat-api user must exist (module activated)');

        $this->apiUserId    = (string) $row['OXID'];
        $this->origPassword = (string) $row['OXPASSWORD'];
        $this->origSalt     = (string) $row['OXPASSSALT'];
    }

    protected function tearDown(): void
    {
        // The reset / user-edit paths change the credentials through OXID's legacy
        // DB layer, outside any DBAL transaction, so undo them explicitly.
        $this->connection()->executeStatement(
            'UPDATE oxuser SET OXPASSWORD = :pw, OXPASSSALT = :salt WHERE OXID = :uid',
            ['pw' => $this->origPassword, 'salt' => $this->origSalt, 'uid' => $this->apiUserId]
        );
        // Remove any leftover seeded tokens.
        $this->connection()->executeStatement(
            'DELETE FROM oegraphqltoken WHERE OXUSERID = :uid AND USERAGENT = :ua',
            ['uid' => $this->apiUserId, 'ua' => self::SEED_USERAGENT]
        );

        parent::tearDown();
    }

    public function testInvalidatorServiceDropsApiUserTokens(): void
    {
        $this->seedApiUserToken();
        $this->assertSame(1, $this->apiUserTokenCount());

        $deleted = $this->invalidator()->invalidateForApiUser();

        $this->assertSame(1, $deleted);
        $this->assertSame(0, $this->apiUserTokenCount());
    }

    public function testResetPasswordDropsApiUserTokens(): void
    {
        $this->seedApiUserToken();
        $this->assertSame(1, $this->apiUserTokenCount());

        $this->apiUserService()->resetPasswordForApiUser();

        $this->assertSame(0, $this->apiUserTokenCount());
    }

    public function testModuleDeactivationDropsApiUserTokens(): void
    {
        $this->seedApiUserToken();
        $this->assertSame(1, $this->apiUserTokenCount());

        ModuleEvents::onDeactivate();

        $this->assertSame(0, $this->apiUserTokenCount());
    }

    public function testServiceUserEditDropsApiUserTokens(): void
    {
        $this->seedApiUserToken();
        $this->assertSame(1, $this->apiUserTokenCount());

        $user = oxNew(User::class);
        $user->load($this->apiUserId);
        $user->setPassword('Rotated-' . bin2hex(random_bytes(4))); // security-relevant change
        $user->save();

        $this->assertSame(0, $this->apiUserTokenCount());
    }

    private function connection(): Connection
    {
        return ContainerFactory::getInstance()
            ->getContainer()
            ->get(QueryBuilderFactoryInterface::class)
            ->create()
            ->getConnection();
    }

    private function invalidator(): TokenInvalidatorInterface
    {
        return ContainerFactory::getInstance()
            ->getContainer()
            ->get(TokenInvalidatorInterface::class);
    }

    private function apiUserService(): ApiUserServiceInterface
    {
        return ContainerFactory::getInstance()
            ->getContainer()
            ->get(ApiUserServiceInterface::class);
    }

    private function seedApiUserToken(): void
    {
        $this->connection()->executeStatement(
            'INSERT INTO oegraphqltoken
                (OXID, OXSHOPID, OXUSERID, USERAGENT, TOKEN, ISSUED_AT, EXPIRES_AT)
             VALUES
                (:oxid, 1, :uid, :ua, :token, NOW(), DATE_ADD(NOW(), INTERVAL 1 HOUR))',
            [
                'oxid'  => substr(md5(uniqid('', true)), 0, 32),
                'uid'   => $this->apiUserId,
                'ua'    => self::SEED_USERAGENT,
                'token' => 'seeded.test.jwt',
            ]
        );
    }

    private function apiUserTokenCount(): int
    {
        return (int) $this->connection()->executeQuery(
            'SELECT COUNT(*) FROM oegraphqltoken WHERE OXUSERID = :uid',
            ['uid' => $this->apiUserId]
        )->fetchOne();
    }
}
