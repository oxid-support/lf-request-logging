<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Logger\Processor;

use OxidSupport\RequestLogger\Logger\CorrelationId\CorrelationIdProviderInterface;
use OxidSupport\RequestLogger\Logger\Processor\CorrelationIdProcessor;
use OxidSupport\RequestLogger\Logger\Processor\CorrelationIdProcessorInterface;
use PHPUnit\Framework\TestCase;

class CorrelationIdProcessorTest extends TestCase
{
    private CorrelationIdProviderInterface $provider;
    private CorrelationIdProcessor $processor;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(CorrelationIdProviderInterface::class);
        $this->processor = new CorrelationIdProcessor($this->provider);
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(CorrelationIdProcessorInterface::class, $this->processor);
    }

    public function testInvokeAddsCorrelationIdToContext(): void
    {
        $this->provider
            ->expects($this->once())
            ->method('provide')
            ->willReturn('correlation-id-123');

        $record = [
            'message' => 'Test message',
            'context' => [],
        ];

        $result = ($this->processor)($record);

        $this->assertArrayHasKey('context', $result);
        $this->assertArrayHasKey('correlationId', $result['context']);
        $this->assertSame('correlation-id-123', $result['context']['correlationId']);
    }

    public function testInvokePreservesExistingContext(): void
    {
        $this->provider
            ->expects($this->once())
            ->method('provide')
            ->willReturn('new-correlation-id');

        $record = [
            'message' => 'Test message',
            'context' => [
                'user' => 'john',
                'action' => 'login',
            ],
        ];

        $result = ($this->processor)($record);

        $this->assertSame('john', $result['context']['user']);
        $this->assertSame('login', $result['context']['action']);
        $this->assertSame('new-correlation-id', $result['context']['correlationId']);
    }

    public function testInvokeCreatesContextIfNotPresent(): void
    {
        $this->provider
            ->expects($this->once())
            ->method('provide')
            ->willReturn('correlation-id-456');

        $record = [
            'message' => 'Test message',
        ];

        $result = ($this->processor)($record);

        $this->assertArrayHasKey('context', $result);
        $this->assertSame('correlation-id-456', $result['context']['correlationId']);
    }

    public function testInvokePreservesOtherRecordFields(): void
    {
        $this->provider
            ->expects($this->once())
            ->method('provide')
            ->willReturn('correlation-id-789');

        $record = [
            'message' => 'Log message',
            'level' => 'INFO',
            'channel' => 'app',
            'datetime' => new \DateTime(),
            'context' => [],
        ];

        $result = ($this->processor)($record);

        $this->assertSame('Log message', $result['message']);
        $this->assertSame('INFO', $result['level']);
        $this->assertSame('app', $result['channel']);
        $this->assertInstanceOf(\DateTime::class, $result['datetime']);
    }

    public function testInvokeOverwritesExistingCorrelationIdInContext(): void
    {
        $this->provider
            ->expects($this->once())
            ->method('provide')
            ->willReturn('new-correlation-id');

        $record = [
            'message' => 'Test',
            'context' => [
                'correlationId' => 'old-correlation-id',
            ],
        ];

        $result = ($this->processor)($record);

        $this->assertSame('new-correlation-id', $result['context']['correlationId']);
    }

    public function testInvokeCallsProviderEveryTime(): void
    {
        $this->provider
            ->expects($this->exactly(3))
            ->method('provide')
            ->willReturnOnConsecutiveCalls('id-1', 'id-2', 'id-3');

        $record = ['message' => 'Test', 'context' => []];

        $result1 = ($this->processor)($record);
        $result2 = ($this->processor)($record);
        $result3 = ($this->processor)($record);

        $this->assertSame('id-1', $result1['context']['correlationId']);
        $this->assertSame('id-2', $result2['context']['correlationId']);
        $this->assertSame('id-3', $result3['context']['correlationId']);
    }

    public function testInvokeReturnsArray(): void
    {
        $this->provider
            ->expects($this->once())
            ->method('provide')
            ->willReturn('test-id');

        $record = ['message' => 'Test', 'context' => []];

        $result = ($this->processor)($record);

        $this->assertIsArray($result);
    }

    public function testInvokeWithEmptyRecord(): void
    {
        $this->provider
            ->expects($this->once())
            ->method('provide')
            ->willReturn('empty-record-id');

        $record = [];

        $result = ($this->processor)($record);

        $this->assertArrayHasKey('context', $result);
        $this->assertSame('empty-record-id', $result['context']['correlationId']);
    }

    public function testInvokeWithNestedContextData(): void
    {
        $this->provider
            ->expects($this->once())
            ->method('provide')
            ->willReturn('nested-id');

        $record = [
            'message' => 'Test',
            'context' => [
                'user' => [
                    'id' => 123,
                    'name' => 'John',
                ],
                'metadata' => [
                    'timestamp' => time(),
                ],
            ],
        ];

        $result = ($this->processor)($record);

        $this->assertIsArray($result['context']['user']);
        $this->assertSame(123, $result['context']['user']['id']);
        $this->assertIsArray($result['context']['metadata']);
        $this->assertSame('nested-id', $result['context']['correlationId']);
    }
}
