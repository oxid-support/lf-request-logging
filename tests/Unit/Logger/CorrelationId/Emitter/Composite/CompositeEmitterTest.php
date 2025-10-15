<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Logger\CorrelationId\Emitter\Composite;

use OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\Composite\CompositeEmitter;
use OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\EmitterInterface;
use PHPUnit\Framework\TestCase;

class CompositeEmitterTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $composite = new CompositeEmitter([]);

        $this->assertInstanceOf(EmitterInterface::class, $composite);
    }

    public function testEmitWithNoEmitters(): void
    {
        $composite = new CompositeEmitter([]);

        // Should not throw exception
        $composite->emit('test-id');

        $this->assertTrue(true);
    }

    public function testEmitCallsAllEmitters(): void
    {
        $emitter1 = $this->createMock(EmitterInterface::class);
        $emitter2 = $this->createMock(EmitterInterface::class);
        $emitter3 = $this->createMock(EmitterInterface::class);

        $emitter1->expects($this->once())->method('emit')->with('test-id-123');
        $emitter2->expects($this->once())->method('emit')->with('test-id-123');
        $emitter3->expects($this->once())->method('emit')->with('test-id-123');

        $composite = new CompositeEmitter([$emitter1, $emitter2, $emitter3]);

        $composite->emit('test-id-123');
    }

    public function testEmitCallsEmittersInOrder(): void
    {
        $callOrder = [];

        $emitter1 = $this->createMock(EmitterInterface::class);
        $emitter1->expects($this->once())
            ->method('emit')
            ->with('test-id')
            ->willReturnCallback(function() use (&$callOrder) {
                $callOrder[] = 'emitter1';
            });

        $emitter2 = $this->createMock(EmitterInterface::class);
        $emitter2->expects($this->once())
            ->method('emit')
            ->with('test-id')
            ->willReturnCallback(function() use (&$callOrder) {
                $callOrder[] = 'emitter2';
            });

        $composite = new CompositeEmitter([$emitter1, $emitter2]);

        $composite->emit('test-id');

        $this->assertSame(['emitter1', 'emitter2'], $callOrder);
    }

    public function testEmitWithSingleEmitter(): void
    {
        $emitter = $this->createMock(EmitterInterface::class);
        $emitter->expects($this->once())->method('emit')->with('single-id');

        $composite = new CompositeEmitter([$emitter]);

        $composite->emit('single-id');
    }

    public function testEmitStopsIfOneEmitterFails(): void
    {
        $emitter1 = $this->createMock(EmitterInterface::class);
        $emitter1->expects($this->once())
            ->method('emit')
            ->with('test-id')
            ->willThrowException(new \RuntimeException('Emitter 1 failed'));

        $emitter2 = $this->createMock(EmitterInterface::class);
        // emitter2 should NOT be called because emitter1 throws
        $emitter2->expects($this->never())->method('emit');

        $composite = new CompositeEmitter([$emitter1, $emitter2]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Emitter 1 failed');

        $composite->emit('test-id');
    }

    public function testEmitWithGenerator(): void
    {
        $generator = function() {
            $emitter1 = $this->createMock(EmitterInterface::class);
            $emitter1->expects($this->once())->method('emit')->with('gen-id');
            yield $emitter1;

            $emitter2 = $this->createMock(EmitterInterface::class);
            $emitter2->expects($this->once())->method('emit')->with('gen-id');
            yield $emitter2;
        };

        $composite = new CompositeEmitter($generator());

        $composite->emit('gen-id');

        $this->assertTrue(true);
    }

    public function testEmitPassesSameIdToAllEmitters(): void
    {
        $id = 'shared-correlation-id-456';

        $emitter1 = $this->createMock(EmitterInterface::class);
        $emitter2 = $this->createMock(EmitterInterface::class);

        $emitter1->expects($this->once())->method('emit')->with($id);
        $emitter2->expects($this->once())->method('emit')->with($id);

        $composite = new CompositeEmitter([$emitter1, $emitter2]);

        $composite->emit($id);
    }
}
