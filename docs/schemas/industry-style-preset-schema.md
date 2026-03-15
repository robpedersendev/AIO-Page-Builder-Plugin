# Industry Style Preset Schema

**Spec**: styling-subsystem-contract.md; css-selector-contract.md; industry-pack-extension-contract.md.

**Status**: Schema for industry style presets. Presets supply token value bundles and optional component override refs over the existing styling system. No arbitrary CSS or new selectors/token names.

---

## 1. Purpose

- Allow industry packs to reference an **industry style preset** by key (Industry_Pack_Schema::token_preset_ref).
- Presets provide **token value bundles** (--aio-* names => values) and optional **component override refs** (from pb-style-components-spec) for visual direction per industry.
- Presets are **overlays** on the styling subsystem; selector and token-name contracts remain fixed. Only **values** may vary.

---

## 2. Required fields

| Field | Type | Description |
|-------|------|-------------|
| **style_preset_key** | string | Stable unique key (e.g. `legal_serious`, `realtor_warm`). Referenced by industry pack token_preset_ref. |
| **label** | string | Human-readable preset name. |
| **version_marker** | string | Schema version (e.g. `1`). Unsupported versions cause load/validate failure. |
| **status** | string | `active`, `draft`, or `deprecated`. Only `active` presets are used. |

---

## 3. Optional fields

| Field | Type | Description |
|-------|------|-------------|
| **industry_key** | string | Industry key this preset is associated with (e.g. `legal`, `healthcare`). Used for listing and validation. |
| **token_values** | map (string => string) | Token name => value. Keys must be allowed --aio-* names per core spec; values must pass styling sanitization. No raw CSS. |
| **token_set_ref** | string | Optional reference to another token set by key (alternative to inline token_values). Resolution defined by implementation. |
| **component_override_refs** | list&lt;string&gt; | Optional component ids from pb-style-components-spec.json; preset may reference approved component overrides. |
| **description** | string | Short description or preview metadata. |
| **preview_metadata** | map | Optional preview/description metadata (no secrets). |

---

## 4. Validation and safety

- **style_preset_key**: Non-empty; pattern `^[a-z0-9_-]+$`; max 64 chars. Unique within registry.
- **token_values**: Keys must match allowed token names from the style registry (--aio-*). Values must be sanitizable per styling-sanitization-rules; invalid names or values are rejected at load. **No raw CSS strings.**
- **component_override_refs**: Each id must exist in the component spec; unknown refs are stripped or cause validation failure per implementation.
- **industry_key**: When present, pattern `^[a-z0-9_-]+$`; max 64 chars (aligned with Industry_Pack_Schema).
- Invalid preset definitions **fail safely**: reject at load or strip invalid entries; never emit unsanitized data.

---

## 5. Relationship to styling subsystem

- Presets **do not** introduce new token names or selectors. They supply **values** for existing --aio-* tokens and **refs** to existing component override definitions.
- Application of presets (e.g. merging into global styling or per-entity payload) is **out of scope** for this schema; this document defines the preset object shape and registry only.
- Compatibility: Presets must be validated against the same Style_Token_Registry and component spec used by the styling subsystem; invalid token names or component refs must not be applied.

---

## 6. Implementation reference

- **Industry_Style_Preset_Registry**: Read-only registry; load(array), get(key), get_all(), list_by_industry(industry_key). Invalid definitions are skipped at load.
- **Industry_Pack_Schema::FIELD_TOKEN_PRESET_REF**: Industry pack optional field; value is a style_preset_key resolved by Industry_Style_Preset_Registry.
- styling-subsystem-contract.md §3.1: Token names fixed; only values may vary.
- style-registry-contract.md: Token and component metadata for validation.
