# Industry Scaffold Promotion-Readiness Scoring Contract (Prompt 564)

**Spec:** scaffold docs; completeness scoring docs; promotion workflow docs; release docs.  
**Status:** Contract. Defines the scoring model that indicates whether scaffolded assets are approaching authored-pack promotion readiness without confusing scaffold progress with release readiness. No implementation of the scoring engine in this prompt; no auto-promotion.

---

## 1. Purpose

- **Promotion planning:** Maintainers need a clear signal for when a scaffolded pack or subtype is nearing readiness for promotion into an authored/release-candidate path. This contract defines the dimensions and stages so tooling can score and report consistently.
- **Distinct from release readiness:** Scaffold-complete and authored-near-ready are **not** release-ready. Release-ready is defined by the release gate and sandbox promotion workflow (lint 0 errors, health 0 errors, explicit promotion step). This contract only defines how to measure progress **toward** that point.
- **Explicit and review-driven:** Promotion remains explicit and human-approved. Scoring is advisory; it does not trigger promotion or weaken release gates.

---

## 2. Promotion-readiness dimensions

| Dimension | What is measured | Evidence source |
|-----------|------------------|-----------------|
| **Authoring completion** | Placeholder content replaced with real name, summary, content_body; refs filled. | Scaffold completeness report (artifact_classes: scaffolded vs authored); placeholder checks per future-industry-scaffold-pack-template. |
| **Validation status** | Lint and health check pass (zero errors). | Industry_Definition_Linter; Industry_Health_Check_Service. Blocking for promotion; score reflects pass/fail. |
| **Docs maturity** | Overlay docs, helper doc refs, one-pager refs present and non-placeholder. | Completeness report docs dimension; overlay registry presence. |
| **Bundle presence** | Starter bundle defined and active or at least scaffolded. | Industry_Starter_Bundle_Registry; scaffold completeness report bundle artifact class. |
| **Overlay coverage** | Section-helper and page one-pager overlays present (scaffolded or authored). | Overlay registries; scaffold completeness report overlay artifact classes. |
| **QA evidence readiness** | QA or acceptance evidence exists (optional for scoring; required for release gate). | Completeness report QA dimension; release gate checklist. |

Dimensions are combined (e.g. weighted or staged) to produce a **promotion-readiness score** or **readiness tier** per scaffold. Exact formula is implementation-defined; this contract fixes the dimensions and intent.

---

## 3. Readiness stages (distinct from release)

| Stage | Meaning | Typical use |
|-------|---------|-------------|
| **Scaffold-complete** | All artifact classes for the scaffold scope exist (scaffolded or authored); no artifact in "missing". Structure is in place. | Scaffold generator output has been applied; author can start filling content. |
| **Authored-near-ready** | Content authored; refs filled; validation (lint, health) passes or has only waivable warnings. Ready for sandbox dry-run and promotion check. | Candidate for Industry_Sandbox_Promotion_Service::check_prerequisites() and get_release_ready_summary(). |
| **Release-ready** | Per [industry-sandbox-promotion-workflow.md](../operations/industry-sandbox-promotion-workflow.md): dry-run passed, prerequisites_met, candidate set approved for inclusion in release artifact. Not implied by score alone; requires explicit promotion step and release gate. | Post-promotion; definitions in release-ready location; pre-release pipeline run. |

**Scaffold-complete** and **authored-near-ready** are scoring/output stages. **Release-ready** is a workflow state defined by the promotion workflow and release gate; the scoring model does not grant release-ready status.

---

## 4. Industry and subtype scaffold paths

- **Industry scaffold:** Pack definition (draft), optional starter bundle, overlays, rules, docs. Dimensions apply to the pack and its bundle/overlay/rule/doc artifacts. Score per industry_key (or per pack scope).
- **Subtype scaffold:** Subtype definition (draft), subtype-scoped bundles and overlays. Dimensions apply to the subtype and its artifacts. Score per subtype_key (or per subtype scope).
- **Combined:** A report may list both industry and subtype scaffold scopes with a single readiness tier and dimension summary per scope. Grouping and sorting are implementation-defined; keep output bounded and readable.

---

## 5. Bounded and explainable

- **Bounded:** Fixed set of dimensions; fixed stages; no unbounded "readiness" scale. Report caps (e.g. max N scaffold scopes) are implementation-defined.
- **Explainable:** Each dimension and stage has a clear meaning. Blockers and missing evidence are listed so authors know what to fix. Suggested next step (e.g. "Run lint and fix refs") is advisory only.

---

## 6. Relation to existing artifacts

- **Scaffold incomplete guardrail:** [scaffold-incomplete-state-guardrail-contract.md](scaffold-incomplete-state-guardrail-contract.md) defines when assets are incomplete and excluded from release. Promotion-readiness scoring does not override that; it measures progress toward clearing incomplete state.
- **Scaffold completeness report:** [industry-scaffold-completeness-report-contract.md](industry-scaffold-completeness-report-contract.md) and Industry_Scaffold_Completeness_Report_Service provide artifact-level state (missing, scaffolded, authored). Promotion-readiness scoring may use that report as an input and add validation (lint/health) and QA dimensions.
- **Promotion workflow:** [industry-sandbox-promotion-workflow.md](../operations/industry-sandbox-promotion-workflow.md) defines prerequisites and release-ready summary. Promotion-readiness score indicates "near ready for promotion check" but does not replace check_prerequisites() or get_release_ready_summary().
- **Release gate:** [industry-pack-release-gate.md](../release/industry-pack-release-gate.md) and pre-release pipeline define release criteria. Promotion-readiness does not weaken them; release-ready remains a separate workflow outcome.

---

## 7. References

- [scaffold-incomplete-state-guardrail-contract.md](scaffold-incomplete-state-guardrail-contract.md) — Incomplete state and exclusion from release.
- [industry-scaffold-completeness-report-contract.md](industry-scaffold-completeness-report-contract.md) — Scaffold artifact state and report.
- [industry-sandbox-promotion-workflow.md](../operations/industry-sandbox-promotion-workflow.md) — Promotion prerequisites and release-ready summary.
- [industry-pack-release-gate.md](../release/industry-pack-release-gate.md) — Release criteria.
- [industry-pack-completeness-scoring-contract.md](industry-pack-completeness-scoring-contract.md) — Completeness dimensions for packs and subtypes.
