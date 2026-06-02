<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\ApiVersion\Schema;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema;
use OxidSupport\Heartbeat\Tests\Unit\Component\ApiVersion\Schema\Fixture\SchemaTestQueryRoot;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Psr16Cache;
use TheCodingMachine\GraphQLite\SchemaFactory;

/**
 * Pins the GraphQL field names and types that ApiVersionType exposes to clients.
 *
 * GraphQLite derives field names from getter method names ("isInstalled" -> "installed",
 * "getApiVersion" -> "apiVersion"). Refactors that change accessors silently rename
 * fields in the schema. This test fails loudly when that happens.
 */
final class ApiVersionSchemaTest extends TestCase
{
    public function testApiVersionTypeExposesExactFieldSet(): void
    {
        $type = $this->apiVersionType();

        self::assertSame(
            ['installed', 'apiVersion', 'apiSchemaHash', 'moduleVersion', 'supportedOperations', 'componentStatus'],
            array_keys($type->getFields()),
            'ApiVersionType field set changed - confirm dashboard and Packagist consumers before merging.',
        );
    }

    public function testApiVersionTypeFieldNullability(): void
    {
        $type = $this->apiVersionType();

        self::assertSame('Boolean!', (string) $type->getField('installed')->getType());
        self::assertSame('String', (string) $type->getField('apiVersion')->getType());
        self::assertSame('String', (string) $type->getField('apiSchemaHash')->getType());
        self::assertSame('String', (string) $type->getField('moduleVersion')->getType());
        self::assertSame('[String!]', (string) $type->getField('supportedOperations')->getType());
        self::assertSame('[ComponentStatusType!]', (string) $type->getField('componentStatus')->getType());
    }

    private function apiVersionType(): ObjectType
    {
        $schema = $this->buildSchema();
        $type = $schema->getType('ApiVersionType');
        self::assertInstanceOf(ObjectType::class, $type);

        return $type;
    }

    private function buildSchema(): Schema
    {
        $container = new class () implements ContainerInterface {
            public function get(string $id): object
            {
                return new SchemaTestQueryRoot();
            }

            public function has(string $id): bool
            {
                return $id === SchemaTestQueryRoot::class;
            }
        };

        $factory = new SchemaFactory(new Psr16Cache(new NullAdapter()), $container);
        $factory->addControllerNamespace('OxidSupport\\Heartbeat\\Tests\\Unit\\Component\\ApiVersion\\Schema\\Fixture');
        $factory->addTypeNamespace('OxidSupport\\Heartbeat\\Component\\ApiVersion\\DataType');

        return $factory->createSchema();
    }
}
