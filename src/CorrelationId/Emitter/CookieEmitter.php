<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\CorrelationId\Emitter;

class CookieEmitter implements EmitterInterface
{
    public function __construct(
        private string $cookieName,
        private int $ttl,
    ) {}

    public function emit(string $id): void
    {
        setcookie(
            $this->cookieName,
            $id,
            [
                'expires'  => time() + $this->ttl, // 30 days
                'path'     => '/',
                'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }
}
