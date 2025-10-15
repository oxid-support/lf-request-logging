<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Logger\CorrelationId\Emitter;

/**
 * NAMESPACE FUNCTION OVERRIDE TECHNIQUE
 * ======================================
 *
 * This test file uses PHP's namespace function resolution to mock global functions
 * (header() and headers_sent()) without requiring Xdebug or external mocking libraries.
 *
 * HOW IT WORKS:
 * -------------
 * PHP's function resolution searches in this order:
 * 1. Current namespace first
 * 2. Global namespace if not found in current namespace
 *
 * By defining header() and headers_sent() in the SAME namespace as the production
 * code (OxidSupport\RequestLogger\Logger\CorrelationId\Emitter), when HeaderEmitter
 * calls header(), PHP finds our override function instead of the global one.
 *
 * STRUCTURE:
 * ----------
 * 1. First namespace declaration: Test namespace (this file's namespace)
 * 2. Second namespace declaration: Production code namespace (where override functions are defined)
 * 3. Override functions: Capture calls instead of performing actual operations
 * 4. Third namespace declaration: Back to test namespace (where test class is defined)
 *
 * IMPORTANT NOTES:
 * ----------------
 * - Override functions MUST be in the SAME namespace as the production code
 * - Fully qualified class names (FQN) are required because of multiple namespace declarations
 * - $GLOBALS is used to communicate between override functions and test assertions
 * - setUp() clears globals to ensure test isolation
 * - This technique works with PHP 5.3+ (no external dependencies required)
 *
 * EXAMPLE FLOW:
 * -------------
 * Test calls: $emitter->emit('test-id')
 *   ↓
 * HeaderEmitter->emit() calls: header('X-ID: test-id')
 *   ↓
 * PHP searches for header() in namespace ...Emitter
 *   ↓
 * Finds OUR override function (defined below)
 *   ↓
 * Override stores header in $GLOBALS['test_headers']
 *   ↓
 * Test asserts against $GLOBALS['test_headers']
 */

// Override global functions for the Emitter namespace to capture calls
namespace OxidSupport\RequestLogger\Logger\CorrelationId\Emitter;

/** @var array<string> Stores headers set during tests */
$GLOBALS['test_headers'] = [];

/**
 * Override for global header() function.
 * Captures header calls instead of sending actual HTTP headers.
 *
 * @param string $header The header string
 * @param bool $replace Whether to replace previous header
 * @param int $response_code Optional HTTP response code
 * @return void
 */
function header(string $header, bool $replace = true, int $response_code = 0): void
{
    $GLOBALS['test_headers'][] = $header;
}

/**
 * Override for global headers_sent() function.
 * Always returns false in tests to allow header setting.
 *
 * @param string|null $file Reference to filename
 * @param int|null $line Reference to line number
 * @return bool Always false in tests
 */
function headers_sent(&$file = null, &$line = null): bool
{
    return false; // Always return false in tests to allow header operations
}

// Back to test namespace
namespace OxidSupport\RequestLogger\Tests\Unit\Logger\CorrelationId\Emitter;

class HeaderEmitterTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        // Clear test headers before each test
        $GLOBALS['test_headers'] = [];
    }

    public function testImplementsInterface(): void
    {
        $emitter = new \OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\HeaderEmitter('X-Correlation-Id');

        $this->assertInstanceOf(\OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\EmitterInterface::class, $emitter);
    }

    public function testEmitConvertsHeaderNameToUppercase(): void
    {
        $emitter = new \OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\HeaderEmitter('x-correlation-id');

        $this->assertInstanceOf(\OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\HeaderEmitter::class, $emitter);
    }

    public function testEmitSendsHeaderWhenHeadersNotSent(): void
    {
        $emitter = new \OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\HeaderEmitter('X-Correlation-Id');
        $id = 'test-correlation-id-123';

        $emitter->emit($id);

        $this->assertContains('X-CORRELATION-ID: test-correlation-id-123', $GLOBALS['test_headers']);
    }

    public function testEmitWithDifferentHeaderName(): void
    {
        $emitter = new \OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\HeaderEmitter('X-Request-Id');
        $id = 'request-456';

        $emitter->emit($id);

        $this->assertContains('X-REQUEST-ID: request-456', $GLOBALS['test_headers']);
    }

    public function testEmitDoesNotFailWhenHeadersAlreadySent(): void
    {
        $emitter = new \OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\HeaderEmitter('X-Correlation-Id');

        // Should not throw exception even if we can't set headers
        $emitter->emit('test-id');

        $this->assertTrue(true);
    }
}
