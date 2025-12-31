

# Logging Framework: Request Logger

**Logging Framework: Request Logger** is an OXID eShop module that provides **detailed request logging**.
It records controller actions, request parameters, and the classes loaded during the lifecycle of a request to local log files on server.

The goal: create a **complete trace of what happened in the shop** so developers, support engineers, and analysts can reconstruct a user's actions.
Logs are **minimally invasive**, stored locally on server, and produce **structured log entries** in Monolog's line format (timestamp, level, message, and JSON context), designed to be consumed by internal monitoring and analytics tools.

---

## Installation

### Live
```bash
composer require oxid-support/request-logger
```

### Dev
```bash
git clone https://github.com/oxid-support/lf-request-logging.git repo/oxs/request-logger
composer config repositories.oxid-support/request-logger path repo/oxs/request-logger
composer require oxid-support/request-logger:@dev
```
### General

**Important!**   
Before activating the module, clear the shop’s cache first.
```bash
./vendor/bin/oe-console o:c:c 
```

#### Activation
```bash
./vendor/bin/oe-console o:m:ac oxsrequestlogger
```

## Module Information

- **Module ID**: `oxsrequestlogger`
- **Module Title**: OXS :: Logging Framework :: Request Logger
- **Version**: 1.0.0
- **Author**: support@oxid-esales.com
- **Supported OXID Versions**: 6.2 - 7.4
- **PHP Version**: 7.4 - 8.3

> **📁 Local Storage Only**: This module writes logs exclusively to server's local filesystem (`OX_BASE_PATH/log/oxs-request-logger/`). No data is transmitted to external services or third parties.

---

## Features

- **Request Route Logging**
    - Records controller (`cl`) and action (`fnc`)
    - Logs referer, user agent, GET and POST parameters
    - **Configurable redaction**: Choose between redacting all values (default) or selective redaction of sensitive parameters
    - Keys always remain visible for diagnostics
    - Arrays/objects converted to JSON (no length limits)
    - Scalar values logged unchanged when selective redaction is enabled

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
    - **Default maximum privacy**: All parameter values redacted by default
    - **Optional selective redaction**: Configure specific sensitive parameters (passwords, tokens, IDs) to mask
    - No session secrets or authentication data in logs
    - All logs stored locally on server filesystem only
    - No data transmission to external services

---

## Remote Configuration (Optional)

For remote management via GraphQL, install the companion module **[Request Logger Remote](https://github.com/oxid-support/lf-request-logging-remote)**:

```bash
composer require oxid-support/request-logger-remote
```

This adds a GraphQL API to:
- Query and modify all module settings remotely
- Activate/deactivate the module via API
- Authenticate via JWT with dedicated API user

> **Note**: Request Logger Remote requires OXID 7.4+ and PHP 8.2+

---

## Module Configuration

The module provides configurable settings accessible via OXID Admin → Extensions → Modules → OXS :: Logging Framework :: Request Logger → Settings:

### 1. Log Level
- **Options**: `standard` | `detailed`
- **Default**: `standard`
- `standard` - Logs request data and performance (request.start and request.finish)
- `detailed` - Additionally logs symbol tracking (request.symbols) showing all classes/interfaces/traits loaded during the request

### 2. Log Frontend Requests
- **Default**: `false` (disabled)
- Enable logging for frontend (shop) requests

### 3. Log Admin Requests
- **Default**: `false` (disabled)
- Enable logging for admin panel requests

### 4. Redact
- **Default**: `['pwd', 'lgn_pwd', 'lgn_pwd2', 'newPassword']`
- List of parameter names (case-insensitive) whose values should be masked as `[redacted]` in logs
- Only applies when "Redact all values" is disabled

### 5. Redact all values
- **Default**: `true` (enabled)
- When enabled, redacts ALL request parameter values (GET/POST) in logs, showing only parameter keys
- When disabled, only parameters listed in the "Redact" setting are masked

---

## Correlation ID System

The module implements a sophisticated correlation ID system that tracks requests across multiple page loads and API calls.

### How It Works

1. **ID Resolution**: The system attempts to resolve an existing correlation ID from:
   - HTTP Header `X-Correlation-Id`
   - Cookie `X-Correlation-Id`
   - If neither exists: Generate new UUID v4
2. **ID Emission**: The correlation ID is returned to the client via:
   - HTTP Response Header: `X-Correlation-Id: <id>`
   - Cookie: `X-Correlation-Id=<id>; Max-Age=2592000; Path=/; HttpOnly; SameSite=Lax`
3. **Log Association**: All log entries include the correlation ID in the `context` field

### Use Cases

- **Multi-step User Flows**: Track a user's journey from product page → cart → checkout → order completion
- **Error Debugging**: When a user reports an error, search logs by their correlation ID to see all recent actions
- **Session Analysis**: Group logs by correlation ID to analyze complete user sessions (up to 30 days)

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

---

### 2. `request.symbols`

- Array of all newly declared FQCNs (fully-qualified class names) in load order
- Only logged when log level is set to `detailed`
- Useful for diagnosing template/render paths and module extension chains

---

### 3. `request.finish`

- Request duration in milliseconds (`durationMs`)
- Peak memory usage in megabytes (`memoryMb`)

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

Each `.log` file contains newline-separated log entries in Monolog's format. The context data is JSON-encoded, making it parseable by log analysis tools.

---

## Testing

**Prerequisites:** Install development dependencies at shop level:
```bash
composer install --dev
```

**Run tests from shop root directory:**
```bash
./vendor/bin/phpunit --config=repo/oxs/request-logger/tests/
```
