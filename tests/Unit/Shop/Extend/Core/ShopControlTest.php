<?php

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Shop\Extend\Core;

use OxidSupport\Heartbeat\Shop\Extend\Core\ShopControl;
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

    private function invokeRedactUrlQueryParams(
        ?string $url,
        bool $redactAll = true,
        array $blocklistLower = []
    ): ?string {
        // Create a partial mock that allows calling the real private method
        $shopControl = $this->getMockBuilder(ShopControl::class)
            ->disableOriginalConstructor()
            ->onlyMethods([]) // Don't mock any methods, use the real implementation
            ->getMock();

        return $this->redactUrlQueryParamsMethod->invoke($shopControl, $url, $redactAll, $blocklistLower);
    }

    private function invokePseudonymizeSessionId(?string $sessionId): ?string
    {
        $reflection = new ReflectionClass(ShopControl::class);
        $method = $reflection->getMethod('pseudonymizeSessionId');
        $method->setAccessible(true);

        $shopControl = $this->getMockBuilder(ShopControl::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        return $method->invoke($shopControl, $sessionId);
    }

    public function testRedactUrlQueryParams_BlocklistMode_RedactsOnlyBlocklistedKeys(): void
    {
        // In blocklist mode the URI must still hide blocklisted query values
        // (previously it was logged raw, leaking e.g. ?token=SECRET even though
        // the same key was redacted inside the get[] array).
        $url = 'http://localhost.local/index.php?cl=account&token=SECRET&keep=visible';
        $result = $this->invokeRedactUrlQueryParams($url, false, ['token']);

        $this->assertStringContainsString('token=[redacted]', $result);
        $this->assertStringNotContainsString('SECRET', $result);
        $this->assertStringContainsString('keep=visible', $result);
        $this->assertStringContainsString('cl=account', $result);
    }

    public function testRedactUrlQueryParams_BlocklistMode_IsCaseInsensitive(): void
    {
        $url = 'http://localhost.local/index.php?Token=SECRET';
        $result = $this->invokeRedactUrlQueryParams($url, false, ['token']);

        $this->assertStringContainsString('Token=[redacted]', $result);
        $this->assertStringNotContainsString('SECRET', $result);
    }

    public function testPseudonymizeSessionId_HidesRawValueButStaysStable(): void
    {
        $raw = 'dc9440e1fcd2cf8f3a7a623ae65c505f';

        $a = $this->invokePseudonymizeSessionId($raw);
        $b = $this->invokePseudonymizeSessionId($raw);

        $this->assertSame($a, $b, 'Same session id must map to the same pseudonym (support correlation).');
        $this->assertStringNotContainsString($raw, (string) $a, 'Raw session id must never appear in the log.');
        $this->assertStringStartsWith('sha256:', (string) $a);
    }

    public function testPseudonymizeSessionId_EmptyStaysEmpty(): void
    {
        $this->assertSame('', $this->invokePseudonymizeSessionId(''));
        $this->assertNull($this->invokePseudonymizeSessionId(null));
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
        $url = 'http://localhost.local/admin/index.php'
            . '?editlanguage=0&force_admin_sid=dc9440e1fcd2cf8f3a7a623ae65c505f&stoken=FF4399CF';
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
        $url = 'http://localhost.local/admin/index.php'
            . '?editlanguage=0&cl=navigation&fnc=logout&force_admin_sid=dc9440e1&stoken=FF4399CF';
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
