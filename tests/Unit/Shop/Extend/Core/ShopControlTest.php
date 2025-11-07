<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Shop\Extend\Core;

use OxidSupport\RequestLogger\Shop\Extend\Core\ShopControl;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class ShopControlTest extends TestCase
{
    private ReflectionMethod $redactUrlQueryParamsMethod;

    protected function setUp(): void
    {
        $reflection = new ReflectionClass(ShopControl::class);
        $this->redactUrlQueryParamsMethod = $reflection->getMethod('redactUrlQueryParams');
        $this->redactUrlQueryParamsMethod->setAccessible(true);
    }

    private function invokeRedactUrlQueryParams(?string $url): ?string
    {
        // Create a partial mock that allows calling the real private method
        $shopControl = $this->getMockBuilder(ShopControl::class)
            ->disableOriginalConstructor()
            ->onlyMethods([]) // Don't mock any methods, use the real implementation
            ->getMock();

        return $this->redactUrlQueryParamsMethod->invoke($shopControl, $url);
    }

    public function testRedactUrlQueryParams_WithNull_ReturnsNull(): void
    {
        $result = $this->invokeRedactUrlQueryParams(null);

        $this->assertNull($result);
    }

    public function testRedactUrlQueryParams_WithNoQueryString_ReturnsOriginalUrl(): void
    {
        $url = 'http://localhost.local/admin/index.php';
        $result = $this->invokeRedactUrlQueryParams($url);

        $this->assertSame($url, $result);
    }

    public function testRedactUrlQueryParams_WithQueryParams_RedactsAllValues(): void
    {
        $url = 'http://localhost.local/admin/index.php?editlanguage=0&force_admin_sid=dc9440e1fcd2cf8f3a7a623ae65c505f&stoken=FF4399CF';
        $result = $this->invokeRedactUrlQueryParams($url);

        $this->assertStringContainsString('editlanguage=[redacted]', $result);
        $this->assertStringContainsString('force_admin_sid=[redacted]', $result);
        $this->assertStringContainsString('stoken=[redacted]', $result);
        $this->assertStringNotContainsString('dc9440e1fcd2cf8f3a7a623ae65c505f', $result);
        $this->assertStringNotContainsString('FF4399CF', $result);
        // Verify [redacted] is not URL-encoded
        $this->assertStringNotContainsString('%5B', $result);
        $this->assertStringNotContainsString('%5D', $result);
    }

    public function testRedactUrlQueryParams_PreservesSchemeHostAndPath(): void
    {
        $url = 'http://localhost.local/admin/index.php?cl=navigation&fnc=test&sid=secret';
        $result = $this->invokeRedactUrlQueryParams($url);

        $this->assertStringStartsWith('http://localhost.local/admin/index.php?', $result);
        $this->assertStringContainsString('sid=[redacted]', $result);
    }

    public function testRedactUrlQueryParams_WithPort_PreservesPort(): void
    {
        $url = 'http://localhost.local:8080/admin/index.php?param=value';
        $result = $this->invokeRedactUrlQueryParams($url);

        $this->assertStringContainsString(':8080', $result);
        $this->assertStringContainsString('param=[redacted]', $result);
    }

    public function testRedactUrlQueryParams_WithFragment_PreservesFragment(): void
    {
        $url = 'http://localhost.local/admin/index.php?param=value#section';
        $result = $this->invokeRedactUrlQueryParams($url);

        $this->assertStringEndsWith('#section', $result);
        $this->assertStringContainsString('param=[redacted]', $result);
    }

    public function testRedactUrlQueryParams_WithMultipleParams_RedactsAll(): void
    {
        $url = 'http://localhost.local/?a=1&b=2&c=3&d=4';
        $result = $this->invokeRedactUrlQueryParams($url);

        $this->assertStringContainsString('a=[redacted]', $result);
        $this->assertStringContainsString('b=[redacted]', $result);
        $this->assertStringContainsString('c=[redacted]', $result);
        $this->assertStringContainsString('d=[redacted]', $result);
        $this->assertStringNotContainsString('a=1', $result);
        $this->assertStringNotContainsString('b=2', $result);
    }

    public function testRedactUrlQueryParams_WithEmptyParamValue_RedactsToRedacted(): void
    {
        $url = 'http://localhost.local/?param=';
        $result = $this->invokeRedactUrlQueryParams($url);

        $this->assertStringContainsString('param=[redacted]', $result);
    }

    public function testRedactUrlQueryParams_WithSpecialCharacters_RedactsValues(): void
    {
        $url = 'http://localhost.local/?email=user@example.com&path=/some/path';
        $result = $this->invokeRedactUrlQueryParams($url);

        $this->assertStringContainsString('email=[redacted]', $result);
        $this->assertStringContainsString('path=[redacted]', $result);
        $this->assertStringNotContainsString('user@example.com', $result);
        $this->assertStringNotContainsString('/some/path', $result);
    }

    public function testRedactUrlQueryParams_DoesNotRedactClParameter(): void
    {
        $url = 'http://localhost.local/?cl=navigation&token=secret123';
        $result = $this->invokeRedactUrlQueryParams($url);

        $this->assertStringContainsString('cl=navigation', $result);
        $this->assertStringNotContainsString('cl=[redacted]', $result);
        $this->assertStringContainsString('token=[redacted]', $result);
    }

    public function testRedactUrlQueryParams_DoesNotRedactFncParameter(): void
    {
        $url = 'http://localhost.local/?fnc=render&cl=article&sid=abc123';
        $result = $this->invokeRedactUrlQueryParams($url);

        $this->assertStringContainsString('fnc=render', $result);
        $this->assertStringContainsString('cl=article', $result);
        $this->assertStringNotContainsString('fnc=[redacted]', $result);
        $this->assertStringNotContainsString('cl=[redacted]', $result);
        $this->assertStringContainsString('sid=[redacted]', $result);
    }

    public function testRedactUrlQueryParams_WithClAndFncAndSensitiveParams(): void
    {
        $url = 'http://localhost.local/admin/index.php?editlanguage=0&cl=navigation&fnc=logout&force_admin_sid=dc9440e1&stoken=FF4399CF';
        $result = $this->invokeRedactUrlQueryParams($url);

        // cl and fnc should not be redacted
        $this->assertStringContainsString('cl=navigation', $result);
        $this->assertStringContainsString('fnc=logout', $result);

        // Other params should be redacted
        $this->assertStringContainsString('editlanguage=[redacted]', $result);
        $this->assertStringContainsString('force_admin_sid=[redacted]', $result);
        $this->assertStringContainsString('stoken=[redacted]', $result);

        // Sensitive values should not appear
        $this->assertStringNotContainsString('dc9440e1', $result);
        $this->assertStringNotContainsString('FF4399CF', $result);
    }
}
