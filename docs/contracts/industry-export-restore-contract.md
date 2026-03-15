# Industry Export/Restore Contract

**Spec**: aio-page-builder-master-spec.md (export/restore); industry-pack-extension-contract. **Prompt**: 355.

This contract defines how Industry Pack subsystem data (industry profile, applied style preset) is included in export bundles and restored. Industry data is **additive** to the `profiles` category; it does not replace the main brand/business profile.

---

## 1. Scope

- **Exported**: Industry Profile (Option_Names::INDUSTRY_PROFILE), Applied Industry Preset (Option_Names::APPLIED_INDUSTRY_PRESET), and schema version for migration.
- **Not exported**: Pack definitions (built-in), overlay definitions (built-in), registries (already in registries category if included). Only site-specific industry **state** is exported under profiles.
- **Secrets**: None. Industry payloads contain no API keys or credentials.

---

## 2. Bundle layout

When the `profiles` category is included in an export:

- `profiles/profile.json` — Brand/business profile (existing).
- `profiles/industry.json` — Industry export payload (new). Present only when industry data exists or when the plugin writes it (always write for consistency; empty state is valid).

---

## 3. Industry payload schema (profiles/industry.json)

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `schema_version` | string | Yes | Export schema version (e.g. `1`). Used for restore migration. |
| `industry_profile` | object | Yes | Normalized industry profile (Industry_Profile_Schema shape). May be empty. |
| `applied_preset` | object \| null | Yes | Current applied industry style preset (Industry_Style_Preset_Application_Service shape) or null. |

**Validation on restore:**

- `schema_version` must be in the supported list (e.g. `['1']`). Unsupported or missing version → skip industry restore and log; do not fail the whole profiles restore.
- `industry_profile` must be an array; otherwise treat as empty and still restore applied_preset if valid.
- `applied_preset` may be null or array; invalid type → restore as null.

---

## 4. Versioning and migration

- **Current schema version**: `1`.
- Restore MUST support current version. Future versions may require migration steps; unsupported versions MUST be skipped with a warning log, not a fatal error.
- Migration logic (if needed later) runs after validation and before writing options.

---

## 5. Restore behavior

1. After restoring `profiles/profile.json`, restore pipeline checks for `profiles/industry.json`.
2. If missing → no industry restore; profiles category restore still succeeds.
3. If present: decode JSON; validate `schema_version`; if supported, normalize `industry_profile` (e.g. via Industry_Profile_Schema::normalize), then write Option_Names::INDUSTRY_PROFILE and Option_Names::APPLIED_INDUSTRY_PRESET (via Settings_Service or update_option). Invalidate any industry-related caches if present.
4. If invalid or unsupported version: log and skip industry restore only; do not roll back profile.json.

---

## 6. Export behavior

When `profiles` is in included categories:

1. Write `profile.json` as today.
2. Build industry payload: `schema_version`, `industry_profile` (from settings get INDUSTRY_PROFILE), `applied_preset` (from settings get APPLIED_INDUSTRY_PRESET).
3. Write `profiles/industry.json`. No redaction (industry data is non-secret).

---

## 7. Security and permissions

- Export/restore are admin-only (enforced by existing export/restore surfaces). No new mutation surfaces.
- No secrets in industry payloads. Invalid payloads fail safely (skip industry restore, log).

---

## 8. Relationship to other contracts

- **Export bundle structure**: Industry data lives under `profiles/`; it does not introduce a new top-level category. Manifest `included_categories` still lists `profiles` only.
- **Industry pack extension**: Aligns with “export/restore must include industry pack definitions and industry profile” by including industry **profile** and **applied preset**; pack/overlay definitions remain built-in or registry-backed.
