# Future Industry Scorecard Template (Prompt 420)

**Purpose:** One scorecard per candidate industry. Use with [future-industry-candidate-evaluation-framework.md](future-industry-candidate-evaluation-framework.md). Evidence is gathered via [future-industry-intake-dossier-workflow.md](future-industry-intake-dossier-workflow.md) and [future-industry-intake-dossier-template.md](future-industry-intake-dossier-template.md); this scorecard can be filled manually or by the Future_Industry_Scorecard_Executor (Prompt 472). Executor contract: [future-industry-scorecard-executor-contract.md](../contracts/future-industry-scorecard-executor-contract.md).

---

## Candidate summary

| Field | Value |
|-------|--------|
| **Candidate name / industry_key (proposed)** | |
| **Evaluator** | |
| **Date** | |
| **Decision** | ☐ Go  ☐ Review  ☐ No-go |

---

## Dimension scores (1–5; 5 = best fit / lowest burden)

| Dimension | Score (1–5) | Notes |
|-----------|-------------|--------|
| **Content-model fit** | | Maps to supported_page_families and section keys without stretching the model. |
| **Template overlap** | | High = reuses most section/page templates; Low = many net-new or one-off overlays. Optional: run Industry_Candidate_Template_Overlap_Analyzer::analyze() for overlap_score and weak_coverage_families. |
| **LPagery posture** | | High = fits existing LPagery rule patterns; Low = conflicting or one-off local-page rules. |
| **CTA complexity** | | High = few CTA patterns suffice; Low = many custom patterns or complex branching. |
| **Documentation burden** | | High = minimal new docs; Low = large authoring/overlay/support doc set. |
| **Styling needs** | | High = reuse or one simple preset; Low = many tokens or divergent presets. |
| **Compliance / caution burden** | | High = few compliance_cautions; Low = heavy regulatory/liability content and review. |
| **Starter bundle viability** | | High = clear default site shape; Low = ambiguous or many valid shapes. |
| **Subtype complexity** | | High = no or few clear subtypes; Low = many or fuzzy subtypes. |
| **Long-term maintenance cost** | | High = low churn, small regression surface; Low = high churn or broad impact. |

**Aggregate (sum):** _____ / 50. **Weighted (if used):** _____.

---

## Flags and conditions

- [ ] No dimension scored 1 on template overlap or long-term maintenance cost (if required by team policy).
- [ ] No new core seams or registries required (per industry-subsystem-roadmap-contract).
- [ ] If Review: conditions for Go (e.g. T1 overlays only, defer subtypes): _________________.

---

## Notes for prompt generation (if Go/Review)

- **Required pieces (from authoring guide):** Pack definition, CTA patterns, overlays (T1/T2), style preset, SEO/LPagery refs, question pack (Y/N), subtypes (Y/N).
- **Risks or waivers:** 
- **Scope limits (if Review):** 

---

*Attach this scorecard to the candidate in backlog and reference in planning. For multi-candidate comparison, use [future-industry-comparison-matrix-template.md](future-industry-comparison-matrix-template.md) and Future_Industry_Comparison_Matrix_Service (Prompt 473).*
