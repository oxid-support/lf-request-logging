<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiUser\Exception;

use OxidEsales\GraphQL\Base\Exception\Error;
use Throwable;

/**
 * Thrown when setting the API user password fails after the setup token was
 * already cleared. The caller restores the token before throwing this, so the
 * setup stays retryable instead of locking the service user out. See OXS-3068.
 */
final class SetPasswordFailedException extends Error
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(
            'API user password update failed; setup token preserved for retry. '
            . 'Run pending module migrations, then retry.',
            previous: $previous
        );
    }

    public function getCategory(): string
    {
        return 'request';
    }
}
