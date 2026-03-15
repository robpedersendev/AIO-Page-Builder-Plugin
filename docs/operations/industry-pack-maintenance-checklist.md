# Industry Pack Subsystem — Maintenance Checklist (Prompt 357)

**Purpose:** Maintenance baseline for future industry packs, overlays, CTA patterns, and related integrations. Use when adding industries, changing overlays, or touching recommendation/scoring logic.

---

## 1. Adding a new industry pack

- [ ] **Schema:** New pack definition conforms to [industry-pack-schema.md](../schemas/industry-pack-schema.md) and Industry_Pack_Schema (industry_key, name, summary, status, version_marker; optional supported_page_families, preferred/discouraged section keys, CTA pattern refs, seo_guidance_ref, token_preset_ref, etc.).
- [ ] **Registry:** Add pack to builtin definitions (e.g. new file under `Industry/Registry/Packs/` or extend existing); load via Industry_Pack_Registry::get_builtin_pack_definitions() or equivalent.
- [ ] **CTA patterns:** Any new CTA pattern keys referenced by the pack must exist in Industry_CTA_Pattern_Registry (add to CTAPatterns definitions if new).
- [ ] **Overlays:** If adding section helper or page one-pager overlays for the industry, add definitions under SectionHelperOverlays/ or PageOnePagerOverlays/; update overlay catalog appendices.
- [ ] **Validation:** Industry_Profile_Validator and pack validator accept the new industry_key; no regression on existing industries.
- [ ] **QA:** Run industry-subsystem-acceptance-report checks for the new industry; update release gate if in scope.

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

*Use this checklist when planning or implementing changes to the Industry Pack subsystem. Update the checklist if new subsystems or contracts are added.*
