# Industry Question Pack Contract

**Spec**: onboarding/profile sections of aio-page-builder-master-spec.md; industry-onboarding-field-contract.md; industry-pack-extension-contract.md.

**Status**: Switching model and storage for industry-specific onboarding question packs. Additive to base onboarding; answers persist in Industry Profile.

---

## 1. Purpose

- **Switch** additional onboarding questions by **primary industry**: when the site’s primary industry is known, load the matching question pack and show its fields.
- **Persist** answers in the **Industry Profile** (key `question_pack_answers`) so they are exportable and usable by later steps (e.g. AI, template ranking).
- **Fail safely** when primary industry is unset, unknown, or has no pack: show no extra fields; no throw.

---

## 2. Switching model

- **Input**: Site **primary industry** (from Industry Profile `primary_industry_key` or from onboarding draft).
- **Lookup**: Registry maps `industry_key` → **question pack definition** (pack_id, name, intent, fields).
- **Output**: If a pack exists for that industry, the UI shows the pack’s fields; otherwise no extra fields (deterministic, exportable).
- **First supported industry keys**: `cosmetology_nail`, `realtor`, `plumber`, `disaster_recovery`. Other industries have no pack until defined; unsupported industry → no pack, safe.

---

## 3. Pack definition (per industry)

Each question pack has:

| Field     | Type   | Description |
|----------|--------|-------------|
| pack_id  | string | Same as industry_key for the first packs. |
| industry_key | string | Industry pack key (must match Industry_Pack_Registry / primary_industry_key). |
| name     | string | Short display name for the pack. |
| intent   | string | Purpose of the pack (e.g. “Gather cosmetology/nail business context”). |
| fields   | array  | List of field definitions: `{ key, label, type }`. type: text, textarea, select, boolean, etc. |

Field keys are unique within the pack. Answers are stored under `question_pack_answers[industry_key][field_key]`.

---

## 4. Storage mapping

- **Where**: Industry Profile option (Option_Names::INDUSTRY_PROFILE).
- **Key**: `question_pack_answers` (Industry_Profile_Schema::FIELD_QUESTION_PACK_ANSWERS).
- **Shape**: `{ [industry_key]: { [field_key]: scalar } }`. Only scalar values; no secrets.
- **Read**: Industry_Profile_Repository::get_profile()[ 'question_pack_answers' ].
- **Write**: Industry_Profile_Repository::merge_profile( array( 'question_pack_answers' => $by_industry ) ). Merge is by industry_key so one industry’s answers do not overwrite another’s.

---

## 5. Integration with onboarding

- **When building UI state**: Resolve primary industry (draft or industry profile); call registry get(primary_industry_key). If pack exists, add `industry_question_pack` (definition) and `industry_question_pack_answers` (from profile for that industry) to state so the UI can render the fields and prefill.
- **When saving (draft or advance)**: If the form posts industry fields (e.g. primary_industry_key) and/or question pack answers (e.g. aio_industry_qp_{field_key}), the onboarding save handler must merge them into Industry_Profile_Repository (industry fields + question_pack_answers). Capability and nonce are enforced at the handler; repository does not enforce permissions.
- **Partial completion**: Allowed. Missing or empty answers are stored as empty; no throw. Unsupported industry or no pack → nothing to persist for pack answers.

---

## 6. Safe failure

- **No primary industry**: No pack; state has industry_question_pack = null, industry_question_pack_answers = [].
- **Unknown industry key**: Registry returns null; no pack shown.
- **No pack for industry**: Same as unknown; no extra fields.
- **Invalid or missing question_pack_answers in profile**: Schema normalizes to array; invalid entries stripped.

---

## 7. Implementation reference

- **Industry_Question_Pack_Registry**: get(industry_key), get_supported_industry_keys(), load(definitions).
- **Industry_Profile_Schema**: FIELD_QUESTION_PACK_ANSWERS; normalize() and get_empty_profile() include question_pack_answers.
- **Industry_Profile_Repository**: merge_profile() supports question_pack_answers (deep merge by industry_key).
- **industry-onboarding-field-contract.md**: Base industry fields (primary_industry_key, etc.) and persistence flow.
- **industry-profile-schema.md**: Full industry profile shape and lifecycle.
