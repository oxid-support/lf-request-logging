<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiUser\Service;

final class TokenGenerator implements TokenGeneratorInterface
{
    public function generate(): string
    {
        // The setup token is the only gate on the unauthenticated
        // heartbeatSetPassword mutation, so it must be unguessable.
        // OXID's generateUId() is md5(uniqid() . microtime()), which is not a
        // CSPRNG; use random_bytes() instead. 32 bytes = 256 bits of entropy.
        return bin2hex(random_bytes(32));
    }
}
