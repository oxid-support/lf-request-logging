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
 */
#[Type]
final class ApiVersionType
{
    /**
     * @param string[]|null $supportedOperations
     * @param ComponentStatusType[]|null $componentStatus
     */
    public function __construct(
        private readonly bool $isInstalled = true,
        private readonly ?string $apiVersion = null,
        private readonly ?string $apiSchemaHash = null,
        private readonly ?string $moduleVersion = null,
        private readonly ?array $supportedOperations = null,
        private readonly ?array $componentStatus = null,
    ) {
    }

    #[Field]
    public function isInstalled(): bool
    {
        return $this->isInstalled;
    }

    #[Field]
    public function getApiVersion(): ?string
    {
        return $this->apiVersion;
    }

    #[Field]
    public function getApiSchemaHash(): ?string
    {
        return $this->apiSchemaHash;
    }

    #[Field]
    public function getModuleVersion(): ?string
    {
        return $this->moduleVersion;
    }

    /**
     * @return string[]|null
     */
    #[Field]
    public function getSupportedOperations(): ?array
    {
        return $this->supportedOperations;
    }

    /**
     * @return ComponentStatusType[]|null
     */
    #[Field]
    public function getComponentStatus(): ?array
    {
        return $this->componentStatus;
    }
}
