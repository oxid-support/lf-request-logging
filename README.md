# OXS :: Heartbeat

**OXS Heartbeat** is an OXID eShop module that enables **remote monitoring and support** for OXID shops.

It provides:
- **Request Logger**: Detailed request logging with correlation ID tracking
- **Log Sender**: Collect and provide log files to external monitoring systems
- **Diagnostics Provider**: Shop diagnostic information (modules, PHP, server)
- **API User**: Secure GraphQL API access for OXID Support

All components are accessible via GraphQL API, allowing OXID Support to remotely analyze shop issues without direct server access.

> **Full Control**: Remote access is **completely optional**. Each component can be enabled/disabled independently by the shop operator. No data is transmitted unless explicitly activated. Access can be revoked at any time.

---

## Installation

### Step 1: Install via Composer

```bash
composer require oxid-support/heartbeat -W
```

Composer automatically selects the version that matches your OXID installation; no module version constraint required.

> **Why `-W` (`--with-all-dependencies`)**: on current OXID eShop 6.5 the composer lock pins `psr/http-message 2.0`, while the GraphQL stack used by the 1.x module line transitively asks for `psr/http-message 1.x`. `-W` lets Composer reconcile the difference (downgrade `psr/http-message` to 1.1) instead of rejecting the install.

> **Important**: The `oxideshop-composer-plugin` will prompt whether `source/modules/oxid-support/heartbeat/` may be overwritten. Confirm with `yes`. Default is `no`, which leaves `source/modules/` unchanged and prevents OXID from picking up the module.

#### For local development
```bash
git clone -b b-6.5.x https://github.com/oxid-support/heartbeat-module.git repo/oxs/heartbeat
composer config repositories.oxid-support/heartbeat path repo/oxs/heartbeat
composer require oxid-support/heartbeat:dev-b-6.5.x
```

> **Note**: The OXID GraphQL Base module is installed automatically as a dependency.

### Step 2: Run Database Migrations

```bash
./vendor/bin/oe-eshop-db_migrate migrations:migrate oe_graphql_base
./vendor/bin/oe-eshop-db_migrate migrations:migrate oxsheartbeat
```

### Step 3: Clear Shop Cache

```bash
./vendor/bin/oe-console oe:cache:clear
```

### Step 4: Activate Modules

**Important**: The GraphQL Base module must be activated **before** activating the Heartbeat module.

Activate via OXID Admin under **Extensions → Modules**, or use the CLI:

```bash
./vendor/bin/oe-console oe:module:activate oe_graphql_base
./vendor/bin/oe-console oe:module:activate oxsheartbeat
```

For more details on OXID GraphQL installation, see the [official documentation](https://docs.oxid-esales.com/interfaces/graphql/en/latest/install.html).

---

## Compatibility

* **Module 2.x ("OXID 7 line")**: OXID 7.0 to 7.4.x (branch `b-7.0.x`)
* **Module 1.x ("OXID 6 line")**: OXID 6.5 (this branch)

Composer picks the right module version based on the installed OXID eShop. Customers never need to specify a module version manually.

## Branch structure

This repo follows a reactive stabilization-branch pattern (similar to Symfony and Doctrine). Every supported OXID line gets its own `b-<X.Y>.x` branch as a peer; there is no privileged `main` branch. The most recent line is configured as the default branch on GitHub so a fresh clone lands on it.

* **`b-7.0.x`** maintenance branch for the OXID 7.0–7.x line (currently the default)
* **`b-6.5.x`** maintenance branch for the OXID 6.5 line
* Future lines (e.g. `b-7.5.x`) will be cut reactively when OXID introduces a BC-break and the previous line still needs to be supported

Where to open your PR:

* Bug or feature for OXID 7.x → `b-7.0.x`
* Bug for OXID 6.5 only → `b-6.5.x`

## Release policy

Each OXID eShop release (patch, minor or major) is reviewed for compatibility with the maintained module lines. One of two outcomes:

* **Compatible without code changes** → a module patch release widens the `oxid-esales/oxideshop-ce` constraint inside the existing module line (e.g. a future 1.0.x covers OXID eShop 6.5 + 6.6 once verified).
* **Breaking change in OXID eShop** → a new module major is cut on a fresh `b-<X.Y>.x` stabilization branch (e.g. 2.x for OXID eShop 7).

In either case the module follows OXID eShop, not the other way around. Customers always `composer require oxid-support/heartbeat` without a manual version pin; Composer resolves to the correct module version for the installed OXID eShop.

## Updating an existing installation

When a new module version is released:

```bash
composer update oxid-support/heartbeat
```

> **Important**: When Composer asks whether `source/modules/oxid-support/heartbeat/` may be overwritten, confirm with `yes`. The default `no` leaves `source/modules/` on the old version even though `vendor/` is updated, and OXID will keep loading the old `metadata.php`.

Then refresh the shop:

```bash
./vendor/bin/oe-eshop-db_migrate migrations:migrate oxsheartbeat
./vendor/bin/oe-console oe:cache:clear
./vendor/bin/oe-console oe:module:deactivate oxsheartbeat
./vendor/bin/oe-console oe:module:activate oxsheartbeat
```

The deactivate + activate re-reads the new `metadata.php` into OXID's database-cached module registry (version number shown in the admin, controller registrations, settings layout). Module configuration values (API user token, request logger settings, etc.) are preserved.

When upgrading OXID itself, bump OXID and Heartbeat together in a single resolve:

```bash
composer require \
  oxid-esales/oxideshop-metapackage-ee:vX.Y.Z \
  oxid-support/heartbeat \
  --with-all-dependencies
```

Two outcomes:

* **Compatible Heartbeat version exists**: Composer installs it automatically.
* **No matching Heartbeat yet**: Composer fails before any change is written. Wait for the next release or temporarily remove the module before the OXID upgrade.

---

## Components

All four components are optional and can be enabled or disabled independently by the customer in the Admin interface under **OXS :: Heartbeat**.

### 1. API User
Additional option to grant OXID Support extended access to the module's GraphQL endpoints, so logs and diagnostics can be evaluated externally. Through this access, Support can also enable or disable other components of this module on demand to improve the data basis for support cases. Enable only if you want Support to read Request Logger configuration, retrieve logs or query diagnostics remotely.

### 2. Request Logger
Records controller actions, request parameters and the classes loaded during the lifecycle of a request to local log files. Provides a GraphQL API for remote configuration when API User is enabled.

### 3. Log Sender
Collects log files from various sources and provides them via GraphQL API for remote retrieval when API User is enabled.

### 4. Diagnostics Provider
Provides shop diagnostic information (modules, PHP config, server info) via GraphQL API when API User is enabled.

---

## Features

### Request Logger Features

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

### Remote Configuration (via GraphQL API)

- Query and modify all Request Logger settings remotely
- Activate/deactivate logging via API
- Authenticate via JWT with dedicated API user
- Requires API User setup

---

## Module Configuration

The module provides configurable settings accessible via OXID Admin under **OXS :: Heartbeat**.

### API User Setup (Required First)

Navigate to: **OXS :: Heartbeat → API User → Setup**

The API User is required for all components that need remote access. Follow the setup workflow:

1. **Migrations**: Ensure database migrations are executed
2. **GraphQL Base**: Ensure GraphQL Base module is activated
3. **Setup Token**: Copy the setup token and send it to OXID Support
4. **Activation**: Wait for OXID Support to set the API password

Once complete, the API User status shows "Active" and other components can be enabled.

### Request Logger Settings

Navigate to: **OXS :: Heartbeat → Request Logger → Settings**

**Note**: Requires API User setup to be complete.

#### 1. Component Activation
- Toggle to enable/disable the Request Logger component

#### 2. Log Frontend Requests
- **Default**: `false` (disabled)
- Enable logging for frontend (shop) requests

#### 3. Log Admin Requests
- **Default**: `false` (disabled)
- Enable logging for admin panel requests

#### 4. Detailed Logging
- **Default**: `false` (disabled)
- When enabled, additionally logs symbol tracking (request.symbols) showing all classes/interfaces/traits loaded during the request

#### 5. Redact all values
- **Default**: `true` (enabled)
- When enabled, redacts ALL request parameter values (GET/POST) in logs, showing only parameter keys
- When disabled, only parameters listed in the "Redact Fields" setting are masked

#### 6. Redact Fields
- **Default**: `['pwd', 'lgn_pwd', 'lgn_pwd2', 'newPassword']`
- List of parameter names (case-insensitive) whose values should be masked as `[redacted]` in logs
- Only applies when "Redact all values" is disabled

### Log Sender Settings

Navigate to: **OXS :: Heartbeat → Log Sender → Manage**

**Note**: Requires API User setup to be complete.

- **Component Activation**: Toggle to enable/disable the Log Sender
- **Log Sources**: View all recognized log sources with availability status
- **Source Toggle**: Enable/disable individual log sources for sending
- **Static Paths**: Configure additional log files or directories to monitor

Log sources can be registered via:
- **DI Tag Provider**: Services implementing `LogPathProviderInterface` with tag `oxs.logsender.provider`
- **Static Paths**: Manual configuration in the admin interface

### Diagnostics Provider Settings

Navigate to: **OXS :: Heartbeat → Diagnostics Provider → Manage**

**Note**: Requires API User setup to be complete.

- **Component Activation**: Toggle to enable/disable the Diagnostics Provider

Provides the following information via GraphQL API:
- Shop details (URL, edition, version, statistics)
- Installed modules
- System information
- PHP configuration
- Server information

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

### 2. `request.symbols`

- Array of all newly declared FQCNs (fully-qualified class names) in load order
- Only logged when "Detailed Logging" is enabled
- Useful for diagnosing template/render paths and module extension chains

### 3. `request.finish`

- Request duration in milliseconds (`durationMs`)
- Peak memory usage in megabytes (`memoryMb`)

---

## Output Location & Format

### File Location
Logs are written to:
```
OX_BASE_PATH/log/oxs-heartbeat/oxs-heartbeat-<CorrelationID>.log
```

### File Organization
- **One file per correlation ID** - All requests sharing the same correlation ID write to the same file
- **Multiple entries per file** - Each request typically creates 2-3 entries: `request.start`, `request.symbols` (if detailed), `request.finish`
- **Monolog Line Format** - Each log entry follows Monolog's standard format: `[timestamp] channel.LEVEL: message {json_context}`

Each `.log` file contains newline-separated log entries in Monolog's format. The context data is JSON-encoded, making it parseable by log analysis tools.

---

## GraphQL API

The Heartbeat module provides GraphQL APIs for remote management of all components.

### Authentication

1. During module activation, an API user (`heartbeat-api@oxid-esales.com`) is created
2. To enable remote access, use the setup token from the Admin interface to set the API user password (via OXID Support: support@oxid-esales.com)
Note: The API user is only used for remote access of the Heartbeats data through the OXID Support and access can be revoked at any time.
### Available Operations

**Request Logger:**
- Query and modify logging settings
- Activate/deactivate the Request Logger component

**Log Sender:**
- Query available log sources
- Read log file contents

**Diagnostics Provider:**
- Query shop diagnostics (modules, PHP config, server info)

---

## Testing

Tests run standalone without requiring a full OXID shop installation. The module uses `oxideshop-ce` as a dev dependency to provide the necessary framework interfaces.

### Setup

```bash
cd repo/oxs/heartbeat
composer install
```

### Run Tests

```bash
./vendor/bin/phpunit --configuration tests/phpunit.xml
```

### Test Coverage

The test suite includes unit tests for all components. Some integration tests (e.g., `ModuleEvents`) are skipped in standalone mode as they require a full shop context.

---

## License

See [LICENSE](LICENSE) file for details.
