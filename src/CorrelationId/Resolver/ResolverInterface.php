<?php
declare(strict_types=1);

namespace OxidSupport\RequestLogger\CorrelationId\Resolver;

interface ResolverInterface
{
    public function resolve(): ?string;
}
