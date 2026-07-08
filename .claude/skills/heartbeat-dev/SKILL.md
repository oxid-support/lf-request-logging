---
name: heartbeat-dev
description: Expert workflow for developing the OXS Heartbeat module across its three parallel OXID version lines (6.5/7.0/7.1). Use this skill for ANY code change to the heartbeat module in this workspace: bug fixes, features, refactorings, security changes, test changes, changelog entries. Also use it for questions about the module's branch strategy and version lines. Even a "one line fix" must go through this workflow, because every change targets three parallel version lines. For cutting a release (choosing the version number, finalizing the changelog into a dated section, tagging, GitHub release, and the constraint/branch decision when a new OXID version appears), use the separate heartbeat-release skill.
---

# Heartbeat Module Development Workflow

The heartbeat module (`oxid-support/heartbeat`, repo `oxid-support/heartbeat-module` on GitHub) supports several OXID eShop versions in parallel from a single repository. A change is only "done" when it landed on every affected version line, each line's tests pass, and the changelog reflects it. Skipping a line silently breaks customers on that OXID version.

## The three active lines

There is one full OXID installation per line under `workspace/`, each containing its own git checkout of the module:

* **`workspace/oxid-65/source/_dev-modules/heartbeat`**, branch `b-6.5.x`, Heartbeat **1.x**, constraint `oxid-esales/oxideshop-ce: >=6.12, <7.0`
* **`workspace/oxid-70/source/_dev-modules/heartbeat`**, branch `b-7.0.x`, Heartbeat **3.x**, constraint `~7.0.0`
* **`workspace/oxid-71/source/_dev-modules/heartbeat`**, branch `b-7.1.x`, Heartbeat **2.x**, constraint `>=7.1, <7.6`

`workspace/oxid-74` and `oxid-75` exist but carry no module checkout; ignore them unless the user says otherwise.

Module majors are assigned **chronologically across all lines**, not mapped to OXID majors. That is why 3.x is the OXID 7.0 line while 2.x is the OXID 7.1 line. Never "correct" this, and never derive the OXID target from the module major. Derive it from the branch name or the composer constraint. The full versioning rationale and the release process live in the `heartbeat-release` skill.

A branch is not pinned to a single OXID version; it covers a **range** that grows over time. `b-7.1.x` started at OXID 7.1 and today covers 7.1 to 7.5 because each compatible OXID release widened its upper bound. The branch name records where the line started, the composer constraint records what it currently covers. Always read the constraint, not the name, to know coverage.

Releasing is out of scope here: choosing the version number, finalizing the changelog into a dated section, tagging, the GitHub release, and the constraint/branch decision when a new OXID version appears (widen the constraint vs fork a new line branch) all live in the **`heartbeat-release`** skill.

## Making a change

1. **Implement on one line first.** Default to `b-7.1.x` (the newest line) unless the change is version specific. Read the surrounding code in that checkout, do not assume the lines are identical: OXID 6.5 code differs structurally (no Symfony DI in places, different shop APIs), and even 7.0 vs 7.1 diverge in details.
2. **Port to the other lines.** Efficient technique that works well: classify each changed file by whether the *other* line's current version is byte-identical to the line you changed *before* your change (`git -C <otherline> show HEAD:path | diff - <changedline>/path`, or compare against the pre-change base). Identical-base files can be copied wholesale; only hand-port the ones that differ. Always diff before copying and adapt per line instead of pasting blindly. If a file does not exist on a line, find out why before creating it. See "Per-line idioms" for what actually differs.
3. **Test-driven, and run the tests in every touched checkout.** Write the test first (red), then the fix (green); it repeatedly caught real per-line issues this project. From the module directory: `composer run-script static` and `composer run-script tests-unit` (same scripts CI runs). Practicalities: PHPStan needs a raised limit or it crashes at the 128M default (`vendor/bin/phpstan -c tests/PhpStan/phpstan.neon analyse src/ --memory-limit=1G`); unit + static run on the host or in the container, but the integration suite needs a running SDK (the user brings ONE up at a time, ports collide); **CI runs unit + static only, not integration** (7.1 additionally on PHP 8.3, the one env you cannot reproduce locally). When you remove remote surface, leave a guard test that it stays gone (e.g. `testResetPasswordMutationStaysRemoved`, `RemoteComponentBlockingTest`). A change that passes on 7.1 but fails on 6.5 is not done.
4. **Update each line's `CHANGELOG.md`** under `## [Unreleased]` (Keep a Changelog format, English). Each line has its own changelog file describing its own history; never copy an entry to a line that did not receive the change. When removing something, check whether it shipped: `git tag --contains <introducing-commit>`. If a released tag contains it, it is a breaking change and needs a `Removed` entry; if it is still unreleased, just drop its `Unreleased` line (net never-shipped). `composer.lock` is gitignored, so never commit it; and do not `git add -A` blindly, PHPUnit cache dirs (`tests/.phpunit*.cache`) can sneak in.
5. **Commit per checkout** on the line branch. English messages, imperative mood, no Co-Authored-By or "Generated with" trailers. One logical change per commit; mention the line only if the implementations differ ("port X to 6.5 line" is fine).

Security relevant changes (auth, GraphQL exposure, redaction, API user) additionally require checking the tests under `tests/Unit/Component/RequestLogger/Security/` on every line; extend them rather than only the feature tests.

## Comment deliberate decisions (with the ticket ref)

When a line embodies a deliberate decision that is not evident from the code itself, leave a short inline comment stating the decision and pointing to the ticket. That comment is the only place the reasoning stays reliably visible: tickets sink into the backlog and commit messages are not read when someone later reviews that single line.

Applies especially to: security/permission mapping (e.g. why `oxidadmin` is dropped from one provider but kept elsewhere), non-obvious visibility (private vs protected vs public), defensive defaults (`redact_all_values = true`), deliberately omitted functionality ("no cache here because ..."), workarounds for library/framework behaviour, counterintuitive performance choices, deliberate inconsistencies, and negative decisions ("Z would be plausible, deliberately not done because ...").

Format: one or two lines, ticket ref at the end. Comment the WHY and the WHY-NOT-otherwise, never the WHAT (the code already says what). Example:

```php
// oxidadmin intentionally not mapped here, this is a support-only operation (OXS-3050)
```

Rule of thumb: if a reader in six months might think "this looks wrong, let me clean it up", add the comment that explains why the current form is intended.

## Per-line idioms (what actually differs)

Same logic, different surrounding code. Knowing these up front saves porting mistakes:

* **6.5 (1.x, PHP 7.4 to 8.1, graphql-base v7):** GraphQLite via **Docblock annotations** (`@Mutation`, `@Logged`, `@Right(name="...")`, `@Type`, `@Field`); explicit typed properties + constructor assignment (no promotion); `switch` not `match`; no `readonly`; `catch (\Throwable $e)` not bare; settings via `ModuleSettingBridgeInterface` (`get`/`save`, plain values); class-based pseudo-enums (`LogPathType::FILE()`); PHPUnit **8.5** (test files may carry `#[CoversClass]` attributes but they are inert there; use portable assertions, no `assertMatchesRegularExpression`). oxconfig values are `ENCODE()`d in the DB.
* **7.0 (3.x, PHP 8.0+, graphql-base v8):** PHP 8 **attributes** + constructor promotion, but **no `readonly`** (8.0 floor) and backed-enum/`match` limits in places; settings via `ModuleSettingServiceInterface` (`getString`/`getInteger`, returns `UnicodeString`); dropped the `graphql-configuration-access` dependency (uses the core settings bridge directly).
* **7.1 (2.x, PHP 8.2+, graphql-base v9):** attributes, `readonly` promoted properties, `match`, backed enums; `ModuleSettingServiceInterface` (`getString`/`getInteger`/`UnicodeString`); depends on `graphql-configuration-access`. Note: graphql-base v9.0.2's shipped integration test base is broken (contravariance fatal), so the phpunit integration suite currently cannot run on 7.1; do not downgrade graphql-base (v9.0.1 reintroduces blocked webonyx CVEs). Verify 7.1 behavior via unit + static + an in-process schema/permission check instead.

## GraphQL contract with the dashboard (cross-repo)

The module's GraphQL operations are a **shared contract** with the Heartbeat dashboard (`oxid-support/heartbeat-dashboard`). The dashboard registers its operation set in `backend/src/services/graphqlOperations.js` and checks compatibility at runtime via the `heartbeatApiVersion` query. Note: the verdict is computed from the **operations-list diff, not the `apiSchemaHash`** (the dashboard does not gate on the hash). The hashes intentionally diverge, because the module hashes its full `SUPPORTED_OPERATIONS` including admin-only ops the dashboard does not register. Do not chase hash parity.

Also: the admin's own actions (password reset, session termination) run through the **admin backend UI into the service layer, not GraphQL**. The dashboard authenticates as the **service user**. So "who can call a GraphQL operation" is the service user (and, in principle, an admin-JWT client), never the admin clicking in the backend. Keep that distinction when reasoning about callers.

Any change to the GraphQL surface — adding, removing, or renaming a query or mutation, or changing its arguments — is therefore a **cross-repo change, never module-only**. Whenever a change touches the GraphQL surface, you MUST proactively flag that a matching dashboard change is required and describe the rollout order. Do not finish such a change silently.

**Scope: this skill is module-side only.** Do not implement dashboard changes from here (the dashboard is a separate repo with its own developer/agent). Instead capture the required dashboard work in a handoff document at the workspace root (see `DASHBOARD-SECURITY-CHANGES.md`, `DASHBOARD-KILLSWITCH.md` for the format: exact files/lines, the operation and flow, rollout order, and what NOT to do) and hand it over. You may read the dashboard repo to verify a fact (e.g. whether it references an operation), but implementing dashboard code is out of scope.

The dashboard's compatibility check is **asymmetric**: a shop with *extra* operations is treated as compatible, but a shop *missing* an operation the dashboard still expects is reported `incompatible` and the call errors. Consequences:

* **Removing or renaming an operation** breaks the **old dashboard + new module** combination: the old dashboard still calls the now-missing operation → version check `incompatible` + runtime error. The dashboard must drop/rename the same operation in its registry (this also keeps the `apiSchemaHash` in sync) and remove any code that calls it, and that dashboard change must ship first or together. New dashboard + old module is safe (the dashboard ignores the extra operation).
* **Adding an operation the dashboard will call** is the mirror case: an old module lacking it is `incompatible` for that feature, so ship the module first or together and gate the dashboard feature on the version check.

Concretely, when you remove or change an operation, tell the user: "this needs a parallel PR in `heartbeat-dashboard` that drops/updates the same operation in `graphqlOperations.js` and any calling code, delivered alongside this module change", and state the deploy order. Worked example: removing the `requestLoggerRedactChange` mutation (so the redact-field list is shop-admin-only) required the dashboard to drop it from the registry, remove the toggle path, and make the redact list read-only; without that parallel dashboard deploy, older dashboards hit `incompatible` plus errors when changing redact fields.

## Every component has an on/off switch (hard rule)

The module's core promise is that remote access is optional and per-component: the shop operator can enable or disable each component independently, and nothing is exposed unless explicitly activated. So **every component must have its own enable/disable switch, and every remote entry point must be gated on it.** When you add a component (or a new remote operation), wire all of the following, or it does not ship:

1. **A setting** `Module::SETTING_<COMPONENT>_ACTIVE` (`oxsheartbeat_<component>_active`) in `Module.php`.
2. **An admin toggle**: an admin controller implementing `TogglableComponentInterface` (`isComponentActive()` / `toggleComponent()`, via `AbstractComponentController`) so the operator can flip it in the backend.
3. **Runtime gating on the switch in every GraphQL operation of that component.** Two existing patterns, follow the component's own one:
   * RequestLogger style: call `$this->componentStatusService->assertComponentActive()` first (throws `RemoteComponentDisabledException`).
   * LogSender / DiagnosticsProvider style: `if (!$this->statusService->isActive()) { return null; /* or [] */ }` first.
   A new remote operation without this gate is a bug, even if it looks read-only.

The switch must be **actually enforced, not just declared.** Cautionary case from the security audit: `Module::SETTING_REMOTE_ACTIVE` (`_remote_active`) exists as a "global remote" master switch but is read nowhere, so toggling it does nothing. A dead switch is worse than none (false sense of control). If you add a switch, add the code path that honors it and a test that proves "off" blocks access.

Tests: for each component, assert that its remote operations are blocked when the component is inactive (see `RemoteComponentBlockingTest`). Add the disabled-case assertion for any new operation.

## Security checklist for GraphQL / logging / auth changes

This is a remote-access module; these recurring rules came out of a full security audit and should be treated as defaults when touching the relevant area:

* **Secrets from a CSPRNG.** Any token/secret (setup token, etc.) uses `random_bytes`, never OXID's `generateUId()` / `uniqid()` / `md5()`.
* **A credential must not manage its own lifecycle in a way that helps a thief.** A leaked token must not be able to rotate/reset its own password to re-establish access (password reset is admin-only, not exposed to the service user). Revoking is fine and useful: `heartbeatInvalidateTokens` is a deliberate service-user kill switch, safe because the thief lacks the password.
* **Redaction is deep and covers every sink.** Recurse into nested arrays/objects; redact query params in `uri` and `referer` (not only the `get`/`post` maps); pseudonymize the session id. A top-level-only or single-field redactor leaks.
* **No log injection.** LineFormatter with `allowInlineLineBreaks=false`; validate/regenerate any client-supplied value that reaches the log (correlation id from cookie/header).
* **Clamp client-supplied sizes server-side** (e.g. `maxBytes`) to a configured maximum; never trust the caller's number.
* **Least privilege on rights.** Each component owns its own right (e.g. `DIAGNOSTICS_VIEW`), never reuse another component's; grant a right only to the group that actually calls the operation.
* **No information leaks.** Client-facing errors stay generic (no migration/deployment hints, no distinguishing "setup pending" from "wrong token" — that is an oracle); restrictive log-file permissions (0640 file, 0750 dir).
* **New remote operation ⇒ verify auth + component gating + a disabled-case test**, and remember it is a cross-repo change (dashboard).

## Guardrails

* Never develop in `repo/oxs` or copy files from outside the three checkouts; the workspace root also contains an unrelated versioning test harness (`scenarios/`, `run-all.sh`, `fixtures/`).
* The module is a remote support/monitoring tool. Anything exposed via GraphQL is customer-facing attack surface: default to shop-admin-only for settings that control logging, redaction, or data access, and question any change that widens remote access.
* GraphQL operations are a shared contract with the dashboard: any add/remove/rename/signature change needs a parallel `heartbeat-dashboard` change and a rollout order. See "GraphQL contract with the dashboard".
* Before recommending upgrade commands to customers, use the variants in the module README's "Updating an existing installation" section instead of improvising composer invocations.
