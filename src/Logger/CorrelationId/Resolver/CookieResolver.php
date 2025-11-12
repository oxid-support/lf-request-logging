<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Logger\CorrelationId\Resolver;

class CookieResolver implements ResolverInterface
{
    private string $cookieName;

    public function __construct(string $cookieName)
    {
        $this->cookieName = $cookieName;
    }

    public function resolve(): ?string
    {
        return $_COOKIE[$this->cookieName] ?? '' ?: null;
    }
}
