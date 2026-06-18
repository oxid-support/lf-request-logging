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
use OxidEsales\GraphQL\Base\Tests\Integration\TokenTestCase;
use OxidSupport\Heartbeat\Component\ApiUser\Controller\Admin\SetupController;
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserServiceInterface;
use OxidSupport\Heartbeat\Component\RequestLogger\Core\ModuleEvents;
use OxidSupport\Heartbeat\Module\Module;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * Integration test for the token-invalidation feature set
 * (OXS-3054 / 3058 / 3059 / 3060).
 *
 * Every path that must drop the heartbeat-api service user's JWTs from
 * oegraphqltoken is exercised in-process (no HTTP, so independent of the
 * web server's Authorization-header handling).
 *
 * Non-destructive: TokenTestCase wraps each test in a DB transaction that is
 * rolled back in tearDown, so the seeded token and any side effects never
 * touch the real shop data.
 */
final class TokenInvalidationTest extends TokenTestCase
{
    private string $apiUserId = '';

    public function setUp(): void
    {
        parent::setUp();

        $this->apiUserId = (string) $this->connection()->executeQuery(
            'SELECT OXID FROM oxuser WHERE OXUSERNAME = :email',
            ['email' => Module::API_USER_EMAIL]
        )->fetchOne();

        $this->assertNotEmpty(
            $this->apiUserId,
            'heartbeat-api user must exist (module activated)'
        );
    }

    public function testInvalidateTokensMutationDropsApiUserTokens(): void
    {
        $this->seedApiUserToken();
        $this->assertSame(1, $this->apiUserTokenCount());

        $this->prepareToken(); // admin (oxidadmin) carries the token-invalidate right
        $result = $this->query('mutation { heartbeatInvalidateTokens }');

        $this->assertArrayNotHasKey('errors', $result['body'], $this->dump($result));
        $this->assertSame(1, $result['body']['data']['heartbeatInvalidateTokens']);
        $this->assertSame(0, $this->apiUserTokenCount());
    }

    public function testResetPasswordDropsApiUserTokens(): void
    {
        // Asserted at the service boundary, which is the exact point OXS-3054
        // wires token invalidation into the password reset. The GraphQL stack
        // (auth + right + mutation) is already covered by the
        // heartbeatInvalidateTokens test above; the heartbeatResetPassword
        // controller additionally persists a module setting, which the
        // in-process TestContainer does not provide.
        $this->seedApiUserToken();
        $this->assertSame(1, $this->apiUserTokenCount());

        /** @var ApiUserServiceInterface $apiUserService */
        $apiUserService = ContainerFactory::getInstance()
            ->getContainer()
            ->get(ApiUserServiceInterface::class);
        $apiUserService->resetPasswordForApiUser();

        $this->assertSame(0, $this->apiUserTokenCount());
    }

    public function testModuleDeactivationDropsApiUserTokens(): void
    {
        $this->seedApiUserToken();
        $this->assertSame(1, $this->apiUserTokenCount());

        ModuleEvents::onDeactivate();

        $this->assertSame(0, $this->apiUserTokenCount());
    }

    /**
     * Isolated process: SetupController is an admin controller whose Config init
     * calls session_cache_limiter(), which fails once another test in this class
     * has already started a session and emitted output. A fresh process gives it
     * a clean session/header state.
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSetupControllerInvalidateActionDropsApiUserTokens(): void
    {
        /**
         * Admin-button action path (OXS-3058). The button delegates to the same
         * TokenInvalidator the GraphQL mutation uses, but the controller path
         * itself is exercised here so the admin entry point cannot silently
         * break without the mutation breaking too.
         */
        $this->seedApiUserToken();
        $this->assertSame(1, $this->apiUserTokenCount());

        $controller = oxNew(SetupController::class);
        $controller->invalidateTokens();

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
                'ua'    => 'phpunit-integration',
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

    private function dump(array $result): string
    {
        return (string) json_encode($result['body'] ?? $result);
    }
}
