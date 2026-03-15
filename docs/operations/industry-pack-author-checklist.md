# Industry Pack Author Checklist (Internal)

**Use when**: Adding a new industry pack. Companion to [industry-pack-authoring-guide.md](industry-pack-authoring-guide.md).

---

## Pre-authoring

- [ ] industry_key chosen and unique (pattern `^[a-z0-9_-]+$`, max 64).
- [ ] Required fields defined: name, summary, status (`active`|`draft`|`deprecated`), version_marker (e.g. `1`).
- [ ] All refs (CTA, SEO, preset, LPagery, overlays) identified; target registries and keys known.

---

## Pack definition

- [ ] Pack definition file created under `Industry/Registry/Packs/` (or equivalent load path).
- [ ] Conforms to [industry-pack-schema.md](../schemas/industry-pack-schema.md) and Industry_Pack_Schema.
- [ ] supported_page_families, preferred_section_keys, discouraged_section_keys (if used) reference valid families/keys.
- [ ] preferred_cta_patterns / required_cta_patterns / discouraged_cta_patterns reference keys that exist in Industry_CTA_Pattern_Registry.
- [ ] seo_guidance_ref, token_preset_ref, lpagery_rule_ref (if used) reference existing registry entries.
- [ ] Validation: Industry_Pack_Schema / validator accepts the pack; invalid or unsupported version rejected.

---

## CTA patterns

- [ ] New CTA pattern keys (if any) added to CTA pattern definitions and loaded by Industry_CTA_Pattern_Registry.
- [ ] [industry-cta-pattern-catalog.md](../appendices/industry-cta-pattern-catalog.md) updated if new patterns added.

---

## Style preset (optional)

- [ ] Preset definition added per industry-style-preset-schema; loaded by Industry_Style_Preset_Registry.
- [ ] Pack token_preset_ref set to preset key.
- [ ] [industry-style-preset-catalog.md](../appendices/industry-style-preset-catalog.md) updated if new preset.

---

## SEO guidance (optional)

- [ ] SEO guidance rule added per industry-seo-guidance-schema; loaded by Industry_SEO_Guidance_Registry.
- [ ] Pack seo_guidance_ref set to rule key.
- [ ] [industry-seo-guidance-catalog.md](../appendices/industry-seo-guidance-catalog.md) updated if new rule.

---

## LPagery rules (optional)

- [ ] LPagery rule added per industry-lpagery-rule-schema; loaded by Industry_LPagery_Rule_Registry.
- [ ] Pack lpagery_rule_ref set to rule key.
- [ ] [industry-lpagery-rule-catalog.md](../appendices/industry-lpagery-rule-catalog.md) updated if new rule.

---

## Helper / one-pager overlays (optional)

- [ ] Section helper overlays (if any) conform to industry-section-helper-overlay-schema; loaded by Industry_Section_Helper_Overlay_Registry.
- [ ] Page one-pager overlays (if any) conform to industry-page-onepager-overlay-schema; loaded by Industry_Page_OnePager_Overlay_Registry.
- [ ] [industry-overlay-catalog.md](../appendices/industry-overlay-catalog.md) updated.

---

## Question packs (optional)

- [ ] Question pack definition added and registered in Industry_Question_Pack_Registry.
- [ ] [industry-question-pack-catalog.md](../appendices/industry-question-pack-catalog.md) updated if new pack.

---

## Catalog and docs

- [ ] [industry-pack-catalog.md](../appendices/industry-pack-catalog.md) updated with new pack entry (name, summary, families, CTA, refs, source file).
- [ ] Any new appendix or contract referenced from authoring guide updated.

---

## Validation and QA

- [ ] Industry_Profile_Validator accepts new industry_key (no regression on existing industries).
- [ ] industry-subsystem-acceptance-report run for new industry (or scope waiver documented).
- [ ] Export/restore: industry profile and preset round-trip; no secrets in payload.
- [ ] No-industry path unchanged (core runs with zero packs / empty profile).
- [ ] [industry-pack-release-gate.md](../release/industry-pack-release-gate.md) and [known-risk-register.md](../release/known-risk-register.md) updated if release scope includes new pack.

---

## Dependency order (summary)

1. CTA patterns (if new keys) → 2. Style preset (if used) → 3. SEO rule (if used) → 4. LPagery rule (if used) → 5. Pack definition (refs point to above) → 6. Helper/one-pager overlays (if used) → 7. Question pack (if used) → 8. Catalogs and QA.
