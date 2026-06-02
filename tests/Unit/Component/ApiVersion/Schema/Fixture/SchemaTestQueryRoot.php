<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\ApiVersion\Schema\Fixture;

use OxidSupport\Heartbeat\Component\ApiVersion\DataType\ApiVersionType;
use TheCodingMachine\GraphQLite\Annotations\Query;

final class SchemaTestQueryRoot
{
    #[Query]
    public function apiVersionSchemaProbe(): ApiVersionType
    {
        return new ApiVersionType();
    }
}
