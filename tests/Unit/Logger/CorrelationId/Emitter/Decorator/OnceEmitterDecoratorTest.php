<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Logger\CorrelationId\Emitter\Decorator;

use OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\Composite\CompositeEmitter;
use OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\Decorator\OnceEmitterDecorator;
use OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\EmitterInterface;
use PHPUnit\Framework\TestCase;

class OnceEmitterDecoratorTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $compositeEmitter = $this->createMock(CompositeEmitter::class);
        $decorator = new OnceEmitterDecorator($compositeEmitter);

        $this->assertInstanceOf(EmitterInterface::class, $decorator);
    }

    public function testEmitCallsDecoratedEmitterOnFirstCall(): void
    {
        $compositeEmitter = $this->createMock(CompositeEmitter::class);
        $compositeEmitter->expects($this->once())->method('emit')->with('test-id-123');

        $decorator = new OnceEmitterDecorator($compositeEmitter);

        $decorator->emit('test-id-123');
    }

    public function testEmitDoesNotCallDecoratedEmitterOnSecondCall(): void
    {
        $compositeEmitter = $this->createMock(CompositeEmitter::class);
        $compositeEmitter->expects($this->once())->method('emit')->with('test-id-123');

        $decorator = new OnceEmitterDecorator($compositeEmitter);

        $decorator->emit('test-id-123');
        $decorator->emit('test-id-123'); // Should not call emit again
    }

    public function testEmitIgnoresSubsequentCalls(): void
    {
        $compositeEmitter = $this->createMock(CompositeEmitter::class);
        $compositeEmitter->expects($this->once())->method('emit')->with('first-id');

        $decorator = new OnceEmitterDecorator($compositeEmitter);

        $decorator->emit('first-id');
        $decorator->emit('second-id'); // Different ID, still ignored
        $decorator->emit('third-id');  // Also ignored
    }

    public function testEmittedFlagIsFalseInitially(): void
    {
        $compositeEmitter = $this->createMock(CompositeEmitter::class);
        $decorator = new OnceEmitterDecorator($compositeEmitter);

        $this->assertFalse($decorator->emitted);
    }

    public function testEmittedFlagIsTrueAfterFirstEmit(): void
    {
        $compositeEmitter = $this->createMock(CompositeEmitter::class);
        $compositeEmitter->expects($this->once())->method('emit')->with('test-id');

        $decorator = new OnceEmitterDecorator($compositeEmitter);

        $decorator->emit('test-id');

        $this->assertTrue($decorator->emitted);
    }

    public function testEmittedFlagRemainsTrue(): void
    {
        $compositeEmitter = $this->createMock(CompositeEmitter::class);
        $compositeEmitter->expects($this->once())->method('emit');

        $decorator = new OnceEmitterDecorator($compositeEmitter);

        $decorator->emit('id1');
        $this->assertTrue($decorator->emitted);

        $decorator->emit('id2');
        $this->assertTrue($decorator->emitted);

        $decorator->emit('id3');
        $this->assertTrue($decorator->emitted);
    }

    public function testEmitHandlesMultipleCallsWithSameId(): void
    {
        $compositeEmitter = $this->createMock(CompositeEmitter::class);
        $compositeEmitter->expects($this->once())->method('emit')->with('same-id');

        $decorator = new OnceEmitterDecorator($compositeEmitter);

        $decorator->emit('same-id');
        $decorator->emit('same-id');
        $decorator->emit('same-id');

        $this->assertTrue($decorator->emitted);
    }

    public function testEmitOnlyCallsDecoratedEmitterExactlyOnce(): void
    {
        $callCount = 0;

        $compositeEmitter = $this->createMock(CompositeEmitter::class);
        $compositeEmitter->expects($this->once())
            ->method('emit')
            ->willReturnCallback(function() use (&$callCount) {
                $callCount++;
            });

        $decorator = new OnceEmitterDecorator($compositeEmitter);

        for ($i = 0; $i < 10; $i++) {
            $decorator->emit("id-$i");
        }

        $this->assertSame(1, $callCount);
    }
}
