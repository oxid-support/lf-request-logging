<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\CorrelationId;

class CorrelationIdGenerator implements CorrelationIdGeneratorInterface
{
    public function generate(): string
    {
        try {
            // 16 random bytes -> 32 lowercase hex chars (bin2hex is already lowercase)
            return bin2hex(random_bytes(16));
        } catch (\Throwable) {
            // Fallback: build 16 bytes with random_int
            $bytes = '';
            for ($i = 0; $i < 16; $i++) {
                $bytes .= chr(random_int(0, 255));
            }
            return bin2hex($bytes); // also 32 lowercase hex
        }
    }
}
