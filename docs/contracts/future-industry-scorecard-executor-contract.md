# Future Industry Scorecard Executor Contract (Prompt 472)

**Spec**: [future-industry-candidate-evaluation-framework.md](../operations/future-industry-candidate-evaluation-framework.md); [future-industry-scorecard-template.md](../operations/future-industry-scorecard-template.md); [future-industry-intake-dossier-workflow.md](../operations/future-industry-intake-dossier-workflow.md).

**Purpose**: Define the contract for the internal scorecard executor that maps a completed future-industry intake dossier into a repeatable scorecard evaluation report (dimension scores, aggregate, major risks, recommendation). Advisory only; no auto-approval; no mutation of runtime pack registries.

---

## 1. Scope

- **Internal use only**: Executor output is for planning and backlog; not public.
- **Input**: A completed intake dossier in structured form (array or object matching dossier sections).
- **Output**: A structured scorecard result suitable for human review and for the comparison matrix (Prompt 473).
- **No side effects**: Executor does not create or alter industries, packs, or registries.

---

## 2. Input: dossier shape

The executor accepts a **dossier** array with the following optional keys. All keys are optional; missing sections imply incomplete evidence and may yield lower confidence or default scores.

| Key | Type | Description |
|-----|------|-------------|
| `candidate_identity` | array | `proposed_industry_key`, `candidate_label`, `evaluator`, `dossier_date`. |
| `content_model_fit` | array or string | Supported page families, section keys, fit notes. |
| `template_overlap` | array | `page_families`, `section_keys`, optional `overlap_score` (0–1), `strongest_reusable_families`, `weak_coverage_families`, `notes`. |
| `cta_posture` | array or string | Primary CTAs, pattern keys, complexity notes. |
| `lpagery_needs` | array or string | Local-page needs, rule ref, posture notes. |
| `proof_expectations` | array or string | Proof model hint, trust notes. |
| `page_hierarchy_bundle` | array or string | Default page set, recommended sections, bundle viability notes. |
| `styling_fit` | array or string | Preset approach, token alignment, styling notes. |
| `compliance_caution` | array or string | Regulatory domain, compliance_cautions scope, notes. |
| `subtype_complexity` | array or string | Subtype count/clarity, list of subtypes, notes. |
| `documentation_maintenance` | array or string | Doc load estimate, churn, maintenance notes. |
| `dimension_scores` | array | Optional pre-filled scores 1–5 per dimension (see §3). If provided, executor uses them and may still compute risks and recommendation. |

Dimension names for `dimension_scores` (when provided): `content_model_fit`, `template_overlap`, `lpagery_posture`, `cta_complexity`, `documentation_burden`, `styling_needs`, `compliance_caution_burden`, `starter_bundle_viability`, `subtype_complexity`, `long_term_maintenance_cost`.

---

## 3. Output: scorecard result shape

The executor returns a **scorecard result** array with the following structure.

| Key | Type | Description |
|-----|------|-------------|
| `candidate_label` | string | From dossier candidate_identity or "Unknown". |
| `proposed_industry_key` | string | From dossier or "". |
| `evaluated_at` | string | ISO 8601 timestamp. |
| `dimension_scores` | array | Keys = dimension names (see framework); values = int 1–5. |
| `aggregate_sum` | int | Sum of dimension scores (max 50). |
| `major_risks` | list&lt;string&gt; | Human-readable risk items (e.g. "Template overlap low", "New core seams implied"). |
| `recommendation` | string | One of `go`, `review`, `no-go`. |
| `summary_text` | string | Short readable summary for internal use. |

Dimension keys in `dimension_scores` must match the evaluation framework: content_model_fit, template_overlap, lpagery_posture, cta_complexity, documentation_burden, styling_needs, compliance_caution_burden, starter_bundle_viability, subtype_complexity, long_term_maintenance_cost.

---

## 4. Recommendation rules

- **No-go**: Any dimension score 1 on template_overlap or long_term_maintenance_cost (if team policy requires); or major_risks contain "new core seams" / "no-go condition"; or aggregate below a configurable minimum (e.g. 25).
- **Go**: Aggregate above threshold (e.g. 40), no dimension below minimum (e.g. 2), no no-go risks.
- **Review**: Otherwise (moderate scores or flagged conditions).

Executor may accept optional threshold overrides; defaults must align with framework guidance.

---

## 5. Integration

- **Dossier source**: Filled from [future-industry-intake-dossier-template.md](../operations/future-industry-intake-dossier-template.md) and workflow; may be built from markdown or a form in a later implementation.
- **Downstream**: Result is consumed by Future_Industry_Comparison_Matrix_Service and [future-industry-comparison-matrix-template.md](../operations/future-industry-comparison-matrix-template.md) (Prompt 473) for multi-candidate comparison.
- **Human review**: Executor output is advisory; human review remains required before any new industry is approved.

---

## 6. Cross-references

- [future-industry-candidate-evaluation-framework.md](../operations/future-industry-candidate-evaluation-framework.md)
- [future-industry-intake-dossier-workflow.md](../operations/future-industry-intake-dossier-workflow.md)
- [future-industry-scorecard-template.md](../operations/future-industry-scorecard-template.md)
- [industry-subsystem-roadmap-contract.md](industry-subsystem-roadmap-contract.md)
