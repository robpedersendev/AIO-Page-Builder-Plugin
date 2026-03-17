# Future Industry Candidate Intake Dossier Workflow (Prompt 471)

**Spec**: [future-industry-candidate-evaluation-framework.md](future-industry-candidate-evaluation-framework.md); [future-industry-scorecard-template.md](future-industry-scorecard-template.md); [industry-subsystem-roadmap-contract.md](../contracts/industry-subsystem-roadmap-contract.md).

**Purpose**: Define the intake workflow for proposed new industries so maintainers gather structured evidence (business model, CTA posture, local-page needs, proof expectations, subtype likelihood, caution burden, content-model fit) before any new industry work begins. Internal-only; no public form; no approval from dossier creation alone.

---

## 1. Scope and principles

- **Internal only**: Intake is for planning and backlog; not a public-facing intake form.
- **Evidence-based**: Every proposed vertical starts from the same [intake dossier template](future-industry-intake-dossier-template.md); ad hoc notes are not sufficient for scorecard evaluation.
- **No implicit approval**: Completing a dossier does not approve or add an industry; human review and scorecard evaluation follow.
- **Reusable for prompt generation**: Dossier structure supports later scorecard execution (Prompt 472) and comparison (Prompt 473).

---

## 2. Required dossier sections

Each candidate must have a dossier with the following sections. Evidence expectations are in §3.

| Section | Purpose | Feeds scorecard dimension(s) |
|---------|---------|------------------------------|
| **Candidate identity** | Proposed industry_key, label, evaluator, date. | Candidate summary. |
| **Business model and content-model fit** | How the industry maps to page/section concepts; supported_page_families and section key fit. | Content-model fit. |
| **Template overlap** | Which existing section/page templates apply; page_families and section_keys; optional overlap analyzer run. | Template overlap. |
| **CTA posture** | Primary CTAs; fit with existing CTA pattern registry; preferred/required/discouraged patterns. | CTA complexity. |
| **Local-page (LPagery) needs** | Service areas, locations, NAP; fit with existing LPagery rule patterns or one-off needs. | LPagery posture. |
| **Proof and trust expectations** | Testimonials, credentials, certifications; proof_model_hint for overlap analyzer. | Template overlap / content fit. |
| **Page hierarchy and bundle viability** | Default site shape; recommended pages/sections; whether a coherent starter bundle is definable. | Starter bundle viability. |
| **Styling and token fit** | One preset vs shared; custom tokens vs reuse. | Styling needs. |
| **Compliance and caution burden** | Regulatory or liability notes; compliance_cautions scope. | Compliance / caution burden. |
| **Subtype likelihood and complexity** | None vs few well-defined subtypes; fuzzy or many subtypes. | Subtype complexity. |
| **Documentation and maintenance** | Expected authoring/overlay/support doc load; churn and regression surface. | Documentation burden; long-term maintenance cost. |

---

## 3. Evidence expectations per section

| Section | Evidence expected | Example / note |
|---------|-------------------|-----------------|
| Candidate identity | Proposed industry_key (slug-safe), short label, evaluator name, dossier date. | Required for all. |
| Business model and content-model fit | List of supported_page_families and section_keys that fit the industry; note any stretch or missing concepts. | Bullet list or table; reference industry-pack-schema. |
| Template overlap | List of page_families and section_keys; optionally run Industry_Candidate_Template_Overlap_Analyzer and attach overlap_score, strongest_reusable_families, weak_coverage_families. | High overlap = lower burden; document weak coverage. |
| CTA posture | Primary CTA types; mapping to existing CTA pattern keys or “new patterns needed”; preferred/required/discouraged. | Few patterns = lower complexity. |
| LPagery needs | Description of local-page needs (service area, NAP, locations); existing LPagery rule ref or “one-off / conflicting”. | Fits existing rules = high posture. |
| Proof and trust | Proof_model_hint; types of proof (testimonials, credentials); impact on section/content. | Feeds overlap analyzer and content fit. |
| Page hierarchy and bundle | Default page set; recommended sections; “clear default shape” vs “ambiguous or many valid shapes”. | Clear shape = high bundle viability. |
| Styling and token fit | One preset vs shared; custom tokens; divergence from existing presets. | Reuse = high; many tokens = low. |
| Compliance and caution | Regulatory domain (e.g. none, light, legal/medical/financial); expected compliance_cautions volume. | Heavy = low score (high burden). |
| Subtype likelihood | None / few well-defined / many or fuzzy; list subtypes if any. | Few clear = high; many fuzzy = low. |
| Documentation and maintenance | New authoring/overlay/support doc estimate; expected churn; regression surface. | Minimal = high; large = low. |

---

## 4. Template overlap and styling-fit prompts

When filling the dossier, answer explicitly:

- **Template overlap**: “Which existing section keys and page_families does this industry reuse? Which are weak or missing?” Use Industry_Candidate_Template_Overlap_Analyzer when candidate page_families and section_keys are known; attach results to the dossier.
- **Styling-fit**: “One preset or shared? Which tokens or presets align with existing packs?” Ensures scorecard styling dimension is evidence-based.

---

## 5. LPagery posture and caution

- **LPagery**: Document whether local-page rules fit existing LPagery rule patterns or introduce one-off/conflicting rules.
- **Caution**: Document compliance/liability scope so the scorecard “compliance / caution burden” dimension is informed.

---

## 6. When a dossier is “complete enough” for scorecard evaluation

A dossier is **complete enough** when:

1. **Candidate identity** is filled (industry_key, label, evaluator, date).
2. **At least** business model/content-model fit, template overlap (or overlap analyzer run), CTA posture, LPagery needs, starter bundle viability, compliance/caution, subtype complexity, and maintenance are addressed with non-empty evidence.
3. Evidence is concrete enough that an evaluator (or the scorecard executor in Prompt 472) can assign dimension scores (1–5) and a go/review/no-go recommendation without guessing.

Missing or vague evidence in any dimension should be flagged in the scorecard step; the dossier workflow does not block submission but incomplete dossiers yield lower confidence in the scorecard.

---

## 7. Workflow steps

1. **Create dossier**: Copy [future-industry-intake-dossier-template.md](future-industry-intake-dossier-template.md) and name it for the candidate (e.g. `future-industry-dossier-{proposed-industry_key}.md`).
2. **Gather evidence**: Fill each section per §2–5; attach overlap analyzer output if run.
3. **Check completeness**: Verify §6 “complete enough” criteria.
4. **Hand off to scorecard**: Pass the completed dossier to the scorecard executor (Prompt 472) or to a human evaluator using the scorecard template. No auto-approval.
5. **Backlog and planning**: Attach scorecard result to backlog; use in next-phase prompt map and roadmap decisions.

---

## 8. Cross-references

- [future-industry-intake-dossier-template.md](future-industry-intake-dossier-template.md) — Reusable template for each candidate.
- [future-industry-candidate-evaluation-framework.md](future-industry-candidate-evaluation-framework.md) — Evaluation criteria and dimensions.
- [future-industry-scorecard-template.md](future-industry-scorecard-template.md) — Scorecard filled from dossier evidence; executor (472) maps dossier → scorecard.
- [industry-subsystem-roadmap-contract.md](../contracts/industry-subsystem-roadmap-contract.md) — Approved seams; no new industry without evaluation.
- [industry-next-phase-prompt-map.md](industry-next-phase-prompt-map.md) — Intake (471) → scorecard executor (472) → comparison matrix (473).

---

*Intake dossier workflow keeps future industry expansion disciplined and evidence-based.*
