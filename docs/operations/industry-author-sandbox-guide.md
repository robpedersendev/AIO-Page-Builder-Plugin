# Industry Author Sandbox (Dry-Run Validation) Guide (Prompt 444)

**Spec**: authoring guide; linting, health report, coverage analysis, and pre-release validation docs; import/preview contracts.

**Purpose**: Safe dry-run validation of candidate pack/bundle definitions without activating them on live configuration. Internal pack authors only.

---

## 1. Scope

- **Industry_Author_Sandbox_Service::run_dry_run( $candidate_packs, $candidate_bundles )** loads candidate definitions into in-memory registries and runs:
  - **Definition linting** (Industry_Definition_Linter): schema, duplicate keys, refs, subtype parent consistency, bundle graph.
  - **Health check** (Industry_Health_Check_Service): pack refs, bundle refs (no profile checks when profile repo is null).
- **No persistence**: Live profile, live pack/bundle registries, and settings are not read or written. The run is fully in-memory.
- **Promotion boundary**: Dry-run results are for review only. Applying candidate definitions to live state is a separate, explicit step (e.g. import flow or registry load change) and is not part of this service.

---

## 2. Usage

- **Caller** (script, CLI, or internal admin tool) prepares:
  - `$candidate_packs`: list of pack definition arrays (same shape as builtin pack definitions).
  - `$candidate_bundles`: list of starter bundle definition arrays (same shape as builtin bundle definitions).
- **Call** `$sandbox->run_dry_run( $candidate_packs, $candidate_bundles )`.
- **Result**: `lint_result`, `health_result`, and a `summary` with counts (lint_errors, lint_warnings, health_errors, health_warnings). Use these to decide whether the candidate set is ready for release or import.
- **No auto-fix**: The sandbox does not modify the candidate arrays or any live state.

---

## 3. Integration

- **Pre-release**: Use the sandbox as part of the pre-release validation pipeline (see industry-pre-release-validation-pipeline.md) when validating new or modified pack/bundle files before release.
- **Authoring**: After editing pack or bundle definition files, run a dry-run (e.g. via WP-CLI or a small script that loads the candidate definitions from file and calls the service) to catch ref and schema issues before committing.
- **Import preview**: Not a replacement for the existing bundle import conflict flow; that flow remains the authority for import. The sandbox can be used to validate a payload before import without applying it.

---

## 4. Promotion (sandbox to release-ready)

- Dry-run results are for review only. To move validated candidates toward release, use **Industry_Sandbox_Promotion_Service**: check_prerequisites( dry_run_result ) and get_release_ready_summary( candidate_packs, candidate_bundles, dry_run_result ). Promotion does not auto-activate; it produces an audit summary. The actual copy of definitions into release-ready locations is an explicit operator step. See [industry-sandbox-promotion-workflow.md](industry-sandbox-promotion-workflow.md).

---

## 5. Do not

- Do not use the sandbox to mutate live registries or profile.
- Do not expose the sandbox to public or unauthenticated users.
- Do not assume dry-run success implies live activation will succeed; other factors (e.g. profile, toggle state) apply at activation time.
- Do not use promotion to auto-activate assets on any site; promotion is review-driven and non-activating.
