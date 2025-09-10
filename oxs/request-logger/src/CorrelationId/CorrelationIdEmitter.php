<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\CorrelationId;

class CorrelationIdEmitter implements CorrelationIdEmitterInterface
{
    private bool $emitted = false;

    public function __construct(
        private string $cookieName,
        private int $ttl,
    ) {}

    public function emit(string $id): void
    {
        if (!headers_sent() && !$this->emitted) {
            //header(self::HEADER . ': ' . $id); //@todo
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
            $this->emitted = true;
        }
    }
}
