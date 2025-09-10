<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\CorrelationId;

final class CorrelationIdProvider implements CorrelationIdProviderInterface
{
    public function __construct(
        private CorrelationIdEmitterInterface $emitter,
        private CorrelationIdGeneratorInterface $generator,
        private CorrelationIdResolverInterface $resolver,
    ) {}

    public function provide(): string
    {
        $id = $this->resolver->resolve() ?? $this->generator->generate();
        $this->emitter->emit($id);

        return $id;
    }
}
