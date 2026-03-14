# Template Library Migration and Upgrade Compatibility Report

**Document type:** QA coverage report for expanded template library upgrade and migration (spec §53.3, §53.4, §58.2, §58.4–58.5, §58.7–58.9; Prompt 202).  
**Purpose:** Document supported upgrade paths, migration coverage, appendix and preview synchronization, version/deprecation continuity, and retry-safe behavior so the expanded template ecosystem does not drift or break across version transitions.

---

## 1. Scope

- Section and page template registries (CPT-backed).
- One-pagers and preview metadata (derived from registry definitions).
- Compare state (user meta).
- Appendix generators (Section/Page Template Inventory).
- Template version and deprecation metadata (stored in definitions).

---

## 2. Supported upgrade paths

| From | To | Behavior |
|------|----|----------|
| Fresh install | Any | No migration; seed phases populate registries. registry_schema recorded by Template_Library_Upgrade_Helper on first run. |
| Existing install (registry_schema "0" or missing) | Current | Template_Library_Upgrade_Helper ensures registry_schema is set to code version. Idempotent; safe to re-run. |
| Existing install (registry_schema = code version) | Same | No-op. Appendix generators and version/deprecation services read live registry; no stored appendix state to migrate. |
| Future: registry_schema N | N+1 | When registry_schema migrations are added, they run in dependency order; tracker records result. Contract: retry-safe where possible. |

**Known limitations:**

- No live page content migration in this pass. Built pages and their template assignments are unchanged by template-library upgrade.
- Appendix content is **generated** from the live registry at export time; there is no persisted appendix store to migrate. Regeneration is implicit on next export or on-demand generation.
- Compare lists (user meta) are not plugin-owned; they survive plugin upgrade and are not modified by migration.

---

## 3. What survives upgrade (no migration needed)

| Asset | Storage | Survival |
|-------|--------|----------|
| Section templates | CPT (section_template) | Survives. Definition (version, deprecation, compatibility, etc.) in post meta. |
| Page templates | CPT (page_template) | Survives. Definition (version, deprecation, template_family, etc.) in post meta. |
| Compositions | CPT (composition) | Survives. |
| Version/deprecation metadata | Inside definition (FIELD_VERSION, deprecation block) | Survives with registry. Template_Versioning_Service and Template_Deprecation_Service read from definition. |
| Compare lists | User meta (_aio_compare_section_templates, _aio_compare_page_templates) | Survives. Not cleared or modified by plugin upgrade. |
| One-pagers / preview metadata | Derived at runtime from registry definitions | No separate store; regenerated when detail screens or export run. |

---

## 4. Appendix regeneration and synchronization

- **Section_Inventory_Appendix_Generator** and **Page_Template_Inventory_Appendix_Generator** build markdown from the **live** section and page template repositories. They do not read from a cached appendix store.
- **After upgrade:** Next export (or any consumer that calls the generators) gets appendix content consistent with the current registry. No migration step is required to "update" appendices; regeneration is implicit.
- **Export/restore:** Template_Library_Export_Validator and Template_Library_Restore_Validator use the generators to validate or produce appendix content. Post-upgrade, these validators use the upgraded code and current registry state.
- **Same-version regen:** Running the generators with no registry change produces deterministic output for the same dataset. Useful for tests (same-version regen).

---

## 5. Version and deprecation continuity

- **Version:** Section_Schema::FIELD_VERSION and Page_Template_Schema::FIELD_VERSION (and optional migration_notes_ref) are part of the stored definition. Template_Versioning_Service::get_version_summary() reads from the definition. Across upgrade, definitions are unchanged unless a migration explicitly updates them.
- **Deprecation:** Deprecation block (reason, replacement_keys, deprecated_at, etc.) is stored in the definition. Template_Deprecation_Service::get_deprecation_summary() reads from the definition. Continuity is preserved as long as registry CPTs are not reset.
- **Traceability:** Migration contract requires logging migration results (migration_id, status, message, safe_retry). Template-library-specific migrations (when added) will record results via Schema_Version_Tracker::record_migration_result().

---

## 6. Retry-safe behavior

- **Template_Library_Upgrade_Helper:** Only ensures registry_schema is set in version_markers. If already set, it does nothing. Safe to run multiple times (idempotent).
- **Table_Installer:** dbDelta is idempotent. table_schema version is set only after all tables succeed.
- **Future registry_schema migrations:** Must be designed for retry-safety where possible (e.g. additive schema, backfill with guards). Migration_Result::safe_retry documents whether a failed run can be retried.

---

## 7. Migration result payload (template library)

When a template-library-related migration or helper runs, the result payload is stable for logging and diagnostics:

- **template_library_upgrade_helper:** `{ validated: bool, registry_schema_recorded: bool, message: string }`. No secrets. Safe for audit.

Future registry_schema migrations will return Migration_Result (status, message, notes, safe_retry, migration_id).

---

## 8. Security and diagnostics

- Migration paths are server-controlled. No client-triggered migration.
- No secret leakage in migration diagnostics or result messages.
- Version markers and _migration_log are stored in options; only sanitized messages and status are recorded.
