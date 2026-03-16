# Industry-Aware Planner Input Contract

**Spec**: AI planning sections of aio-page-builder-master-spec.md; industry-pack-extension-contract.md; industry-profile-validation-contract.md.

**Status**: Additive planner input artifact structure for industry context. Schema-validated and versioned; safe failure when industry context is incomplete.

---

## 1. Purpose

- Carry **industry context**, **industry pack refs**, **CTA preferences**, and **LPagery posture** into the AI planning layer in a controlled, schema-validated way.
- Remain **additive** to the existing input artifact; no replacement of profile, crawl, registry, goal, or planning_guidance.
- Stay **auditable** and **exportable** per existing artifact policy; no secrets.

---

## 2. Artifact additive structure

The planner input artifact (Input_Artifact_Schema, Input_Artifact_Builder) gains an **optional** root key: **industry_context** (or equivalent). When present, it has the following shape (schema version 1).

### 2.1 industry_context (optional root)

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **schema_version** | string | Yes | `1` for this contract. |
| **industry_profile** | object | No | Validated industry profile snapshot or ref. When present, contains primary_industry_key, readiness_state, readiness_score; may contain sanitized subset of profile (no secrets). |
| **active_industry_pack_refs** | array | No | List of industry pack refs (e.g. `{ industry_key, version_marker }`) for the primary (and optionally secondary) industries. |
| **readiness** | object | No | Readiness result summary: state, score, validation_passed. From Industry_Profile_Readiness_Result. |
| **cta_pattern_refs** | array | No | CTA pattern refs or ids preferred for this industry (from industry pack or profile). |
| **lpagery_posture** | string | No | LPagery stance for planning (e.g. `prefer_local`, `neutral`, `defer`). Optional; from industry pack or default. |
| **industry_guidance_refs** | array | No | Refs to industry-specific guidance (helper overlays, one-pager overlays, or rule refs). |
| **industry_subtype_key** | string | No | Resolved subtype key when valid (e.g. realtor_buyer_agent). Empty when no subtype or invalid (Prompt 430; industry-subtype-ai-overlay-contract.md). |
| **resolved_subtype_snapshot** | object | No | Sanitized snapshot for planning: label, summary only. Omitted when no valid subtype. |
| **subtype_bundle_refs** | array | No | Optional list of starter bundle keys recommended for this subtype. |
| **subtype_cta_posture_ref** | string | No | Optional CTA posture ref from subtype definition. |
| **subtype_rule_refs** | array | No | Optional caution/rule refs from subtype definition. |

- **Required** for artifact validity: none of these fields are required at artifact root; the existing required_root_keys remain unchanged.
- **Optional**: All industry_context fields are optional. When industry profile is missing or not ready, industry_context may be omitted or contain only readiness with state `none`/`minimal`.
- **Safe failure**: If industry context is incomplete, assemblers must not invent data; omit industry_context or set readiness only. Downstream (prompt-pack overlay, template ranking) may treat missing industry_context as “no industry guidance”.

---

## 3. Versioning and validation

- **Artifact schema_version**: Unchanged (e.g. `1`). Industry_context has its own schema_version inside the block for future evolution.
- **Prohibited keys**: Industry context must not introduce prohibited keys (secrets, tokens, etc.); Input_Artifact_Schema::find_prohibited_keys_in_array continues to apply.
- **Export**: Industry context is part of the artifact and subject to the same export/redaction policy as other artifact sections.

---

## 4. Integration with input artifact assembly

- **Input_Artifact_Builder**: Accepts optional `industry_context` in options; when present and valid, sets `artifact[Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT]` (or chosen constant). No change to required keys.
- **Onboarding_Planning_Request_Orchestrator** (or equivalent): May build industry_context from Industry_Profile_Repository, Industry_Profile_Validator/Readiness, Industry_Pack_Registry, and CTA pattern registry; passes it into the builder when available. Safe failure: if profile missing or readiness is none/minimal, pass empty or omit.
- **Normalized_Prompt_Package_Builder**: May consume artifact.industry_context for placeholder resolution or planning guidance; see industry-prompt-pack-overlay-contract.

---

## 5. Implementation reference

- **Input_Artifact_Schema**: ROOT_INDUSTRY_CONTEXT constant; industry_context not in required_root_keys.
- **Input_Artifact_Builder**: build() accepts options['industry_context']; sets artifact root when provided and passes prohibited-key check.
- **industry-profile-validation-contract.md**: Readiness and validated profile shape.
- **industry-pack-extension-contract.md**: Industry pack refs and CTA/LPagery posture sources.
