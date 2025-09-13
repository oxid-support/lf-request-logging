<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\CorrelationId\Resolver;

class HeaderResolver implements ResolverInterface
{
    public function __construct(
        private string $headerName,
    ) {}

    /**
     * Testing: curl -i http://localhost.local/ -H "X-Correlation-Id: abc1234"
     * @return string|null
     */
    public function resolve(): ?string
    {
        $headerName = 'HTTP_' . strtoupper(
            str_replace('-', '_', $this->headerName)
        );

        return $_SERVER[$headerName] ?? '' ?: null;
    }
}
