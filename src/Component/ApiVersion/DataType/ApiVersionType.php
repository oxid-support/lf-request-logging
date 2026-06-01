<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiVersion\DataType;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * GraphQL type for API version information.
 *
 * Two response shapes depending on caller authentication state:
 *   - unauthenticated: only `isInstalled` is populated, everything else is null
 *   - authenticated: all fields populated
 *
 * @Type
 */
final class ApiVersionType
{
    private bool $isInstalled;
    private ?string $apiVersion;
    private ?string $apiSchemaHash;
    private ?string $moduleVersion;

    /** @var string[]|null */
    private ?array $supportedOperations;

    /** @var ComponentStatusType[]|null */
    private ?array $componentStatus;

    /**
     * @param string[]|null $supportedOperations
     * @param ComponentStatusType[]|null $componentStatus
     */
    public function __construct(
        bool $isInstalled = true,
        ?string $apiVersion = null,
        ?string $apiSchemaHash = null,
        ?string $moduleVersion = null,
        ?array $supportedOperations = null,
        ?array $componentStatus = null
    ) {
        $this->isInstalled = $isInstalled;
        $this->apiVersion = $apiVersion;
        $this->apiSchemaHash = $apiSchemaHash;
        $this->moduleVersion = $moduleVersion;
        $this->supportedOperations = $supportedOperations;
        $this->componentStatus = $componentStatus;
    }

    /**
     * @Field
     */
    public function isInstalled(): bool
    {
        return $this->isInstalled;
    }

    /**
     * @Field
     */
    public function getApiVersion(): ?string
    {
        return $this->apiVersion;
    }

    /**
     * @Field
     */
    public function getApiSchemaHash(): ?string
    {
        return $this->apiSchemaHash;
    }

    /**
     * @Field
     */
    public function getModuleVersion(): ?string
    {
        return $this->moduleVersion;
    }

    /**
     * @return string[]|null
     *
     * @Field
     */
    public function getSupportedOperations(): ?array
    {
        return $this->supportedOperations;
    }

    /**
     * @return ComponentStatusType[]|null
     *
     * @Field
     */
    public function getComponentStatus(): ?array
    {
        return $this->componentStatus;
    }
}
