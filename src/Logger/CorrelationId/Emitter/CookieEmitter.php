<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Logger\CorrelationId\Emitter;

class CookieEmitter implements EmitterInterface
{
    private string $cookieName;
    private int $ttl;

    public function __construct(string $cookieName, int $ttl)
    {
        $this->cookieName = $cookieName;
        $this->ttl = $ttl;
    }

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
