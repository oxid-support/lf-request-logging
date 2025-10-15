

# Logging Framework: Request Logger

**Logging Framework: Request Logger** is an OXID eShop module that provides **detailed request logging**.
It records controller actions, request parameters, and the classes loaded during the lifecycle of a request to local log files on server.

The goal: create a **complete trace of what happened in the shop** so developers, support engineers, and analysts can reconstruct a user's actions.
Logs are **minimally invasive**, stored locally on server, and produce **structured log entries** in Monolog's line format (timestamp, level, message, and JSON context), designed to be consumed by internal monitoring and analytics tools.

---

## Installation

### Live
```bash
composer config repositories.oxid-support/request-logger vcs https://github.com/oxid-support/lf-request-logging.git
composer require oxid-support/request-logger
```

### Dev
```bash
git clone https://github.com/oxid-support/lf-request-logging.git repo/oxs/request-logger
composer config repositories.oxid-support/request-logger path repo/oxs/request-logger
composer require oxid-support/request-logger:@dev
```
### General

#### Activation
```bash
./vendor/bin/oe-console o:m:a oxsrequestlogger
```

## Module Information

- **Module ID**: `oxsrequestlogger`
- **Module Title**: Minimal Invasive Massive Logging
- **Version**: 1.0.0
- **Author**: support@oxid-esales.com
- **Supported OXID Versions**: 6.2 - 7.4

> **📁 Local Storage Only**: This module writes logs exclusively to server's local filesystem (`OX_BASE_PATH/log/oxs-request-logger/`). No data is transmitted to external services or third parties.

---

## Features

- **Request Route Logging**
    - Records controller (`cl`) and action (`fnc`)
    - Logs referer, user agent, GET and POST parameters
    - Sensitive values masked (`[redacted]`), keys remain visible
    - Arrays/objects converted to JSON (no length limits)
    - Scalar values logged unchanged (no truncation)

- **Correlation ID Tracking**
    - Unique ID assigned to each request for tracing across multiple requests
    - Correlation ID transmitted via HTTP header (`X-Correlation-Id`) and cookie
    - Cookie TTL: 30 days (2592000 seconds)
    - Allows tracking user sessions and multi-step flows
    - Each log file named by correlation ID for easy request grouping

- **Symbol Tracking**
    - Tracks all classes, interfaces, and traits **declared during the request**
    - Preserves the **exact load order**
    - Filters:
        - Removes OXID module aliases (`*_parent`)
        - Removes legacy lowercase aliases (`oxuser`, `oxdb`, …)
        - Removes aliases without a file (`class_alias`, eval)
    - Produces a **raw list of FQCNs** (fully-qualified class names)

- **Request Finish Logging**
    - Duration in ms (`durationMs`)
    - Memory usage in MB (`memoryMb`)

- **Security & Privacy**
    - Sensitive parameters (passwords, tokens, IDs) are masked with `[redacted]`
    - No session secrets or authentication data in logs
    - All logs stored locally on server filesystem only
    - No data transmission to external services

---

## Module Configuration

The module provides configurable settings accessible via OXID Admin → Extensions → Modules → Minimal Invasive Massive Logging → Settings:

### 1. Log Level
- **Type**: Select dropdown
- **Options**: `debug` | `info`
- **Default**: `info`
- **Description**: Controls the minimum log level.
  - `info` - Logs request.start and request.finish (recommended for production)
  - `debug` - Additionally logs request.symbols (verbose, use for debugging)

### 2. Redact
- **Type**: Array
- **Default**: `['pwd', 'lgn_pwd', 'lgn_pwd2']`
- **Description**: List of parameter names (case-insensitive) whose values should be masked as `[redacted]` in logs. Add sensitive parameter names like passwords, tokens, API keys, etc.
- **Example**: Add `api_key`, `token`, `password`, `credit_card` to protect sensitive data

**Module Settings Location**: The settings are stored in OXID's module configuration and can be managed via:
- Admin interface (recommended)
- Database: `oxconfig` table, module-specific settings
- Configuration files (for deployment automation)

---

## Architecture Flow


### Diagram (simplified overview)
```txt
Request Start
│
├── request.start  (Controller + Params + Context)
│
Request Processing …
│       └── SymbolTracker records classes
│
├── request.symbols (All loaded classes in load order)
│
└── request.finish  (Duration, memory)
```

## Building Blocks

1. ShopControl (Extension)
    - Hooks into the OXID request lifecycle (start/terminate)
    - Emits request.start, request.symbols, request.finish
    - Guarantees a consistent correlation ID per request
2. ShopRequestRecorder 
    - Thin façade around a pre-configured Monolog\Logger service
    - Keeps the logging call sites minimal and consistent
3. LoggerFactory
    - Factory that resolves and prepares the Monolog\Logger logger instance
    - Configures channel names / handlers (Monolog line format)
    - Pushes processors (e.g., correlation ID) before writing
    - Ensures the log directory exists and processors are attached
    - Returns the concrete logger bound as DI service
4. SensitiveDataRedactor
    - Redacts sensitive values in GET/POST parameters based on configurable blocklist
    - Masks sensitive values while keeping parameter keys for diagnostics
    - Handles arrays/objects by converting to JSON
    - Protects sensitive data in logs
5. SymbolTracker
    - Records the set of declared classes/interfaces/traits at request start
    - Computes the delta at the request end and outputs the exact load order list
    - Strips OXID aliases, legacy lowercase names and eval'd classes for cleaner analysis

---

## Correlation ID System

The module implements a sophisticated correlation ID system that tracks requests across multiple page loads and API calls.

### How It Works

1. **ID Generation**: When a request arrives, the system first attempts to resolve an existing correlation ID
2. **Resolution Priority** (highest to lowest):
   - HTTP Header `X-Correlation-Id` (priority: 190)
   - Cookie `X-Correlation-Id` (priority: 100)
   - If neither exists: Generate new UUID v4
3. **ID Emission**: The correlation ID is emitted back to the client via:
   - HTTP Response Header: `X-Correlation-Id: <id>`
   - Cookie: `X-Correlation-Id=<id>; Max-Age=2592000; Path=/; HttpOnly; SameSite=Lax`
4. **Log Association**: All log entries for the request include the correlation ID in the `context` field

### Use Cases

- **Multi-step User Flows**: Track a user's journey from product page → cart → checkout → order completion
- **API Tracing**: Follow API calls across multiple microservices using the same correlation ID
- **Error Debugging**: When a user reports an error, search logs by their correlation ID to see all recent actions
- **Session Analysis**: Group logs by correlation ID to analyze complete user sessions (up to 30 days)

### Configuration

Correlation ID behavior is configured in `services.yaml`:

```yaml
parameters:
    oxs.request_logger.cookie_name: 'X-Correlation-Id'
    oxs.request_logger.header_name: 'X-Correlation-Id'
    oxs.request_logger.ttl: 2592000 # 30 days in seconds
```

### Architecture Components

- **CorrelationIdProvider**: Main entry point, coordinates resolvers and emitters
- **CorrelationIdGenerator**: Generates new UUID v4 IDs when needed
- **Resolvers**: Extract existing IDs from headers/cookies (CompositeResolver with priority)
- **Emitters**: Return IDs to client via headers/cookies (CompositeEmitter)
- **OnceEmitterDecorator**: Ensures IDs are only emitted once per request (prevents duplicate headers)
- **CorrelationIdProcessor**: Injects correlation ID into Monolog log records

---

## Log Events

A request usually emits three entries:

### 1. `request.start`

**Content:**
- HTTP method, URI, referer, user agent
- Redacted GET/POST parameters (sensitive values masked)
- Shop context: version, edition, shopId, shopUrl, language
- Session/user info: sessionId, userId, username
- Request metadata: IP address, PHP version
- Correlation ID for tracing

**Example:**
```
[2025-10-14 10:52:09] oxsrequestlogger.INFO: request.start {"version":"7.3.1","edition":"EE","shopId":1,"shopUrl":"http://localhost.local/","referer":null,"uri":"http://localhost.local/","method":"GET","get":[],"post":[],"userAgent":"Mozilla/5.0...","lang":"de","sessionId":"abc123sessionid","userId":"oxdefaultadmin","username":"admin@example.com","ip":"172.21.0.1","php":"8.3.22","correlationId":"d3aa840faf0c5ead64f3a65aebfde6ff"}
```

---

### 2. `request.symbols`

**Content:**
- Array of all newly declared FQCNs (fully-qualified class names) in load order
- Good for diagnosing template/render paths and module extension chains
- Correlation ID for tracing
- **Note**: Only logged at `debug` level

**Example:**
```
[2025-10-14 10:52:09] oxsrequestlogger.DEBUG: request.symbols {"symbols":["OxidEsales\\Eshop\\Core\\Config","OxidEsales\\EshopCommunity\\Core\\Config","OxidSolutionCatalysts\\Unzer\\Core\\Config","OxidEsales\\Eshop\\Application\\Model\\User","OxidEsales\\EshopCommunity\\Application\\Model\\User"],"correlationId":"d3aa840faf0c5ead64f3a65aebfde6ff"}
```

---

### 3. `request.finish`

**Content:**
- Request duration in milliseconds (`durationMs`)
- Peak memory usage in megabytes (`memoryMb`)
- Correlation ID for tracing

**Example:**
```
[2025-10-14 10:52:10] oxsrequestlogger.INFO: request.finish {"durationMs":831,"memoryMb":18,"correlationId":"d3aa840faf0c5ead64f3a65aebfde6ff"}
```

---

## Output Location & Format

### File Location
Logs are written to:
```
OX_BASE_PATH/log/oxs-request-logger/oxs-request-logger-<CorrelationID>.log
```

### File Organization
- **One file per correlation ID** - All requests sharing the same correlation ID write to the same file
- **Multiple entries per file** - Each request typically creates 2-3 entries: `request.start`, `request.symbols` (if debug level), `request.finish`
- **Monolog Line Format** - Each log entry follows Monolog's standard format: `[timestamp] channel.LEVEL: message {json_context}`

### Example File Structure
```
log/
└── oxs-request-logger/
    ├── oxs-request-logger-d3aa840faf0c5ead64f3a65aebfde6ff.log
    ├── oxs-request-logger-a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6.log
    └── oxs-request-logger-f7e8d9c0b1a2958473625140abcdef98.log
```

Each `.log` file contains newline-separated log entries in Monolog's format. The context data is JSON-encoded, making it parseable by log analysis tools (Elasticsearch, Splunk, Graylog, etc.).

### Example Log Entry
```
[2025-10-14 10:52:09] oxsrequestlogger.INFO: request.start {"version":"7.3.1","edition":"EE","shopId":1,"shopUrl":"http://localhost.local/","referer":"http://localhost.local/admin/","uri":"http://localhost.local/admin/index.php?cl=navigation","method":"GET","get":{"cl":"navigation"},"post":[],"userAgent":"Mozilla/5.0...","lang":"de","sessionId":"abc123...","userId":"0ee27750...","username":"admin@example.com","ip":"192.168.65.1","php":"8.3.22","correlationId":"d61c8083350dcb0c4c5e82ee340df251"}
```

**Format breakdown:**
- `[2025-10-14 10:52:09]` - Timestamp
- `oxsrequestlogger.INFO` - Channel and log level
- `request.start` - Log message
- `{...}` - Context data in JSON format

---

## Testing

### Test Organization

This module follows the OXID eShop testing conventions with tests organized by type:

```
tests/
├── phpunit.xml          # PHPUnit configuration
└── Unit/                # Unit tests (isolated, mocked dependencies)
```

### PHPUnit Configuration

The module's `tests/phpunit.xml` is configured to use the **shop's vendor autoloader** (`../../../../vendor/autoload.php`) rather than the module's own vendor directory. This is intentional and necessary because:

1. **Dependency Compatibility**: The module relies on `psr/log` (via `Psr\Log\LoggerInterface`), which is provided by the OXID shop installation, not the module itself.

2. **Version Alignment**: The module supports OXID eShop versions 6.2-7.4, each potentially using different versions of `psr/log` (v1.x in current versions). By using the shop's autoloader, tests always run against the same `psr/log` version that will be used at runtime.

3. **Future Compatibility**: When OXID 8.x ships (potentially with `psr/log` v3.x), tests automatically use the correct version without module changes, since the module only **consumes** `LoggerInterface` (doesn't implement it).

4. **Accurate Testing Environment**: Tests run in the same dependency context as production, ensuring compatibility issues are caught early.

### Running Tests

**Prerequisites:**

1. The module must be installed as described in the **Installation → Dev** section above (located at `repo/oxs/request-logger/` relative to shop root)

2. **Development dependencies must be installed at the shop level** to provide required testing libraries (including `mikey179/vfsstream` for virtual filesystem mocking):

   ```bash
   # Run from shop root directory
   composer install --dev
   ```

   This installs dev dependencies from OXID packages like `oxideshop-ce`, which include testing tools used by the module's tests.

**Executing tests:**

All test commands must be executed from the **shop root directory**, not from within the module directory.

**Execute all tests:**
```bash
# From shop root (where vendor/ directory is located)
./vendor/bin/phpunit --config=repo/oxs/request-logger/tests/
```

**Execute with specific test suite:**
```bash
# From shop root
./vendor/bin/phpunit --config=repo/oxs/request-logger/tests/ --testsuite="Unit Tests"
```

**With coverage (requires Xdebug):**
```bash
# From shop root
XDEBUG_MODE=coverage ./vendor/bin/phpunit --config=repo/oxs/request-logger/tests/ --coverage-html repo/oxs/request-logger/coverage-html/
```

### Test Dependencies

The module does **not** include test dependencies in its own `composer.json`. All testing tools (PHPUnit, vfsStream, etc.) are provided by the OXID shop packages' `require-dev` sections (specifically `oxideshop-ce`).

Runtime dependencies like `psr/log` are also **intentionally NOT included** in the module's `composer.json`, as they are provided by the OXID shop installation.

### Compatibility Layer

The module includes compatibility adapters for differences between OXID 6.x and 7.x:
- `src/Shop/Compatibility/DiContainer/` - Handles DI container access differences
- `src/Shop/Compatibility/ModuleSettings/` - Handles module settings API differences

These adapters use runtime detection to work across all supported OXID versions without requiring separate test environments.

### Testing Global Functions (Namespace Function Override)

The module tests HTTP header and cookie emission functionality using **PHP's namespace function override technique**, eliminating the need for Xdebug or external mocking libraries.

**How it works:**

PHP's function resolution searches in this order:
1. Current namespace first
2. Global namespace if not found

By defining `header()`, `headers_sent()`, and `setcookie()` functions in the **same namespace as the production code**, tests can intercept and capture these calls without actually sending headers or cookies.

**Example from `HeaderEmitterTest.php`:**

```php
// Define override in production code's namespace
namespace OxidSupport\RequestLogger\Logger\CorrelationId\Emitter;

function header(string $header, bool $replace = true, int $response_code = 0): void
{
    $GLOBALS['test_headers'][] = $header; // Capture instead of sending
}

// Switch back to test namespace
namespace OxidSupport\RequestLogger\Tests\Unit\Logger\CorrelationId\Emitter;

class HeaderEmitterTest extends TestCase
{
    public function testEmitSendsHeader(): void
    {
        $emitter = new \OxidSupport\RequestLogger\Logger\CorrelationId\Emitter\HeaderEmitter('X-Request-Id');
        $emitter->emit('test-123');

        // Assert against captured data
        $this->assertContains('X-REQUEST-ID: test-123', $GLOBALS['test_headers']);
    }
}
```

**Benefits:**
- ✅ No Xdebug dependency
- ✅ No external mocking libraries
- ✅ Uses only OXID-provided tools (PHPUnit)
- ✅ Works with PHP 5.3+
- ✅ Consistent across all environments

See `tests/Unit/Logger/CorrelationId/Emitter/HeaderEmitterTest.php` and `CookieEmitterTest.php` for complete implementation with detailed inline documentation.
