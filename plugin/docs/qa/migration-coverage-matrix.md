# Migration Coverage Matrix

**Document type:** QA matrix for schema versioning and migration coverage (spec §53.3, §53.4, §58.4, §58.5, §58.7–58.9; Prompt 202).  
**Purpose:** Map version keys to what is migrated, upgrade behavior, and template-library-specific coverage.

---

## 1. Version keys (Versions::version_keys())

| Key | Description | Set by | Migration contract |
|-----|-------------|--------|--------------------|
| `plugin` | Plugin release version | Constants::plugin_version() | Not migrated; informational. |
| `global_schema` | Global schema contract | Future migrations | Placeholder. |
| `table_schema` | Custom table schema | Table_Installer after dbDelta | Set after all tables install/upgrade. |
| `registry_schema` | Section/page template registry schema | Template_Library_Upgrade_Helper (and future migrations) | Ensured on activation; future migrations apply when applies_to( installed ) is true. |
| `export_schema` | Export manifest and backup schema | Future migrations | Placeholder. |

---

## 2. What is migrated per key

| Key | What is migrated / ensured | Retry-safe | Blocking on failure |
|-----|----------------------------|------------|----------------------|
| `table_schema` | Custom tables (dbDelta). Version recorded after full success. | Yes (dbDelta idempotent) | Yes (activation fails). |
| `registry_schema` | Version marker only (no registry data mutation). Template_Library_Upgrade_Helper sets marker when "0" or missing. | Yes (idempotent) | No (phase is non-blocking). |
| `export_schema` | Not yet. | — | — |

---

## 3. Template-library-specific coverage (Prompt 202)

| Surface | Storage / source | Upgrade behavior | Document |
|---------|-------------------|------------------|----------|
| Section templates | CPT | Survive; definitions unchanged unless a registry_schema migration runs. | template-library-migration-coverage-report.md |
| Page templates | CPT | Same. | template-library-migration-coverage-report.md |
| Compositions | CPT | Survive. | template-library-migration-coverage-report.md |
| Version metadata | In definition (FIELD_VERSION) | Continuity with registry. Template_Versioning_Service reads definition. | template-library-migration-coverage-report.md §5 |
| Deprecation metadata | In definition (deprecation block) | Continuity with registry. Template_Deprecation_Service reads definition. | template-library-migration-coverage-report.md §5 |
| One-pagers / preview | Derived at runtime | No store; regenerated when needed. | template-library-migration-coverage-report.md §3, §4 |
| Appendix (Section/Page Inventory) | Generated from live registry | Regenerated on export or on-demand; no persisted appendix to migrate. | template-library-migration-coverage-report.md §4 |
| Compare state | User meta | Not modified by plugin upgrade; survives. | template-library-migration-coverage-report.md §3 |

---

## 4. ACF field architecture (Prompt 225)

| Surface | Storage / source | Upgrade behavior | Document |
|---------|-------------------|------------------|----------|
| Field keys / group keys | Blueprints + Field_Key_Generator | Deterministic; verified by ACF migration verification harness. | acf-migration-verification-report.md |
| Registry-to-group mapping | Blueprints, ACF registration | Programmatic registration survives; harness verifies stability. | acf-migration-verification-report.md |
| Page assignments (PAGE_TEMPLATE, PAGE_COMPOSITION) | assignment_map | Relevance verified (target_ref in registry); orphaned flagged. | acf-migration-verification-report.md §3.3 |
| Local JSON mirror | Generated at export / debug | Coherence with registry verified; version drift flagged. | acf-local-json-mirror-contract.md, acf-migration-verification-report.md |
| Regeneration/repair | ACF_Regeneration_Service | Plan buildable after version change; repair candidates consistent. | acf-migration-verification-report.md §3.1 |

---

## 5. Lifecycle phase order (activation)

1. validate_environment  
2. check_dependencies  
3. init_options  
4. check_tables_schema (table_schema set here on success)  
5. **template_library_upgrade_compatibility** (registry_schema ensured; non-blocking)  
6. register_capabilities  
7. register_schedules  
8. seed_form_templates … (remaining seed phases)  
9. install_notification_eligibility  
10. first_run_redirect_readiness  

---

## 6. Known limitations

- No live page content migration in template-library pass.
- registry_schema migrations (N → N+1) are not yet implemented; when added, they will run in dependency order and record results via Schema_Version_Tracker.
- Appendix and preview outputs are not versioned as separate artifacts; they reflect current code and registry at generation time.
