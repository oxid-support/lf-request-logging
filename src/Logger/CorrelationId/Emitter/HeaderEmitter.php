<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Logger\CorrelationId\Emitter;

class HeaderEmitter implements EmitterInterface
{
    private string $headerName;

    public function __construct(string $headerName)
    {
        $this->headerName = $headerName;
    }

    public function emit(string $id): void
    {
        if (headers_sent()) {
            return;
        }

        header(strtoupper($this->headerName) . ': ' . $id);
    }
}
