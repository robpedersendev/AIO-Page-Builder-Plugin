# Industry Profile Validation and Readiness Contract

**Spec**: industry-profile-schema.md; industry-question-pack-contract.md; onboarding/profile completeness.

**Status**: Validation rules, safe defaults, and completeness/readiness scoring for Industry Profile. Additive; does not replace existing onboarding behavior.

---

## 1. Purpose

- **Validate** Industry Profile and question-pack answers so later systems (AI planner, template ranking, LPagery) can rely on structured, bounded data.
- **Provide safe defaults** for missing optional fields (no invented industry context).
- **Compute completeness/readiness** so callers can tell whether enough industry context exists to rank templates, apply AI rules, or recommend LPagery safely.
- **Expose validation and readiness** in a reusable, explainable way.

---

## 2. Validation rules

### 2.1 Base profile

- **schema_version**: Must be supported (e.g. `1`). Unsupported → invalid; repository already returns normalized empty profile on load.
- **primary_industry_key**: When non-empty, should match a known industry pack key when Industry_Pack_Registry is available; otherwise advisory (storage accepts any non-secret string).
- **secondary_industry_keys**: Must be list of non-empty strings; duplicates and non-strings stripped by schema normalize.
- **subtype**, **service_model**, **geo_model**: No format requirement; trimmed strings or empty.
- **question_pack_answers**: Must be industry_key => field_key => scalar; nested arrays or secrets prohibited. Schema normalizer strips invalid entries.

### 2.2 Question-pack answers (when primary has a pack)

- When **Industry_Question_Pack_Registry** is available and primary_industry_key has a pack: validation may report **warnings** for missing recommended fields (not errors; partial completion is allowed).
- Invalid or non-scalar values in question_pack_answers for that industry → validation error for that entry.

### 2.3 Safe failure

- Corrupt or incomplete profile → validator must not throw; return validation result with errors and readiness state **none** or **minimal**.
- No unsafe assumptions from incomplete data; validation output is bounded and admin-safe.

---

## 3. Defaults

- **Missing optional fields**: Use Industry_Profile_Schema::get_empty_profile() defaults (empty string, empty array). Validator does not invent or overwrite with misleading industry context.
- **Readiness when profile missing**: state = **none**, score = 0, no invented data.

---

## 4. Readiness / completeness scoring

- **States**: `none` | `minimal` | `partial` | `ready`.
  - **none**: Profile absent, corrupt, or unsupported schema version.
  - **minimal**: Profile valid; primary_industry_key empty (industry context not yet chosen).
  - **partial**: Primary industry set; question pack may exist but answers incomplete or not required for “ready”.
  - **ready**: Primary industry set and, when a question pack exists for that industry, at least one answer provided (or product rule: ready when primary set and no pack, or primary set and pack satisfied).
- **Score**: 0–100 integer. 0 = none, ~25 = minimal, ~50–75 = partial, 100 = ready. Exact formula is product-defined; must be explainable (e.g. primary_set + qp_covered_ratio).
- **Explainability**: Readiness result includes a short breakdown (e.g. primary_set: bool, question_pack_complete: bool, validation_errors_count).

---

## 5. Exposure

- **Industry_Profile_Validator**: validate( profile, options? ) → validation result (errors, warnings).
- **Industry_Profile_Readiness_Result**: Immutable value object: state, score, validation_errors, validation_warnings, details (breakdown).
- **Industry_Profile_Repository**: May offer get_readiness() that returns Readiness_Result using validator and optional registries; or callers use validator + readiness calculator separately. Contract does not require repository to hold readiness logic.

---

## 6. Integration

- Onboarding and AI input packaging can call validator and readiness before including industry context in artifacts.
- Existing onboarding/profile flows unchanged; validation is additive. Safe failure if profile is corrupt or registries unavailable.

---

## 7. Implementation reference

- **Industry_Profile_Validator**: validate(), get_validation_errors(), get_validation_warnings().
- **Industry_Profile_Readiness_Result**: get_state(), get_score(), get_validation_errors(), get_validation_warnings(), get_details(), to_array().
- **industry-profile-schema.md**: Field definitions and normalize().
- **industry-question-pack-contract.md**: Question-pack structure and storage.
