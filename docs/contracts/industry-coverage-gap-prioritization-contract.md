# Industry Coverage Gap Prioritization Contract (Prompt 523)

**Spec:** industry-coverage-gap-analysis-guide.md; industry-pack-completeness-scoring-contract.md; roadmap and maintenance docs.  
**Status:** Contract. Defines the prioritization model for coverage gaps so missing overlays, metadata, bundles, cautions, presets, and docs can be ranked by likely impact. No implementation in this prompt; no auto-generation of missing assets; human roadmap judgment remains final.

---

## 1. Purpose

- **Rank gaps:** Turn a flat list of coverage gaps into a prioritized order (urgent, important, optional) so maintainers know which gaps matter most.
- **Explainable:** Scoring dimensions and tiers are bounded and documented; no hidden auto-prioritization.
- **Advisory:** Prioritization supports backlog review and planning; it does not replace human roadmap decisions.

---

## 2. Scoring dimensions for gap priority

| Dimension | What is measured | Weight / note |
|-----------|------------------|---------------|
| **User impact** | Whether the gap affects onboarding, recommendation quality, or visible UX (e.g. no starter bundle = high). | Core for "urgent" tier. |
| **Recommendation impact** | Whether the gap affects section/page recommendation or Build Plan scoring (e.g. missing overlays = medium). | Drives "important" tier. |
| **Planning impact** | Whether the gap affects Build Plan shape or page-family emphasis (e.g. missing CTA/SEO ref = low–medium). | Optional expansion. |
| **Release risk** | Whether fixing the gap is commonly required for release (e.g. no starter bundle = release blocker for that scope). | Flags "likely release blocker". |
| **Breadth** | Number of industries or subtypes affected (single scope vs many). | Higher breadth can raise priority. |
| **Artifact class** | Gap type (starter_bundle, section_helper_overlays, page_onepager_overlays, style_preset, seo_guidance, compliance_rules, question_pack). | Class-specific weighting (e.g. starter_bundle = high by default). |

Dimensions are combined into a **priority score** (e.g. 0–100 or tier-only). Exact formula is implementation-defined; contract requires that the model is documented and that urgent/important/optional tiers are defined.

---

## 3. Tiers: urgent, important, optional

- **Urgent:** High user or release impact; affects core flows (e.g. no starter bundle for an active industry). Should be addressed before release or called out in release waiver.
- **Important:** Medium impact on recommendation quality or UX (e.g. no section overlays, unresolved preset ref). Backlog priority; not necessarily release-blocking.
- **Optional:** Low impact (e.g. no question pack, no compliance rules). Expansion or polish; deferrable.

Tier boundaries may be defined by thresholds on the priority score or by artifact-class rules (e.g. GAP_STARTER_BUNDLE → urgent; GAP_QUESTION_PACK → optional).

---

## 4. Industry, subtype, goal, and shared-fragment scope

- **Industry scope:** Gap applies to a single industry_key. Breadth = 1 unless same gap class appears for many industries.
- **Subtype scope:** Gap applies to industry|subtype_key. Same dimension scoring; subtype gaps may be weighted by parent-industry importance.
- **Goal scope:** If gap is goal-related (e.g. goal overlay or goal caution missing), score similarly with goal impact dimension if in scope.
- **Shared-fragment:** Gaps in shared-fragment coverage (if applicable) follow the same tier logic; document in implementation.

---

## 5. Artifact-class-specific weighting

| Artifact class (from gap analyzer) | Default tier | Note |
|-----------------------------------|--------------|------|
| starter_bundle | Urgent | No default bundle for scope = high impact. |
| section_helper_overlays | Important | Affects section recommendation and preview. |
| page_onepager_overlays | Important | Affects page one-pager and planning. |
| style_preset | Low / Important | Unresolved ref = important; missing ref = low. |
| seo_guidance | Low / Important | Same as style_preset. |
| compliance_rules | Optional | Advisory. |
| question_pack | Optional | Onboarding polish. |

Weighting can be overridden by release risk or breadth in the implementation.

---

## 6. Use in planning

- **Backlog review:** Run prioritization over current gaps; sort by priority score or tier. Use for sprint or roadmap ordering.
- **Release planning:** Surface "likely release blockers" (urgent tier or release_risk flag) separately; human review decides whether to fix or waive.
- **Documentation:** Prioritization result (gap ref, score, tier, rationale) should be explainable so maintainers can adjust backlog by judgment.

---

## 7. Cross-references

- [industry-coverage-gap-analysis-guide.md](../operations/industry-coverage-gap-analysis-guide.md) — Gap analyzer output (scope, missing_artifact_class, priority, explanation).
- [industry-pack-completeness-scoring-contract.md](industry-pack-completeness-scoring-contract.md) — Completeness dimensions; gaps may align with missing completeness dimensions.
- [industry-pack-maintenance-checklist.md](../operations/industry-pack-maintenance-checklist.md) — Maintenance baseline; prioritization feeds backlog.
- [industry-phase-two-backlog-map.md](../operations/industry-phase-two-backlog-map.md) — Backlog and roadmap context.
