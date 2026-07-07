<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiUser\Exception;

use OxidEsales\GraphQL\Base\Exception\Error;

final class UserNotFoundException extends Error
{
    public function __construct()
    {
        parent::__construct('API user not found.');
    }

    public function getCategory(): string
    {
        return 'notfound';
    }
}
