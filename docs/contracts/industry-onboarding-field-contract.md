# Industry Onboarding Field Contract

**Spec**: onboarding/profile sections of aio-page-builder-master-spec.md; industry-pack-extension-contract.md; industry-profile-schema.md.

**Status**: Field architecture for industry-aware onboarding and profile intake. Industry fields are additive to existing business/brand profile; persistence via Industry_Profile_Repository.

---

## 1. Purpose

- Define **field keys and storage mapping** for industry context (primary industry, secondary industries, subtype, service model, geo model) so onboarding and profile editing can collect them without replacing the existing profile system.
- Ensure **persistence** flows through **Industry_Profile_Repository** (Option_Names::INDUSTRY_PROFILE).
- Keep **exportability** and **structured storage**; no hardcoded single-industry assumption.

---

## 2. Field definitions (storage mapping)

All industry onboarding fields map to **Industry_Profile_Schema** and are read/written via **Industry_Profile_Repository**. Profile mutations remain admin/authorized-only; no secrets.

| Form/UI field concept | Storage key (Industry_Profile_Schema) | Type | Notes |
|-----------------------|----------------------------------------|------|--------|
| Primary industry | `primary_industry_key` | string | Single industry pack key (e.g. legal, cosmetology, realtor, plumber, disaster_recovery). |
| Secondary industries | `secondary_industry_keys` | list&lt;string&gt; | Zero or more additional industry keys. |
| Subtype | `subtype` | string | Optional industry subtype (e.g. nail, residential_realtor). |
| Service model | `service_model` | string | Optional (e.g. b2b, b2c, local_service, emergency_response). |
| Geo model | `geo_model` | string | Optional (e.g. local, regional, national). |

- **primary_industry_key**: Used for question-pack switching (Prompt 329) and template ranking; empty when not set.
- **secondary_industry_keys**: Used for broader filtering; no duplicate keys; order optional.
- **subtype**, **service_model**, **geo_model**: Freeform or controlled per product; stored as-is (trimmed); max lengths per industry-profile-schema.

---

## 3. Persistence flow

- **Read**: Onboarding or profile UI reads current industry context via `Industry_Profile_Repository::get_profile()`. Default empty state when none set.
- **Write**: On save, call `Industry_Profile_Repository::merge_profile( $partial )` with keys `primary_industry_key`, `secondary_industry_keys`, `subtype`, `service_model`, `geo_model` as applicable. Callers must enforce capability and nonce; repository does not enforce permissions.
- **Storage**: Option_Names::INDUSTRY_PROFILE; shape normalized by Industry_Profile_Schema::normalize(). Export/restore includes this option.

---

## 4. Integration with onboarding steps

- Industry context may be collected in a **dedicated onboarding step** (e.g. industry_context) or merged into an existing step (e.g. after business_profile or before template_preferences). Implementation may add a step key to Onboarding_Step_Keys when a dedicated step is introduced.
- **Draft state**: Onboarding draft (Option_Names::ONBOARDING_DRAFT) may hold in-progress industry selections; final save persists to Industry_Profile_Repository so profile and onboarding stay in sync.
- **Existing steps**: BUSINESS_PROFILE, BRAND_PROFILE, TEMPLATE_PREFERENCES, and others remain unchanged; industry fields are additive.

---

## 5. Field group / blueprint contract

- When **onboarding or profile field blueprint services** register fields for intake, they should include industry fields that:
  - Use the storage keys above.
  - Persist via Industry_Profile_Repository (not Profile_Store brand/business roots).
  - Are compatible with existing onboarding persistence and editing flows (capability-gated, nonce-verified).
- **Industry-specific question packs** (Prompt 329) extend this with additional fields per primary industry; those answers may persist in the same Industry Profile (e.g. additive keys or question_pack_answers) per industry-question-pack-contract.

---

## 6. Safe handling

- **Missing or partial values**: Repository and schema normalize missing keys to default (empty string or empty array); no throw. UI may show placeholders when profile is empty.
- **Invalid industry keys**: Validation at use time (e.g. against Industry_Pack_Registry); storage accepts any non-secret string for primary_industry_key; invalid keys may result in no question pack or no ranking benefit until corrected.

---

## 7. Implementation reference

- **Industry_Profile_Repository**: get_profile(), set_profile(), merge_profile(). Container key: industry_profile_store (when settings available).
- **Industry_Profile_Schema**: get_empty_profile(), normalize(), field constants.
- **industry-profile-schema.md**: Full schema and lifecycle.
- **profile-schema.md**: Brand/business profile; industry is separate (industry-profile-schema).
