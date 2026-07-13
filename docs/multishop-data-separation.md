# Multishop data separation in the Heartbeat module

Status: analysis + strategy, no code changed yet.
Scope: 7.1 line (`b-7.1.x`, module 2.x/6.0.0 codebase); the findings apply structurally to the 6.5 and 7.0 lines as well because the affected code is line-identical in design.
Context: OXID EE subshops. Module settings live per shop (`var/configuration/shops/<N>/modules/`). `blMallUsers` off means per-subshop users, on means one shared user row. The GraphQL request's shop context comes from the `shp` parameter, so every `ModuleSettingServiceInterface` read/write inside a GraphQL or admin request is scoped to the requested shop.

For each component, separation is assessed at three levels:

* (a) storage location: path, file, or table
* (b) access/read path: GraphQL and admin backend
* (c) record/content: is the data itself attributable to a shop

## 1. RequestLogger (request logs)

Verdict: settings and gating are per shop, the log data itself is NOT separated. Storage is installation-global, records are only partially attributable.

* **(a) Storage: shared, no shop id in the path.**
  `src/Component/RequestLogger/Infrastructure/Logger/LoggerFactory.php:126-132` builds the directory as `ShopFacade::getLogsPath() . 'oxs-request-logger/'`. `src/Shop/Facade/ShopFacade.php:32-35` delegates to `Config::getLogsDir()`, which is `sShopDir . 'log/'` (oxideshop-ce `source/Core/Config.php:1941-1944`): one directory per installation, identical for every subshop. The filename is `oxs-request-logger-<correlationId>.log` (`LoggerFactory.php:88-99`), keyed by correlation id, never by shop id. All subshops write into the same flat directory.
* **(c) Records: shop id only on the start record, and files can interleave shops.**
  `src/Shop/Extend/Core/ShopControl.php:109` writes `shopId` into the `request.start` record. The `request.symbols` and `request.finish` records (`ShopControl.php:130-148`) carry no shop id; attribution of those lines works only via the surrounding file. Worse, the correlation id is resolved from a client cookie or header (`src/Component/RequestLogger/Infrastructure/Logger/CorrelationId/CorrelationIdProvider.php:26-41`). On an EE installation where subshops share a cookie domain, one browser session appends records from several subshops into the SAME file. A single file is therefore not guaranteed to belong to one shop.
* **Write-time settings: correctly per shop.**
  Component active, log level, frontend/admin toggles and both redaction settings are read through `ModuleSettingFacade` (`src/Shop/Facade/ModuleSettingFacade.php`), which is shop-scoped. `ShopControl::start()` (`ShopControl.php:30-42`) evaluates them in the context of the shop handling the request, and redaction (`ShopControl.php:85-103`) is applied with the writing shop's own blocklist/redact-all configuration. So each record is redacted according to the shop it came from. The per-shop promise holds at write time.
* **(b) Access: no read path inside RequestLogger itself.**
  The component's admin controllers manage settings, setup and password only; there is no admin log viewer. Its GraphQL surface (`src/Component/RequestLogger/Controller/GraphQL/SettingController.php`) reads and writes settings, all shop-scoped. The log files are exposed exclusively through LogSender via `src/Component/RequestLogger/Service/LogPathProvider.php:33-46`, which hands LogSender the SHARED directory (same `getLogsPath() . 'oxs-request-logger/'`) with pattern `*.log` and no shop filter. Its `isActive()` (`LogPathProvider.php:63-66`) checks the current shop's setting, but that only gates WHETHER the source is offered, not WHOSE files it contains.

## 2. LogSender

Verdict: gating and configuration are per shop, everything it reads and returns is installation-global. This is the component where the cross-shop exposure materializes.

* **(b) GraphQL read path: shop-scoped gate, unscoped data.**
  `src/Component/LogSender/Controller/GraphQL/LogController.php`: `logSenderSources` (:45-62) and `logSenderContent` (:70-121) are gated on the per-shop active setting (`LogSenderStatusService`) and the per-shop `SETTING_LOGSENDER_ENABLED_SOURCES` collection (:126-137), and the `maxBytes` clamp (:89-99) uses the per-shop `SETTING_LOGSENDER_MAX_BYTES`. But once a source id passes the gate, `findFirstReadableFile()` (:144-179) globs the source's directory and returns the newest file across ALL subshops. There is no shop filter anywhere on the read path.
* **`LogReaderService` has no shop concept at all.**
  `src/Component/LogSender/Service/LogReaderService.php`: `tail()`, `listFiles()`, `readFile()`, `getFileInfo()` operate on raw paths and bytes. Correct as a low-level reader; the scoping responsibility sits one level up and is currently absent.
* **`LogCollectorService`: aggregates unscoped sources.**
  `src/Component/LogSender/Service/LogCollectorService.php:43-78` merges static paths from the per-shop `SETTING_LOGSENDER_STATIC_PATHS` setting (:99-132) with DI-tagged providers. The setting storage is per shop, but the values are arbitrary absolute filesystem paths: a subshop admin can point a static source at any server-readable file, including another subshop's data. Static paths are inherently unscopable and are an operator-level capability by design.
* **`OxidCoreLogPathProvider`: intentionally installation-wide.**
  `src/Component/LogSender/Service/OxidCoreLogPathProvider.php:31-43` exposes `source/log/oxideshop.log`, and `isActive()` (:60-64) is hard-coded `true`. The OXID core log is one shared file per installation by core design; its entries are not reliably shop-tagged. This source cannot be shop-scoped without changing the shop core.
* **Concrete exposure (the RequestLogger case, precisely):**
  A service user authenticated against subshop N (dashboard connected with `shp=N`) with the `provider_requestlogger` source enabled in shop N receives, from `logSenderContent`, the newest `*.log` file in the shared `oxs-request-logger/` directory. That file may belong entirely to subshop M's traffic, redacted under shop M's redaction settings, even if shop M has RequestLogger and/or LogSender switched OFF. Shop M's per-component switches do not protect shop M's data; they only close shop M's own endpoint. Additionally `logSenderSources` returns absolute server paths (`src/Component/LogSender/DataType/LogSourceType.php:62-76`), the same for every shop.
* **(c) Records:** whatever the underlying file contains; for the requestlogger source, see component 1 (shop id only on start records).

## 3. DiagnosticsProvider

Verdict: mixed by nature. Shop-specific sections are correctly scoped to the requesting shop; the rest is system-level data that is legitimately installation-wide. No cross-subshop business data leak.

* **(b) Access:** `src/Component/DiagnosticsProvider/Controller/GraphQL/DiagnosticsController.php:33-41`, gated on the per-shop `SETTING_DIAGNOSTICSPROVIDER_ACTIVE` via the status service. Shop-scoped gate.
* **(a)/(c) Content** (`src/Component/DiagnosticsProvider/Service/DiagnosticsProvider.php:56-83`):
  * Shop-scoped: `aShopDetails` uses the current shop's `sShopURL`, edition and version (:62-68); `aModuleList` (:38-51) reads `ShopConfigurationDaoBridge->get()`, the CURRENT shop's module configuration. A subshop's diagnostics reflect that subshop.
  * Installation/system-global by nature: `aInfo` (system requirements), `aCollations` (database collations), `aPhpConfigparams`, `sPhpDecoder`, `aServerInfo` (:72-80). These describe the shared host, PHP runtime and database; they are the same for every subshop and there is nothing per-shop to isolate.
* No stored data, no files, no tables: the component computes on request. Nothing to migrate or scope on disk.

## 4. ApiUser and module settings

Verdict: already per shop, deliberately and mall-users-aware. This is the model the log side should follow.

* **Provisioning:** `src/Component/ApiUser/Service/ApiUserProvisioningService.php` creates group, user and membership in the current shop context on activation (user row gets the current `oxshopid`, :95). Lookup honours `blMallUsers` via `ApiUserShopScope` (`src/Component/ApiUser/Service/ApiUserShopScope.php:26-33`): mall users off restricts `oxuser` queries to the current shop id, mall users on looks up the shared row. See OXS-3046.
* **Setup token:** per shop, with an explicit defense against the EE behaviour that a new subshop inherits the base shop's module settings: `src/Component/ApiUser/Service/SetupTokenService.php:38-49` regenerates or clears the token based on THIS shop's service-user password state, never trusting an inherited value. See OXS-3103.
* **Token invalidation:** `src/Component/ApiUser/Service/TokenInvalidator.php:24-57` resolves the user through the same shop scope and deletes only that user's `oegraphqltoken` rows.
* **Settings:** every constant in `src/Module/Module.php:43-64` is stored through `ModuleSettingServiceInterface`, i.e. per shop in `var/configuration/shops/<N>/modules/`. GraphQL setting reads/writes (`SettingController`) and admin toggles (`AbstractComponentController` and subclasses) all act on the requesting shop's configuration.

## Summary matrix (bullet form)

* **RequestLogger** storage: SHARED (flat `log/oxs-request-logger/`); read path: exposed only via LogSender, UNSCOPED; records: shop id on start record only, files can mix shops; settings/gating: per shop.
* **LogSender** storage: n/a (reads others); read path: UNSCOPED (newest file wins, shared paths returned); gating/config: per shop; core log source: inherently global; static paths: inherently unscopable, per-shop stored.
* **DiagnosticsProvider** storage: none; read path: per-shop gate; content: shop sections scoped to requesting shop, system sections legitimately global.
* **ApiUser + settings** storage: per shop (`oxuser.OXSHOPID`, per-shop config); read path: shop-scoped, mall-users-aware; done, model to follow.

## Trust model: is cross-subshop log visibility an actual problem?

Who can read across shops today: only the Heartbeat service user (the dashboard, i.e. OXID Support), holding a per-shop credential, through a shop whose operator enabled LogSender and enabled the source. Subshop admins have no log read path in this module. So this is NOT a classic attacker-confidentiality vulnerability: the reading principal is the same trusted support organisation regardless of which shop's endpoint it uses.

It is nevertheless a real problem, for three reasons:

1. **Consent gap.** The module's core promise is per-component, per-shop opt-in ("nothing is exposed unless explicitly activated"). Today shop M's data is exposed through shop N's activation even when shop M has every switch off. The switch does not protect the data it claims to protect; that is the same class of defect as a dead switch.
2. **Data governance.** EE subshops can be operated as separate brands or even legally distinct data controllers on one installation. Request logs contain personal data (IPs, usernames, URIs). Serving shop M's customer data under an authorization that shop M's controller never granted is a GDPR-relevant processing question, independent of who the reader is.
3. **Redaction asymmetry.** Redaction is per shop at write time. A shop that chose strict `redact_all_values` is fine, but a shop with a lax blocklist has its raw data readable through any sibling shop's endpoint, invisible to that shop's operator.

Clear recommendation: fix it, classified as security hardening with medium priority, not as a critical vulnerability requiring an emergency release. The single-trusted-principal reality caps the practical risk; the consent and governance gap makes the fix mandatory for the multishop story.

## Recommendation

1. **Isolate the module's own log data per shop, by default, without a toggle.** Shop id in the storage path plus a shop-scoped read path. A configuration toggle would add a second switch that can silently not do what it claims (see the `SETTING_REMOTE_ACTIVE` dead-switch lesson); and on CE the shop id is always 1, so unconditional scoping is a no-op there. EE and CE run the same code, CE just always sees `/1/`.
2. **Leave inherently global sources global, but say so.** The OXID core log (`oxideshop.log`) and admin-configured static paths cannot be shop-scoped by this module. Keep them, and label them as installation-wide in the admin UI text and the source `description` so an operator enabling them knows they cover the whole installation.
3. **Diagnostics stays as is.** System sections are legitimately installation-wide; shop sections already follow the requesting shop.
4. **ApiUser and settings: no change**, they are the reference implementation.

## Proposed changes

### Write path (RequestLogger)

* `LoggerFactory::logDirectoryPath()` becomes `getLogsPath() . 'oxs-request-logger/' . <shopId> . '/'`, shop id from `ShopFacadeInterface::getShopId()` (already injected). Filename stays `oxs-request-logger-<correlationId>.log`. Keep 0750 dir / 0640 file permissions on the new subdirectory.
* Effect on the correlation-id-per-file model: a browser session spanning subshops now produces one file PER shop with the SAME correlation id. That is a correctness improvement, not a regression: the id still correlates (it is in the filename), and each file now belongs to exactly one shop, which also repairs record attribution for `request.symbols`/`request.finish` lines that carry no shop id. Optionally, add `shopId` to every record via the existing processor chain; cheap, but no longer strictly needed once the directory carries the attribution.
* No rotation exists today (nothing in the module deletes request-logger files; `ModuleEvents` only cleans tmp on activation). Per-shop subdirectories do not change that, but any future cleanup job must recurse into `oxs-request-logger/*/`. Note this in the cleanup ticket when it comes.

### Read path (LogSender)

* `RequestLogger\Service\LogPathProvider::getLogPaths()` returns only the CURRENT shop's subdirectory (`.../oxs-request-logger/<shopId>/`). Since the shop context of a GraphQL request is the `shp`-selected shop, `logSenderSources` and `logSenderContent` automatically serve only that shop's files. No change to `LogReaderService` (correctly path-agnostic) or to `LogCollectorService` (providers own their scoping).
* This makes the provider the single scoping point, mirroring how `ApiUserShopScope` centralizes the user scoping rule. If other per-shop file providers appear later, they follow the same pattern: the provider resolves the shop-scoped path, LogSender stays generic.
* `OxidCoreLogPathProvider`: unchanged behaviour; update `getProviderDescription()` and the admin template wording to state "installation-wide, covers all subshops". Same wording note for static paths in the LogSender admin controller help text.

### Backward compatibility and legacy files

* Existing flat files in `oxs-request-logger/` cannot be attributed to a shop without parsing each file's start record, and even then mixed-shop files exist. Do not migrate them. New writes go to the shop subdirectory immediately; the read path points only at the subdirectory, so legacy flat files simply stop being served via GraphQL (they remain on disk for manual retrieval by the installation operator and age out naturally, since these are short-lived support artifacts).
* If support continuity matters for an ongoing case at upgrade time, an acceptable interim is a one-release fallback that ALSO lists the flat directory, but only for the base shop (shop 1). Recommendation: skip the fallback, keep it simple; a support engineer can re-trigger the traffic capture after the update.

### GraphQL contract / dashboard implications

* No operation is added, removed or renamed and no signature changes: `logSenderSources` and `logSenderContent` keep their shapes, so no `graphqlOperations.js` change and no API_VERSION-relevant surface change. Only VALUES change: `paths[].path` in `logSenderSources` and `filePath` in `logSenderContent` gain a `/<shopId>/` segment.
* Before shipping, verify the dashboard treats these path strings as display-only and does not parse or hardcode `oxs-request-logger/` layout anywhere. If it does, that is a parallel dashboard fix; module first or together is the safe order either way, because an old dashboard against a new module only ever sees the new path strings as data.

### Rollout

* Implement on `b-7.1.x` first, port to `b-7.0.x` and `b-6.5.x` per the heartbeat-dev workflow (6.5: no constructor promotion/readonly, `LogPathType::DIRECTORY()` pseudo-enum call style, docblock annotations).
* Tests per line: LoggerFactory writes under `<shopId>/`; LogPathProvider returns the shop-scoped dir for the current shop context; a two-shop simulation asserting shop N's `logSenderContent` never returns a file written under shop M's id; keep the existing security tests green (`tests/Unit/Component/RequestLogger/Security/`).
* Changelog: one `Changed` entry per line, e.g. "Request logs are now stored and served per shop (subdirectory per shop id); log files written by previous versions are no longer listed by the Log Sender."
* Versioning: minor bump per line (behavioral change, no API surface change).

### Explicitly out of scope

* Scoping `oxideshop.log` per shop (core design, not fixable in the module).
* Restricting admin-configured static paths (deliberate operator capability; document, do not restrict).
* Migrating or splitting legacy flat log files (unattributable, ephemeral).
* Any DiagnosticsProvider change.
