<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\CorrelationId\Resolver;

class CookieResolver implements ResolverInterface
{
    public function __construct(
        private string $cookieName,
    ) {}

    public function resolve(): ?string
    {
        return $_COOKIE[$this->cookieName] ?? '' ?: null;
    }
}
