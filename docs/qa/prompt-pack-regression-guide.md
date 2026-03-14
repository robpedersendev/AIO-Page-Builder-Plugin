# Prompt Pack Regression Harness

Internal QA guide for the prompt-pack regression harness (spec §26, §28.11–28.13, §56.2, §58.3, Prompt 120). The harness compares validator output against golden fixtures to detect schema, dropped-record, and normalized-output regressions before adopting prompt-pack revisions.

## Purpose

- **Regression testing**: Run validator on fixed inputs and compare results to stored expected outcomes.
- **Schema compliance**: Ensure validation pipeline behavior (passed/partial/failed, blocking stage) remains stable.
- **Dropped-record behavior**: Compare dropped-record count and shapes when partial output is expected.
- **Normalized output quality**: Compare normalized output structure and values when state is passed or partial.

The harness is **internal and review-oriented**. It is not a runtime feature and is not exposed to ordinary users.

## Fixture Location and Versioning

- Fixtures live under **`plugin/tests/fixtures/prompt-packs/`**.
- Naming: `{internal_key_sanitized}-{version}-{suffix}.json` (e.g. `aio-build-plan-draft-1.0.0-golden.json`).
- Fixture versioning is tied to prompt-pack version; one fixture file per scenario (golden pass, validation failed, partial with drops, etc.).

## Golden Fixture Shape

Fixtures are JSON files or arrays with this structure:

| Key | Type | Description |
|-----|------|-------------|
| `fixture_version` | string | Optional; for fixture schema evolution. |
| `prompt_pack_ref` | object | `internal_key`, `version` – identifies the prompt pack. |
| `schema_ref` | string | Schema reference (e.g. `aio/build-plan-draft-v1`). |
| `input` | string or object | Raw content to validate (JSON string or parsed object). |
| `expected` | object | Expected validator outcome. |

### Expected Object

| Key | Type | Description |
|-----|------|-------------|
| `final_validation_state` | string | `passed`, `partial`, or `failed`. |
| `blocking_failure_stage` | string, optional | When failed: e.g. `raw_capture`, `parse`, `top_level`, `item`. |
| `normalized_output` | object, optional | When passed/partial: expected normalized output for comparison. |
| `dropped_records` | array, optional | When partial: expected dropped-record shapes (`section`, `index`, `reason`, `errors`). |

No secrets or unsafe production data. Redaction rules apply to any captured examples.

## Regression Result Shape

Results are machine-readable:

- **outcome**: `pass` | `fail` | `regression`
- **regression_run**: `run_id`, `prompt_pack_ref`, `schema_ref`, `ran_at`
- **normalized_output_diff_summary**: `match`, `added_keys`, `removed_keys`, `value_diffs` (when expected normalized output is provided)
- **validator_regression_summary**: `final_validation_state_match`, `blocking_stage_match`, `dropped_count_match`, `dropped_record_diffs`
- **message**: Short human-readable summary

## How to Run

1. **From code**: Instantiate `Prompt_Pack_Regression_Harness` with `AI_Output_Validator` and optional `fixtures_base_path`. Call `run( $fixture_array )` or `run( 'path/to/fixture.json' )`.
2. **Fixtures base path**: When loading by relative path, set `fixtures_base_path` to `plugin/tests/fixtures` (or absolute equivalent) so that `prompt-packs/aio-build-plan-draft-1.0.0-golden.json` resolves correctly.

## Adding or Updating Fixtures

1. Add a new JSON file under `plugin/tests/fixtures/prompt-packs/` with the golden_fixture shape.
2. Use a minimal, deterministic input that exercises the scenario (exact pass, partial with drops, or validation failed).
3. Set `expected.final_validation_state` and, when relevant, `expected.normalized_output` and `expected.dropped_records`.
4. Run the regression test; if the validator behavior is intentional, update the fixture expected values and re-run until the outcome is `pass`.

## Security and Permissions

- Fixtures must not contain secrets or unsafe production data.
- Harness execution is internal and admin/dev controlled.
- No live provider calls; no cost; no Build Plan execution.

## Boundaries

- **In scope**: Golden fixtures, validator comparison, machine-readable pass/fail/regression, fixture versioning tied to prompt-pack version.
- **Out of scope**: Live provider cost optimization, self-modifying prompts, automatic prompt-pack promotion, Build Plan execution.

---

## Template-Recommendation Regression (Prompt 211)

A separate regression harness targets **template-family recommendations** (spec §58.3, §60.5). It compares recommendation payloads (class, family, CTA-law, explanation) against golden cases so prompt-pack and planning changes can be tested without execution. Internal QA only.

### Fixture Location and Shape

- Fixtures live under **`plugin/tests/fixtures/template-recommendations/`**.
- Naming: e.g. `golden-top-level.json`, `golden-hub.json`, `golden-nested-hub.json`, `golden-child-detail.json`.
- Each fixture has: `case_id`, `scenario`, `fixture_version`, `recommendation`, `expected`, and optionally `template_metadata` for CTA checks.

| Key | Type | Description |
|-----|------|-------------|
| `case_id` | string | Case identifier. |
| `scenario` | string | `top_level`, `hub`, `nested_hub`, or `child_detail`. |
| `fixture_version` | string | Fixture schema version. |
| `recommendation` | object | Actual or synthetic recommendation (may include `proposed_template_summary`). |
| `expected` | object | Expected constraints. |
| `template_metadata` | object, optional | When CTA-law is checked: `min_cta`, `last_section_cta`. |

### Recommendation Object

- Top-level or under `proposed_template_summary`: `template_key`, `template_category_class`, `template_family`, and optionally `template_selection_reason`.

### Expected Object

| Key | Type | Description |
|-----|------|-------------|
| `template_category_class` | string or string[] | Required class or list of allowed classes. |
| `allowed_template_families` | string[], optional | Allowed template families; omitted means no family check. |
| `cta_law_aligned` | bool, optional | When set, CTA-law alignment is checked (using `template_metadata` when present). |
| `require_explanation` | bool, optional | When true, non-empty `template_selection_reason` is required. |

### How to Run

1. **From code**: Instantiate `Template_Recommendation_Regression_Harness` with optional `fixtures_base_path`. Call `run( $fixture_array )` or `run( 'template-recommendations/golden-top-level.json' )`.
2. **Fixtures base path**: Set to `plugin/tests/fixtures` (or absolute equivalent) when loading by relative path.

### Result Shape (template_recommendation_regression_result)

Machine-readable payload from `Template_Recommendation_Regression_Result::to_array()`:

- **outcome**: `pass` | `fail` | `regression`
- **regression_run**: `case_id`, `scenario`, `fixture_version`, `ran_at`
- **class_fit**: bool
- **family_fit**: bool
- **cta_law_aligned**: bool | null (null when not checked)
- **explanation_fit**: bool
- **message**: string
- **details**: object (e.g. `class_mismatch`, `family_mismatch`, `cta_law`, `explanation_missing`, `fixture_invalid`)

### Example template_recommendation_regression_result Payload

```json
{
  "outcome": "pass",
  "regression_run": {
    "case_id": "golden-top-level",
    "scenario": "top_level",
    "fixture_version": "1",
    "ran_at": "2025-07-15T12:00:00Z"
  },
  "class_fit": true,
  "family_fit": true,
  "cta_law_aligned": true,
  "explanation_fit": true,
  "message": "Template recommendation regression pass: class, family, and explanation fit.",
  "details": {}
}
```

Evaluation is not limited to exact template_key: family fit, hierarchy class fit, and CTA-law awareness (when applicable) are checked. Fixtures must be synthetic and versioned; no secrets or real customer data.
