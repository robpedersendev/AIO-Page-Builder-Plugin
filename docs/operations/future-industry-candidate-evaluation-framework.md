# Future Industry Candidate Evaluation Framework (Prompt 420)

**Spec:** industry-subsystem-roadmap-contract.md; industry-pack-authoring-guide.md; industry-pack-maintenance-checklist.md.

**Purpose:** Internal evaluation framework for deciding which new industries to add next. Ensures expansion is rational, fits the subsystem architecture, and keeps maintenance cost under control.

---

## 1. Scope and principles

- **Internal only:** This framework is for planning and backlog; no public roadmap product.
- **Additive and overlay-based:** Candidates must fit the one-plugin, registry-first, overlay model (industry-subsystem-roadmap-contract §1–2).
- **Maintenance-aware:** Long-term maintenance cost is part of the decision model; avoid industries that destabilize the architecture or overload documentation.

---

## 2. Evaluation criteria (dimensions)

| Dimension | Description | What to assess |
|-----------|-------------|----------------|
| **Content-model fit** | Alignment with existing section/page template library and industry affinity model. | Does the industry map cleanly to supported_page_families and existing section keys? Or does it require many new section/page concepts that don't exist? |
| **Template overlap** | Reuse of existing section and page templates vs net-new content. | High overlap = lower authoring and QA burden. Low overlap = more overlays, more edge cases, higher regression risk. Use **Industry_Candidate_Template_Overlap_Analyzer** (container key `industry_candidate_template_overlap_analyzer`) to score candidate page_families and section_keys against existing packs; see overlap_score, strongest_reusable_families, weak_coverage_families, and notes for CTA/LPagery/proof. |
| **LPagery posture** | Fit with local-page (LPagery) rules and location-based guidance. | Does the industry have clear local-page needs (service areas, locations, NAP) already covered by LPagery rule patterns? Or does it introduce conflicting or one-off rules? |
| **CTA complexity** | Fit with existing CTA pattern registry and pack preferred/required/discouraged. | Can CTA needs be expressed with existing or few new CTA patterns? Or does the industry require many custom patterns and complex branching? |
| **Documentation burden** | Authoring, overlay, and support documentation load. | How much new content is needed for industry-pack-authoring-guide, overlay catalogs, troubleshooting, and operator guides? |
| **Styling needs** | Fit with industry style preset and token system. | One preset per industry vs shared presets; custom tokens vs reuse. High divergence increases preset maintenance and testing. |
| **Compliance / caution burden** | Regulatory or liability notes (compliance_cautions in overlays). | Industries with heavy compliance (e.g. legal, medical, financial) need more caution content, review, and potential waiver handling. |
| **Starter bundle viability** | Whether a coherent starter bundle (recommended pages/sections) can be defined. | Clear “default site shape” for the industry vs ambiguous or many valid shapes. |
| **Subtype complexity** | Whether the industry needs subtypes (e.g. buyer vs listing agent, residential vs commercial). | None vs few well-defined subtypes. Many or fuzzy subtypes increase resolver and overlay complexity. |
| **Long-term maintenance cost** | Ongoing updates, deprecations, and regression surface. | Expected churn in overlays, CTA patterns, and recommendation rules; impact on industry-subsystem-acceptance-report and regression guards. |

---

## 3. Scoring

- **Scale:** Per dimension, use a simple score (e.g. 1–5) where higher = better fit / lower burden. Define what 1 vs 5 means per dimension in the scorecard template.
- **Weighting:** Optional; teams may weight “template overlap” and “long-term maintenance cost” more heavily to avoid architectural drift.
- **Aggregate:** Sum or weighted sum yields a candidate score. Use for ordering backlog, not as sole go/no-go.

---

## 4. Go/no-go and review categories

| Category | Meaning | Action |
|----------|---------|--------|
| **Go** | Strong fit; low burden; aligns with approved seams. | Schedule for implementation per industry-pack-authoring-guide; use scorecard in prompt/backlog. |
| **Review** | Moderate fit or moderate burden; needs product/tech alignment. | Document trade-offs; decide in planning; may require scope reduction (e.g. T1 overlays only, no subtypes in v1). |
| **No-go** | Poor content-model fit, high compliance burden, or would require new core seams. | Do not add until architecture or scope is revisited; document reason for future reference. |

**Thresholds (internal guidance):**

- **Go:** Aggregate score above team-defined threshold; no dimension below a minimum (e.g. no “1” on template overlap or maintenance cost).
- **No-go:** Any dimension that implies new core registries, non-overlay behavior, or unbounded documentation/compliance.
- **Review:** Between go and no-go; or any dimension flagged for explicit stakeholder decision.

---

## 5. Use in workflow

1. **Intake (Prompt 471):** Before scorecard evaluation, gather evidence using the [future-industry-intake-dossier-workflow.md](future-industry-intake-dossier-workflow.md) and [future-industry-intake-dossier-template.md](future-industry-intake-dossier-template.md). Every proposed vertical starts from a consistent dossier; ad hoc notes are not sufficient.
2. **After approval:** Once a candidate is Go (or Review with scope agreed), create a scaffold per [future-industry-scaffold-pack-template.md](future-industry-scaffold-pack-template.md) and follow [future-industry-first-pack-authoring-runbook.md](future-industry-first-pack-authoring-runbook.md) (Prompt 539) for authoring through validation and release readiness.
3. **Backlog:** When considering a new industry, fill [future-industry-scorecard-template.md](future-industry-scorecard-template.md) (from dossier evidence or via Future_Industry_Scorecard_Executor) and attach to the candidate.
2. **Planning:** Use score and categories to order “New industries” in roadmap (industry-subsystem-roadmap-contract §5) and to set expectations (e.g. “T1 overlays only” for Review candidates).
5. **Prompt generation:** Scorecard and framework provide concrete inputs for future prompts (required pieces, overlays, subtypes, compliance notes).
6. **Authoring:** Once approved, follow industry-pack-authoring-guide and industry-pack-maintenance-checklist; framework does not replace them. For consistent file and placeholder skeletons, use [industry-scaffold-generator-contract.md](../contracts/industry-scaffold-generator-contract.md) and the concrete [future-industry-scaffold-pack-template.md](future-industry-scaffold-pack-template.md).

---

## 6. Cross-references

- [future-industry-intake-dossier-workflow.md](future-industry-intake-dossier-workflow.md) — Intake workflow and evidence expectations (Prompt 471).
- [future-industry-intake-dossier-template.md](future-industry-intake-dossier-template.md) — Reusable dossier template per candidate.
- [industry-subsystem-roadmap-contract.md](../contracts/industry-subsystem-roadmap-contract.md) — Extension seams, roadmap categories, “Adding a new industry”.
- [industry-pack-authoring-guide.md](industry-pack-authoring-guide.md) — Required pieces and implementation order.
- [industry-pack-maintenance-checklist.md](industry-pack-maintenance-checklist.md) — Ongoing maintenance and deprecation.
- [future-industry-scorecard-template.md](future-industry-scorecard-template.md) — Scorecard template for each candidate; can be filled from dossier via scorecard executor (Prompt 472).
- [future-industry-scorecard-executor-contract.md](../contracts/future-industry-scorecard-executor-contract.md) — Contract for Future_Industry_Scorecard_Executor (dossier → scorecard result).
- [future-industry-comparison-matrix-template.md](future-industry-comparison-matrix-template.md) — Comparison matrix workflow and template; Future_Industry_Comparison_Matrix_Service (Prompt 473) for multi-candidate side-by-side comparison.
- [future-industry-scaffold-pack-template.md](future-industry-scaffold-pack-template.md) — Concrete scaffold pack template for new industries (Prompt 516); artifact classes, placement, placeholders, docs/QA minimums.
- **Future industry readiness screen** (Prompt 566) — Internal admin screen aggregating candidate scorecard summary, scaffold readiness, promotion-readiness, and blockers; linked from Industry Author Dashboard. Use for planning when to start future-industry work.

---

*This framework keeps future industry growth additive, explainable, and aligned with the subsystem actually built.*
