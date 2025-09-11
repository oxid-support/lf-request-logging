

# Logging Framework: Request Logger

**Logging Framework: Request Logger** is an OXID eShop module that provides **detailed request logging**.  
It captures raw data about controller actions, request parameters, and the classes loaded during the lifecycle of a request.

The goal: create a **complete trace of what happened in the shop** so developers, support engineers, and analysts can reconstruct a user’s actions.  
Logs are **minimally invasive** and produce **structured JSON entries**, designed to be consumed later by a GUI or analytics tools.

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
composer config repositories.oxid-support/logger path repo/oxs/request-logger
composer require oxid-support/request-logger:@dev
```
### General

#### Activation
```bash
./vendor/bin/oe-console o:m:a oxsrequestlogger
```

## Features

- **Request Route Logging**
    - Captures controller (`cl`) and action (`fnc`)
    - Logs referer, user agent, GET and POST parameters
    - Sensitive values masked (`[redacted]`), keys remain visible
    - No whitelist: all parameters are logged
    - Value length limited to 500 characters

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

- **Security**
    - Sensitive parameters (passwords, tokens, IDs) are masked
    - No session secrets or authentication data exposed
    - Raw JSON output suitable for automated processing

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
    - Configures channel names / handlers (JSON line format)
    - Pushes processors (e.g., request context, sanitizing) before writing
    - Ensures the log directory exists and processors are attached
    - Returns the concrete logger bound as DI service (e.g., oxs.logger.request)
4. Sanitizer (Processor)
    - Normalizes GET/POST
    - Masks sensitive values while keeping parameter keys for diagnostics 
    - Prevents accidental leakage of secrets in logs
5. SymbolTracker
    - Records the set of declared classes/interfaces/traits at request start
    - Computes the delta at the request end and outputs the exact load order list
    - Strips OXID aliases, legacy lowercase names and eval’d classes for cleaner analysis

---

## Log Events

A request usually emits three entries:

### `request.start`
- Contains HTTP method, URI, referer, UA
- Sanitized get/post
- OXID context: cl, fnc, lang, edition, PHP/Shop versions
- Session/user info (masked as needed)

## `request.symbols`
- symbols: string[] with all newly declared FQCNs in load order
- Good for diagnosing template/render paths and module extension chains (*_parent)

## `request.finish`
- durationMs, memoryMb

### `request.start`
```json
{
    "message": "request.start",
    "context": {
        "userAgent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36",
        "referer": null,
        "get": [],
        "post": [],
        "shopId": 1,
        "shopUrl": "http://localhost.local/",
        "sessionId": null,
        "userId": "no user",
        "userLogin": null,
        "ip": "172.21.0.1",
        "method": "GET",
        "uri": "http://localhost.local/",
        "lang": "de",
        "edition": "EE",
        "php": "8.3.22",
        "oxid": "",
        "cl": "",
        "fnc": "render",
        "correlationId": "d3aa840faf0c5ead64f3a65aebfde6ff"
    },
    "level": 200,
    "level_name": "INFO",
    "channel": "oxslogger",
    "datetime": {
        "date": "2025-09-10 14:32:28.389082",
        "timezone_type": 3,
        "timezone": "Europe/Berlin"
    },
    "extra": []
}
```

### `request.symbols`
```json
{
  "message": "request.symbols",
  "context": {
    "requestId": "abc123",
    "symbols": [
      "OxidEsales\\Eshop\\Core\\Config",
      "OxidEsales\\EshopCommunity\\Core\\Config",
      "OxidSolutionCatalysts\\Unzer\\Core\\Config",
      "OxidEsales\\Eshop\\Application\\Model\\User",
      "OxidEsales\\EshopCommunity\\Application\\Model\\User"
    ]
  },
  "extra": {
    "requestId": "abc123"
  }
}
```
### `request.finish`
```json
{
    "message": "request.finish",
    "context": {
        "durationMs": 216,
        "memoryMb": 32,
        "correlationId": "d3aa840faf0c5ead64f3a65aebfde6ff"
    },
    "level": 200,
    "level_name": "INFO",
    "channel": "oxslogger",
    "datetime": {
        "date": "2025-09-10 14:38:13.865587",
        "timezone_type": 3,
        "timezone": "Europe/Berlin"
    },
    "extra": []
}
```

---

## Output Location

- JSON lines are written under:  
    OX_BASE_PATH/log/oxs-request-logger/<CorrelationID>.json  
    (one file per request/correlation ID).

---

### Benefits for Developers & Support
* Debugging: See which classes were loaded, in what order, with which controller.
* Support cases: Reconstruct exactly what the user did (controller + parameters).
* Performance monitoring: Duration and memory are logged per request.
* Compatibility checks: Identify which modules extend which classes (*_parent chains).
