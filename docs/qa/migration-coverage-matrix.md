# Migration Coverage and Upgrade/Downgrade QA Matrix

**Governs:** Spec §53.3 Upgrade Flow; §53.4 Migration Flow; §58.1–58.5, §58.7, §58.9; §56.3, §56.4; §59.14.  
**Purpose:** Evidence for supported version transitions, migration ordering, retry-safety, and import/restore compatibility.  
**Related:** migration-contract.md; Versions.php; Schema_Version_Tracker; Table_Installer; Import_Validator; Export_Manifest_Builder.

---

## 1. Version Map and Stored State

| Version key | Source | Stored in | Meaning |
|-------------|--------|-----------|---------|
| `plugin` | Constants::plugin_version() | Optional in VERSION_MARKERS | Plugin release; not written by migrations. |
| `global_schema` | Versions::global_schema() | VERSION_MARKERS | Global schema contract; default 1. |
| `table_schema` | Versions::table_schema() | VERSION_MARKERS | Custom table schema; set by Table_Installer on success. |
| `registry_schema` | Versions::registry_schema() | VERSION_MARKERS | Section/page registry; default 1. |
| `export_schema` | Versions::export_schema() | VERSION_MARKERS | Export manifest schema; default 1. |

**Storage:** Option `aio_page_builder_version_markers` (Option_Names::VERSION_MARKERS). Absent keys treated as `"0"`. `_migration_log` holds last result per migration_id (status, message, safe_retry, recorded_at).

**Current code versions (placeholders):** All schema keys at `1`; plugin from Constants (e.g. 0.1.0). No discrete Migration_Contract implementations run in production; Table_Installer performs table create/upgrade and sets `table_schema` in one step.

---

## 2. Upgrade Flow (Spec §53.3)

| Step | Current behavior |
|------|------------------|
| Detect old version | Implicit: stored version_markers vs code (Versions::all()). Lifecycle does not yet compare stored plugin version to current for “upgrade” path. |
| Compare schema versions | check_tables_schema runs Table_Installer; tracker.is_installed_version_future() blocks when installed > code for any version key. |
| Run migrations as needed | Table_Installer::install_or_upgrade() runs dbDelta for all tables; no discrete Migration_Contract list invoked. Version set only after all tables succeed. |
| Record upgrade result | set_installed_version('table_schema', code_version) on success. record_migration_result() available for discrete migrations (not used by Table_Installer). |
| Surface upgrade notices | On failure: activation returns Lifecycle_Result blocking with message. On future schema: blocking message “Unsupported schema: installed version is newer…”. |

---

## 3. Migration Flow (Spec §53.4)

| Requirement | Implementation |
|-------------|----------------|
| Versioned | table_schema and other keys in Versions.php; export_schema in manifest. |
| Ordered | Activation phase order: validate_environment → check_dependencies → init_options → check_tables_schema → … . Within check_tables_schema: all tables then single version write. |
| Logged | _migration_log in VERSION_MARKERS for discrete migrations; Table_Installer failure returns message (no log write on success for table_schema). |
| Retry-aware | install_or_upgrade() is idempotent (dbDelta); safe to re-run. On failure version is not set, so next activation retries. |
| Blocked re-run once successful | table_schema is set only after full success; re-activation runs dbDelta again but does not “re-run” a migration step—state remains consistent. |

---

## 4. Test Scenarios Matrix

| # | Scenario | Expected result | Observed | Notes |
|---|----------|-----------------|----------|--------|
| 1 | **Fresh install → current** | Activate plugin; options/tables created; table_schema=1; no errors. | *Execute and record* | Baseline. |
| 2 | **Prior-supported → current** | Upgrade from last supported release; tables/options upgraded if needed; activation succeeds. | *Execute when 2+ versions exist* | Currently single version; document when multi-version tested. |
| 3 | **Same-major import restore** | Export from current; import on same or other site (same export_schema major); validation passes; restore proceeds. | *Execute and record* | Import_Validator: same major required; incoming ≤ current. |
| 4 | **Older-supported export import** | Import package with export_schema same major but lower patch; validation passes if ≥ migration_floor. | *Execute and record* | Floor and same_major in manifest compatibility_flags. |
| 5 | **Newer export schema import** | Package with schema_version > current export_schema; import blocked with clear message. | *Execute and record* | check_schema_version returns failure. |
| 6 | **Failed migration recovery** | Simulate table install failure (e.g. permissions); activation fails; fix; re-activate; tables created, version set. | *Execute and record* | Version not set on failure; retry on next activation. |
| 7 | **Retry-safe migration rerun** | Activate twice; second run does not duplicate work; table_schema remains 1; no errors. | *Execute and record* | dbDelta idempotent. |
| 8 | **Future schema (downgrade)** | Manually set stored table_schema > code (e.g. 2); activate; blocking message “Unsupported schema…”. | *Execute and record* | is_installed_version_future() in Lifecycle_Manager. |
| 9 | **Export then import round-trip** | Full export; import on same or clean site; critical categories present; no secrets in package. | *Execute and record* | Redaction and schema compliance. |

---

## 5. Import/Export Version Rules

- **Export manifest:** plugin_version, schema_version (export_schema), compatibility_flags (schema_version, same_major_required, migration_floor, max_supported_export_schema), min_import_plugin_version (Export_Manifest_Builder).
- **Import validator (check_schema_version):** Incoming below migration_floor → blocked. Same major required and major mismatch → blocked. Incoming > current export_schema → blocked. Otherwise allowed.
- **Same major:** Major = first segment of version (e.g. "1" for "1.0"). Same-major required is true in current manifest; cross-major import is blocked.

---

## 6. Remediation Status

| Issue | Severity | Status | Evidence / waiver |
|-------|----------|--------|-------------------|
| *(none open)* | — | — | — |

Narrow fixes in this pass: none required. Table_Installer is idempotent and records version only on success; Import_Validator enforces schema and same-major; future-schema blocks activation. Document any verified issue and fix here when found.

---

## 7. Residual Caveats and Release Notes

- **Plugin version upgrade detection:** Lifecycle does not yet compare stored plugin version to current to run “only on upgrade” logic. Table and schema checks run every activation; that is correct for idempotent table install. For future “run once per release” notices or data migrations, add stored_plugin_version read and comparison.
- **Discrete migrations:** Migration_Contract and get_pending_migrations/record_migration_result are implemented and tested (Schema_Version_Tracker_Test) but no production migration list is registered. When table_schema or registry_schema advances with multi-step migrations, register ordered list and run via tracker.
- **Downgrade:** No supported downgrade path beyond “install older plugin and restore from export.” Unsupported future schema (installed > code) blocks activation with clear message (§58.9 rollback policy).
- **Release notes (§58.6):** For each release, include migrations or compatibility notes (e.g. “Table schema remains 1; no migration required” or “Export schema 1; same-major import only”). Document any breaking change to export/import or schema per §58.7.

---

## 8. QA Evidence Summary

- **Measured run (2026-03-21, git `ca94de0`, `plugin/`):** Full PHPUnit **exit 0** (**3,056** tests, **55,458** assertions, **5** skipped, **8** deprecations). Migration-adjacent automated coverage includes `Schema_Version_Tracker_Test`, `Migration_Result_Test`, and lifecycle/table paths as exercised by the suite (see §4 scenario list for what still needs manual **Observed** entries).
- **Unit:** Schema_Version_Tracker_Test (get_installed_versions, set, pending migrations, future version, record_migration_result). Migration_Result_Test. Table_Installer covered indirectly via lifecycle/table tests.
- **Integration:** Activation flow runs check_tables_schema; failure blocks activation; success sets table_schema. Import_Validator schema check unit/integration as applicable.
- **Manual / E2E:** Execute scenario matrix (§4); fill Observed column; run export → import round-trip; verify no secrets in package and restore behavior. Record in this doc and in release checklist.
- **Rollback:** Document “restore from export” and “prior release package” in release notes; matrix confirms future-schema block and retry-safe table install.

---

## 9. Release Checklist Link

Before release: run migration/upgrade scenarios from this matrix; update Observed column; confirm release notes include migration or compatibility notes per §58.6. See [RELEASE_CHECKLIST.md](RELEASE_CHECKLIST.md).
