# Industry Pack Object Schema

**Spec**: industry-pack-extension-contract.md; aio-page-builder-master-spec.md; data-schema-appendix §11.

**Status**: Canonical schema for Industry Pack definition objects. Supports versioned, exportable, and portable pack definitions aligned with existing registry conventions.

---

## 1. Purpose

- Define the **canonical shape** of an industry pack object.
- Support **validation**, **versioning**, and **persistence** (PHP definitions, option-backed, or DB-backed) consistent with plugin registry patterns.
- Ensure **export/restore** can include pack definitions and industry profile; schema must be deterministic and versioned.

---

## 2. Required fields

| Field | Type | Description |
|-------|------|-------------|
| **industry_key** | string | Stable, unique key for the pack (e.g. `legal`, `healthcare`). Used in profile and overlays. |
| **name** | string | Human-readable pack name. |
| **summary** | string | Short description of the industry vertical and pack purpose. |
| **status** | string | Lifecycle status: `active`, `draft`, or `deprecated`. Only `active` packs are used for overlays and ranking. |
| **version_marker** | string | Schema version (e.g. `1`). Used to reject unsupported or future versions at load/validate. |

---

## 3. Optional fields

| Field | Type | Description |
|-------|------|-------------|
| **supported_page_families** | list&lt;string&gt; | Page template families this pack supports; used for template ranking and filtering. |
| **preferred_section_keys** | list&lt;string&gt; | Section template internal_keys preferred for this industry. |
| **discouraged_section_keys** | list&lt;string&gt; | Section keys to deprioritize or warn for this industry. |
| **default_cta_patterns** | list&lt;string&gt; or map | Preferred CTA pattern keys (alias for preferred_cta_patterns); see industry-cta-pattern-contract. |
| **preferred_cta_patterns** | list&lt;string&gt; | CTA pattern keys preferred for this industry (Industry_CTA_Pattern_Registry). |
| **discouraged_cta_patterns** | list&lt;string&gt; | CTA pattern keys to deprioritize or avoid for this industry. |
| **required_cta_patterns** | list&lt;string&gt; | CTA pattern keys required or strongly recommended for this industry. |
| **seo_guidance_ref** | string | Reference to SEO guidance doc or block. |
| **helper_overlay_refs** | list&lt;string&gt; | Refs to helper docs applied when this pack is active (overlay on section helper refs). |
| **one_pager_overlay_refs** | list&lt;string&gt; | Refs to one-pager docs applied when this pack is active (overlay on page one-pager refs). |
| **token_preset_ref** | string | Reference to LPagery/token preset for this industry. |
| **lpagery_rule_ref** | string | Reference to LPagery rule set for this industry. |
| **ai_rule_ref** | string | Reference to AI planning rule or prompt overlay for this industry. |
| **metadata** | map | Optional arbitrary metadata (e.g. label, sort order). Must not contain secrets. |

---

## 4. Validation rules

- **industry_key**: Non-empty; pattern `^[a-z0-9_-]+$`; max length 64. Must be unique within the registry.
- **name**, **summary**: Non-empty when present; reasonable max length (e.g. 256 for name, 1024 for summary).
- **status**: Must be one of `active`, `draft`, `deprecated`.
- **version_marker**: Must match a supported schema version (e.g. `1`). Unsupported versions cause validation failure and safe rejection at load.
- **supported_page_families**, **preferred_section_keys**, **discouraged_section_keys**: Arrays of non-empty strings; keys should exist in section/page registries when used (validation may be strict or advisory per implementation).
- **Refs** (seo_guidance_ref, helper_overlay_refs, one_pager_overlay_refs, token_preset_ref, lpagery_rule_ref, ai_rule_ref): No secrets; safe for export. Format and resolution defined by respective subsystems.

Malformed or invalid pack definitions must be **rejected** by validation; no silent correction. Validation returns a list of error codes (e.g. `missing_required`, `invalid_status`, `unsupported_version`) with optional field reference.

---

## 5. Versioning

- **version_marker** on the pack object identifies the **schema version** the pack is written against.
- The plugin supports a fixed set of schema versions (e.g. `1`). Loading or validating a pack with an unsupported version_marker must fail with a clear error (e.g. `unsupported_version`).
- Version is **deterministic**: same pack content produces the same validation result for a given schema version.

---

## 6. Persistence and load

- Persistence strategy is **not** fixed by this schema: packs may be stored as PHP array definitions, option-backed registry, or DB-backed registry, consistent with existing plugin registry patterns.
- **Load** behavior: loader must validate each pack with Industry_Pack_Schema::validate_pack(); invalid or unsupported-version packs are skipped or rejected; valid packs are exposed by the registry service.
- **Export/restore**: When export includes industry data, pack definitions must be serialized in a format that round-trips through validate_pack() after restore. Industry profile (primary/secondary keys) is stored separately; see industry-pack-extension-contract and Profile in industry-pack-service-map.

---

## 7. Export/restore compatibility

- Industry pack objects are **portable**. Export payload must include all required and optional fields needed to restore behavior; no reliance on environment-specific secrets or paths in the pack object.
- Restore must re-validate restored packs; unsupported versions or invalid data must not overwrite valid state; failures must be reported and optionally logged.

---

## 8. Implementation reference

- **Industry_Pack_Schema** (plugin/src/Domain/Industry/Registry/Industry_Pack_Schema.php): Field constants, `get_required_fields()`, `get_optional_fields()`, `get_allowed_statuses()`, `is_supported_version()`, `validate_pack( array $pack ): array` (returns list of `{ code, field? }`).
- **data-schema-appendix.md** §11: Summary of industry pack schema for quick reference.
