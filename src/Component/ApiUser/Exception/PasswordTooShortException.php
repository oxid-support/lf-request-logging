<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiUser\Exception;

use OxidEsales\GraphQL\Base\Exception\Error;

final class PasswordTooShortException extends Error
{
    public function __construct()
    {
        parent::__construct('Password must be at least 12 characters long.');
    }

    public function getCategory(): string
    {
        return 'validation';
    }
}
