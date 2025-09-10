<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\CorrelationId;

interface CorrelationIdResolverInterface
{
    public function resolve(): ?string; //@todo null or exception?
}
