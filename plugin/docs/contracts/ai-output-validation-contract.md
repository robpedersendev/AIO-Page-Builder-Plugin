# AI Output Validation Contract

**Document type:** Authoritative contract for the AI output validation pipeline (spec §25.4, §28, §28.11–28.14, §29.1, §29.3, §29.4, §59.8).  
**Governs:** Staged validation order, validation and dropped-record reports, normalized output, repair-attempt hook, and the rule that only validated normalized output may feed Build Plan generation.  
**Reference:** Master Specification §28.1–28.14; ai-provider-contract.md (normalized response, validation_failure); prompt-pack-schema.md (schema_target_ref, repair_prompt_ref).

---

## 1. Scope and principles

- **Only validated output downstream:** Raw or invalid provider output must never enter Build Plan generation or executor pathways (§28.1, §28.14). The validation pipeline is the single gate.
- **Staged pipeline:** Validation runs in a fixed order. Any blocking failure stops downstream plan creation unless partial-output rules apply (§28.11).
- **Raw vs normalized separation:** Raw provider response is stored separately. Validation report and normalized output are distinct artifacts. Dropped-record report is produced when partial handling is used (§28.14, §29.1).
- **One bounded repair attempt:** When output is invalid, one automated repair attempt may be made using a schema-repair prompt reference. The validator exposes a hook for this; it does not perform provider calls. If repair fails, no Build Plan is generated (§28.12).
- **Partial output rules:** Partial acceptance is allowed only when all top-level required sections exist, invalidity is limited to item-level records, and invalid records can be removed without corrupting global structure. Dropped records are logged and surfaced (§28.13).

---

## 2. Validation pipeline order

Validation shall occur in this order (§28.11):

| Stage | Description | On failure |
|-------|-------------|------------|
| 1. Raw response capture | Accept raw provider response (string or pre-parsed array). Record capture status. | If no response or unrecoverable capture → blocking; no parse. |
| 2. Parse attempt | Decode JSON if string; validate root is object/array. | Parse failure → blocking; validation_report.parse_status = failed. |
| 3. Top-level schema check | All required top-level keys present; types correct. | Missing required key or wrong type → blocking unless partial rules allow (they do not for top-level). |
| 4. Object-shape validation | Nested objects match expected shape. | Per-section; may be item-level (array items). |
| 5. Enum validation | Fields with enum constraints have allowed values. | Per-field; record in record_validation_results. |
| 6. Required-field validation | Required fields within objects present. | Per-record; can drive item-level drop. |
| 7. Internal-reference validation | References (e.g. target_page, parent_url) resolve or are consistent. | Can be item-level. |
| 8. Local-target resolution | Resolve local targets where applicable (plugin-defined). | Optional; can be item-level. |
| 9. Normalization | Build internal plugin-owned structure from validated payload. | Only when validation passes (full or partial). |

---

## 3. Validation result and report shapes

### 3.1 Validation report (machine-readable)

| Field | Type | Description |
|-------|------|-------------|
| `raw_capture_status` | string | `ok` \| `empty` \| `error`. |
| `parse_status` | string | `ok` \| `failed`. |
| `top_level_valid` | bool | True if all required top-level sections exist and are correct type. |
| `schema_ref` | string | Schema reference used (e.g. aio/build-plan-draft-v1). |
| `record_validation_results` | array | Per-section or per-item results: section_key, index (if item), valid, errors[]. |
| `dropped_records` | array | When partial handling: list of dropped record descriptors (section, index, reason). |
| `normalized_output` | object \| null | Populated only when final_validation_state allows handoff; else null. |
| `final_validation_state` | string | `passed` \| `partial` \| `failed`. |
| `blocking_failure_stage` | string \| null | When failed: stage at which validation failed (e.g. parse, top_level, item). |
| `repair_attempted` | bool | Whether a repair attempt was invoked (by caller). |
| `repair_succeeded` | bool | If repair attempted, whether it produced valid output. |

No secrets or raw credential values in the report.

### 3.2 Dropped record report (when partial handling)

| Field | Type | Description |
|-------|------|-------------|
| `section` | string | Top-level section key (e.g. existing_page_changes). |
| `index` | int | Index of the dropped record in the array. |
| `reason` | string | Short reason (e.g. invalid_enum, missing_required_field). |
| `errors` | array | List of error codes or messages (redacted; no sensitive data). |

Dropped records are logged and surfaced so the Build Plan can show omitted recommendations (§28.13).

---

## 4. Final validation states

| State | Meaning | Build Plan handoff |
|-------|---------|---------------------|
| `passed` | All validation stages passed. | Allowed; normalized_output present. |
| `partial` | Top-level valid; one or more item-level records dropped; remaining structure valid. | Allowed; normalized_output present; dropped_record_report present. |
| `failed` | Blocking failure (parse, top-level, or unrecoverable item-level). | Not allowed; normalized_output null. |

---

## 5. Repair-attempt hook

- **Contract:** The validator does not call the provider. The caller may invoke a repair flow (e.g. request with schema-repair prompt) and pass the repair response back into the validator. The validator records `repair_attempted` and `repair_succeeded` when the caller indicates a repair was attempted and whether the second validation passed.
- **One attempt only:** At most one automated repair attempt per run (§28.12). If repair fails, the run shall not generate a Build Plan.

---

## 6. Example: passing validation report

```json
{
  "raw_capture_status": "ok",
  "parse_status": "ok",
  "top_level_valid": true,
  "schema_ref": "aio/build-plan-draft-v1",
  "record_validation_results": [],
  "dropped_records": [],
  "normalized_output": {
    "schema_version": "1",
    "run_summary": { "summary_text": "Draft plan.", "planning_mode": "mixed", "overall_confidence": "medium" },
    "site_purpose": {},
    "site_structure": { "recommended_top_level_pages": [], "hierarchy_map": [], "navigation_summary": "" },
    "existing_page_changes": [],
    "new_pages_to_create": [],
    "menu_change_plan": [],
    "design_token_recommendations": [],
    "seo_recommendations": [],
    "warnings": [],
    "assumptions": [],
    "confidence": {}
  },
  "final_validation_state": "passed",
  "blocking_failure_stage": null,
  "repair_attempted": false,
  "repair_succeeded": false
}
```

---

## 7. Example: partial-output validation report

```json
{
  "raw_capture_status": "ok",
  "parse_status": "ok",
  "top_level_valid": true,
  "schema_ref": "aio/build-plan-draft-v1",
  "record_validation_results": [
    { "section": "existing_page_changes", "index": 1, "valid": false, "errors": ["invalid_enum: action"] }
  ],
  "dropped_records": [
    { "section": "existing_page_changes", "index": 1, "reason": "invalid_enum", "errors": ["action"] }
  ],
  "normalized_output": { "schema_version": "1", "existing_page_changes": [ { "current_page_url": "/", "action": "keep", "reason": "Keep as is.", "confidence": "high", "risk_level": "low" } ], "new_pages_to_create": [], "menu_change_plan": [], "design_token_recommendations": [], "seo_recommendations": [], "warnings": [], "assumptions": [], "confidence": {} },
  "final_validation_state": "partial",
  "blocking_failure_stage": null,
  "repair_attempted": false,
  "repair_succeeded": false
}
```

---

## 8. Cross-references

- **Provider response:** ai-provider-contract.md — provider returns raw response; driver normalizes to response shape; validator consumes `structured_payload` (or raw string) and produces validation report and normalized output.
- **Prompt pack:** prompt-pack-schema.md — schema_target_ref and repair_prompt_ref link to output schema and repair pack.
- **Artifact storage:** §29.3 Raw Provider Response Storage; §29.4 Normalized Output Storage. Validation report and dropped-record report are stored as distinct categories (§29.1).
