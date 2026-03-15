# Styling Export and Restore Verification

**Purpose**: Verify that global styling settings and per-entity style payloads are exported and restored correctly; schema version validation and post-restore cache invalidation behave as specified.

**Contract refs**: [styling-subsystem-contract.md](../contracts/styling-subsystem-contract.md), [global-styling-settings-contract.md](../contracts/global-styling-settings-contract.md), [per-entity-style-payload-contract.md](../contracts/per-entity-style-payload-contract.md), [styling-cache-and-versioning-contract.md](../contracts/styling-cache-and-versioning-contract.md).

**Prompt**: 257 (Export, restore, and migration support for styling data).

---

## 1. Export inclusion

- [ ] **Full operational backup**: Export with mode FULL_OPERATIONAL_BACKUP includes category `styling`; the package contains `styling/global_settings.json` and `styling/entity_payloads.json` with current option data (or empty/default shapes when none set).
- [ ] **Template-only export**: Export with mode TEMPLATE_ONLY_EXPORT includes `styling`; per-entity payloads for section/page templates are present so that template + styling travel together.
- [ ] **Pre-uninstall backup**: Styling category is included; both files are present.
- [ ] **Manifest**: `included_categories` in manifest.json lists `styling` when the category was exported.
- [ ] **No generated caches**: Export does not include style cache version or preview snapshot cache; only canonical options (global_settings, entity_payloads) are written.

---

## 2. Restore and version validation

- [ ] **Restore order**: Styling is restored after `settings` and before `profiles` per RESTORE_ORDER. Restore validator EXPECTED_ORDER includes `styling` in the correct position.
- [ ] **Supported version**: Package with global_settings.json and entity_payloads.json at schema version `1` (Global_Style_Settings_Schema::SCHEMA_VERSION, Entity_Style_Payload_Schema::SCHEMA_VERSION) passes import validation and is applied on restore.
- [ ] **Unsupported version**: Package with a different or missing `version` in global_settings or entity_payloads fails import validation with a clear failure message (e.g. "Unsupported global styling schema version" or "Unsupported entity style payloads schema version"); no restore write occurs for styling.
- [ ] **Invalid payload**: Corrupt or non-array JSON in styling files is handled safely (decode failure or version check failure); restore does not overwrite with invalid data. Other categories (e.g. registries, settings) are unaffected.

---

## 3. Post-restore cache invalidation

- [ ] **Style cache**: After a successful restore that includes the styling category, Style_Cache_Service::invalidate() is called so the style output version is bumped.
- [ ] **Preview cache**: Preview snapshot cache is cleared as a result of the same invalidation so that detail/compare screens show restored styling on next load.
- [ ] **Front end**: Next front-end request that enqueues the base stylesheet uses the new cache version; no stale global or per-entity CSS.

---

## 4. Safety and discipline

- [ ] **Export/import capability-gated**: Export and import actions remain behind the same capability and nonce checks as before; no new public API.
- [ ] **No content mutation**: Restore of styling does not modify saved post_content or block markup; only options aio_global_style_settings and aio_entity_style_payloads are updated.
- [ ] **No secrets**: Exported styling files contain only token values and override data; no API keys or secrets. Redaction for support bundle does not apply to styling (styling is not included in support bundle mode per category matrix).

---

## 5. Migration and schema evolution

- [ ] **Documentation**: Data-schema-appendix and contracts document what is exported (global_settings, entity_payloads), expected schema version, and that invalid/incompatible data is rejected at restore.
- [ ] **Future versions**: When schema version is bumped (e.g. global or entity payload structure changes), migration path and supported versions in Import_Validator and Restore_Pipeline must be updated; this checklist should be re-run.

---

*Run this checklist after changes to Export_Generator, Restore_Pipeline, Import_Validator, styling schemas, or Style_Cache_Service.*
