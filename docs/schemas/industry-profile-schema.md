# Industry Profile Schema (Site-Level)

**Spec**: industry-pack-extension-contract.md; aio-page-builder-master-spec.md; onboarding/profile sections.

**Status**: Site-level industry context for template ranking, onboarding, AI planning, and LPagery. Additive to existing brand/business profile; stored in a dedicated option.

---

## 1. Purpose

- Store **site-level industry context**: primary industry, optional secondary industries, subtype, service model, geo model.
- Provide a **single source of truth** for later onboarding steps, template ranking, AI planning, and LPagery logic.
- Remain **additive** to existing Profile_Store (brand_profile, business_profile, template_preference_profile); no replacement of those roots.
- Support **export/restore** and **versioned** structure; safe failure on corrupt or incomplete data.

---

## 2. Storage

- **Option key**: `aio_page_builder_industry_profile` (Option_Names::INDUSTRY_PROFILE).
- **Shape**: Single object per site. See §3.
- **Lifecycle**: Initial empty state (no key or empty structure); set during onboarding or settings; later edits overwrite; export/restore includes this option when industry data is implemented.

---

## 3. Required and optional fields

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| **schema_version** | string | Yes | `1` | Schema version for migration and validation. |
| **primary_industry_key** | string | No | `""` | Industry pack key (e.g. `legal`, `healthcare`) that best matches the site. Empty when not set. |
| **secondary_industry_keys** | list&lt;string&gt; | No | `[]` | Additional industry keys for broader filtering or fallback. |
| **subtype** | string | No | `""` | Optional freeform subtype hint (legacy or display). |
| **industry_subtype_key** | string | No | `""` | Optional structured subtype key (industry-subtype-schema.md). Must reference a subtype whose parent_industry_key matches primary_industry_key; otherwise invalid and resolution falls back to parent industry only. |
| **service_model** | string | No | `""` | Optional service model hint (e.g. `b2b`, `b2c`, `local_service`). |
| **geo_model** | string | No | `""` | Optional geo model hint (e.g. `local`, `regional`, `national`). |
| **derived_flags** | object | No | `{}` | Optional flags set by subsystems. Use `multi_industry` (bool) when both primary and secondary industries are set; used for conflict-resolution and weighted recommendation (industry-conflict-resolution-contract.md). |
| **question_pack_answers** | object | No | `{}` | Industry-specific question-pack answers: `{ [industry_key]: { [field_key]: scalar } }`. See industry-question-pack-contract.md. |

- **primary_industry_key**: Must match an existing industry pack key when non-empty; validation may be advisory at storage time and strict at use time.
- **secondary_industry_keys**: Array of non-empty strings; no duplicates; keys should exist in industry pack registry when used.
- **subtype**: Freeform or legacy; no secrets; exportable. **industry_subtype_key**: When non-empty, must resolve to a valid subtype for the primary industry (see industry-subtype-extension-contract.md); invalid ref is ignored at resolution.
- **service_model**, **geo_model**: Freeform or controlled per product; no secrets; exportable.

---

## 4. Empty state and defaults

- **Initial empty state**: No option set, or option value not an array → treat as empty profile. Repository returns default shape with `schema_version`, empty `primary_industry_key`, empty `secondary_industry_keys`, and other optional fields at default.
- **Default empty profile**: `{ schema_version: "1", primary_industry_key: "", secondary_industry_keys: [], subtype: "", industry_subtype_key: "", service_model: "", geo_model: "", derived_flags: {} }`.

---

## 5. Validation and safe failure

- **schema_version**: Supported value `1`. Unsupported version → reject load or return empty profile and log.
- **Corrupt or incomplete data**: Repository must not throw publicly; return normalized default or sanitized structure. Invalid array elements (e.g. non-string in secondary_industry_keys) are stripped.
- **No secrets**: Industry profile must not contain API keys, tokens, or passwords.

---

## 6. Lifecycle

- **Initial**: Empty; no industry selected.
- **Onboarding set**: Primary (and optionally secondary, subtype, service_model, geo_model) set via onboarding or settings (UI out of scope for this schema).
- **Later edits**: Full replace or merge per repository API; version retained.
- **Export/restore**: Option included in export payload; restore validates schema_version and applies same safe-failure rules.

---

## 7. Integration with existing profile

- **Profile_Store** (brand_profile, business_profile, template_preference_profile) is unchanged. Industry profile is a **separate option** and **additive**.
- Onboarding and AI context builders may **read** industry profile in addition to Profile_Store; industry profile does not replace any existing root.

---

## 8. Implementation reference

- **Industry_Profile_Schema**: Field constants, default empty array, validation helpers.
- **Industry_Profile_Repository**: get_profile(), set_profile(), merge_profile(), get_empty_profile(), get_readiness(); uses Settings_Service and Option_Names::INDUSTRY_PROFILE.
- **Industry_Profile_Validator**: validate(), get_readiness(); optional Industry_Pack_Registry and Industry_Question_Pack_Registry for strict validation and completeness.
- **Industry_Profile_Readiness_Result**: state (none|minimal|partial|ready), score 0–100, validation_errors, validation_warnings, details; see industry-profile-validation-contract.md.
- **industry-profile-validation-contract.md**: Validation rules, safe defaults, completeness/readiness scoring, exposure for downstream systems.
- **industry-onboarding-field-contract.md**: Field keys, storage mapping, and persistence flow for onboarding/profile intake; industry fields are additive and persist via this repository.
- **data-schema-appendix.md**: Summary of industry profile schema.
