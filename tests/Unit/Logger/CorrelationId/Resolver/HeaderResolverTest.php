<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Logger\CorrelationId\Resolver;

use OxidSupport\RequestLogger\Logger\CorrelationId\Resolver\HeaderResolver;
use OxidSupport\RequestLogger\Logger\CorrelationId\Resolver\ResolverInterface;
use PHPUnit\Framework\TestCase;

class HeaderResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clean up $_SERVER modifications
        unset($_SERVER['HTTP_X_CORRELATION_ID']);
        unset($_SERVER['HTTP_X_REQUEST_ID']);
        unset($_SERVER['HTTP_CUSTOM_HEADER']);
    }

    public function testImplementsInterface(): void
    {
        $resolver = new HeaderResolver('X-Correlation-Id');

        $this->assertInstanceOf(ResolverInterface::class, $resolver);
    }

    public function testResolveReturnsNullWhenHeaderNotPresent(): void
    {
        $resolver = new HeaderResolver('X-Correlation-Id');

        $result = $resolver->resolve();

        $this->assertNull($result);
    }

    public function testResolveReturnsValueWhenHeaderPresent(): void
    {
        $_SERVER['HTTP_X_CORRELATION_ID'] = 'test-correlation-id-123';

        $resolver = new HeaderResolver('X-Correlation-Id');

        $result = $resolver->resolve();

        $this->assertSame('test-correlation-id-123', $result);
    }

    public function testResolveConvertsHeaderNameToServerFormat(): void
    {
        // Header: X-Request-Id becomes $_SERVER['HTTP_X_REQUEST_ID']
        $_SERVER['HTTP_X_REQUEST_ID'] = 'request-456';

        $resolver = new HeaderResolver('X-Request-Id');

        $result = $resolver->resolve();

        $this->assertSame('request-456', $result);
    }

    public function testResolveHandlesDashesToUnderscores(): void
    {
        $_SERVER['HTTP_CUSTOM_HEADER'] = 'custom-value';

        $resolver = new HeaderResolver('Custom-Header');

        $result = $resolver->resolve();

        $this->assertSame('custom-value', $result);
    }

    public function testResolveIsCaseInsensitive(): void
    {
        $_SERVER['HTTP_X_CORRELATION_ID'] = 'case-test-123';

        $resolver = new HeaderResolver('x-correlation-id');

        $result = $resolver->resolve();

        $this->assertSame('case-test-123', $result);
    }

    public function testResolveReturnsNullForEmptyString(): void
    {
        $_SERVER['HTTP_X_CORRELATION_ID'] = '';

        $resolver = new HeaderResolver('X-Correlation-Id');

        $result = $resolver->resolve();

        $this->assertNull($result);
    }

    public function testResolveWithMultipleHyphens(): void
    {
        // Header: My-Custom-Test-Header becomes HTTP_MY_CUSTOM_TEST_HEADER
        $_SERVER['HTTP_MY_CUSTOM_TEST_HEADER'] = 'multi-hyphen-value';

        $resolver = new HeaderResolver('My-Custom-Test-Header');

        $result = $resolver->resolve();

        $this->assertSame('multi-hyphen-value', $result);
    }

    public function testResolveWithWhitespaceValue(): void
    {
        $_SERVER['HTTP_X_CORRELATION_ID'] = '   ';

        $resolver = new HeaderResolver('X-Correlation-Id');

        $result = $resolver->resolve();

        // Whitespace-only strings should be returned as-is (not converted to null)
        $this->assertSame('   ', $result);
    }

    public function testResolveWithNumericValue(): void
    {
        $_SERVER['HTTP_X_CORRELATION_ID'] = '12345';

        $resolver = new HeaderResolver('X-Correlation-Id');

        $result = $resolver->resolve();

        $this->assertSame('12345', $result);
    }
}
