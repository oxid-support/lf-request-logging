<?php

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\RequestLogger\Infrastructure\Logger\CorrelationId;

use OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\CorrelationId\CorrelationIdGenerator;
use OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\CorrelationId\CorrelationIdGeneratorInterface;
use PHPUnit\Framework\TestCase;

class CorrelationIdGeneratorTest extends TestCase
{
    private CorrelationIdGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new CorrelationIdGenerator();
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(
            CorrelationIdGeneratorInterface::class,
            $this->generator
        );
    }

    public function testGenerateReturnsString(): void
    {
        $result = $this->generator->generate();

        $this->assertIsString($result);
    }

    public function testGenerateReturns32CharacterHexString(): void
    {
        $result = $this->generator->generate();

        $this->assertSame(32, strlen($result));
        $this->assertRegExp('/^[0-9a-f]{32}$/', $result);
    }

    public function testGenerateReturnsLowercaseHexOnly(): void
    {
        $result = $this->generator->generate();

        $this->assertSame(strtolower($result), $result);
        $this->assertRegExp('/^[0-9a-f]+$/', $result);
    }

    public function testGenerateCreatesUniqueIds(): void
    {
        $ids = [];
        for ($i = 0; $i < 100; $i++) {
            $ids[] = $this->generator->generate();
        }

        $uniqueIds = array_unique($ids);

        $this->assertCount(100, $uniqueIds, 'All generated IDs should be unique');
    }

    public function testGenerateIsDeterministicInLength(): void
    {
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = strlen($this->generator->generate());
        }

        $this->assertSame([32, 32, 32, 32, 32, 32, 32, 32, 32, 32], $results);
    }
}
