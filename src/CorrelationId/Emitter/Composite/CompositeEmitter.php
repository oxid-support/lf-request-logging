<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\CorrelationId\Emitter\Composite;

use OxidSupport\RequestLogger\CorrelationId\Emitter\EmitterInterface;

class CompositeEmitter implements EmitterInterface
{
    /** EmitterInterface[] $emitters */
    public function __construct(
        private iterable $emitters,
    ) {}

    public function emit(string $id): void
    {
        foreach ($this->emitters as $emitter) {
            $emitter->emit($id);
        }
    }
}
