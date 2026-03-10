# Migration Contract

**Document type:** Authoritative contract for schema versioning and migration runs (spec §8.10, §11.9, §53.1, §58.4, §58.5, §58.7–58.9).  
**Governs:** Stored version map, migration interface, ordering, retry semantics, failure recording.  
**Related:** global-options-schema.md (VERSION_MARKERS); custom-table-manifest.md; Versions.php.

---

## 1. Stored version map shape

- **Storage:** Option `aio_page_builder_version_markers` (Option_Names::VERSION_MARKERS). Value: associative array.
- **Shape:** `{ [version_key: string]: version_string }` plus optional `_migration_log` for failure/audit.
- **Canonical version keys (do not rename):** `plugin`, `global_schema`, `table_schema`, `registry_schema`, `export_schema`. Defined in Versions::version_keys() and Versions::all().
- **Semantics:** Each key holds the currently installed (or last successfully migrated) version for that domain. Absent key is treated as `"0"` (no migration run). Plugin version is synced with release; other keys are advanced by migrations.
- **Adding keys:** Future domain-specific version keys may be added by appending to Versions::version_keys() and the default map in Versions::all(). Root key names must not be renamed without a migration and contract revision.

---

## 2. Migration interface

- **Interface:** `Migration_Contract` (src/Domain/Storage/Migrations/Migration_Contract.php).
- **Methods:** `id()`, `version_key()`, `from_version()`, `to_version()`, `applies_to( string $current_installed_version ): bool`, `run(): Migration_Result`.
- **Applicability:** A migration applies when the installed version for its `version_key` equals `from_version`. Tracker uses this to compute pending migrations.
- **Execution:** `run()` performs one upgrade step and returns a result. Must be safe to retry when the migration is idempotent or recoverable; result carries `safe_retry` flag.

---

## 3. Migration result and run status

- **Result object:** `Migration_Result` with `status`, `message`, `notes`, `safe_retry`, `migration_id`.
- **Status enum:** `success` | `warning` | `failure` | `skipped`. No arbitrary statuses.
- **Message and notes:** Must be sanitized; no secrets. Safe for logging and for limited user-facing display.
- **safe_retry:** True when the migration is idempotent or can be safely re-run after failure (e.g. table create if not exists). False when re-run could corrupt state.

---

## 4. Ordering rules

- Migrations are run in **dependency order**: by version_key (e.g. table_schema before registry_schema if registry depends on tables), then by from_version → to_version within a key.
- Implementations must register migrations in the order they should run. Tracker returns pending list in the order provided; caller is responsible for ordering.
- No migration may assume another migration has run unless the contract explicitly defines the order (e.g. table_schema migrations before registry_schema migrations that depend on tables).

---

## 5. Safe-retry and failure recording

- **Safe retry:** Migrations that are idempotent (e.g. CREATE TABLE IF NOT EXISTS) or that record progress and can resume set `safe_retry = true`. On failure, the system may retry. Non-idempotent or destructive steps must set `safe_retry = false`; no automatic retry.
- **Failure recording:** Tracker exposes `record_migration_result( migration_id, result )`. Stored in version_markers under `_migration_log`. Minimum fields recorded: `migration_id`, `status`, `message`, `safe_retry`, `recorded_at`. No secrets. Used for audit and support; blocking vs non-blocking is determined by lifecycle (activation may block on migration failure).

---

## 6. Blocking vs non-blocking

- **Activation:** A migration failure during activation may be **blocking** (activation fails, plugin not activated) or **non-blocking** (activation succeeds, migration retried later or reported). Contract does not mandate one; lifecycle implementation decides. Recommendation: blocking for required schema (e.g. table_schema) when migration fails and safe_retry is false.
- **Runtime:** Migrations must not be triggered by arbitrary request data. Version decisions are server-side only.

---

## 7. Migration class naming convention

- **Pattern:** `Migration_{Domain}_{FromVersion}_to_{ToVersion}` or `Migration_{VersionKey}_{From}_{To}`. Example: `Migration_Table_Schema_1_To_2`.
- **Location:** Under `src/Domain/Storage/Migrations/` or domain-specific migration namespace. One class per migration step.
- **Id:** Stable string (e.g. `table_schema_1_to_2`) returned by `id()`; used in logs and failure records.

---

## 8. Unsupported future schema

- When **installed** version for a key is **greater** than the **code** version (e.g. site was on a newer plugin, then downgraded), the tracker reports "future" version. Activation or upgrade logic should treat this as unsupported and either block activation with a clear message or require upgrade of the plugin. Contract: do not run migrations when installed > code; explicit handling required.

---

## 9. Logging and breaking changes

- Schema changes must be **logged** (migration result, version key, from/to). No silent migrations.
- **Breaking changes** (spec §58.7) must be documented and versioned; not folded in silently. Export and registry versioning remain first-class in the version map.
