# Changelog for the OXID 6 Line

All notable changes to the Heartbeat module on the OXID 6.5 line are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- Module activation no longer regenerates the database views.
- Module activation no longer clears the shop caches itself.

### Fixed
- Activating the module could abort with "You have requested a non-existent service", because the activation hook took the container from the cache file that a concurrent request had just rewritten, which hands the process the stale container class it booted with.

## [4.1.1] - 2026-09-07

### Added
- A hint on the module's own settings page points to the "API User" page in the left menu, where the Heartbeat settings are configured.

### Fixed
- The settings page of every installed module showed no input fields, because the module registered a template block for `module_config.tpl` that pointed at an empty file.

## [4.1.0] - 2026-07-14

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

## [4.0.0] - 2026-07-08

### Changed
- `metadata.php` reads the module version from `HeartbeatModule::VERSION` instead of duplicating the literal string. `Module.php` is the single source of truth from this release on.
- Active sessions of the `heartbeat-api` service user are terminated immediately on password reset and on module deactivation.
- New admin button "Terminate API Sessions" in the API user setup UI.
- Edits to the service user in the OXID admin area (password, login email, active flag) terminate the sessions too.
- GraphQL mutation `heartbeatInvalidateTokens` (callable by the service user) revokes all of the service user's JWTs, giving OXID Support a remote kill switch for a leaked token without resetting the password.
- Integration tests no longer assume an empty `oegraphqltoken` baseline; they are stable on shops with existing API-user tokens.
- README brought up to the current three-line branch structure: adds the branch switcher, corrects the OXID 7.0 line to Heartbeat 3.x and the default branch to `b-7.1.x`, and adds the OXID 7.1 to 7.5 line (Heartbeat 2.x).

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

## [1.0.2] - 2026-06-02

### Changed
- `heartbeatApiVersion` detail fields now require JWT authentication.
- README cleanup

## [1.0.1] - 2026-05-27

### Added
- Explicit `oxid-esales/oxideshop-ce: ">=6.12, <7.0"` constraint in composer.json `require`.
  Note: OXID has two version spaces. The marketing version "OXID eShop 6.5" maps to
  oxideshop-ce codebase versions 6.12.0 (= 6.5.0) through 6.14.x (= 6.5.5). Composer
  constraints address the ce codebase version, so the range covers the entire 6.5 marketing line.
  Composer is now the first line of defense against installing the 1.x line on an
  incompatible OXID major (e.g. on an OXID 7.x shop, where the 2.x module line belongs).

### Changed
Code refactored to be PHP 7.4 compatible, matching the PHP versions OXID 6.5 supports
(PHP 7.4 / 8.0 / 8.1). This allows dropping the explicit `php` constraint from the module
composer.json: `oxideshop-ce: ">=6.12, <7.0"` already pins the supported core, and Composer
intersects that with the PHP requirement declared by the core.

Concretely:
- All PHP 8 `#[Attribute]` annotations (GraphQLite `#[Type]`, `#[Field]`, `#[Query]`,
  `#[Mutation]`, `#[Logged]`, `#[Right]`) replaced by Docblock annotations
  (`@Type`, `@Field`, `@Query`, `@Mutation`, `@Logged`, `@Right(name="…")`).
- All constructor property promotion (`function __construct(private string $x, …)`)
  expanded to explicit typed properties + assignment in the constructor body.
- All `match (…) { … }` expressions rewritten as `switch (…)` statements.
- All non-capturing catches (`catch (\Throwable)`) rewritten as `catch (\Throwable $e)`.
- PHP 8 string functions (`str_starts_with`, `str_ends_with`) replaced with
  PHP 7.4 compatible equivalents (`strpos(…) === 0`, `substr(…, -1) === …`).
- `composer.json`: `oxid-esales/oxideshop-ce: dev-b-6.5.x` removed from `require-dev`
  since the explicit constraint in `require` makes it redundant.
- `metadata.php` and `src/Module/Module.php`: bump and sync version to `1.0.1`.
- Unit tests adjusted accordingly: GraphQLite annotation presence is asserted via docblock
  substring instead of `ReflectionClass::getAttributes()`. GraphQLite v5 (shipped with
  graphql-base ^7.0) recognises both notations at runtime, so the runtime behaviour is
  unchanged; only the test assertions were aligned.
- README install section: dropped obsolete `composer config repositories.oxid-support/heartbeat
  vcs ...` step (module is on Packagist).
- README update section: added explicit warning about the `oxideshop-composer-plugin` overwrite
  prompt (default `no`) and an `oe:module:deactivate` + `oe:module:activate` step that
  re-reads the new `metadata.php` into OXID's DB-cached module registry on 6.5.
- README install step: added `-W` (`--with-all-dependencies`) to `composer require`. Current
  OXID eShop 6.5 ships with `psr/http-message 2.0` in the lock, while the 1.x module line's
  `graphql-base ^7.0` transitively asks for `psr/http-message 1.x`. Without `-W`, Composer
  refuses the install with a transitive-conflict error on a fresh OXID 6.5 shop. `-W`
  reconciles by downgrading `psr/http-message` to 1.1.

### Why
The combination of (a) the explicit constraint and (b) the PHP-version-aware code
cleanup means: Composer reliably installs the right module version for any supported
OXID, and the supported PHP range is honest about what the code actually does.

### Customer impact
- Customers on OXID 6.5 with PHP 7.4, 8.0 or 8.1: receive 1.0.1 automatically on next
  `composer update`. No behavioural change, no breaking changes.
- Customers who accidentally have `^1.0` pinned and try to upgrade to OXID 7.x: Composer
  refuses to install 1.0.1 (constraint mismatch), signalling that the 2.x line is
  required for OXID 7.

### Upgrade
```bash
composer update --no-dev
./vendor/bin/oe-eshop-db_migrate migrations:migrate oxsheartbeat
./vendor/bin/oe-console oe:cache:clear
```
The module stays activated; no re-activation needed.

## [1.0.0] - 2026

Initial 1.x release for the OXID 6.5 line. See git history for details.

### Retroactive metadata correction (applied when 1.0.1 was released)

Tag `1.0.0` was force-pushed at the time of the 1.0.1 release with an explicit
`oxid-esales/oxideshop-ce: ">=6.12, <7.0"` constraint added to `composer.json`.

**The code is unchanged.** The constraint reflects what 1.0.0 has always actually
required: the code uses PHP 8 features (`match`, `#[Attribute]`) and was intended
exclusively for the OXID 6.5 line.

Effect on customers:

- On OXID 6.5 with `^1.0`: no behavioural change, 1.0.0 still resolves and runs
  identically.
- On OXID 7.x with `composer require oxid-support/heartbeat`: Composer now rejects
  1.0.0 (constraint mismatch) and falls through to 2.x, which is the correct line
  for OXID 7. If the customer pins 1.0.0 explicitly, Composer refuses with a clear
  "could not be resolved" error instead of installing successfully and crashing.
- On OXID 6.6+ (if it ever ships) with `^1.0`: Composer now refuses, signalling that
  the customer needs to either upgrade their module constraint to a future patched
  module release or wait for a compatible release.

This is a one-off metadata-only correction. No further force-pushes of 1.0.0 are planned.
