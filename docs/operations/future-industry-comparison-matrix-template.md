# Future Industry Candidate Comparison Matrix Template (Prompt 473)

**Spec**: [future-industry-candidate-evaluation-framework.md](future-industry-candidate-evaluation-framework.md); [future-industry-scorecard-template.md](future-industry-scorecard-template.md); [future-industry-intake-dossier-workflow.md](future-industry-intake-dossier-workflow.md).

**Purpose**: Internal workflow to compare multiple future-industry candidates side by side across reuse fit, caution burden, subtype complexity, bundle viability, and long-term maintenance cost. Use scorecard outputs from Future_Industry_Scorecard_Executor; human roadmap decisions required. Internal only; no public prioritization tool; no auto-selection.

---

## 1. Scope

- **Input**: Two or more scorecard results (each from a completed intake dossier run through Future_Industry_Scorecard_Executor).
- **Output**: A comparison matrix (structured data and/or readable report) showing per-candidate: strongest/weakest dimensions, reuse vs new-build burden, subtype/caution complexity, and go/hold/defer categorization.
- **Consumer**: Roadmap planning; prioritization of which candidate(s) to schedule next. Human review required.

---

## 2. Matrix structure (data shape)

The comparison matrix can be represented as:

| Element | Description |
|---------|-------------|
| **Candidates** | List of candidate labels (and optional proposed_industry_key) in comparison order. |
| **Dimension comparison** | Per dimension: each candidate’s score (1–5); optional highlight of strongest/weakest per dimension. |
| **Per-candidate summary** | Aggregate sum, recommendation (go/review/no-go), count of major_risks; optional “hold” or “defer” tag for internal use. |
| **Reuse vs new-build** | Derived from template_overlap and content_model_fit: high scores = likely reuse; low = likely new-build burden. |
| **Subtype / caution highlight** | Subtype_complexity and compliance_caution_burden scores; flag when both are low (higher burden). |
| **Suggested order** | Optional: candidates ordered by aggregate or by “go first, then review, then no-go” for roadmap discussion. |

---

## 3. Usage

1. **Run scorecard executor** for each candidate dossier (Prompt 472).
2. **Pass scorecard results** into Future_Industry_Comparison_Matrix_Service::build_matrix( $scorecard_results ).
3. **Use the returned structure** to fill this template or generate an internal report (e.g. markdown table or JSON).
4. **Roadmap meeting**: Use matrix to prioritize next industry; no automatic selection—human decision.

---

## 4. Example (readable format)

| Candidate | Aggregate | Recommendation | Strongest dimensions | Weakest dimensions | Reuse vs new-build | Subtype/caution |
|-----------|-----------|----------------|---------------------|--------------------|--------------------|------------------|
| Industry A | 42 | go | template_overlap, starter_bundle_viability | documentation_burden | High reuse | Low burden |
| Industry B | 35 | review | content_model_fit | compliance_caution_burden, subtype_complexity | Mixed | Caution/subtype burden |
| Industry C | 22 | no-go | — | template_overlap, long_term_maintenance_cost | New-build heavy | High burden |

---

## 5. Cross-references

- [future-industry-candidate-evaluation-framework.md](future-industry-candidate-evaluation-framework.md)
- [future-industry-scorecard-template.md](future-industry-scorecard-template.md)
- [future-industry-scorecard-executor-contract.md](../contracts/future-industry-scorecard-executor-contract.md)
- [industry-subsystem-roadmap-contract.md](../contracts/industry-subsystem-roadmap-contract.md)
- [industry-next-phase-prompt-map.md](industry-next-phase-prompt-map.md)
