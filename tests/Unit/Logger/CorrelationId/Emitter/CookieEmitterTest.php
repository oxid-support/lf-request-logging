<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Logger\CorrelationId\Emitter;

/**
 * NAMESPACE FUNCTION OVERRIDE TECHNIQUE
 * ======================================
 *
 * This test file uses PHP's namespace function resolution to mock the global
 * setcookie() function without requiring Xdebug or external mocking libraries.
 *
 * HOW IT WORKS:
 * -------------
 * PHP's function resolution searches in this order:
 * 1. Current namespace first
 * 2. Global namespace if not found in current namespace
 *
 * By defining setcookie() in the SAME namespace as the production code
 * (OxidSupport\RequestLogger\Logger\CorrelationId\Emitter), when CookieEmitter
 * calls setcookie(), PHP finds our override function instead of the global one.
 *
 * STRUCTURE:
 * ----------
 * 1. First namespace declaration: Test namespace (this file's namespace)
 * 2. Second namespace declaration: Production code namespace (where override function is defined)
 * 3. Override function: Captures cookie data instead of setting actual cookies
 * 4. Third namespace declaration: Back to test namespace (where test class is defined)
 *
 * IMPORTANT NOTES:
 * ----------------
 * - Override function MUST be in the SAME namespace as the production code
 * - Fully qualified class names (FQN) are required because of multiple namespace declarations
 * - $GLOBALS is used to communicate between override function and test assertions
 * - setUp() clears globals to ensure test isolation between tests
 * - This technique works with PHP 5.3+ (no external dependencies required)
 * - The captured options array includes: expires, path, secure, httponly, samesite
 *
 * EXAMPLE FLOW:
 * -------------
 * Test calls: $emitter->emit('correlation-123')
 *   ↓
 * CookieEmitter->emit() calls: setcookie('correlation_id', 'correlation-123', [...])
 *   ↓
 * PHP searches for setcookie() in namespace ...Emitter
 *   ↓
 * Finds OUR override function (defined below)
 *   ↓
 * Override stores cookie data in $GLOBALS['test_cookies']
 *   ↓
 * Test asserts against $GLOBALS['test_cookies']
 *
 * WHY NOT USE Xdebug:
 * -------------------
 * - Xdebug is not always available in all environments
 * - This solution uses only OXID-provided tools (PHPUnit)
 * - No external dependencies required
 * - Works consistently across all PHP versions
 * - Simpler and more maintainable than alternatives
 */

// Override global function for the Emitter namespace to capture calls
namespace OxidSupport\RequestLogger\Logger\CorrelationId\Emitter;

/** @var array<array> Stores cookies set during tests */
$GLOBALS['test_cookies'] = [];

/**
 * Override for global setcookie() function.
 * Captures cookie data instead of setting actual cookies.
 *
 * @param string $name Cookie name
 * @param string $value Cookie value
 * @param array|int $options Cookie options (expires, path, secure, httponly, samesite)
 * @return bool Always true in tests
 */
function setcookie(string $name, string $value = "", array|int $options = 0): bool
{
    $GLOBALS['test_cookies'][] = [
        'name' => $name,
        'value' => $value,
        'options' => $options,
    ];
    return true;
}

// Back to test namespace
namespace OxidSupport\RequestLogger\Tests\Unit\Logger\CorrelationId\Emitter;

class CookieEmitterTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        // Clear test cookies before each test
        $GLOBALS['test_cookies'] = [];
    }

    public function testImplementsInterface(): void
    {
        $emitter = new \OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\CookieEmitter('correlation_id', 2592000);

        $this->assertInstanceOf(\OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\EmitterInterface::class, $emitter);
    }

    public function testEmitSetsCookie(): void
    {
        $emitter = new \OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\CookieEmitter('correlation_id', 3600);
        $id = 'test-cookie-id-123';

        $emitter->emit($id);

        $this->assertNotEmpty($GLOBALS['test_cookies']);
        $cookie = $GLOBALS['test_cookies'][0];
        $this->assertEquals('correlation_id', $cookie['name']);
        $this->assertEquals('test-cookie-id-123', $cookie['value']);
    }

    public function testEmitSetsHttpOnlyFlag(): void
    {
        $emitter = new \OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\CookieEmitter('correlation_id', 3600);

        $emitter->emit('test-id');

        $cookie = $GLOBALS['test_cookies'][0];
        $this->assertTrue($cookie['options']['httponly']);
    }

    public function testEmitSetsSameSiteLax(): void
    {
        $emitter = new \OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\CookieEmitter('correlation_id', 3600);

        $emitter->emit('test-id');

        $cookie = $GLOBALS['test_cookies'][0];
        $this->assertEquals('Lax', $cookie['options']['samesite']);
    }

    public function testEmitSetsPathToRoot(): void
    {
        $emitter = new \OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\CookieEmitter('correlation_id', 3600);

        $emitter->emit('test-id');

        $cookie = $GLOBALS['test_cookies'][0];
        $this->assertEquals('/', $cookie['options']['path']);
    }

    public function testEmitWithCustomCookieName(): void
    {
        $emitter = new \OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\CookieEmitter('my_custom_cookie', 7200);

        $emitter->emit('custom-id-456');

        $cookie = $GLOBALS['test_cookies'][0];
        $this->assertEquals('my_custom_cookie', $cookie['name']);
        $this->assertEquals('custom-id-456', $cookie['value']);
    }

    public function testEmitSetsSecureFlagWhenHttps(): void
    {
        $_SERVER['HTTPS'] = 'on';

        $emitter = new \OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\CookieEmitter('correlation_id', 3600);

        $emitter->emit('test-id');

        $cookie = $GLOBALS['test_cookies'][0];
        $this->assertTrue($cookie['options']['secure']);

        unset($_SERVER['HTTPS']);
    }

    public function testEmitDoesNotSetSecureFlagWhenNoHttps(): void
    {
        unset($_SERVER['HTTPS']);

        $emitter = new \OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\CookieEmitter('correlation_id', 3600);

        $emitter->emit('test-id');

        $cookie = $GLOBALS['test_cookies'][0];
        $this->assertFalse($cookie['options']['secure']);
    }
}
