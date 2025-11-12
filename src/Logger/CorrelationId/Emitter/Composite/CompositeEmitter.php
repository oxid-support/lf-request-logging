<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\Composite;

use OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\EmitterInterface;

class CompositeEmitter implements EmitterInterface
{
    /** @var EmitterInterface[] */
    private iterable $emitters;

    public function __construct(iterable $emitters)
    {
        $this->emitters = $emitters;
    }

    public function emit(string $id): void
    {
        foreach ($this->emitters as $emitter) {
            $emitter->emit($id);
        }
    }
}
