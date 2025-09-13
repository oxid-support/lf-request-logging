<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\CorrelationId;

use OxidSupport\RequestLogger\CorrelationId\Emitter\EmitterInterface;
use OxidSupport\RequestLogger\CorrelationId\Resolver\ResolverInterface;

final class CorrelationIdProvider implements CorrelationIdProviderInterface
{
    public function __construct(
        private EmitterInterface $emitter,
        private CorrelationIdGeneratorInterface $generator,
        private ResolverInterface $resolver,
    ) {}

    public function provide(): string
    {
        $id = $this->resolver->resolve() ?? $this->generator->generate();
        $this->emitter->emit($id);

        return $id;
    }
}
