# Industry Pack Family Comparison View Contract (Prompt 557)

**Spec:** maturity matrix docs; roadmap docs; author dashboard docs; future-industry evaluation docs.  
**Status:** Contract. Defines the **internal** comparison view for pack families (launch industries, subtype families, future candidate families) across bundle coverage, overlay depth, caution burden, and planning maturity. Comparison is advisory and read-only; no UI implementation in this contract; no mutation of roadmap state.

---

## 1. Purpose

- **Planning lens:** Maintainers can compare supported families and candidate families side by side to balance support effort, prioritize expansion, and align with roadmap.
- **Bounded view:** Comparison uses existing report outputs (completeness, maturity, gap, candidate-evaluation); no new runtime schema or hidden prioritization side effects.
- **Internal only:** Artifact is for maintainers and roadmap planning; no public comparison view.

---

## 2. Comparison frame

- **Baseline frame:** One-plugin architecture; all families are compared within the same subsystem (same registries, same completeness/maturity evidence sources).
- **Family scope:**
  - **Launch-industry families:** Active packs (industry_key) as the primary comparison unit.
  - **Subtype families:** Subtype scopes within or across packs (e.g. per-pack subtype count, subtype-level completeness).
  - **Future-candidate families:** Proposed industries or verticals evaluated via [future-industry-candidate-evaluation-framework.md](../operations/future-industry-candidate-evaluation-framework.md) and scorecard outputs; included in the comparison view where scorecard data exists.

---

## 3. Comparison dimensions

| Dimension | What is compared | Data source / notes |
|-----------|------------------|---------------------|
| **Completeness** | Pack/subtype band (release-grade, strong, minimal, below minimal) and dimension scores. | Industry_Pack_Completeness_Report_Service — pack_results, summary; per-pack and per-subtype scope. |
| **Coverage** | Section/page overlay coverage; gap counts by priority. | Industry_Coverage_Gap_Analyzer; optional Industry_Coverage_Gap_Prioritization_Service. Overlay depth = count or density of overlays per family. |
| **Caution burden** | Compliance/caution rules and overlay compliance_cautions density. | Goal/secondary-goal caution registries; overlay catalog or completeness dimensions. Higher burden = more review and maintenance. |
| **Bundle** | Starter bundle presence and depth (subtype-goal overlays, secondary-goal overlays). | Industry_Starter_Bundle_Registry; completeness report bundle dimension. |
| **Preset** | Style preset attachment and token usage. | Industry_Style_Preset_Registry; pack token_preset_ref. |
| **Maturity** | Evidence-based maturity level (production-ready, stable, experimental, draft, gap) for capability areas that apply to the family. | [industry-subsystem-maturity-matrix.md](../operations/industry-subsystem-maturity-matrix.md); maturity is typically at capability-area level; family-level maturity may be inferred from completeness + release evidence. |

Dimensions are **read-only inputs** from existing reports and registries. No derived field should drive automatic prioritization or roadmap mutation.

---

## 4. Grouping and sorting expectations

- **Grouping:** Families may be grouped by (a) launch vs candidate, (b) pack_key, (c) subtype_key within pack, (d) maturity band or completeness band. Implementation may choose a primary grouping (e.g. by pack) with secondary grouping (e.g. by subtype) for drill-down.
- **Sorting:** Default sort should be defined and bounded (e.g. by pack_key alphabetical, or by completeness band then pack_key). Optional: sort by one dimension (e.g. gap count ascending, completeness total descending). No unbounded or user-driven sort that could obscure the set.
- **Bounded set:** Comparison view shows a finite set of families (all active packs + optionally active subtypes + optionally candidates with scorecard data). Cap at a reasonable limit (e.g. 100 rows) if needed; document the cap.

---

## 5. Future-candidate comparison hooks

- **Where candidates appear:** When candidate scorecard or evaluation output exists (e.g. from Industry_Candidate_Template_Overlap_Analyzer, future-industry-scorecard executor), the comparison view may include a **candidate** segment with the same dimensions where applicable (e.g. template overlap, CTA complexity, compliance burden from [future-industry-candidate-evaluation-framework.md](../operations/future-industry-candidate-evaluation-framework.md)).
- **Candidate dimensions:** Overlap score, strongest_reusable_families, weak_coverage_families, and evaluation dimensions (content-model fit, template overlap, LPagery posture, CTA complexity, documentation burden, styling needs, compliance/caution burden, starter bundle viability, subtype complexity, long-term maintenance cost). Candidates need not have completeness/coverage in the same shape as launch families; map scorecard fields to the comparison view where meaningful.
- **No auto-prioritization:** Including candidates in the view does not change roadmap state or backlog order; it informs human decisions.

---

## 6. Readability and boundedness

- **Readable:** Labels and values must be interpretable (e.g. band names, counts, dimension names). Avoid raw internal keys without a short label where it helps.
- **Bounded:** Fixed set of dimensions; fixed grouping/sorting options; no unbounded lists in the contract (implementation may paginate or cap).
- **Explainable:** Source of each compared value (which report or registry) should be documentable so maintainers can trace back.

---

## 7. Relation to existing reports and dashboard

- **Completeness:** [industry-pack-completeness-scoring-contract.md](industry-pack-completeness-scoring-contract.md) and Industry_Pack_Completeness_Report_Service remain authoritative for completeness bands and dimension scores.
- **Coverage gaps:** [industry-coverage-gap-prioritization-contract.md](industry-coverage-gap-prioritization-contract.md) and gap analyzer/prioritization services remain authoritative for gap counts and priority.
- **Maturity:** [industry-subsystem-maturity-matrix.md](../operations/industry-subsystem-maturity-matrix.md) remains authoritative for capability-area maturity; family-level view infers from pack/subtype evidence.
- **Author dashboard:** Dashboard may link to the comparison screen or surface a short “comparison” summary (e.g. pack count, maturity summary); full comparison lives on a dedicated internal screen per implementation (Prompt 558).

---

## 8. References

- [industry-subsystem-maturity-matrix.md](../operations/industry-subsystem-maturity-matrix.md) — Capability areas and maturity levels.
- [industry-subsystem-roadmap-contract.md](industry-subsystem-roadmap-contract.md) — Extension seams and future industry evaluation.
- [industry-pack-completeness-scoring-contract.md](industry-pack-completeness-scoring-contract.md) — Completeness dimensions and bands.
- [industry-coverage-gap-prioritization-contract.md](industry-coverage-gap-prioritization-contract.md) — Gap prioritization.
- [future-industry-candidate-evaluation-framework.md](../operations/future-industry-candidate-evaluation-framework.md) — Candidate evaluation dimensions and scorecard.
- [industry-author-dashboard-contract.md](industry-author-dashboard-contract.md) — Dashboard widgets and links.
