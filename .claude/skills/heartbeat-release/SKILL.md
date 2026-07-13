---
name: heartbeat-release
description: Release/publish workflow for the OXS Heartbeat module (oxid-support/heartbeat). Use ONLY when cutting a release: deciding the version number, choosing whether a new OXID version fits an existing line (widen the constraint) or needs a new branch, finalizing the changelog into a dated section, tagging per line, creating the GitHub release, and publishing to Packagist. Also use when answering questions about how to release, the version number of a release, or the constraint/branch decision for a new OXID version. Do NOT use for code changes, bug fixes, or features (that is the heartbeat-dev skill); this skill assumes the code is already committed and CI-green.
---

# Heartbeat Module Release Workflow

Releasing is a separate concern from developing (see `heartbeat-dev` for making changes across the three OXID lines). This skill takes an already-committed, CI-green state and turns it into published tags/releases, cleanly.

The module ships **manually, tag-based** (no release-please/semantic-release in the repo). Packagist auto-updates from GitHub tags. The version lives in one place: `src/Module/Module.php` `HeartbeatModule::VERSION`; `metadata.php` reads it. The full versioning model (version streams, the widen-vs-fork constraint decision, and version numbering) is documented in the sections of this skill below.

## The three version streams

Each OXID line is its own branch with its own major stream (as of July 2026):

* `b-6.5.x` = Heartbeat **1.x** (OXID 6.5), constraint `oxid-esales/oxideshop-ce: >=6.12, <7.0`
* `b-7.0.x` = Heartbeat **3.x** (OXID 7.0), constraint `~7.0.0`
* `b-7.1.x` = Heartbeat **2.x** (OXID 7.1 to 7.5), constraint `>=7.1, <7.6`

Module majors are **chronological across all lines**, not mapped to OXID majors, and reserved for module BC breaks. That is why 3.x is the OXID 7.0 line and 2.x is the OXID 7.1 line. A branch is not pinned to one OXID version: it covers a **range that grows over time**, recorded by the composer constraint, not the branch name. `b-7.1.x` started at 7.1 and today covers 7.1 to 7.5 because compatible OXID releases widened its upper bound. Always read the constraint to know coverage.

## Preconditions (do not release otherwise)

* All intended changes are committed on the line branches and pushed.
* CI (`php.yml`) is green on each branch you are about to tag. No green, no tag.
* Each line's `## [Unreleased]` changelog section is complete and matches the shipped code.
* If this release changed the shared GraphQL contract, the matching dashboard change is **already published**. The dashboard must always be live first; **ask the user to confirm it is published before you tag** and do not cut until they do (see the cross-repo section below).

## A new OXID version appears: widen the constraint or fork a branch?

This is the core constraint/branch decision. Example: OXID 7.6 ships. Do not reflexively create a branch. Run the decision tree (2-week window, details in the strategy doc):

1. **Test the newest covering line against the new OXID version.** For 7.6 that is `b-7.1.x` (its constraint `<7.6` excludes it so far, on purpose: untested means excluded). "Test" means a real **`composer` resolve + install on that OXID version with all transitive deps**, then activate the module and a GraphQL smoke check, NOT just a green phpunit run. A module can be code-compatible yet **uninstallable** because a dependency constraint excludes the version the new OXID needs: e.g. capping `graphql-configuration-access` at `^3` (or `graphql-base` at `^12`) while OXID 7.5 requires v4 (v13). Widening the `oxideshop-ce` upper bound therefore almost always also requires **widening the `graphql-base` / `graphql-configuration-access` constraints** to the versions the new OXID ships, and re-verifying against those APIs. No clean install on the target OXID means the line does NOT support it yet, regardless of tests. (Real miss: `b-7.1.x` advertised `<7.6` but could not install on 7.5 for exactly this reason, see OXS-3127.) **When a dependency is what caps the line, prefer widening its constraint (backport the newer major into the version union) over dropping the dependency.** As long as a version compatible with the target OXID exists, keep the dependency and add its new major to the union (`^1 || ^2 || ^3 || ^4`), so composer selects the right one per shop. For OXS-3127 the fix was gca `^1..^4` + base `^9..^13`, not removing gca: gca v4 supports OXID 7.5 with the same `ModuleSettingServiceInterface`, so the one code site (`RequestLogger/Service/Remote/SettingService.php`) needed no change. Dropping a dependency and rebuilding on a core equivalent is correct ONLY when no version supports the target OXID at all (why `b-7.0.x`/`b-6.5.x` dropped gca, NOT why 7.1 should). Do not advise dropping as the reflex.
2. **Tests green (no BC break):** stay on the existing branch. Release a **patch** that only **widens the upper bound** (`<7.6` becomes `<7.7`). Customers pick it up via plain `composer update`, no action needed. This is the common case and must stay friction-free.
3. **Tests red (BC break in the module interface):** fork a **new line branch** from the last compatible one, named after the first OXID version it targets (consistent with existing names: `b-7.6.x`). Adapt the code there and give it a **new module major** (next free major across the whole repo). The old branch keeps its capped constraint and lives on for its OXID range.
4. **OXID security patch:** same tree, fast-tracked (target under 48h), usually just the constraint-widening patch.

**Tag hygiene (Packagist):** every published tag's `composer.json` must carry a tight `oxideshop-ce` constraint. An unconstrained tag (or a stray pre-1.0 beta) becomes a silent fallback trap: on a shop with `minimum-stability: dev`, a bare `composer require oxid-support/heartbeat` that cannot resolve any properly-constrained version installs the oldest **unconstrained** tag instead (once observed: `1.0.0-beta.3`, still under the old "Request Logger" name). Never publish an unconstrained tag; consider removing legacy beta tags so they cannot be resolved.

Creating a new line also means: new README branch banner on every line (the switcher lists all lines), a workspace install for the new OXID version, and a `php.yml` CI workflow adjusted to the new branch name and PHP matrix.

## Which version number

Classify the change set for each line, then pick the number:

* **PATCH** (e.g. 2.0.4 to 2.0.5): bug fix, doc, or widening the `oxideshop-ce` upper bound after a green compatibility test. Backward compatible.
* **MINOR** (2.0.x to 2.1.0): new backward-compatible feature.
* **MAJOR**: any BC break. This includes removing/renaming a released GraphQL operation, changing an operation's arguments, or a PHP-level BC break (removed/renamed public method, class, or interface member). A MAJOR does **not** take "line-major + 1"; it takes the **next free major across ALL lines**. Find the highest used major with `git tag | grep -E '^[0-9]+\.[0-9]+\.[0-9]+$' | sort -V | tail -1` (semver release tags only; ignore `backup/*` and other non-release tags).

When the **same BC break ships on several lines at once**, the lines do **not** share one number: each cut line consumes the **next consecutive global major, in cut order**. Example: highest used major is 3 and all three lines break BC together, so the lines you cut become 4.0.0, 5.0.0, 6.0.0 (which line gets which number follows the order you cut, not the OXID version). A line's low own history (e.g. the 6.5 line still at 1.x) is irrelevant: the major is a repo-wide chronological marker, not a per-line counter, so a jump like 1.0.2 to 4.0.0 is correct and expected.

Determine BC honestly: a removal only breaks BC if the removed thing was in that line's **last released tag**. Verify against the tree, do not guess. Get the newest release tag with `git tag --merged <branch> | grep -E '^[0-9]+\.[0-9]+\.[0-9]+$' | sort -V | tail -1` (do **not** use plain `git describe --tags --abbrev=0`, it can return a `backup/*` tag), then `git grep -l '<symbol>' <tag> -- src`. If the symbol is present, removing it is a MAJOR even if no known client used it. Do not blindly tag the current `VERSION` constant: it may be a stale pre-planned **patch** bump (e.g. a `x.y.z+1` sitting in `Module.php`) that no longer reflects the accumulated `[Unreleased]` scope; reassess the whole `[Unreleased]` set and override the constant if the real classification is higher.

## Per line, cut the release (one line fully before the next)

Work in that line's checkout under `workspace/oxid-*/source/_dev-modules/heartbeat`:

1. Bump `HeartbeatModule::VERSION` in `src/Module/Module.php`. Do not touch `metadata.php`'s version (it reads the constant).
2. In `CHANGELOG.md`, turn `## [Unreleased]` into a dated `## [X.Y.Z] - YYYY-MM-DD` section (keep an empty `## [Unreleased]` above). English, Keep a Changelog format. Each line's changelog is its own history. Keep each entry to one or two short, plain sentences stating what changed; rationale and analysis go in the ticket/commit, not the changelog.
3. Verify the `oxid-esales/oxideshop-ce` constraint in `composer.json` states exactly the OXID range actually tested, with a **tight upper bound**. Never tag ahead of the constraint: the constraint in the tagged `composer.json` is what composer evaluates.
4. Commit ("Release X.Y.Z"). Push the branch. Wait for CI green.
5. Create an **annotated** tag `X.Y.Z` on the line branch and push it. Tags are global across the repo; use the number from the decision above, never reuse or renumber a published tag. Force-pushing a tag is emergency-only (constraint hotfix, see the 2.0.1/2.0.2 history) and needs explicit user approval.
6. Create the GitHub release. Title carries version + OXID target, e.g. `Heartbeat 5.0.0 (für OXID 7.1)` (no codename scheme is in use, see the note below). Body states which OXID versions it covers and reminds other-version users that `composer require oxid-support/heartbeat` resolves the right version automatically.
7. Packagist picks the tag up automatically, no manual publish step. Verify the version appears and `composer require oxid-support/heartbeat:X.Y.Z` resolves on a matching OXID.

A cross-line release (same change on all lines) = repeat per line: separate version numbers, changelog sections, tags, GitHub releases. Finish one line completely before the next so a CI failure never leaves half-tagged state.

## Keep the README and the VERSION constant in sync (do not skip)

`HeartbeatModule::VERSION` in `src/Module/Module.php` is the single source of truth for a line's version. The README must follow it, never the reverse. Every line's README carries the full line map: a **banner** (the switcher, naming this line's OXID range only, no module major by design), a **Compatibility** list (`Module X.x: OXID ...`), and a **Branch structure** list (`b-...` to `Heartbeat X.x`). The module major lives in the Compatibility and Branch-structure sections, not in the banner. Each README also cross-references the *other* lines. So a version-number change on one line means editing that line's entry in **all three branches' READMEs**, not only the changed line's own README. Miss one and the map contradicts itself. This drifted once (the `b-6.5.x` README described only two lines, called the 7.0 line "2.x", and named `b-7.0.x` the default); catch it by reconciling all three every time.

Reconcile on every release, and verify before you tag:

1. **Released line's own README**: the banner states the tested OXID range (no major); the `Module X.x: OXID ...` compatibility line and the `b-...` branch-structure entry state the new major and the range.
2. **The other two branches' READMEs**: their cross-reference to the released line (major + range) matches.
3. **Version reconciliation**: the major shown in each README must equal the major of that line's `HeartbeatModule::VERSION`. Check per branch: read the banner major and compare with `git grep -hoE "VERSION = '[^']+'" HEAD -- src/Module/Module.php`. They must agree. If they disagree, the constant wins and the README is wrong.
4. When a **new major scheme jump** ships (e.g. a line moving 1.x to 4.x under the global-major rule), add a one-line note in the README explaining that majors are a repo-wide chronological sequence, so the jump does not read as arbitrary.
5. No em/en dashes in the range text: write "7.1 to 7.5", per house style.

## Cross-repo coordination (dashboard): dashboard ships first, always

The GraphQL operations are a shared contract with `heartbeat-dashboard`. **Hard rule: the dashboard is always published before the module release goes out.**

So whenever this release changed the shared contract in any way (added, removed, renamed, or re-signatured a GraphQL query/mutation, or changed the operation set), you MUST **stop and ask the user explicitly, before tagging: "is the matching dashboard change already published?"** Do not cut until they confirm it is. If they cannot confirm, wait for the dashboard.

Do not reason the ordering out case by case at release time (removed vs added, module-first-is-technically-safe, etc.). The operational default is simply dashboard-already-live, because the dashboard's compatibility check errors when it expects an operation the shop lacks, and dashboard-first is safe in every case. This applies to additions too (e.g. a new kill-switch mutation the dashboard will call): the dashboard button must be live first.

If the release did **not** touch the shared contract, this gate does not apply. Capture any required dashboard work in a `DASHBOARD-*.md` handoff (the dashboard is a separate repo with its own agent). See `heartbeat-dev` "GraphQL contract with the dashboard" for the underlying compatibility mechanics.

## After releasing

* Honor the 2-week-window promise: after a new OXID version, ship a matching module release within 14 days (patch if compatible, new major if BC-broken).
* Keep the communicative layer in sync: the README banner/Compatibility/Branch-structure across all three branches (see "Keep the README and the VERSION constant in sync" above) and any auto-generated compatibility matrix.

## Codenames

No codename scheme is in use (as of July 2026): releases are titled by version + OXID target only, matching every prior release (1.0.0, 3.0.0, ...). If codenames are introduced later, avoid Norse symbols the far right has co-opted (war gods like Odin/Thor/Tyr, runes, Valknut, Sonnenrad, Walhalla imagery); the healing/wisdom/messenger/bridge corner of Norse myth (e.g. Eir, Mímir, Ratatoskr, Bifröst) is both unappropriated and on-theme for a heartbeat/monitoring module.

## What this skill is not

Not for writing code, tests, or fixing bugs, that is `heartbeat-dev`. If a release reveals a code problem, switch to `heartbeat-dev`, fix and test across the three lines, then come back here.
