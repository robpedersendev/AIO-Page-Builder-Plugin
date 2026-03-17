# Industry Pack Subsystem — Maintenance Checklist (Prompt 357)

**Purpose:** Maintenance baseline for future industry packs, overlays, CTA patterns, and related integrations. Use when adding industries, changing overlays, or touching recommendation/scoring logic.

**Adding a new industry:** Follow [industry-pack-authoring-guide.md](industry-pack-authoring-guide.md) and [industry-pack-author-checklist.md](industry-pack-author-checklist.md) for required objects, dependency order, validation, and release steps. For consistent asset skeletons (file paths, naming, placeholder status), see [industry-scaffold-generator-contract.md](../contracts/industry-scaffold-generator-contract.md). The checklist below is the ongoing maintenance view; the authoring guide covers the full author workflow. **Pre-release:** Use [industry-pre-release-validation-pipeline.md](../release/industry-pre-release-validation-pipeline.md) and [industry-pre-release-checklist.md](../release/industry-pre-release-checklist.md) for repeatable validation before shipping pack or overlay changes. **Coverage gaps:** Use [industry-coverage-gap-analysis-guide.md](industry-coverage-gap-analysis-guide.md) and Industry_Coverage_Gap_Analyzer to prioritize missing overlays, bundles, or metadata. To rank gaps by impact, use [industry-coverage-gap-prioritization-contract.md](../contracts/industry-coverage-gap-prioritization-contract.md) and the prioritization report generator (Prompt 524).

**Extension boundaries and roadmap:** Long-term extension seams, deprecation policy, and roadmap categories are defined in [industry-subsystem-roadmap-contract.md](../contracts/industry-subsystem-roadmap-contract.md). New work must use approved seams only. **Phase-two backlog:** Completed clusters and remaining work are summarized in [industry-phase-two-backlog-map.md](industry-phase-two-backlog-map.md) for dependency-aware prioritization. **New industry candidates** must be evaluated with [future-industry-candidate-evaluation-framework.md](future-industry-candidate-evaluation-framework.md) and [future-industry-scorecard-template.md](future-industry-scorecard-template.md) before implementation. **Support and operators:** For shared terminology and troubleshooting, use [industry-support-training-packet.md](industry-support-training-packet.md) and [industry-operator-curriculum.md](industry-operator-curriculum.md). **Author dashboard:** For a single-place view of health, completeness, gaps, and release readiness, use the internal [Industry Author Dashboard](../contracts/industry-author-dashboard-contract.md) (contract and screen per Prompts 521–522). **Task queue:** To turn completeness, gap prioritization, and override conflicts into a single maintenance queue, use [industry-author-task-queue-contract.md](../contracts/industry-author-task-queue-contract.md) and `Industry_Author_Task_Queue_Service::generate_queue()`.

---

## 1. Adding a new industry pack

- [ ] **Schema:** New pack definition conforms to [industry-pack-schema.md](../schemas/industry-pack-schema.md) and Industry_Pack_Schema (industry_key, name, summary, status, version_marker; optional supported_page_families, preferred/discouraged section keys, CTA pattern refs, seo_guidance_ref, token_preset_ref, etc.).
- [ ] **Registry:** Add pack to builtin definitions (e.g. new file under `Industry/Registry/Packs/` or extend existing); load via Industry_Pack_Registry::get_builtin_pack_definitions() or equivalent.
- [ ] **CTA patterns:** Any new CTA pattern keys referenced by the pack must exist in Industry_CTA_Pattern_Registry (add to CTAPatterns definitions if new).
- [ ] **Overlays:** If adding section helper or page one-pager overlays for the industry, add definitions under SectionHelperOverlays/ or PageOnePagerOverlays/; update overlay catalog appendices.
- [ ] **Validation:** Industry_Profile_Validator and pack validator accept the new industry_key; no regression on existing industries.
- [ ] **Linting:** Run Industry_Definition_Linter after load; resolve errors (see [industry-definition-linting-guide.md](industry-definition-linting-guide.md)).
- [ ] **QA:** Run industry-subsystem-acceptance-report checks for the new industry; update release gate if in scope. Run [industry-pre-release-checklist.md](../release/industry-pre-release-checklist.md) when releasing.

---

## 2. Changing overlays (section or page one-pager)

- [ ] **Schema:** Overlay shape matches industry-section-helper-overlay-schema or industry-page-onepager-overlay-schema; only allowed override regions are used.
- [ ] **Registry:** Load order and keying (industry_key + section_key or page_template_key) unchanged unless contract updated.
- [ ] **Composer:** Industry_Helper_Doc_Composer / Industry_Page_OnePager_Composer merge behavior unchanged; base content_body remains authoritative.
- [ ] **Catalog:** Update [industry-overlay-catalog.md](../appendices/industry-overlay-catalog.md) (section families, page families, loading).

---

## 3. Changing CTA patterns

- [ ] **Contract:** Pattern object shape matches [industry-cta-pattern-contract.md](../contracts/industry-cta-pattern-contract.md) (pattern_key, name, description, urgency_notes, trust_notes, action_framing).
- [ ] **Registry:** New or changed patterns loaded via Industry_CTA_Pattern_Registry; duplicate keys (first wins) and invalid entries skipped.
- [ ] **Pack references:** All preferred/required/discouraged_cta_patterns in pack definitions resolve to existing pattern_key.
- [ ] **Catalog:** Update [industry-cta-pattern-catalog.md](../appendices/industry-cta-pattern-catalog.md) if present.

---

## 4. Build Plan scoring and recommendation resolvers

- [ ] **Context:** Industry profile passed into scoring/recommendation context only when industry subsystem and profile are available; no hard dependency on industry for core flow.
- [ ] **Resolvers:** Industry_Section_Recommendation_Resolver and Industry_Page_Template_Recommendation_Resolver: affinity/discouraged logic aligned with pack definitions; unknown industry_key fails safe (no crash, generic behavior).
- [ ] **Tests:** Unit tests for resolvers and scoring with/without industry profile; no regression on no-industry path.

---

## 5. Export/restore and diagnostics

- [ ] **Export:** New industry-related options or payloads must be added to profiles/industry.json (or documented exception); schema_version and industry-export-restore-contract updated if schema changes.
- [ ] **Restore:** Restore pipeline supports new schema version or migrates; unsupported version skips industry restore with log.
- [ ] **Diagnostics:** Industry_Diagnostics_Service snapshot remains bounded; new fields (if any) documented and non-sensitive.

---

## 6. Style presets and token presets

- [ ] **Preset registry:** New industry style presets added per industry-style-preset-schema; loaded by Industry_Style_Preset_Registry.
- [ ] **Pack ref:** token_preset_ref in pack definition resolves to a preset key in the registry.
- [ ] **Application:** Industry_Style_Preset_Application_Service apply/clear; applied preset stored in Option_Names::APPLIED_INDUSTRY_PRESET and included in export.

---

## 7. Architecture discipline

- Industry Packs remain **additive**; they do not replace core registries or flows.
- Core plugin must run correctly with **zero** industry packs or empty industry profile.
- All new behavior must be **documented** (contracts, appendices, release/QA docs as appropriate).
- **Secrets:** No API keys or credentials in industry definitions, overlays, or export payloads.

---

## 8. Deprecation (packs and overlays)

- [ ] **Policy:** Follow [industry-pack-deprecation-contract.md](../contracts/industry-pack-deprecation-contract.md) for lifecycle states (deprecated, inactive, superseded, removed), replacement refs, profile handling, and export/restore. No automatic destructive migration of profile data.
- [ ] **Pack deprecation:** Set pack status to `deprecated`; set optional `deprecated_at`, `replacement_ref`, `deprecation_note` per schema. Do not remove from registry load until documented sunset. Update [industry-pack-catalog.md](../appendices/industry-pack-catalog.md); export/restore continues to support deprecated industry_key for existing profiles. See [industry-subsystem-roadmap-contract.md](../contracts/industry-subsystem-roadmap-contract.md) §4.
- [ ] **Overlay deprecation:** Set overlay status to `archived` or `draft`; registry skips non-active. Remove from builtin definitions only when safe; document in changelog and coverage matrix.
- [ ] **Ref deprecation:** Do not remove CTA/style/SEO/LPagery keys that packs still reference; migrate pack refs or deprecate pack first. Invalid refs fail safely at resolution.
- [ ] **Pack migration (Prompt 412):** Use Industry_Pack_Migration_Executor for deprecated-to-replacement transitions. Admin-only; nonce and capability required. See [industry-pack-migration-contract.md](../contracts/industry-pack-migration-contract.md). Do not rewrite Build Plan or approval snapshots.
- [ ] **Pack version diff (Prompt 418):** Use Industry_Pack_Diff_Service to compare two pack states (e.g. built-in vs bundle) for release review and change summaries. See [industry-pack-diff-contract.md](../contracts/industry-pack-diff-contract.md). Read-only; no pack mutation.

---

## 9. Bad-fit recommendation troubleshooting (support)

- [ ] **Playbook:** When users report wrong, generic, or mismatched recommendations, use [industry-bad-fit-recommendation-troubleshooting.md](industry-bad-fit-recommendation-troubleshooting.md) for diagnostic steps (profile completeness, pack activation, subtype selection, overlays, metadata, overrides, starter bundle, no-industry fallback).
- [ ] **Evidence:** Use Industry_Diagnostics_Service snapshot, Industry_Health_Check_Service, and recommendation/overlay contracts; no secrets in support artifacts. Escalate to pack/overlay maintainers or engineering per playbook §4.

---

*Use this checklist when planning or implementing changes to the Industry Pack subsystem. Update the checklist if new subsystems or contracts are added.*
