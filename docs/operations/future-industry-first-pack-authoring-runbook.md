# Future Industry First-Pack Authoring Runbook (Prompt 539)

**Spec:** Future-industry evaluation docs; scaffold docs; release and authoring docs.

**Purpose:** End-to-end runbook for taking a future-industry scaffold from initial candidate approval through first real authored pack creation, validation, QA, and release readiness. Internal-only; human review required; no bypass of release gates.

---

## 1. Scope and principles

- **One run per first pack:** Use this runbook when authoring the **first** real pack for a newly approved industry (post-scaffold). Subsequent packs or iterations may follow the same sequence; maintenance uses [industry-pack-maintenance-checklist.md](industry-pack-maintenance-checklist.md).
- **Dependency-aware:** Each step assumes prerequisites are met (e.g. candidate approved, scaffold created, registries load paths known). References point to evaluation, scaffold, authoring, and release docs.
- **Release rigor intact:** Linting, health check, completeness, benchmarks, and release gate are required checkpoints. No shortcuts that bypass validation or sign-off.
- **Scaffolds remain incomplete until authored:** Scaffold assets (draft, placeholder) are excluded from release until status is active and evidence is in place.

---

## 2. Prerequisites (before starting the runbook)

| Prerequisite | Reference | Checkpoint |
|--------------|-----------|------------|
| **Candidate approved** | [future-industry-candidate-evaluation-framework.md](future-industry-candidate-evaluation-framework.md); [future-industry-scorecard-template.md](future-industry-scorecard-template.md); [future-industry-intake-dossier-workflow.md](future-industry-intake-dossier-workflow.md) | Go/Review decision; scorecard and dossier completed. |
| **Scaffold created** | [future-industry-scaffold-pack-template.md](future-industry-scaffold-pack-template.md); [industry-scaffold-generator-contract.md](../contracts/industry-scaffold-generator-contract.md) | Pack, bundle, overlay/rule placeholders, docs/QA placeholders in place; status = draft; incomplete markers present. |
| **Starter bundle scaffold** | [future-industry-starter-bundle-scaffold-template.md](future-industry-starter-bundle-scaffold-template.md) | At least one parent bundle placeholder; optional subtype/goal hook noted. |
| **Overlay/rule scaffolds** | [future-industry-overlay-scaffold-template-set.md](future-industry-overlay-scaffold-template-set.md) | Helper/page overlay and rule placeholders if pack will reference them. |

---

## 3. Authoring sequence (first-industry pack)

Execute in order. Each step has a **signoff checkpoint** (self-check or review) before proceeding.

| Step | Action | Required vs optional | Signoff checkpoint |
|------|--------|----------------------|--------------------|
| **1. Pack definition** | Replace placeholder name/summary; set supported_page_families, preferred/discouraged section keys; add CTA refs, seo_guidance_ref, token_preset_ref, lpagery_rule_ref, overlay refs only when dependencies exist. Validate with Industry_Pack_Schema. | Required | Pack passes schema validation; refs point to keys that exist or are created in next steps. |
| **2. CTA patterns** | Add or reuse CTA pattern definitions referenced by pack; register in Industry_CTA_Pattern_Registry. | Required if pack references CTAs | All pack CTA keys resolve. |
| **3. Style preset** | Add preset if pack uses token_preset_ref; register in Industry_Style_Preset_Registry. | Optional (use when industry has dedicated preset) | Preset key resolves. |
| **4. SEO guidance** | Add SEO guidance if pack uses seo_guidance_ref; register in Industry_SEO_Guidance_Registry. | Optional | Ref resolves. |
| **5. LPagery rules** | Add LPagery rule if pack uses lpagery_rule_ref; register in Industry_LPagery_Rule_Registry. | Optional | Ref resolves. |
| **6. Starter bundle(s)** | Author bundle content per [future-industry-starter-bundle-scaffold-template.md](future-industry-starter-bundle-scaffold-template.md) §6; fill recommended_* refs; set status = active when complete. Set pack starter_bundle_ref. | Required (at least one) | Bundle(s) pass schema; refs resolve; pack starter_bundle_ref set. |
| **7. Section helper overlays** | Author overlay content for section keys; set status active; add to pack helper_overlay_refs only when ready. | Optional (per pack design) | Overlay refs resolve; no placeholder content in refs. |
| **8. Page one-pager overlays** | Author overlay content for page template keys; set status active; add to pack one_pager_overlay_refs only when ready. | Optional (per pack design) | Overlay refs resolve. |
| **9. Compliance/caution rules** | Add industry compliance rules if pack references compliance_rule_refs. | Optional | Refs resolve. |
| **10. Subtypes** | If the industry is planned to have subtypes from day one, create subtype definitions per [future-subtype-scaffold-pack-template.md](future-subtype-scaffold-pack-template.md) and [future-subtype-first-pack-authoring-runbook.md](future-subtype-first-pack-authoring-runbook.md). Otherwise defer. | Optional | Subtype definitions valid; parent_industry_key = this pack. |
| **11. Conversion goals** | No schema change in pack for goals; goal overlays apply at Build Plan/recommendation layer. If pack will support conversion goals, ensure goal overlay authoring is planned (see conversion-goal contracts). | Optional | Document intent if goal overlays will be added later. |
| **12. Docs and catalog** | Replace scaffold README/QA placeholders with real authoring notes; update industry-pack-catalog (or equivalent); document any operator/support guidance. | Required (minimal: catalog or doc entry) | Scaffold "incomplete" marker removed; release placeholder replaced with checklist ref. |
| **13. Set status active** | Change pack (and bundle, overlays) status from draft to active only after steps 1–12 are complete and refs resolve. | Required | No draft pack in release set; health check passes. |

---

## 4. When subtype, goals, and presets are required vs optional

| Layer | Required for first pack? | When to include |
|-------|---------------------------|------------------|
| **Subtype** | No | Include only if the approved candidate and scorecard plan subtypes from day one; otherwise add in a second pass per [launch-subtype-second-wave-planning-framework.md](launch-subtype-second-wave-planning-framework.md). |
| **Conversion goals** | No | Goal overlays and caution rules are additive; add when funnel differentiation is in scope. Primary/secondary goal support is profile-level; pack does not embed goal keys. |
| **Style preset** | No | Optional token_preset_ref; use when industry needs a dedicated preset. Shared or no preset is valid. |
| **Starter bundle** | Yes | At least one industry-scoped bundle required for pack usability per release gate and completeness. |
| **Overlays (section/page)** | Strongly recommended | Pack may ship with minimal overlays; completeness and release-grade bands expect overlay coverage. Plan T1 overlays at minimum. |
| **CTA patterns** | Yes (if pack refs them) | Pack preferred/required/discouraged CTA keys must resolve; define or reuse patterns. |

---

## 5. Linting, completeness, benchmark, and release evidence

| Expectation | When | Reference |
|-------------|------|-----------|
| **Definition linting** | Before marking pack active and before any release. Errors = 0; warnings documented. | [industry-definition-linting-guide.md](industry-definition-linting-guide.md) |
| **Health check** | After all refs are in place. Industry_Health_Check_Service::run(); errors = 0. | industry-pack-release-gate; health-report contracts |
| **Completeness (advisory)** | Run Industry_Pack_Completeness_Report_Service::generate_report() to assess minimal vs strong vs release-grade band. Does not replace gate. | [industry-pack-completeness-scoring-contract.md](../contracts/industry-pack-completeness-scoring-contract.md) |
| **Scaffold completeness (advisory)** | If starting from scaffold, run Industry_Scaffold_Completeness_Report_Service::generate_report() to confirm missing → scaffolded → authored progress. | [industry-scaffold-completeness-report-contract.md](../contracts/industry-scaffold-completeness-report-contract.md) |
| **Recommendation benchmark** | For first pack in a new industry, run Industry_Recommendation_Benchmark_Service per protocol; document or waive. | [industry-recommendation-benchmark-protocol.md](../qa/industry-recommendation-benchmark-protocol.md) |
| **AI prompt-pack evaluation** | Run industry and (if applicable) goal evaluation fixtures; document results. | [industry-ai-prompt-evaluation-fixtures.md](../qa/industry-ai-prompt-evaluation-fixtures.md); [conversion-goal-ai-prompt-evaluation-fixtures.md](../qa/conversion-goal-ai-prompt-evaluation-fixtures.md) |
| **Pre-release pipeline** | Full pipeline before release candidate: lint → health → coverage → benchmarks → regression guards → release gate. | [industry-pre-release-validation-pipeline.md](../release/industry-pre-release-validation-pipeline.md); [industry-pre-release-checklist.md](../release/industry-pre-release-checklist.md) |
| **Release gate** | All criteria in [industry-pack-release-gate.md](../release/industry-pack-release-gate.md) met or waived; human sign-off. | industry-pack-release-gate §1 |

---

## 6. Signoff checkpoints (summary)

1. **Post-scaffold:** Scaffold created per template; incomplete markers present; no activation.
2. **Post-authoring:** Pack, bundle, and referenced artifacts authored; status = active only when refs resolve; lint and health pass.
3. **Pre-release:** Pre-release checklist run; benchmarks and regression guards pass or waived; release gate criteria satisfied.
4. **Release sign-off:** Human review and sign-off per release gate; no auto-approval.

---

## 7. Cross-references

- [industry-pack-authoring-guide.md](industry-pack-authoring-guide.md) — Required pieces and implementation order; validation and QA steps.
- [industry-pack-author-checklist.md](industry-pack-author-checklist.md) — Concise checklist per pack.
- [future-industry-scaffold-pack-template.md](future-industry-scaffold-pack-template.md) — Scaffold structure and promotion path.
- [future-industry-candidate-evaluation-framework.md](future-industry-candidate-evaluation-framework.md) — Candidate approval and scorecard.
- [industry-pre-release-validation-pipeline.md](../release/industry-pre-release-validation-pipeline.md) — Validation steps and order.
- [industry-pack-release-gate.md](../release/industry-pack-release-gate.md) — Gate criteria and sign-off.
- [scaffold-incomplete-state-guardrail-contract.md](../contracts/scaffold-incomplete-state-guardrail-contract.md) — Scaffold exclusion from release until authored.
