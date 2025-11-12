<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Logger\CorrelationId\Resolver;

class HeaderResolver implements ResolverInterface
{
    private string $headerName;

    public function __construct(string $headerName)
    {
        $this->headerName = $headerName;
    }

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
