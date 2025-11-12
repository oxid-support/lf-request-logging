<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Logger\CorrelationId;

use OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\EmitterInterface;
use OxidSupport\RequestLogger\Logger\CorrelationId\Resolver\ResolverInterface;

final class CorrelationIdProvider implements CorrelationIdProviderInterface
{
    private EmitterInterface $emitter;
    private CorrelationIdGeneratorInterface $generator;
    private ResolverInterface $resolver;

    public function __construct(
        EmitterInterface $emitter,
        CorrelationIdGeneratorInterface $generator,
        ResolverInterface $resolver
    ) {
        $this->emitter = $emitter;
        $this->generator = $generator;
        $this->resolver = $resolver;
    }

    public function provide(): string
    {
        $id = $this->resolver->resolve() ?? $this->generator->generate();
        $this->emitter->emit($id);

        return $id;
    }
}
