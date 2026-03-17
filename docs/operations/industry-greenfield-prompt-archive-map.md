# Industry Subsystem Greenfield Prompt Archive Map (Prompt 570)

**Spec:** Roadmap contract; greenfield closure report; implementation-audit entrypoint map; maturity matrix; v2 guardrails.  
**Purpose:** Final archival map for the greenfield prompt system (Prompts 318–570). Summarizes which prompt ranges built which capability layers, which artifacts are authoritative, and where implementation-audit work begins. Enables future teams to understand the program and start audit-phase prompt generation from a single reference.

---

## 1. Scope and use

- **Audience:** Future maintainers, implementation-audit prompt authors, and anyone needing to trace capability to prompt range or to find authoritative contracts.
- **Not a substitute for:** The roadmap contract, v2 guardrails, or the implementation-audit entrypoint map. This document **archives** the greenfield program and points to those artifacts for ongoing decisions and audit work.
- **Greenfield phase:** Treated as **closed** with this map. New greenfield-style prompts (adding major capability layers) must be explicitly scoped and aligned with the roadmap; the next-phase prompt map and backlog remain the place for optional expansion.

---

## 2. Prompt ranges by capability cluster

The following table groups the greenfield prompt sequence by capability cluster. Ranges are approximate; dependencies often cross clusters.

| Capability cluster | Approximate prompt range | Key outcomes |
|--------------------|--------------------------|--------------|
| **Packs, registry, schema, validation** | 318–400+ | Industry_Pack_Registry, Industry_Pack_Validator, industry-pack-schema; pack loaders; roadmap contract (400). |
| **Industry profile (repository, schema, validation)** | 318–400+ | Industry_Profile_Repository, Industry_Profile_Schema, Industry_Profile_Validator; export/restore; profile settings screen. |
| **Subtypes (registry, resolver, fallback)** | 414+ | Subtype schema, Industry_Subtype_Resolver, parent fallback; subtype overlays and bundles. |
| **Starter bundles** | 400+ | Bundle registry, schema, bundle-to-plan conversion; comparison and import preview screens. |
| **Section and page overlays** | 318+ | Section-helper and page-onepager overlay registries; composition; allowed regions; overlay schemas. |
| **Recommendation resolvers** | 318+ | Section and page template recommendation resolvers; cache; neutral fallback; regression guard contract. |
| **Build Plan scoring and context** | 318+ | Additive metadata; context profile injection; planner/executor separation. |
| **AI / prompt pack overlays** | 318+ | Industry prompt pack overlay; subtype AI overlay; evaluation fixtures. |
| **Health, linting, repair, coverage** | 438–443+ | Health check service; definition linter; repair suggestion engine; coverage-gap analyzer; guided repair screen. |
| **Pre-release, sandbox, promotion** | 440, 444, 454+ | Pre-release validation pipeline; sandbox dry-run; promotion workflow; release gate; maintenance checklist. |
| **Phase-two checkpoint and backlog** | 445 | industry-phase-two-backlog-map.md; completed clusters and remaining gaps. |
| **Maturity matrix and v2 guardrails** | 468, 469 | industry-subsystem-maturity-matrix.md; industry-subsystem-v2-guardrails.md. |
| **Next-phase prompt map** | 470 | industry-next-phase-prompt-map.md; dependency baseline 318–469; prompt clusters for next phase. |
| **Future-industry evaluation and tooling** | 420, 471–473, 516+ | Candidate evaluation framework; intake dossier; scorecard template/executor; comparison matrix; scaffold pack template; first-pack runbook. |
| **Author dashboard and comparison screens** | 522, 557–558 | Industry Author Dashboard; pack family comparison contract and screen; comparison screens (subtype, bundle, goal). |
| **Completeness, scaffold, maturity reporting** | 519, 538, 555, 559–560 | Pack completeness scoring; scaffold completeness report; asset aging; maturity delta report contract and service/screen. |
| **Drift detection and drift report** | 561–562 | industry-subsystem-drift-detection-contract.md; Industry_Drift_Report_Service and screen. |
| **Future expansion readiness and promotion-readiness** | 563–567 | Future expansion readiness widget; scaffold promotion-readiness contract and report/screen; Future_Industry_Readiness_Screen; Future_Subtype_Readiness_Screen. |
| **Greenfield closure and audit handoff** | 568–570 | industry-greenfield-closure-report.md; industry-implementation-audit-entrypoint-map.md; this archive map. |
| **Optional late-stage backlog** | 571–585 | Prompts 571–584 (secondary-goal, variance, scaffold export, evidence packet) are optional; listed in [industry-optional-late-stage-greenfield-backlog.md](industry-optional-late-stage-greenfield-backlog.md) (585). Implementation-audit is next priority. |

---

## 3. Authoritative contracts and core artifact families

**Authoritative contracts** (define behavior and boundaries; changes require explicit contract update):

- **Roadmap and guardrails:** industry-subsystem-roadmap-contract.md, industry-subsystem-v2-guardrails.md.
- **Pack and profile:** industry-pack-schema, industry-pack-extension-contract, industry-profile-schema, industry-profile-validation-contract, industry-export-restore-contract.
- **Subtypes:** industry-subtype-extension-contract, industry-subtype-schema.
- **Overlays:** industry-section-helper-overlay-schema, industry-page-onepager-overlay-schema; composition and allowed regions.
- **Recommendation:** industry-section-recommendation-contract, industry-page-template-recommendation-contract; industry-recommendation-regression-guard.
- **Release and authoring:** industry-pack-release-gate, industry-sandbox-promotion-workflow, industry-pack-authoring-guide, industry-pack-maintenance-checklist.
- **Cache, diagnostics, degraded mode:** industry-cache-contract, industry-subsystem-diagnostics-checklist, industry-degraded-mode-contract.
- **Scaffold and promotion-readiness:** industry-scaffold-generator-contract, scaffold-incomplete-state-guardrail-contract, industry-scaffold-promotion-readiness-contract.
- **Drift and maturity:** industry-subsystem-drift-detection-contract, industry-maturity-delta-report-contract.

**Core artifact families** (registries, services, screens implied by the above):

- **Registries:** Industry_Pack_Registry, Industry_Profile_Repository, Industry_Subtype_Registry, Industry_Starter_Bundle_Registry, overlay registries, CTA/style/SEO/LPagery registries.
- **Services:** Recommendation resolvers, overlay composers, health check, linter, coverage analyzer, completeness/promotion/drift/maturity report services, sandbox and promotion services.
- **Screens:** Industry Profile, Author Dashboard, Health Report, comparison screens (pack family, subtype, bundle, goal), readiness screens (future industry, future subtype), report screens (maturity delta, drift, scaffold promotion readiness), Guided Repair, Bundle Import Preview.

**Seeded content and templates** (operational artifacts; not contracts): industry-pack-catalog, overlay catalogs, coverage matrices, future-industry scorecard/dossier templates, runbooks, maintenance checklist. These are updated as the system evolves; they do not define subsystem boundaries.

---

## 4. Optional vs foundational late-stage expansions

- **Foundational (in scope for greenfield closure):** All capability layers listed in the greenfield closure report §1 are considered implemented and contracted. The archive map does not reclassify them as optional.
- **Optional late-stage expansion:** Documented in the closure report §2 and next-phase prompt map: additional subtypes, deeper T2/T3 overlays, what-if UI surface, overlay coverage in diagnostics, CLI wrappers, scorecard executor/comparison matrix automation. These do not block implementation-audit; audit work should verify **existing** behavior first.
- **No new greenfield scope after 570:** New prompts that add major capability layers should be explicitly scoped (e.g. via roadmap or backlog); the default next phase is implementation-audit per the entrypoint map.

---

## 5. Transition point into implementation-audit work

- **Transition:** The greenfield prompt system is **closed** with Prompt 570. The next phase of prompt generation should be **implementation-audit** (correctness, safety, failure modes, test coverage), not new feature expansion.
- **Starting reference:** Use [industry-implementation-audit-entrypoint-map.md](industry-implementation-audit-entrypoint-map.md) (Prompt 569) for priority audit domains, high-risk codepaths, registries/screens to audit, and audit clusters (safety-critical, correctness-critical, polish).
- **Closure report:** [industry-greenfield-closure-report.md](industry-greenfield-closure-report.md) (Prompt 568) defines completed layers, remaining optional opportunities, and practical audit clusters. It and this archive map together give a complete handoff.

Implementation-audit prompts must not add new runtime features unless explicitly scoped elsewhere; they produce audits, tests, or documentation updates that verify existing behavior and close gaps.

---

## 6. References

- [industry-greenfield-closure-report.md](industry-greenfield-closure-report.md) — Closure report (Prompt 568); completed layers, optional expansion, audit handoff.
- [industry-implementation-audit-entrypoint-map.md](industry-implementation-audit-entrypoint-map.md) — Audit entrypoint map (Prompt 569); priority domains, codepaths, clusters.
- [industry-subsystem-roadmap-contract.md](../contracts/industry-subsystem-roadmap-contract.md) — Extension seams and roadmap categories.
- [industry-subsystem-maturity-matrix.md](industry-subsystem-maturity-matrix.md) — Capability maturity and evidence.
- [industry-subsystem-v2-guardrails.md](../contracts/industry-subsystem-v2-guardrails.md) — Unacceptable drift and boundaries.
- [industry-next-phase-prompt-map.md](industry-next-phase-prompt-map.md) — Next-phase clusters and greenfield/audit handoff (§6).
- [industry-phase-two-backlog-map.md](industry-phase-two-backlog-map.md) — Phase-two checkpoint and backlog.
- [industry-optional-late-stage-greenfield-backlog.md](industry-optional-late-stage-greenfield-backlog.md) — Optional late-stage backlog (Prompt 585); Prompts 571–584; audit first.
