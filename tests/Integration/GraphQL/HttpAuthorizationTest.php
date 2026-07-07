<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Integration\GraphQL;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\GraphQL\Base\Tests\Integration\TokenTestCase;
use OxidSupport\Heartbeat\Component\ApiVersion\Service\ApiVersionService;
use OxidSupport\Heartbeat\Module\Module;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Exercises Bearer-token authentication over REAL HTTP against the running
 * web server, deliberately not in-process.
 *
 * The in-process harness can never see web-server misconfiguration: Apache
 * with mod_proxy_fcgi silently drops the Authorization header unless
 * "CGIPassAuth On" is set, which makes every "authenticated" GraphQL call
 * anonymous (heartbeatApiVersion then returns null details and the dashboard
 * refuses the shop). This suite fails loudly on exactly that class of bug.
 *
 * When no web server is reachable (integration run without the full SDK
 * stack), the tests are skipped with an explicit message instead of failing,
 * so the suite stays usable in reduced environments. A skip here means:
 * HTTP auth is NOT verified.
 */
final class HttpAuthorizationTest extends TokenTestCase
{
    // Pinned on purpose: graphql-base's TokenTestCase ships different admin
    // defaults per major (v8: admin@admin.com, v9: noreply@oxid-esales.com)
    // and neither is guaranteed to match the SDK shop this suite talks to.
    protected const ADMIN_USER = 'noreply@oxid-esales.com';
    protected const ADMIN_PASS = 'admin';

    private const HTTP_TIMEOUT_SECONDS = 10;

    private string $graphQlUrl = '';

    private string $testStartedAt = '';

    public function setUp(): void
    {
        parent::setUp();

        $shopUrl = (string) Registry::getConfig()->getConfigParam('sShopURL');
        $this->assertNotEmpty($shopUrl, 'sShopURL must be configured');
        $this->graphQlUrl = rtrim($shopUrl, '/') . '/graphql/';

        $this->testStartedAt = date('Y-m-d H:i:s');
        $this->skipUnlessWebServerReachable();
    }

    public function tearDown(): void
    {
        $this->removeTokensIssuedDuringThisTest();

        try {
            parent::tearDown();
        } catch (IOException) {
            // The inherited tearDown clears var/cache while the live web
            // stack (exercised by this suite's HTTP calls) concurrently
            // rewrites it; losing that race is harmless here.
        }
    }

    public function testAnonymousCallExposesOnlyInstalledFlag(): void
    {
        $body = $this->httpGraphQl(
            'query { heartbeatApiVersion { installed apiVersion moduleVersion supportedOperations } }'
        );

        $this->assertArrayNotHasKey('errors', $body, (string) json_encode($body));

        $data = $body['data']['heartbeatApiVersion'];
        $this->assertTrue($data['installed']);
        $this->assertNull($data['apiVersion'], 'version details must stay hidden for anonymous callers');
        $this->assertNull($data['moduleVersion'], 'version details must stay hidden for anonymous callers');
        $this->assertNull($data['supportedOperations'], 'version details must stay hidden for anonymous callers');
    }

    public function testMalformedBearerTokenIsRejected(): void
    {
        $body = $this->httpGraphQl(
            'query { heartbeatApiVersion { installed } }',
            'not.a.jwt'
        );

        $this->assertArrayHasKey(
            'errors',
            $body,
            'A malformed Bearer token was accepted silently. That means the Authorization '
            . 'header never reached PHP - typically the web server drops it '
            . '(Apache mod_proxy_fcgi needs "CGIPassAuth On"). Response: '
            . (string) json_encode($body)
        );
    }

    public function testValidTokenUnlocksVersionDetails(): void
    {
        $token = $this->fetchTokenViaHttp();

        $body = $this->httpGraphQl(
            'query { heartbeatApiVersion {
                installed apiVersion apiSchemaHash moduleVersion supportedOperations
                componentStatus { name active }
            } }',
            $token
        );

        $this->assertArrayNotHasKey('errors', $body, (string) json_encode($body));

        $data = $body['data']['heartbeatApiVersion'];
        $this->assertTrue($data['installed']);
        $this->assertSame(Module::API_VERSION, $data['apiVersion']);
        $this->assertSame(Module::VERSION, $data['moduleVersion']);
        $this->assertSame(array_values(Module::SUPPORTED_OPERATIONS), $data['supportedOperations']);
        $this->assertSame(
            ApiVersionService::computeSchemaHash(Module::SUPPORTED_OPERATIONS),
            $data['apiSchemaHash']
        );

        $componentNames = array_column($data['componentStatus'], 'name');
        $this->assertSame(['requestLogger', 'logSender', 'diagnosticsProvider'], $componentNames);
        foreach ($data['componentStatus'] as $component) {
            $this->assertIsBool($component['active'], $component['name'] . ' must report a boolean status');
        }
    }

    private function fetchTokenViaHttp(): string
    {
        $body = $this->httpGraphQl(sprintf(
            'query { token(username: "%s", password: "%s") }',
            self::ADMIN_USER,
            self::ADMIN_PASS
        ));

        $this->assertArrayNotHasKey(
            'errors',
            $body,
            'token query failed - check the SDK admin credentials: ' . (string) json_encode($body)
        );

        return $body['data']['token'];
    }

    /**
     * @return array<string, mixed> decoded JSON response body
     */
    private function httpGraphQl(string $query, ?string $bearerToken = null): array
    {
        $headers = "Content-Type: application/json\r\n";
        if ($bearerToken !== null) {
            $headers .= "Authorization: Bearer {$bearerToken}\r\n";
        }

        $response = file_get_contents($this->graphQlUrl, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                // PHP's http wrapper defaults to HTTP/1.0, which the SDK's
                // h2-enabled Apache rejects; 1.1 + Connection: close works.
                'protocol_version' => 1.1,
                'header' => $headers . "Connection: close\r\n",
                'content' => (string) json_encode(['query' => $query]),
                'timeout' => self::HTTP_TIMEOUT_SECONDS,
                'ignore_errors' => true,
            ],
        ]));

        $this->assertNotFalse($response, 'HTTP request to ' . $this->graphQlUrl . ' failed');

        $body = json_decode($response, true);
        $this->assertIsArray($body, 'GraphQL endpoint did not return JSON: ' . substr($response, 0, 200));

        return $body;
    }

    private function skipUnlessWebServerReachable(): void
    {
        // Generous timeout on purpose: earlier tests wipe var/cache in their
        // tearDown, so the first HTTP request afterwards recompiles the DI
        // container and GraphQL schema. A slow warm-up must not be mistaken
        // for "web server down" (a down stack refuses the connection fast).
        $probe = @file_get_contents($this->graphQlUrl, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'protocol_version' => 1.1,
                'header' => "Content-Type: application/json\r\nConnection: close\r\n",
                'content' => '{"query":"query { __typename }"}',
                'timeout' => 60,
                'ignore_errors' => true,
            ],
        ]));

        if ($probe === false) {
            $this->markTestSkipped(
                'Web server not reachable at ' . $this->graphQlUrl
                . ' - HTTP authorization is NOT verified. Start the SDK web stack to run this suite.'
            );
        }
    }

    /**
     * The token query issues real rows in oegraphqltoken outside the test
     * transaction (separate HTTP process). Remove what this test created so
     * repeated runs do not pile up tokens or hit the user's token quota.
     */
    private function removeTokensIssuedDuringThisTest(): void
    {
        if ($this->testStartedAt === '') {
            return;
        }

        ContainerFactory::getInstance()
            ->getContainer()
            ->get(QueryBuilderFactoryInterface::class)
            ->create()
            ->getConnection()
            ->executeStatement(
                'DELETE t FROM oegraphqltoken t
                  JOIN oxuser u ON u.OXID = t.OXUSERID
                 WHERE u.OXUSERNAME = :admin AND t.ISSUED_AT >= :startedAt',
                ['admin' => self::ADMIN_USER, 'startedAt' => $this->testStartedAt]
            );
    }
}
