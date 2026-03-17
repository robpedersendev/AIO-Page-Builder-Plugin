# Industry Pre-Release Checklist (Prompt 440)

**Purpose**: Executable checklist tying together definition linting, health checks, coverage analysis, benchmarks, regression guards, and release gate. Run before shipping a new pack version or subtype layer.

**Pipeline**: [industry-pre-release-validation-pipeline.md](industry-pre-release-validation-pipeline.md).

---

## Before you start

- [ ] Registries loaded (builtin or bundle) with the pack/overlay/bundle/subtype changes you intend to release.
- [ ] No uncommitted or untested definition file changes that could affect lint/health.

---

## 1. Definition linting

- [ ] Run **Industry_Definition_Linter** with same registries as health check (pack, CTA, SEO, LPagery, preset, section overlay, page overlay, question pack, starter bundle, profile repo, subtype registry). Pass **Industry_Health_Check_Service** for ref checks.
- [ ] **Errors** = 0 (fix or waive and document).
- [ ] **Warnings** reviewed and documented or backlogged.
- [ ] Ref: [industry-definition-linting-guide.md](../operations/industry-definition-linting-guide.md).

---

## 2. Health check

- [ ] Run **Industry_Health_Check_Service::run()**.
- [ ] **Errors** = 0 (fix refs, profile, or bundle graph).
- [ ] **Warnings** reviewed and documented or backlogged.

---

## 3. Coverage-gap analysis

- [ ] Run **Industry_Coverage_Gap_Analyzer::analyze(true)** (include subtypes).
- [ ] Review **gaps** and **by_scope**; use to prioritize backlog or document known gaps for this release.
- [ ] Ref: [industry-coverage-gap-analysis-guide.md](../operations/industry-coverage-gap-analysis-guide.md).

---

## 4. Recommendation benchmarks (if in scope)

- [ ] Run recommendation benchmark per [industry-recommendation-benchmark-protocol.md](../qa/industry-recommendation-benchmark-protocol.md).
- [ ] No regressions, or waiver recorded.

---

## 5. Regression guards

- [ ] **Industry_Recommendation_Regression_Guard_Test** and related unit tests pass.
- [ ] Ref: [industry-recommendation-regression-guard.md](../qa/industry-recommendation-regression-guard.md).

---

## 6. AI prompt-pack evaluation (if in scope)

- [ ] Run evaluation fixtures per [industry-ai-prompt-evaluation-fixtures.md](../qa/industry-ai-prompt-evaluation-fixtures.md).
- [ ] Results documented; no unwaived regressions.

---

## 7. Lifecycle and fallback

- [ ] No-industry path still works (industry-neutral-mode-audit or smoke test).
- [ ] Deprecated pack handling per [industry-pack-deprecation-contract.md](../contracts/industry-pack-deprecation-contract.md); no unintended breakage.

---

## 8. Release gate

- [ ] All criteria in [industry-pack-release-gate.md](industry-pack-release-gate.md) §1 met or waived.
- [ ] Sign-off (QA, technical lead, product owner) per release gate §3 when industry is in release scope.

---

## 9. Sign-off

- [ ] **Completed by**: ___________________ **Date**: ___________
- [ ] **Errors resolved or waived**: ___________________
- [ ] **Warnings / gaps documented**: ___________________

*This checklist references real tooling and artifacts. Update if new validation steps or contracts are added.*
