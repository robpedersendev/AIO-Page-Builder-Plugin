# Industry Pre-Release Validation Pipeline (Prompt 440)

**Spec**: release gate docs; authoring guide; linting, health, coverage, and benchmark contracts.  
**Purpose**: Repeatable pre-release validation for industry subsystem changes so pack authors and maintainers can run a disciplined series of checks before shipping a new pack version or subtype layer.

---

## 1. Overview

- **Scope**: Industry subsystem only (packs, overlays, bundles, subtypes, rules, presets). Does not replace full product release process.
- **Who runs**: Pack authors, maintainers, or QA before submitting a pack change or new industry for release. Human review remains required; no automated release decision.
- **When**: Before merging pack/overlay/bundle changes; before tagging a release that includes industry changes; when adding a new industry or subtype.

---

## 2. Validation steps (required order)

| Step | Tool / artifact | Pass / fail / warning | Reference |
|------|-----------------|------------------------|-----------|
| 1. Definition linting | Industry_Definition_Linter::lint() | Errors = fail; warnings = advisory | [industry-definition-linting-guide.md](../operations/industry-definition-linting-guide.md) |
| 2. Health check | Industry_Health_Check_Service::run() | Errors = fail; warnings = advisory | industry-pack-release-gate; health-report contracts |
| 3. Coverage-gap analysis | Industry_Coverage_Gap_Analyzer::analyze() | Informational; use to prioritize backlog | [industry-coverage-gap-analysis-guide.md](../operations/industry-coverage-gap-analysis-guide.md) |
| 4. Recommendation benchmarks | Industry_Recommendation_Benchmark_Service (if available) | Per benchmark protocol; regressions = fail or waive | [industry-recommendation-benchmark-protocol.md](../qa/industry-recommendation-benchmark-protocol.md) |
| 5. Regression guards | Industry_Recommendation_Regression_Guard_Test | Unit tests must pass | [industry-recommendation-regression-guard.md](../qa/industry-recommendation-regression-guard.md) |
| 6. AI prompt-pack evaluation | Evaluation fixtures for overlays (if in scope) | Per fixtures; document results | [industry-ai-prompt-evaluation-fixtures.md](../qa/industry-ai-prompt-evaluation-fixtures.md) |
| 7. Lifecycle / fallback audit | Manual or script: no-industry path, deprecated pack handling | No regressions | industry-pack-deprecation-contract; industry-neutral-mode-audit |
| 8. Dry-run sandbox (optional) | Industry_Author_Sandbox_Service::run_dry_run() for candidate packs/bundles | Lint/health errors = fix before promote | [industry-author-sandbox-guide.md](../operations/industry-author-sandbox-guide.md) |
| 9. Release gate criteria | [industry-pack-release-gate.md](industry-pack-release-gate.md) | All criteria met or waived | Release gate §1 |

---

## 3. Pass / fail / warning

- **Pass**: Step completes with no errors; warnings (if any) documented and accepted.
- **Fail**: One or more errors. Release blocked until fixed or formally waived and recorded in sign-off.
- **Warning**: Advisory only; does not block release but should be tracked in backlog or waiver log.

---

## 4. Integration with tooling

- **Linter**: Run after registries are loaded (e.g. bootstrap or after loading a bundle). Fix all errors before release.
- **Health check**: Same registries as linter; run in same session. Resolve ref and profile errors.
- **Coverage analyzer**: Use output to document known gaps and prioritize; does not block.
- **Benchmarks and regression tests**: Run as part of existing test suite; failures block unless waived.
- **Checklist**: Use [industry-pre-release-checklist.md](industry-pre-release-checklist.md) for a concise run list.

---

## 5. Out of scope

- Full CI platform or public release dashboards.
- Automated destructive actions or auto-fix of definition files.
- Bypassing human review for release decisions.

---

## 6. References

- [industry-pre-release-checklist.md](industry-pre-release-checklist.md) — Executable checklist.
- [industry-pack-release-gate.md](industry-pack-release-gate.md) — Gate criteria and sign-off.
- [industry-pack-authoring-guide.md](../operations/industry-pack-authoring-guide.md) — Author workflow.
- [industry-pack-maintenance-checklist.md](../operations/industry-pack-maintenance-checklist.md) — Ongoing maintenance.
