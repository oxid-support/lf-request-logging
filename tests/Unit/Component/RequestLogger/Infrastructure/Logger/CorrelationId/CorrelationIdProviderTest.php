<?php

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\RequestLogger\Infrastructure\Logger\CorrelationId;

use OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\CorrelationId\CorrelationIdGenerator;
use OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\CorrelationId\CorrelationIdGeneratorInterface;
use OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\CorrelationId\CorrelationIdProvider;
use OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\CorrelationId\CorrelationIdProviderInterface;
use OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\CorrelationId\Emitter\EmitterInterface;
use OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\CorrelationId\Resolver\ResolverInterface;
use PHPUnit\Framework\TestCase;

class CorrelationIdProviderTest extends TestCase
{
    private EmitterInterface $emitter;
    private CorrelationIdGeneratorInterface $generator;
    private ResolverInterface $resolver;

    protected function setUp(): void
    {
        $this->emitter = $this->createMock(EmitterInterface::class);
        $this->generator = $this->createMock(CorrelationIdGeneratorInterface::class);
        $this->resolver = $this->createMock(ResolverInterface::class);
    }

    public function testImplementsInterface(): void
    {
        $provider = new CorrelationIdProvider(
            $this->emitter,
            $this->generator,
            $this->resolver
        );

        $this->assertInstanceOf(CorrelationIdProviderInterface::class, $provider);
    }

    public function testProvideUsesResolvedIdWhenAvailable(): void
    {
        $this->resolver
            ->expects($this->once())
            ->method('resolve')
            ->willReturn('resolved-id-123');

        $this->emitter
            ->expects($this->once())
            ->method('emit')
            ->with('resolved-id-123');

        $this->generator
            ->expects($this->never())
            ->method('generate');

        $provider = new CorrelationIdProvider(
            $this->emitter,
            $this->generator,
            $this->resolver
        );

        $result = $provider->provide();

        $this->assertSame('resolved-id-123', $result);
    }

    /**
     * A correlation id resolved from a client cookie/header is attacker
     * controlled. PHP URL-decodes cookies, so it can contain newlines/control
     * chars, which would let an attacker forge log lines. The provider must
     * reject anything that is not a safe token and generate a fresh id instead.
     *
     * @dataProvider maliciousCorrelationIdProvider
     */
    public function testProvideRejectsMalformedResolvedId(string $malicious): void
    {
        $this->resolver
            ->expects($this->once())
            ->method('resolve')
            ->willReturn($malicious);

        $this->generator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('generated-safe-id');

        $this->emitter
            ->expects($this->once())
            ->method('emit')
            ->with('generated-safe-id');

        $provider = new CorrelationIdProvider(
            $this->emitter,
            $this->generator,
            $this->resolver
        );

        $this->assertSame('generated-safe-id', $provider->provide());
    }

    public static function maliciousCorrelationIdProvider(): array
    {
        return [
            'newline injection' => ["abc\n2026-01-01 00:00:00 FAKE LOG LINE"],
            'carriage return' => ["abc\r\ninjected"],
            'null byte' => ["abc\0def"],
            'spaces' => ['has spaces'],
            'too long' => [str_repeat('a', 65)],
            'empty' => [''],
            'special chars' => ['<script>alert(1)</script>'],
        ];
    }

    public function testProvideGeneratesIdWhenResolverReturnsNull(): void
    {
        $this->resolver
            ->expects($this->once())
            ->method('resolve')
            ->willReturn(null);

        $this->generator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('generated-id-456');

        $this->emitter
            ->expects($this->once())
            ->method('emit')
            ->with('generated-id-456');

        $provider = new CorrelationIdProvider(
            $this->emitter,
            $this->generator,
            $this->resolver
        );

        $result = $provider->provide();

        $this->assertSame('generated-id-456', $result);
    }

    public function testProvideEmitsTheIdBeforeReturning(): void
    {
        $callOrder = [];

        $this->resolver
            ->expects($this->once())
            ->method('resolve')
            ->willReturn('test-id');

        $this->emitter
            ->expects($this->once())
            ->method('emit')
            ->with('test-id')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'emit';
            });

        $provider = new CorrelationIdProvider(
            $this->emitter,
            $this->generator,
            $this->resolver
        );

        $result = $provider->provide();
        $callOrder[] = 'return';

        $this->assertSame(['emit', 'return'], $callOrder);
        $this->assertSame('test-id', $result);
    }

    public function testProvideReturnsString(): void
    {
        $this->resolver
            ->expects($this->once())
            ->method('resolve')
            ->willReturn(null);

        $this->generator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('new-id');

        $this->emitter
            ->expects($this->once())
            ->method('emit')
            ->with('new-id');

        $provider = new CorrelationIdProvider(
            $this->emitter,
            $this->generator,
            $this->resolver
        );

        $result = $provider->provide();

        $this->assertIsString($result);
    }

    public function testProvideCallsResolverFirst(): void
    {
        $callOrder = [];

        $this->resolver
            ->expects($this->once())
            ->method('resolve')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'resolve';
                return null;
            });

        $this->generator
            ->expects($this->once())
            ->method('generate')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'generate';
                return 'generated';
            });

        $this->emitter
            ->expects($this->once())
            ->method('emit')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'emit';
            });

        $provider = new CorrelationIdProvider(
            $this->emitter,
            $this->generator,
            $this->resolver
        );

        $provider->provide();

        $this->assertSame(['resolve', 'generate', 'emit'], $callOrder);
    }

    public function testProvideConsistentlyReturnsResolvedId(): void
    {
        $this->resolver
            ->expects($this->exactly(3))
            ->method('resolve')
            ->willReturn('consistent-id');

        $this->emitter
            ->expects($this->exactly(3))
            ->method('emit')
            ->with('consistent-id');

        $provider = new CorrelationIdProvider(
            $this->emitter,
            $this->generator,
            $this->resolver
        );

        $id1 = $provider->provide();
        $id2 = $provider->provide();
        $id3 = $provider->provide();

        $this->assertSame('consistent-id', $id1);
        $this->assertSame('consistent-id', $id2);
        $this->assertSame('consistent-id', $id3);
    }

    public function testProvideWithRealGenerator(): void
    {
        $realGenerator = new CorrelationIdGenerator();

        $this->resolver
            ->expects($this->once())
            ->method('resolve')
            ->willReturn(null);

        $this->emitter
            ->expects($this->once())
            ->method('emit')
            ->willReturnCallback(function ($id) {
                $this->assertIsString($id);
                $this->assertSame(32, strlen($id));
            });

        $provider = new CorrelationIdProvider(
            $this->emitter,
            $realGenerator,
            $this->resolver
        );

        $result = $provider->provide();

        $this->assertIsString($result);
        $this->assertSame(32, strlen($result));
    }
}
