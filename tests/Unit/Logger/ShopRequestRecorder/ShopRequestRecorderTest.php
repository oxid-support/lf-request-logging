<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Logger\ShopRequestRecorder;

use OxidSupport\RequestLogger\Logger\ShopRequestRecorder\ShopRequestRecorder;
use OxidSupport\RequestLogger\Logger\ShopRequestRecorder\ShopRequestRecorderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ShopRequestRecorderTest extends TestCase
{
    private LoggerInterface $logger;
    private ShopRequestRecorder $recorder;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->recorder = new ShopRequestRecorder($this->logger);
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(ShopRequestRecorderInterface::class, $this->recorder);
    }

    public function testLogStartCallsLoggerInfoWithCorrectMessage(): void
    {
        $record = ['key' => 'value'];

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('request.start', $record);

        $this->recorder->logStart($record);
    }

    public function testLogSymbolsCallsLoggerDebugWithCorrectMessage(): void
    {
        $record = ['symbols' => ['Class1', 'Class2']];

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with('request.symbols', $record);

        $this->recorder->logSymbols($record);
    }

    public function testLogFinishCallsLoggerInfoWithCorrectMessage(): void
    {
        $record = ['status' => 'completed'];

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('request.finish', $record);

        $this->recorder->logFinish($record);
    }

    public function testLogStartWithEmptyRecord(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('request.start', []);

        $this->recorder->logStart([]);
    }

    public function testLogSymbolsWithEmptyRecord(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with('request.symbols', []);

        $this->recorder->logSymbols([]);
    }

    public function testLogFinishWithEmptyRecord(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('request.finish', []);

        $this->recorder->logFinish([]);
    }

    public function testLogStartWithComplexData(): void
    {
        $record = [
            'request' => [
                'method' => 'POST',
                'uri' => '/api/endpoint',
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ],
            'timestamp' => time(),
        ];

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('request.start', $record);

        $this->recorder->logStart($record);
    }

    public function testLogSymbolsWithSymbolList(): void
    {
        $record = [
            'symbols' => [
                'Namespace\\Class1',
                'Namespace\\Class2',
                'Namespace\\Interface1',
            ],
            'count' => 3,
        ];

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with('request.symbols', $record);

        $this->recorder->logSymbols($record);
    }

    public function testLogFinishWithDurationAndStatus(): void
    {
        $record = [
            'duration' => 1.234,
            'status' => 200,
            'response_size' => 1024,
        ];

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('request.finish', $record);

        $this->recorder->logFinish($record);
    }

    public function testAllMethodsDoNotReturnValue(): void
    {
        $this->logger
            ->expects($this->exactly(2))
            ->method('info');

        $this->logger
            ->expects($this->once())
            ->method('debug');

        $result1 = $this->recorder->logStart([]);
        $result2 = $this->recorder->logSymbols([]);
        $result3 = $this->recorder->logFinish([]);

        $this->assertNull($result1);
        $this->assertNull($result2);
        $this->assertNull($result3);
    }

    public function testMultipleLogsInSequence(): void
    {
        $callCount = 0;
        $this->logger
            ->expects($this->exactly(3))
            ->method($this->callback(function ($method) use (&$callCount) {
                $callCount++;
                switch ($callCount) {
                    case 1:
                        $this->assertEquals('info', $method);
                        break;
                    case 2:
                        $this->assertEquals('debug', $method);
                        break;
                    case 3:
                        $this->assertEquals('info', $method);
                        break;
                }
                return true;
            }));

        $this->recorder->logStart(['action' => 'start']);
        $this->recorder->logSymbols(['symbols' => []]);
        $this->recorder->logFinish(['action' => 'finish']);
    }

    public function testLogStartPreservesRecordStructure(): void
    {
        $originalRecord = [
            'nested' => [
                'data' => [
                    'value' => 123,
                ],
            ],
        ];

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('request.start', $this->callback(function($record) use ($originalRecord) {
                return $record === $originalRecord;
            }));

        $this->recorder->logStart($originalRecord);
    }
}
