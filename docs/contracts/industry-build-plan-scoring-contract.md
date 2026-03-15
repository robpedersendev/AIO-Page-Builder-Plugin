# Industry Build Plan Scoring Contract

**Spec**: Build Plan sections of aio-page-builder-master-spec.md; industry-planner-input-contract.md; industry-page-template-recommendation-contract.md; industry-section-recommendation-contract.md.

**Status**: Additive scoring layer applied to normalized output before or during Build Plan generation. Industry logic is additive and explainable; review and approval gating unchanged.

---

## 1. Purpose

- **Incorporate industry recommendation scores** into Build Plan generation so proposed page families, section combinations, CTA models, and hierarchy patterns are influenced by the active industry context.
- **Prefer** recommended page templates and sections where appropriate; apply CTA pattern preferences and hierarchy/LPagery fit signals.
- **Produce explanation metadata** (industry source refs, recommendation reasons, fit scores, warning flags) for why plan items were favored or down-ranked.
- **Fail safely** to generic Build Plan behavior when industry context is weak or missing. No automatic execution; planner/executor separation preserved.

---

## 2. Integration point

- **Industry_Build_Plan_Scoring_Service**: Enriches normalized output (Build_Plan_Draft_Schema shape) with industry metadata before or during plan generation.
- **When**: Enrichment runs before Build_Plan_Generator builds steps, or the generator calls the scoring service at the start of generate(). Caller may pass industry context in `context`; or the service resolves profile/pack from container when available.
- **Input**: Normalized output (validated); optional industry context (profile, primary_pack, page template list for resolver, section list for section resolver).
- **Output**: Same normalized output structure with **additive** keys on each relevant record (new_pages_to_create, existing_page_changes): industry_source_refs, recommendation_reasons, industry_fit_score, industry_warning_flags. Optionally reorder new_pages by fit (recommended first). Plan-level industry warnings may be appended to normalized_output.warnings.

---

## 3. Additive artifact fields

Build Plan **items** (per Build_Plan_Item_Schema) gain optional payload or item-level keys for industry metadata. These are **additive**; existing required keys unchanged.

| Field | Type | Description |
|-------|------|-------------|
| **industry_source_refs** | array | Industry keys that contributed to this item's score. |
| **recommendation_reasons** | array | Short reason codes (e.g. pack_family_fit, template_affinity_primary, hierarchy_fit). |
| **industry_fit_score** | int | Numeric fit score from industry resolver. |
| **industry_warning_flags** | array | Warnings (e.g. cta_mismatch, discouraged_for_industry). |

- Stored in item **payload** for new_page and existing_page_change items so UI and explanations can surface them. Other item types may omit industry metadata.
- Plan-level: optional **industry_warnings** array in plan definition (e.g. "Required page family X not present") when Industry_Page_Family_Rule_Engine is used (Prompt 346). LPagery planning warnings (e.g. required_tokens_for_central_lpagery, weak_fit_local_page) may be merged from Industry_LPagery_Planning_Advisor when present.

---

## 4. Behavior

- **Page template recommendations**: For each new_page and existing_page_change record with template_key (or target_template_key), resolve industry fit via Industry_Page_Template_Recommendation_Resolver; attach industry_source_refs, explanation_reasons, score, warning_flags to the record. Optionally reorder new_pages by fit (recommended first, then weak, then neutral, then discouraged).
- **Section recommendations**: Where section_guidance or section keys are present, Industry_Section_Recommendation_Resolver can score section combinations; result reasons and flags may be merged into item metadata or plan-level warnings.
- **CTA and LPagery**: CTA pattern preferences and LPagery posture from industry pack or planner input inform scoring; hierarchy_fit and lpagery_fit from page resolver are included in recommendation_reasons or explanation. **Industry_LPagery_Planning_Advisor** (industry-lpagery-planning-contract.md) may be consulted to attach plan-level LPagery guidance, required-token warnings, and weak-page cautions without mutating LPagery binding.
- **Missing profile**: If industry profile is missing, minimal, or invalid, return normalized output unchanged; no throw. Downstream generation proceeds with no industry metadata.

---

## 5. Security and constraints

- No automatic execution; scoring must not create unsafe direct mutations.
- Invalid or incomplete industry profile data must be handled safely (fallback to generic behavior).
- Industry scoring is internal/config-driven; no client-supplied scoring overrides without explicit capability.

---

## 6. Files

- **Service**: plugin/src/Domain/Industry/AI/Industry_Build_Plan_Scoring_Service.php
- **Contract**: docs/contracts/industry-build-plan-scoring-contract.md
- **Build Plan generator**: Optional integration (inject scoring service; call enrich_output at start of generate()).
- **Build Plan item generator**: Pass through industry_* keys from enriched records into item payload when building new_page and existing_page_change items.
