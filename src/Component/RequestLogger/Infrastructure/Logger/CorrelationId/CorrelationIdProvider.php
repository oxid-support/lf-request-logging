<?php

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\CorrelationId;

use OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\CorrelationId\Emitter\EmitterInterface;
use OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\CorrelationId\Resolver\ResolverInterface;

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
        $resolved = $this->resolver->resolve();

        // A resolved id comes from a client cookie/header and is attacker
        // controlled (PHP URL-decodes cookies, so it may contain newlines or
        // control chars). Only accept a safe token; otherwise generate a fresh
        // one, so a malicious value can never reach the log content.
        $id = ($resolved !== null && $this->isValid($resolved))
            ? $resolved
            : $this->generator->generate();

        $this->emitter->emit($id);

        return $id;
    }

    private function isValid(string $id): bool
    {
        return preg_match('/^[A-Za-z0-9\-_]{1,64}$/', $id) === 1;
    }
}
