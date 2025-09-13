<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\CorrelationId\Emitter\Decorator;

use OxidSupport\RequestLogger\CorrelationId\Emitter\Composite\CompositeEmitter;
use OxidSupport\RequestLogger\CorrelationId\Emitter\EmitterInterface;

class OnceEmitterDecorator implements EmitterInterface
{
    public bool $emitted = false;

    public function __construct(
        private CompositeEmitter $emitter,
    ) {}

    public function emit(string $id): void
    {
        if (!$this->emitted) {
            $this->emitter->emit($id);
            $this->emitted = true;
        }
    }
}
