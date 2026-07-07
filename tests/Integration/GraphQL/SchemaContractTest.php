<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Integration\GraphQL;

use OxidEsales\GraphQL\Base\Tests\Integration\TokenTestCase;
use OxidSupport\Heartbeat\Module\Module;

/**
 * Guards the GraphQL contract with the Heartbeat dashboard.
 *
 * Module::SUPPORTED_OPERATIONS is the module's half of a shared contract: the
 * dashboard keeps the same operation list in its registry, derives the
 * apiSchemaHash from it, and treats a shop whose schema misses an expected
 * operation as incompatible. When the real schema and the declared list drift
 * apart (operation removed from a controller but not from the list, or the
 * other way around), customers see "incompatible" in the dashboard without any
 * test failing. This test pins the list and the actually built schema to each
 * other, in both directions.
 */
final class SchemaContractTest extends TokenTestCase
{
    /**
     * Operation name prefixes owned by this module. Schema fields starting
     * with one of these must be declared in SUPPORTED_OPERATIONS.
     */
    private const MODULE_PREFIXES = ['heartbeat', 'requestLogger', 'logSender', 'diagnostics'];

    /** @var string[] */
    private array $schemaOperations = [];

    public function setUp(): void
    {
        parent::setUp();

        // Admin token: guarantees the introspected schema is the complete one,
        // independent of any visibility rules for anonymous callers.
        $this->prepareToken();

        $result = $this->query('
            query {
                __schema {
                    queryType { fields { name } }
                    mutationType { fields { name } }
                }
            }
        ');

        $this->assertArrayNotHasKey('errors', $result['body'], (string) json_encode($result['body']));

        $schema = $result['body']['data']['__schema'];
        $this->schemaOperations = array_merge(
            array_column($schema['queryType']['fields'], 'name'),
            array_column($schema['mutationType']['fields'], 'name'),
        );
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
        $moduleOperations = array_filter(
            $this->schemaOperations,
            static function (string $name): bool {
                foreach (self::MODULE_PREFIXES as $prefix) {
                    if (str_starts_with($name, $prefix)) {
                        return true;
                    }
                }

                return false;
            }
        );

        $undeclared = array_diff($moduleOperations, Module::SUPPORTED_OPERATIONS);

        $this->assertSame(
            [],
            array_values($undeclared),
            'The schema exposes module operations that are not declared in '
            . 'Module::SUPPORTED_OPERATIONS. The apiSchemaHash no longer describes '
            . 'the real API surface; add them to the list (and to the dashboard registry).'
        );
    }
}
