<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\CorrelationId\Emitter;

class HeaderEmitter implements EmitterInterface
{
    public function __construct(
        private string $headerName,
    ){}

    public function emit(string $id): void
    {
        if (headers_sent()) {
            return; // @todo oxideshop.log
        }

        header(strtoupper($this->headerName) . ': ' . $id);
    }
}
