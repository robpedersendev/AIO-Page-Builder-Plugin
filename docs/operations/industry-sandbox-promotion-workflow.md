# Industry Sandbox-to-Release Promotion Workflow (Prompt 454)

**Spec:** author sandbox guide; pre-release validation pipeline; pack authoring and release gate docs.  
**Purpose:** Bounded, review-driven path from validated sandbox candidates to release-ready asset description. Promotion does not auto-activate on live sites.

---

## 1. Scope

- **Sandbox:** Industry_Author_Sandbox_Service::run_dry_run( candidate_packs, candidate_bundles ) validates candidates in-memory; no live state read or written.
- **Promotion service:** Industry_Sandbox_Promotion_Service checks prerequisites from dry-run output and returns a release-ready candidate summary (pack keys, bundle keys, audit text). No file write, no activation, no mutation of live registries or profile.
- **Handoff:** Operators use the summary and validated definitions to copy or merge into release-ready definition files (e.g. builtin pack/bundle loaders). That copy step is explicit and out-of-band (script, manual, or CI); the plugin does not perform it.

---

## 2. Promotion prerequisites

Before a candidate set is considered release-ready:

| Prerequisite | Source | Blocking |
|--------------|--------|----------|
| Lint errors = 0 | dry_run_result.summary.lint_errors | Yes |
| Health check errors = 0 | dry_run_result.summary.health_errors | Yes |
| Lint warnings | Advisory; document and waive if accepted | No |
| Health warnings | Advisory; document and waive if accepted | No |

Industry_Sandbox_Promotion_Service::check_prerequisites( dry_run_result ) returns prerequisites_met (bool) and missing_requirements (list of strings). Use to gate the next step.

---

## 3. Approved / release-ready state

- **Release-ready** means: the candidate pack and bundle definitions have passed dry-run validation (lint + health with zero errors) and are suitable for inclusion in a release artifact. It does **not** mean they are activated on any site.
- **State transition:** Sandbox (validated) → Release-ready (artifact description) is explicit. The promotion service only produces a summary (pack_keys, bundle_keys, prerequisites_met, summary string). The actual inclusion of definitions in the codebase or release bundle is an operator/author step.

---

## 4. Operator steps

1. **Validate:** Run Industry_Author_Sandbox_Service::run_dry_run( candidate_packs, candidate_bundles ). Resolve all lint and health errors.
2. **Check prerequisites:** Run Industry_Sandbox_Promotion_Service::check_prerequisites( dry_run_result ). If not prerequisites_met, fix missing_requirements and re-run dry run.
3. **Get release-ready summary:** Run get_release_ready_summary( candidate_packs, candidate_bundles, dry_run_result ). Use pack_keys and bundle_keys for audit and for the next step.
4. **Promote (out-of-band):** Copy or merge the validated candidate definitions into the release-ready location (e.g. builtin pack/bundle definitions loaded by the plugin). This step is not implemented by the promotion service; it is manual, scripted, or CI. Do not auto-activate promoted assets on live sites; activation remains a separate configuration step (e.g. industry profile selection, pack toggle).
5. **Release gate:** Run the full pre-release validation pipeline (lint, health, coverage, benchmarks, regression guards) against the release candidate. See [industry-pre-release-validation-pipeline.md](../release/industry-pre-release-validation-pipeline.md) and [industry-release-candidate-manifest-contract.md](../contracts/industry-release-candidate-manifest-contract.md).

---

## 5. Auditability

- get_release_ready_summary() returns pack_keys and bundle_keys so reviewers know exactly which definitions passed validation and are in the candidate set.
- Dry-run result and promotion summary are for internal review only; no secrets. Retain in logs or CI artifacts as needed for audit.

---

## 6. Safe failure

- If prerequisites are not met, check_prerequisites() returns prerequisites_met = false and missing_requirements list. get_release_ready_summary() still returns pack_keys and bundle_keys but summary text and prerequisites_met indicate that promotion should not proceed until errors are fixed.
- The service never mutates live state; safe to call from scripts or admin tooling.

---

## 7. Promotion-readiness scoring

Maintainers can assess how close a scaffold is to promotion using the internal [industry-scaffold-promotion-readiness-contract.md](../contracts/industry-scaffold-promotion-readiness-contract.md) (Prompt 564) and Industry_Scaffold_Promotion_Readiness_Report_Service (Prompt 565). The report is available from the Industry Author Dashboard and the "Scaffold promotion readiness" admin screen. Score indicates scaffold-complete vs authored-near-ready; it does not replace check_prerequisites() or the release gate.

---

## 8. References

- [industry-author-sandbox-guide.md](industry-author-sandbox-guide.md) — Dry-run usage.
- [industry-pre-release-validation-pipeline.md](../release/industry-pre-release-validation-pipeline.md) — Full validation steps.
- [industry-pack-release-gate.md](../release/industry-pack-release-gate.md) — Release gate criteria.
- [industry-release-candidate-manifest-contract.md](../contracts/industry-release-candidate-manifest-contract.md) — Evidence required for release candidate.
