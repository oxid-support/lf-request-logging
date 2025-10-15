<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Logger\CorrelationId\Resolver\Composite;

use OxidSupport\RequestLogger\Logger\CorrelationId\Resolver\Composite\CompositeResolver;
use OxidSupport\RequestLogger\Logger\CorrelationId\Resolver\ResolverInterface;
use PHPUnit\Framework\TestCase;

class CompositeResolverTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $composite = new CompositeResolver([]);

        $this->assertInstanceOf(ResolverInterface::class, $composite);
    }

    public function testResolveReturnsNullWhenNoResolvers(): void
    {
        $composite = new CompositeResolver([]);

        $result = $composite->resolve();

        $this->assertNull($result);
    }

    public function testResolveReturnsNullWhenAllResolversReturnNull(): void
    {
        $resolver1 = $this->createMock(ResolverInterface::class);
        $resolver2 = $this->createMock(ResolverInterface::class);

        $resolver1->expects($this->once())->method('resolve')->willReturn(null);
        $resolver2->expects($this->once())->method('resolve')->willReturn(null);

        $composite = new CompositeResolver([$resolver1, $resolver2]);

        $result = $composite->resolve();

        $this->assertNull($result);
    }

    public function testResolveReturnsFirstNonNullValue(): void
    {
        $resolver1 = $this->createMock(ResolverInterface::class);
        $resolver2 = $this->createMock(ResolverInterface::class);
        $resolver3 = $this->createMock(ResolverInterface::class);

        $resolver1->expects($this->once())->method('resolve')->willReturn(null);
        $resolver2->expects($this->once())->method('resolve')->willReturn('found-id-123');
        $resolver3->expects($this->never())->method('resolve'); // Should not be called

        $composite = new CompositeResolver([$resolver1, $resolver2, $resolver3]);

        $result = $composite->resolve();

        $this->assertSame('found-id-123', $result);
    }

    public function testResolveStopsAtFirstMatch(): void
    {
        $resolver1 = $this->createMock(ResolverInterface::class);
        $resolver2 = $this->createMock(ResolverInterface::class);
        $resolver3 = $this->createMock(ResolverInterface::class);

        $resolver1->expects($this->once())->method('resolve')->willReturn('first-match');
        $resolver2->expects($this->never())->method('resolve');
        $resolver3->expects($this->never())->method('resolve');

        $composite = new CompositeResolver([$resolver1, $resolver2, $resolver3]);

        $result = $composite->resolve();

        $this->assertSame('first-match', $result);
    }

    public function testResolveWithSingleResolver(): void
    {
        $resolver = $this->createMock(ResolverInterface::class);
        $resolver->expects($this->once())->method('resolve')->willReturn('single-id');

        $composite = new CompositeResolver([$resolver]);

        $result = $composite->resolve();

        $this->assertSame('single-id', $result);
    }

    public function testResolveRespectsResolverOrder(): void
    {
        // First resolver returns null, second returns value
        $resolver1 = $this->createMock(ResolverInterface::class);
        $resolver1->expects($this->once())->method('resolve')->willReturn(null);

        $resolver2 = $this->createMock(ResolverInterface::class);
        $resolver2->expects($this->once())->method('resolve')->willReturn('second-resolver-id');

        $composite = new CompositeResolver([$resolver1, $resolver2]);

        $result = $composite->resolve();

        $this->assertSame('second-resolver-id', $result);
    }

    public function testResolveWithGenerator(): void
    {
        $generator = function() {
            $resolver1 = $this->createMock(ResolverInterface::class);
            $resolver1->expects($this->once())->method('resolve')->willReturn(null);
            yield $resolver1;

            $resolver2 = $this->createMock(ResolverInterface::class);
            $resolver2->expects($this->once())->method('resolve')->willReturn('gen-id');
            yield $resolver2;
        };

        $composite = new CompositeResolver($generator());

        $result = $composite->resolve();

        $this->assertSame('gen-id', $result);
    }

    public function testResolveDoesNotCallSubsequentResolversAfterMatch(): void
    {
        $callCount = 0;

        $resolver1 = $this->createMock(ResolverInterface::class);
        $resolver1->expects($this->once())->method('resolve')->willReturnCallback(function() use (&$callCount) {
            $callCount++;
            return null;
        });

        $resolver2 = $this->createMock(ResolverInterface::class);
        $resolver2->expects($this->once())->method('resolve')->willReturnCallback(function() use (&$callCount) {
            $callCount++;
            return 'matched';
        });

        $resolver3 = $this->createMock(ResolverInterface::class);
        $resolver3->expects($this->never())->method('resolve');

        $composite = new CompositeResolver([$resolver1, $resolver2, $resolver3]);

        $result = $composite->resolve();

        $this->assertSame('matched', $result);
        $this->assertSame(2, $callCount);
    }

    public function testResolveHandlesExceptionFromResolver(): void
    {
        $resolver1 = $this->createMock(ResolverInterface::class);
        $resolver1->expects($this->once())
            ->method('resolve')
            ->willThrowException(new \RuntimeException('Resolver failed'));

        $composite = new CompositeResolver([$resolver1]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Resolver failed');

        $composite->resolve();
    }
}
