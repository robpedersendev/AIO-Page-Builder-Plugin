# Industry Subsystem — Long-Term Extension Hooks and Roadmap Contract (Prompt 400)

**Spec:** industry-pack-extension-contract.md; industry-pack-release-gate.md; industry-subsystem-final-signoff.md.  
**Purpose:** Define extension seams, subsystem boundaries, and roadmap contract for future industry expansion so additional verticals, bundle families, overlays, and recommendation rules can be added without architectural drift.

---

## 1. Scope and principles

- **One core plugin:** Industry packs remain overlays; they do not become separate products or fork core.
- **Schema-driven, exportable, reviewable:** All expansion must use established schemas, registries, and export/restore contracts.
- **No hidden bypasses:** Extension guidance must not normalize unsafe shortcuts or policy drift. No secrets or runtime policy drift in roadmap docs.

---

## 2. Approved extension seams

The following are **approved seams** for adding or extending industry behavior. New work must use these seams; no ad hoc code paths.

| Seam | Description | Contract / registry |
|------|-------------|---------------------|
| **Pack definitions** | New industry_key with pack object (name, summary, status, version_marker, CTA refs, supported_page_families, optional refs). | industry-pack-schema.md; Industry_Pack_Registry; industry-pack-authoring-guide.md |
| **CTA patterns** | New pattern_key definitions; pack preferred/required/discouraged_cta_patterns reference them. | industry-cta-pattern-contract.md; Industry_CTA_Pattern_Registry |
| **Section helper overlays** | Overlay definitions keyed by industry_key + section_key; allowed regions only (tone_notes, cta_usage_notes, seo_notes, compliance_cautions, media_notes). | industry-section-helper-overlay-schema.md; Industry_Section_Helper_Overlay_Registry; industry-helper-overlay-expansion-plan.md |
| **Page one-pager overlays** | Overlay definitions keyed by industry_key + page_template_key; allowed regions per page-onepager schema. | industry-page-onepager-overlay-schema.md; Industry_Page_OnePager_Overlay_Registry |
| **Style presets** | New preset keys; pack token_preset_ref. | industry-style-preset-schema.md; Industry_Style_Preset_Registry |
| **SEO guidance** | New rule keys; pack seo_guidance_ref. | industry-seo-guidance-schema.md; Industry_SEO_Guidance_Registry |
| **LPagery rules** | New rule keys; pack lpagery_rule_ref. | industry-lpagery-rule-schema.md; Industry_LPagery_Rule_Registry |
| **Starter bundles** | Bundle definitions (recommended page/section refs, CTA/style refs); pack starter_bundle_ref when implemented. | industry-starter-bundle-schema.md; Industry_Starter_Bundle_Registry |
| **Question packs** | Onboarding question-pack definitions; profile reference. | industry-question-pack-contract.md; Industry_Question_Pack_Registry |
| **Recommendation resolvers** | Industry profile and pack drive affinity/discouraged scoring; no change to resolver API without contract update. | industry-section-recommendation-contract.md; industry-page-template-recommendation-contract.md |

---

## 3. What may vary per industry vs global/core

### 3.1 Per industry (allowed to vary)

- **industry_key**, pack name, summary, supported_page_families.
- **Preferred/discouraged** section keys, page template keys, CTA pattern keys (references only; no new core enums).
- **Overlay content** (tone_notes, cta_usage_notes, seo_notes, compliance_cautions, media_notes) for section and page overlays.
- **Style preset** (token_preset_ref), **SEO guidance** (seo_guidance_ref), **LPagery rule** (lpagery_rule_ref), **starter bundle** (starter_bundle_ref).
- **Question pack** for onboarding.
- **Export payload**: industry profile and applied preset are per-site; pack definitions are shared (builtin or imported).

### 3.2 Global / core (must remain shared)

- **Section and page template registries** — authoritative; industry does not add or remove templates.
- **Helper doc and one-pager base content** — authoritative; overlays only add or override allowed regions.
- **Build Plan and AI planner** — industry is optional context; no industry = generic behavior.
- **Export/restore schema** — industry profile and applied_preset in profiles category; schema_version and validation rules are global.
- **Diagnostics** — bounded snapshot shape; industry adds refs only, no unbounded or secret data.
- **Capability and nonce** — all admin and mutation surfaces remain capability-checked and nonce-verified; no industry-specific bypasses.
- **Uninstall and portability** — industry data follows same preservation/export rules as rest of plugin; no hidden retention.

---

## 4. Deprecation policy for packs and overlays

- **Deprecation of an industry pack:** Set pack status to `deprecated` in definition; do not remove from registry load until a documented sunset. Recommendation and overlays may skip deprecated packs or treat as legacy; behavior must be documented. Export/restore continues to support deprecated industry_key for existing profiles.
- **Deprecation of overlay entries:** Set overlay status to `archived` or `draft`; registry skips non-active for composition. Remove from builtin definitions only when safe for all consumers (document in changelog and maintenance checklist).
- **Deprecation of CTA patterns, style presets, or other refs:** Do not remove keys that packs still reference; either add a new key and migrate pack refs, or deprecate the pack first. Invalid refs fail safely at resolution (skip or generic).
- **Review cadence:** When adding or deprecating, update industry-pack-catalog.md, industry-overlay-catalog.md, and coverage matrices; run acceptance and regression guards applicable to the change.

---

## 5. Future industry candidate evaluation

Before adding a new industry, evaluate the candidate using the internal framework and scorecard so expansion stays rational and maintainable:

- **Framework:** [future-industry-candidate-evaluation-framework.md](../operations/future-industry-candidate-evaluation-framework.md) — criteria (content-model fit, template overlap, LPagery posture, CTA complexity, documentation burden, styling needs, compliance/caution burden, starter bundle viability, subtype complexity, long-term maintenance cost), scoring, and go/no-go/review categories.
- **Scorecard:** [future-industry-scorecard-template.md](../operations/future-industry-scorecard-template.md) — one scorecard per candidate; attach to backlog and use in planning and prompt generation.

New industries should score above the team-defined threshold and must not require new core seams.

---

## 5.1 Phase-two checkpoint and backlog map (Prompt 445)

- **Checkpoint**: Completed capability clusters (packs, profile, recommendation, overlays, subtypes, health, linting, repair suggestions, coverage analyzer, pre-release pipeline, sandbox) are documented in [industry-phase-two-backlog-map.md](../operations/industry-phase-two-backlog-map.md).
- **Backlog map**: Remaining gaps, optional extensions, and future work clusters are grouped there for dependency-aware prompt generation. Use it to prioritize hardening vs content seeding vs new industry.

---

## 6. Roadmap categories for future expansion

Concrete categories for prompt generation and backlog; expansion stays within approved seams.

| Category | Description | Priority / notes |
|----------|-------------|------------------|
| **New industries** | Add industry_key, pack definition, CTA patterns, overlays (T1 then T2), style preset, SEO/LPagery refs as needed. | Follow industry-pack-authoring-guide and industry-pack-author-checklist; update acceptance report and release gate if in scope. |
| **Deeper overlays** | Expand section-helper and page-one-pager overlay coverage (e.g. T2/T3 families per industry-helper-overlay-expansion-plan). | Schema-valid overlays only; update overlay catalog and coverage matrix. |
| **Additional starter bundles** | Define or extend starter bundles per industry; link via pack starter_bundle_ref when flow is implemented. | industry-starter-bundle-schema; no change to core Build Plan execution without contract. |
| **Richer diagnostics** | Add optional fields to industry diagnostics snapshot (e.g. overlay coverage summary, ref resolution status). | Bounded, no secrets; document in industry-subsystem-diagnostics-checklist. |
| **Recommendation rules** | Refine affinity/discouraged logic, scoring weights, or substitute quality per industry. | industry-section-recommendation-contract; industry-recommendation-regression-guard must pass. |
| **Export/restore evolution** | New schema_version or new optional fields in industry profile/preset payload. | industry-export-restore-contract; backward compatibility or migration path required. |
| **Question packs and onboarding** | New or updated question packs per industry. | industry-question-pack-contract; profile and onboarding flow only. |
| **Industry subtypes** | Structured sub-variants per parent industry (e.g. buyer-agent/listing-agent realtor, residential/commercial plumber). Subtype schema and overlay scope per industry-subtype-schema.md and industry-subtype-extension-contract.md. Profile stores optional industry_subtype_key; resolver falls back to parent when invalid. | industry-subtype-extension-contract.md; industry-subtype-schema.md; Industry_Subtype_Resolver (Prompt 414). |

---

## 6. Adding a new industry (summary)

1. Create pack definition; register in Industry_Pack_Registry (e.g. new file under Packs/).
2. Add CTA patterns referenced by pack; register in Industry_CTA_Pattern_Registry.
3. Add style preset, SEO guidance, LPagery rule as needed; link refs in pack.
4. Add section-helper and page-one-pager overlays per expansion plan; load via overlay registries.
5. Add question pack if used; link from profile/onboarding.
6. Update industry-pack-catalog.md, industry-overlay-catalog.md, coverage matrices.
7. Run validation and QA (Industry_Profile_Validator, acceptance report, regression guards); update release gate evidence if in release scope.

---

## 8. Cross-references

- [industry-phase-two-backlog-map.md](../operations/industry-phase-two-backlog-map.md) — Phase-two checkpoint and backlog map (Prompt 445).
- [industry-pack-extension-contract.md](industry-pack-extension-contract.md) — Subsystem boundary and terminology.
- [industry-pack-authoring-guide.md](../operations/industry-pack-authoring-guide.md) — Author workflow and required pieces.
- [industry-pack-maintenance-checklist.md](../operations/industry-pack-maintenance-checklist.md) — Ongoing maintenance.
- [industry-pack-catalog.md](../appendices/industry-pack-catalog.md) — Built-in pack list.
- [industry-subsystem-final-signoff.md](../release/industry-subsystem-final-signoff.md) — First release signoff.
- [industry-helper-overlay-expansion-plan.md](../operations/industry-helper-overlay-expansion-plan.md) — Overlay tiers and waves.
- [industry-export-restore-contract.md](industry-export-restore-contract.md) — Export/restore schema and versioning.

---

*Future expansion must stay within these boundaries to avoid architectural drift and preserve one-plugin, overlay-based design.*
