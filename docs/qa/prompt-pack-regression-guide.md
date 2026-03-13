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
