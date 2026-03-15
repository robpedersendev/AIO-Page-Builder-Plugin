# Industry Helper Overlay Expansion Plan (Internal)

**Spec**: industry-section-helper-overlay-schema.md; industry-pack-extension-contract.md; industry-pack-authoring-guide.md. **Prompt**: 381.

This document defines the batching and authoring framework for expanding industry section-helper overlays beyond the first seeded families. It maps remaining section families into priority tiers, defines consistency rules, and scaffolds batch-ready overlay definition structures for later authoring. It does **not** author every remaining overlay in one go.

---

## 1. Purpose and scope

- **Goal**: Scale helper guidance across a larger portion of the section library without ad hoc overlay authoring. Overlays remain additive and scoped by industry_key + section_key; base helper docs stay authoritative.
- **In scope**: Coverage tiers by section_purpose_family; priority matrix for next authoring waves; consistency rules (tone, CTA, SEO, accessibility); batch-ready overlay scaffolding for high-value families.
- **Out of scope**: Authoring every overlay in this prompt; replacing the base helper-doc corpus; redesigning the helper resolver.

---

## 2. Section family coverage tiers

Section purpose families are from [section-template-category-taxonomy-contract.md](../contracts/section-template-category-taxonomy-contract.md) §2. Overlay coverage is grouped into tiers:

| Tier | Section purpose family | Description | Current status |
|------|------------------------|-------------|----------------|
| **T1 Seeded** | hero, cta, proof, contact, offer (subset) | First wave: Hero (hero_conv_02), CTA (cta_booking_01), Proof/Trust (tp_badge_01), Contact/Form (gc_contact_form_01), Feature/benefit (gc_offer_value_01). | Covered for cosmetology_nail, realtor, plumber, disaster_recovery. |
| **T2 High value** | listing (gallery/media), profile (staff/profile), offer (pricing/packages), listing (service-grid), comparison, timeline/explainer (process), faq, utility (map/location), proof (trust/certification expansion) | High-impact sections for multiple industries; often requested for onboarding and composition. | Pending; scaffold below. |
| **T3 Medium value** | stats, legal, related, explainer (broader) | Stats blocks, legal snippets, related content, general explainer. | Later waves. |
| **T4 Lower priority** | utility (non-map), other | Remaining utility and uncategorized. | As needed. |

---

## 3. Priority matrix (next authoring waves)

| Wave | Section families (primary) | Representative section keys (examples) | Industries to extend | Rationale |
|------|----------------------------|----------------------------------------|----------------------|-----------|
| **Wave 2a** | listing (gallery/media), profile (staff/profile) | Gallery and profile section internal_keys from registry. | All four seeded industries. | Gallery and staff sections are core for local/service businesses. |
| **Wave 2b** | offer (pricing/packages), listing (service-grid) | Pricing and service-grid section keys. | All four. | Pricing and service grids drive conversion and clarity. |
| **Wave 2c** | comparison, timeline (process), faq | Comparison, process/steps, FAQ section keys. | All four. | Decision support and process/FAQ are cross-industry. |
| **Wave 2d** | utility (map/location), proof (trust/certification expansion) | Map/location sections; additional proof/trust sections beyond tp_badge_01. | All four. | Location and trust/certs matter for local and regulated verticals. |

Authoring order within a wave: one section_key × one industry at a time, or batch by industry (all section keys for realtor, then plumber, etc.) per team preference. Consistency rules (§4) apply to all.

---

## 4. Consistency rules for overlay content

All new overlays must follow these rules so tone, CTA, SEO, and accessibility stay consistent and schema-valid:

- **Tone**: tone_notes must match pack positioning (e.g. plumber: trust and urgency; realtor: professional and local). Max 1024 chars; no marketing fluff; actionable.
- **CTA**: cta_usage_notes must reference pack preferred/required/discouraged CTA patterns where relevant. Align with industry-cta-pattern-catalog and pack default_cta_patterns.
- **SEO**: seo_notes must be concise (max 512 chars); no keyword stuffing; focus on heading and content hints for this section/industry.
- **Accessibility**: Where overlay content implies UI or content author guidance, mention accessibility (e.g. alt text for media_notes, heading hierarchy in seo_notes). No new overlay field for a11y; use existing regions.
- **Compliance**: compliance_cautions only where the industry has real regulatory or board requirements (e.g. licensing, claims). Max 1024 chars.
- **Schema**: Every overlay object must have industry_key, section_key, scope `section_helper_overlay`, status (`active`|`draft`|`archived`). Optional: tone_notes, cta_usage_notes, compliance_cautions, media_notes, seo_notes, additive_blocks. Invalid or duplicate (industry_key, section_key) skipped at load.

---

## 5. Batch-ready overlay scaffolding

Scaffolding is **structure only** (no full content). Use when authoring Wave 2+ overlays.

**Per industry, per section_key:**

- Create or extend the industry overlay file (e.g. `SectionHelperOverlays/overlays-{industry_key}.php`).
- For each new section_key in the wave, add one overlay array entry with:
  - industry_key, section_key, scope, status (draft until copy ready).
  - Placeholder or empty strings for tone_notes, cta_usage_notes, compliance_cautions, media_notes, seo_notes, additive_blocks as needed.
- Register in `Industry_Section_Helper_Overlay_Registry::get_builtin_overlay_definitions()` (or equivalent loader) so the new entries are in the load list.
- Replace placeholders with real content in a follow-up pass; set status to `active` when ready.

**Section key source of truth**: Section registry (Section_Schema, section template CPT). Do not invent section_key values; use internal_key from the registry for sections that have or will have base helpers.

---

## 6. Coverage map (summary)

- **Seeded (T1)**: hero, cta, proof, contact, offer (5 families; one or more section keys each). See [industry-helper-overlay-coverage-matrix.md](../appendices/industry-helper-overlay-coverage-matrix.md).
- **Pending (T2)**: listing (gallery/media, service-grid), profile, offer (pricing), comparison, timeline/explainer (process), faq, utility (map/location), proof (additional trust/cert). Coverage matrix lists these as pending with target section_key examples.
- **Later (T3–T4)**: stats, legal, related, explainer (broader), utility (other), other. Documented for future waves.

---

## 7. Cross-references

- **Schema**: [industry-section-helper-overlay-schema.md](../schemas/industry-section-helper-overlay-schema.md).
- **Coverage matrix**: [industry-helper-overlay-coverage-matrix.md](../appendices/industry-helper-overlay-coverage-matrix.md).
- **Overlay catalog**: [industry-overlay-catalog.md](../appendices/industry-overlay-catalog.md).
- **Authoring**: [industry-pack-authoring-guide.md](industry-pack-authoring-guide.md) §2 Helper overlays; [industry-pack-author-checklist.md](industry-pack-author-checklist.md) Helper/one-pager overlays.
- **Section taxonomy**: [section-template-category-taxonomy-contract.md](../contracts/section-template-category-taxonomy-contract.md) §2.
