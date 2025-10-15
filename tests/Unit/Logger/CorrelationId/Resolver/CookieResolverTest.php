<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Logger\CorrelationId\Resolver;

use OxidSupport\RequestLogger\Logger\CorrelationId\Resolver\CookieResolver;
use OxidSupport\RequestLogger\Logger\CorrelationId\Resolver\ResolverInterface;
use PHPUnit\Framework\TestCase;

class CookieResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clean up $_COOKIE modifications
        unset($_COOKIE['correlation_id']);
        unset($_COOKIE['custom_cookie']);
    }

    public function testImplementsInterface(): void
    {
        $resolver = new CookieResolver('correlation_id');

        $this->assertInstanceOf(ResolverInterface::class, $resolver);
    }

    public function testResolveReturnsNullWhenCookieNotPresent(): void
    {
        $resolver = new CookieResolver('correlation_id');

        $result = $resolver->resolve();

        $this->assertNull($result);
    }

    public function testResolveReturnsValueWhenCookiePresent(): void
    {
        $_COOKIE['correlation_id'] = 'cookie-correlation-id-123';

        $resolver = new CookieResolver('correlation_id');

        $result = $resolver->resolve();

        $this->assertSame('cookie-correlation-id-123', $result);
    }

    public function testResolveWithCustomCookieName(): void
    {
        $_COOKIE['custom_cookie'] = 'custom-value-456';

        $resolver = new CookieResolver('custom_cookie');

        $result = $resolver->resolve();

        $this->assertSame('custom-value-456', $result);
    }

    public function testResolveReturnsNullForEmptyString(): void
    {
        $_COOKIE['correlation_id'] = '';

        $resolver = new CookieResolver('correlation_id');

        $result = $resolver->resolve();

        $this->assertNull($result);
    }

    public function testResolveWithWhitespaceValue(): void
    {
        $_COOKIE['correlation_id'] = '   ';

        $resolver = new CookieResolver('correlation_id');

        $result = $resolver->resolve();

        // Whitespace-only strings should be returned as-is
        $this->assertSame('   ', $result);
    }

    public function testResolveWithNumericValue(): void
    {
        $_COOKIE['correlation_id'] = '12345';

        $resolver = new CookieResolver('correlation_id');

        $result = $resolver->resolve();

        $this->assertSame('12345', $result);
    }

    public function testResolveWithSpecialCharacters(): void
    {
        $_COOKIE['correlation_id'] = 'abc-123_xyz.456';

        $resolver = new CookieResolver('correlation_id');

        $result = $resolver->resolve();

        $this->assertSame('abc-123_xyz.456', $result);
    }

    public function testResolveDoesNotAffectOtherCookies(): void
    {
        $_COOKIE['correlation_id'] = 'target-cookie';
        $_COOKIE['other_cookie'] = 'other-value';

        $resolver = new CookieResolver('correlation_id');

        $result = $resolver->resolve();

        $this->assertSame('target-cookie', $result);
        $this->assertArrayHasKey('other_cookie', $_COOKIE);
        $this->assertSame('other-value', $_COOKIE['other_cookie']);
    }
}
