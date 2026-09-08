# Changelog for the OXID 7.0 Line

All notable changes to the Heartbeat module on the OXID 7.0 line are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed
- Activating the module could abort with "You have requested a non-existent service", because the activation hook took the container from the cache file that a concurrent request had just rewritten, which hands the process the stale container class it booted with.

## [5.1.0] - 2026-07-14

### Added
- Full OXID Enterprise multishop support. Each subshop is onboarded and monitored on its own: its own API service user and setup token, its own request logs, and log access scoped to that shop, cleanly separated from the other shops of the installation. (OXS-3046, OXS-3103, OXS-3130, OXS-3131, OXS-3132)

### Changed
- The support API (Request Logger, Log Sender, Diagnostics) is served to the `oxsheartbeat_api` service user; the shop admin group does not carry these GraphQL grants, since admins configure everything through the backend UI. (OXS-3050)
- The API service user, its group and membership are created on module activation in the current shop context instead of by a database migration; the line no longer runs migrations, matching 7.1. Provisioning is idempotent and mall-user aware: with `blMallUsers` on, one shared service user is reused across subshops; with it off, a service user is created per subshop. (OXS-3046, OXS-3066)

### Fixed
- Diagnostics reports the current subshop's URL instead of the installation base URL. (OXS-3134)
- Editing the API service user in the OXID admin reliably ends that user's own sessions, including in multishop setups. (OXS-3133)

### Removed
- The module's database migration (`Version20251223000001`) and the "migration executed" setup step and status check. The module no longer ships migrations; there is nothing to run before activation. (OXS-3046)

## [5.0.0] - 2026-07-08

### Changed
- Active sessions of the `heartbeat-api` service user are terminated immediately on password reset and on module deactivation.
- New admin button "Terminate API Sessions" in the API user setup UI.
- Edits to the service user in the OXID admin area (password, login email, active flag) terminate the sessions too.
- GraphQL mutation `heartbeatInvalidateTokens` (callable by the service user) revokes all of the service user's JWTs, giving OXID Support a remote kill switch for a leaked token without resetting the password.
- Integration tests no longer assume an empty `oegraphqltoken` baseline; they are stable on shops with existing API-user tokens.

### Added
- Integration tests guarding the dashboard contract: `SchemaContractTest` pins `Module::SUPPORTED_OPERATIONS` to the actually built GraphQL schema (both directions), `HttpAuthorizationTest` verifies Bearer-token authentication over real HTTP and fails loudly when the web server drops the Authorization header (Apache `mod_proxy_fcgi` without `CGIPassAuth On`).

### Removed
- GraphQL mutation `requestLoggerRedactChange`. The redact field list (values that are always redacted from request logs, even when "redact all values" is disabled) can now only be changed by the shop admin via the module's admin settings page. The read query `requestLoggerRedact` is unaffected. Removed with it: `SettingServiceInterface::setRedactItems()` and the internal exception class `InvalidCollectionException` (PHP-level BC break for code extending the remote setting service).
- GraphQL mutation `heartbeatResetPassword` removed from the API. Resetting the service user's password is a shop-admin action in the module admin UI; it is no longer exposed over GraphQL, where a stolen token could otherwise rotate the password to re-establish access. It was part of released versions, so this is a breaking change for any GraphQL client that called it (the dashboard does not).

### Fixed
- `heartbeatSetPassword` restores the setup token when the password update fails, keeping setup retryable instead of locking out the service user.

### Security
- Setup token now uses a CSPRNG (`random_bytes`) instead of `md5(uniqid())`, closing a predictable-token path to taking over the API user via the unauthenticated `heartbeatSetPassword`.
- Leak response for a compromised service-user token: `heartbeatInvalidateTokens` (callable by the service user) revokes all of its JWTs including a stolen one, while `heartbeatResetPassword` and the service user's password-reset right are removed. A token thief cannot rotate the password to re-establish access, and support re-authenticates with the password it holds.
- Sensitive-data redaction now recurses into nested arrays/objects, so a blocklisted key (e.g. `password`) nested in a request body is no longer logged in clear.
- Query parameters in the logged `uri` and `referer` are redacted in blocklist mode too, not only in redact-all mode; the session id is pseudonymized instead of logged raw.
- Log injection hardened: the log formatter no longer allows inline line breaks, and a client-supplied correlation id is validated (and regenerated if malformed) before it reaches the log.
- `logSenderContent` clamps the caller-supplied `maxBytes` to the configured maximum, preventing both a limit bypass and unbounded memory use.
- Request log files are created with 0640 permissions and their directory with 0750, instead of world-readable defaults.
- Diagnostics is gated by a dedicated `DIAGNOSTICS_VIEW` right instead of reusing `LOG_SENDER_VIEW`, allowing separate granting of log and diagnostics access.
- `heartbeatSetPassword` returns the same generic error whether or not a setup is pending (closes a setup-status oracle), the service-user password minimum is raised to 12 characters, and internal deployment details are removed from client-facing error messages.

## [3.0.1] - 2026-06-02

### Changed
- `heartbeatApiVersion` detail fields now require JWT authentication.
- README cleanup

## [3.0.0] - 2026-05-27

Initial release of the 3.x line, dedicated to OXID eShop 7.0. Forked from tag `2.0.2`
with one structural change: the `oxid-esales/graphql-configuration-access` dependency
is removed.

### Why a separate line for 7.0
On the 2.x line, Heartbeat depends on `graphql-configuration-access ^1.1 || ^2.0 || ^3.0`.
None of these constraints allow `oxideshop-ce` 7.0.x — the only graphql-configuration-access
release that does (`v1.0.0`) is explicitly excluded. Effectively, the 2.x line cannot be
installed on OXID 7.0 at all, despite earlier composer.json wording suggesting otherwise.
The honest fix for 7.0 is a separate line that drops the dependency and uses OXID's own
`ModuleSettingBridgeInterface` directly (same pattern as the 1.x line on OXID 6.5).

### Constraint
- `oxid-esales/oxideshop-ce: "~7.0.0"` — only the OXID 7.0.x marketing line.
- `oxid-esales/graphql-base: "^8.0"` — the graphql-base series compatible with ce 7.0.
- `oxid-esales/graphql-configuration-access`: removed.
- `php: "^8.0 || ^8.1 || ^8.2 || ^8.3 || ^8.4"` — covers the full PHP range OXID 7.0
  supports. The 2.x-derived code had to be downgraded for this: `final readonly class`
  (PHP 8.2+) became `final class`, promoted `readonly` parameters became plain promoted
  parameters, and `LogPathType` enum was replaced with a class-based pseudo-enum (1.x
  pattern) because backed enums + `match` are PHP 8.1+.

### Code changes versus 2.0.2
- `src/Component/RequestLogger/Service/Remote/SettingService.php`: rewritten to use
  `ModuleSettingBridgeInterface` directly (ported from the 1.x implementation), no
  longer references `OxidEsales\GraphQL\ConfigurationAccess\…`.
- `src/Component/RequestLogger/Controller/Admin/ModuleConfigController.php` +
  `RemoteSetupController.php`: `isConfigAccessActivated()` now hard-returns `true`
  because no external module is required.
- `final readonly class` → `final class` (3 service classes); `private readonly` /
  `public readonly` promoted parameters → plain promoted parameters (~21 sites).
  Both syntaxes are PHP 8.1/8.2+ and are not compatible with OXID 7.0's PHP 8.0 floor.
- `src/Component/LogSender/DataType/LogPathType.php`: backed enum + `match` replaced
  with the 1.x class-based pseudo-enum (FILE_VALUE / DIRECTORY_VALUE constants,
  static FILE() / DIRECTORY() singletons, switch statements in label methods).
  All callers updated from `LogPathType::FILE` → `LogPathType::FILE()`.
- `composer.json`: see above.
- `metadata.php` and `src/Module/Module.php`: version bumped to `3.0.0`.

### Validation
- PHPUnit: 891 tests, 0 failures.
- Psalm with `phpVersion="8.0"`: 0 ParseErrors.
- PHPCompatibility scan testVersion 8.0-8.4: 0 errors.

### Feature parity
All 17 heartbeat GraphQL operations registered identically to the 2.x line.
`@Logged` + `@Right` authorization layer, GraphQLite schema build, JWT token flow
unchanged. Customer-visible behaviour from the dashboard side is identical to 2.x.
The only structural difference is the internal Settings storage layer; the GraphQL
contract is unchanged.

### Customer impact
- Customers on OXID 7.0 with `composer require oxid-support/heartbeat`: now resolve to
  Heartbeat 3.0.0 cleanly.
- Customers on OXID 7.1-7.5: keep getting 2.x; their `^2.0` Composer constraint will
  not match 3.x (different major).
- Customers on OXID 6.5: keep getting 1.x.

### Upgrade
```bash
composer require oxid-support/heartbeat
./vendor/bin/oe-eshop-doctrine_migration migrations:migrate oxsheartbeat
./vendor/bin/oe-console oe:cache:clear
./vendor/bin/oe-console oe:module:activate oxsheartbeat
```

### Branch
The 3.x line lives on the `b-7.0.x` branch. Patches for OXID 7.0 should be opened
against that branch.
