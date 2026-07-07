<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Integration\GraphQL;

use OxidEsales\Eshop\Core\Registry;
use OxidSupport\Heartbeat\Module\Module;
use PHPUnit\Framework\TestCase;

/**
 * Guards the GraphQL contract with the Heartbeat dashboard (OXID 6.5 line).
 *
 * Module::SUPPORTED_OPERATIONS is the module's half of a shared contract: the
 * dashboard keeps the same operation list in its registry, derives the
 * apiSchemaHash from it, and treats a shop whose schema misses an expected
 * operation as incompatible. When the real schema and the declared list drift
 * apart, customers see "incompatible" in the dashboard without any test
 * failing. This test pins the list and the actually built schema to each
 * other, in both directions.
 *
 * Unlike the 7.x lines the schema is introspected over HTTP (graphql-base v7
 * test bases do not load on the phpunit 8.5 platform of OXID 6.5), so this
 * suite is skipped when the web server is down.
 */
final class SchemaContractTest extends TestCase
{
    // The OXID 6.5 SDK demodata admin is plain "admin" (the 7.x SDKs use
    // noreply@oxid-esales.com); adjust here if your shop's admin differs.
    private const ADMIN_USER = 'admin';
    private const ADMIN_PASS = 'admin';

    /**
     * Operation name prefixes owned by this module. Schema fields starting
     * with one of these must be declared in SUPPORTED_OPERATIONS.
     */
    private const MODULE_PREFIXES = ['heartbeat', 'requestLogger', 'logSender', 'diagnostics'];

    /** @var string */
    private $graphQlUrl = '';

    /** @var string[] */
    private $schemaOperations = [];

    protected function setUp(): void
    {
        parent::setUp();

        $shopUrl = (string) Registry::getConfig()->getConfigParam('sShopURL');
        $this->assertNotEmpty($shopUrl, 'sShopURL must be configured');
        $this->graphQlUrl = rtrim($shopUrl, '/') . '/graphql/';

        $this->schemaOperations = $this->introspectSchemaOperations();
    }

    public function testEveryDeclaredOperationExistsInSchema(): void
    {
        $missing = array_diff(Module::SUPPORTED_OPERATIONS, $this->schemaOperations);

        $this->assertSame(
            [],
            array_values($missing),
            'SUPPORTED_OPERATIONS declares operations the schema does not provide. '
            . 'The dashboard will call these and fail. Either restore the operation '
            . 'or remove it from Module::SUPPORTED_OPERATIONS (and from the dashboard registry).'
        );
    }

    public function testEveryModuleOperationIsDeclared(): void
    {
        $moduleOperations = [];
        foreach ($this->schemaOperations as $name) {
            foreach (self::MODULE_PREFIXES as $prefix) {
                if (strncmp($name, $prefix, strlen($prefix)) === 0) {
                    $moduleOperations[] = $name;
                    break;
                }
            }
        }

        $undeclared = array_diff($moduleOperations, Module::SUPPORTED_OPERATIONS);

        $this->assertSame(
            [],
            array_values($undeclared),
            'The schema exposes module operations that are not declared in '
            . 'Module::SUPPORTED_OPERATIONS. The apiSchemaHash no longer describes '
            . 'the real API surface; add them to the list (and to the dashboard registry).'
        );
    }

    /**
     * @return string[]
     */
    private function introspectSchemaOperations(): array
    {
        // Admin token: guarantees the introspected schema is the complete one,
        // independent of any visibility rules for anonymous callers.
        $tokenBody = $this->httpGraphQl(sprintf(
            'query { token(username: "%s", password: "%s") }',
            self::ADMIN_USER,
            self::ADMIN_PASS
        ));
        $this->assertArrayNotHasKey('errors', $tokenBody, (string) json_encode($tokenBody));

        $result = $this->httpGraphQl(
            'query {
                __schema {
                    queryType { fields { name } }
                    mutationType { fields { name } }
                }
            }',
            $tokenBody['data']['token']
        );
        $this->assertArrayNotHasKey('errors', $result, (string) json_encode($result));

        $schema = $result['data']['__schema'];

        return array_merge(
            array_column($schema['queryType']['fields'], 'name'),
            array_column($schema['mutationType']['fields'], 'name')
        );
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

        // Generous timeout: a cold shop recompiles caches on the first request.
        $response = @file_get_contents($this->graphQlUrl, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                // PHP's http wrapper defaults to HTTP/1.0, which the SDK's
                // h2-enabled Apache rejects; 1.1 + Connection: close works.
                'protocol_version' => 1.1,
                'header' => $headers . "Connection: close\r\n",
                'content' => (string) json_encode(['query' => $query]),
                'timeout' => 60,
                'ignore_errors' => true,
            ],
        ]));

        if ($response === false) {
            $this->markTestSkipped(
                'Web server not reachable at ' . $this->graphQlUrl
                . ' - schema contract is NOT verified. Start the SDK web stack to run this suite.'
            );
        }

        $body = json_decode($response, true);
        $this->assertIsArray($body, 'GraphQL endpoint did not return JSON: ' . substr($response, 0, 200));

        return $body;
    }
}
