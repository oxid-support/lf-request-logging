# Changelog for the OXID 6 Line

All notable changes to the Heartbeat module on the OXID 6.5 line are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
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
