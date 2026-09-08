# Changelog for the OXID 7 Line

All notable changes to the Heartbeat module on the OXID 7 line are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- Module activation no longer regenerates the database views. The module ships no schema of its own, so there was nothing for the view rebuild to pick up, and it cost roughly half a second on every activation, on Enterprise once per subshop. (OXS-3375)
- Module activation no longer clears the shop caches itself. The shop clears template, language, menu and module-variable caches on the same event anyway, and it clears more than the module did. (OXS-3376)

### Fixed
- Activating the module could abort with "You have requested a non-existent service", because the activation hook took the container from the cache file that a concurrent request had just rewritten, which hands the process the stale container class it booted with.

## [6.1.0] - 2026-07-14

### Added
- Full OXID Enterprise multishop support. Each subshop is onboarded and monitored on its own: its own API service user and setup token, its own request logs, and log access scoped to that shop, cleanly separated from the other shops of the installation. (OXS-3046, OXS-3103, OXS-3130, OXS-3131, OXS-3132)
- Runs on OXID eShop 7.5. `graphql-base ^13.0` and `graphql-configuration-access ^4.0` are allowed; Composer still selects the matching stack per OXID version, so 7.1 to 7.4 stay on gca v3 / base v12. (OXS-3127)

### Changed
- The support API (Request Logger, Log Sender, Diagnostics) is served to the `oxsheartbeat_api` service user; the shop admin group does not carry these GraphQL grants, since admins configure everything through the backend UI. (OXS-3050)
- The API service user, its group and membership are created on module activation in the current shop context instead of by a database migration. Provisioning is idempotent and mall-user aware: with `blMallUsers` on, one shared service user is reused across subshops; with it off, a service user is created per subshop. (OXS-3046)

### Fixed
- Diagnostics reports the current subshop's URL instead of the installation base URL. (OXS-3134)
- Editing the API service user in the OXID admin reliably ends that user's own sessions, including in multishop setups. (OXS-3133)

### Removed
- The module's database migration (`Version20251223000001`) and the "migration executed" setup step and status check. The module no longer ships migrations; there is nothing to run before activation. (OXS-3046)

## [6.0.0] - 2026-07-08

### Changed
- Active sessions of the `heartbeat-api` service user are terminated immediately on password reset and on module deactivation.
- New admin button "Terminate API Sessions" in the API user setup UI.
- Edits to the service user in the OXID admin area (password, login email, active flag) terminate the sessions too.
- GraphQL mutation `heartbeatInvalidateTokens` (callable by the service user) revokes all of the service user's JWTs, giving OXID Support a remote kill switch for a leaked token without resetting the password.
- API user setup workflow and actions moved from the module config "Einstell." tab to the dedicated "OXS :: Heartbeat > API User" admin page. The "Einstell." tab now shows only a hint linking there. This also eliminates a stale-status bug where the "Einstell." tab claimed setup was complete while the API User page still showed the token-send step active.
- Module activation no longer runs database migrations. Operators run `oe-eshop-doctrine_migration migrations:migrate oxsheartbeat` as an explicit step before activation (already documented in README).
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

## [2.0.3] - 2026-06-02

### Changed
- `heartbeatApiVersion` detail fields now require JWT authentication.
- README cleanup

## [2.0.2] - 2026-05-27

### Changed
- `oxid-esales/oxideshop-ce` constraint widened to `">=7.1, <7.6"` (was `">=7.0, <7.5"` in 2.0.1).
  - Upper bound bumped from `<7.5` to `<7.6` to include OXID eShop 7.5 (= ce 7.5.x).
  - Lower bound bumped from `>=7.0` to `>=7.1` to match what the module actually delivers
    on the 2.x line. The original `>=7.0` claim in 2.0.1 was empty: although the
    `ContainerFacade → ContainerFactory` refactor enabled OXID 7.0 from the module's
    own perspective, the required `oxid-esales/graphql-configuration-access ^1.1` has
    no version that supports ce 7.0.x (only its excluded v1.0.0 does). Composer rejected
    every real 7.0 install. Tag 2.0.1 was retroactively force-pushed on 2026-05-27 to
    the same `">=7.1, <7.5"` constraint to stop advertising the empty 7.0 support.
- `metadata.php` and `src/Module/Module.php`: bump and sync version to `2.0.2`.
- `metadata.php` now reads the version from `HeartbeatModule::VERSION` instead of duplicating
  the literal string. `Module.php` is the single source of truth from this release on;
  future patches only have to bump the constant.

### Why
- OXID eShop 7.5 ships today and is binary-compatible with the 2.x heartbeat code; only
  the composer constraint was blocking the install. Widening it is the entire patch.
- Honesty about which OXID versions the 2.x line actually supports (7.1-7.5, not 7.0).

### Customer impact
- Customers on OXID 7.1-7.4 with `^2.0`: receive 2.0.2 automatically on next
  `composer update`. No behavioural change, no breaking changes.
- Customers on OXID 7.5: can now install Heartbeat 2.0.2 with a plain
  `composer require oxid-support/heartbeat`. Composer picks v12 of graphql-base and
  v3 of graphql-configuration-access transitively (both compatible with ce 7.5).
- Customers on OXID 7.0: still no installable Heartbeat 2.x. A dedicated 3.x line
  (planned, on a `b-7.0.x` branch with graphql-configuration-access dependency dropped)
  will fill that gap.

### Upgrade
```bash
composer update oxid-support/heartbeat
./vendor/bin/oe-eshop-doctrine_migration migrations:migrate oxsheartbeat
./vendor/bin/oe-console oe:cache:clear
```
The module stays activated; no re-activation needed.

## [2.0.1] - 2026-05-21

### Added
- Explicit `oxid-esales/oxideshop-ce: ">=7.0, <7.5"` constraint in composer.json `require`.
  This makes Composer the first line of defense against incompatible OXID combinations.

### Changed
- `src/Shop/Extend/Core/ShopControl.php`: replaced the OXID 7.1+-only
  `OxidEsales\EshopCommunity\Core\Di\ContainerFacade::get()` with the OXID 7.0+ compatible
  `OxidEsales\EshopCommunity\Internal\Container\ContainerFactory::getInstance()->getContainer()->get()`.
  This makes the module work on OXID 7.0 in addition to 7.1+.
- `services.yaml`: removed all six `oxid.view_controller` tag registrations.
  Controllers are registered exclusively via the `controllers` array in `metadata.php` now.
  This complies with the OXID 7.3+ documentation that explicitly forbids registering controllers in both places.
- `metadata.php`: removed the comment about "Required for OXID eShop 7.2 compatibility (7.4+ uses services.yaml tags)"
  because the dual-registration was already a workaround, not a requirement.
- `src/Module/Module.php`: fixed `VERSION` constant which was stuck at `1.0.0` while metadata.php was `2.0.0`.
  Both are now in sync at `2.0.1`.

### Why
The combination of (a) the explicit constraint and (b) the code cleanup means: Composer reliably installs the right 
module version for any supported OXID, and the supported range is honest about what the code actually does.

### Customer impact
- Customers on OXID 7.1-7.4 with `^2.0`: receive 2.0.1 automatically on next `composer update`.
  No action required, no breaking changes.
- Customers on OXID 7.0: can now install and run the module.

### Upgrade
```bash
composer update --no-dev
vendor/bin/oe-eshop-doctrine_migration migrations:migrate oxsheartbeat
vendor/bin/oe-console oe:cache:clear
```
The module stays activated; no re-activation needed.

## [2.0.0] - 2026

Initial 2.x release. See git history for details.

### Retroactive metadata correction (applied when 2.0.1 was released)

Tag `2.0.0` was force-pushed at the time of the 2.0.1 release with an explicit
`oxid-esales/oxideshop-ce: ">=7.1, <7.5"` constraint added to `composer.json`.

**The code is unchanged.** The constraint reflects what 2.0.0 has always actually
required: its `ShopControl` extension uses `OxidEsales\EshopCommunity\Core\Di\ContainerFacade`
which was introduced in OXID 7.1.0 and does not exist in OXID 7.0.

Effect on customers:

- On OXID 7.1-7.4 with `^2.0`: no behavioural change, 2.0.0 still resolves and runs
  identically.
- On OXID 7.0 with `composer require oxid-support/heartbeat`: Composer now rejects
  2.0.0 (constraint mismatch) and falls through to 2.0.1, which works on 7.0+ thanks
  to the ContainerFactory fix. Customer ends up with 2.0.1 installed automatically.
  If the customer pins 2.0.0 explicitly, Composer refuses with a clear "could not
  be resolved" error instead of installing successfully and crashing at first request.
- On OXID 7.5+ with `^2.0`: Composer now refuses, signalling that the customer needs
  to either upgrade their module constraint to `^X` (where X is the next compatible
  major) or wait for a compatible module release.

This is a one-off metadata-only correction. No further force-pushes of 2.0.0 are planned.
