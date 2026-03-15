# Industry Page One-Pager Overlay Expansion Plan (Internal)

**Spec**: industry-page-onepager-overlay-schema.md; industry-pack-extension-contract.md; industry-pack-authoring-guide.md. **Prompt**: 382.

This document defines the batching and authoring framework for expanding industry page one-pager overlays beyond the initial seeded page families. It maps remaining page families into priority tiers, defines consistency rules, and scaffolds batch-ready overlay structures. It does **not** author every remaining one-pager overlay in one go.

---

## 1. Purpose and scope

- **Goal**: Scale industry-specific page guidance across a larger portion of the page template library without ad hoc overlay authoring. Overlays remain additive and scoped by industry_key + page_template_key; base one-pagers stay authoritative.
- **In scope**: Page-family overlay coverage tiers; priority matrix for next batches; consistency rules (page-purpose, CTA strategy, hierarchy, LPagery/local); batch-ready scaffolding; coverage map (seeded vs pending).
- **Out of scope**: Authoring every overlay in this prompt; replacing the base one-pager library; redesigning Industry_Page_OnePager_Composer.

---

## 2. Page-family overlay coverage tiers

Page template families align with [page-template-category-taxonomy-contract.md](../contracts/page-template-category-taxonomy-contract.md) §3 (template_family) and §5 (page_purpose_family). Overlay coverage is grouped into tiers:

| Tier | Page family / purpose | Description | Current status |
|------|------------------------|-------------|----------------|
| **T1 Seeded** | home, about, contact, services (core) | First wave: Home (pt_home_conversion_01), About (pt_about_story_01), Contact (pt_contact_request_01), Services (pt_services_overview_01). | Covered for cosmetology_nail, realtor, plumber, disaster_recovery. |
| **T2 High value** | booking, valuation, emergency/service, local/service-area, neighborhood, gallery, financing, trust/certification | High-impact page types for multiple industries; often requested for onboarding and create-page flow. | Pending; scaffold below. |
| **T3 Medium value** | locations, products, offerings, faq, comparison, profiles, events | Hub and child_detail pages for locations, products, offerings, FAQ, comparison, profiles, events. | Later waves. |
| **T4 Lower priority** | privacy, terms, accessibility, informational, other | Legal, accessibility, and uncategorized. | As needed. |

---

## 3. Priority matrix (next authoring waves)

| Wave | Page families / template types (primary) | Example template keys (from registry) | Industries to extend | Rationale |
|------|------------------------------------------|----------------------------------------|----------------------|-----------|
| **Wave 2a** | Booking-focused, valuation-focused | Page templates with booking or valuation intent (template_family / purpose). | All four seeded industries. | Booking and valuation CTAs are core for many verticals. |
| **Wave 2b** | Emergency service, local/service-area | Emergency, service-area, and location-detail templates. | plumber, disaster_recovery, realtor, cosmetology_nail. | Emergency and local pages need industry-specific hierarchy and CTA guidance. |
| **Wave 2c** | Neighborhood, gallery, financing | Neighborhood/market-area, gallery, and financing/trust pages. | realtor, cosmetology_nail, plumber, disaster_recovery. | Neighborhood and gallery matter for realtor/cosmetology; financing for plumber/disaster. |
| **Wave 2d** | Trust/certification pages | Trust-led, certification, or authority child_detail pages. | All four. | Trust and certification messaging is cross-industry. |

**Example page_template_key values** must be resolved from the live page template registry. This plan references family/purpose; when authoring, use actual internal_key from the registry for templates that have (or will have) base one-pagers.

---

## 4. Consistency rules for page overlay content

All new page one-pager overlays must follow these rules so guidance stays schema-valid and consistent:

- **Hierarchy**: hierarchy_hints must align with pack supported_page_families and template_category_class (top_level, hub, nested_hub, child_detail). Max 1024 chars; actionable parent/child and placement guidance.
- **CTA strategy**: cta_strategy must reference pack preferred/required/discouraged CTA patterns where relevant. Align with industry-cta-pattern-catalog and pack default_cta_patterns.
- **LPagery/local**: lpagery_seo_notes must be concise (max 512 chars); focus on local SEO, token usage, or page-level LPagery hints for this industry.
- **Compliance**: compliance_cautions only where the industry has real regulatory or board requirements. Max 1024 chars.
- **Schema**: Every overlay object must have industry_key, page_template_key, scope `page_onepager_overlay`, status (`active`|`draft`|`archived`). Optional: hierarchy_hints, cta_strategy, lpagery_seo_notes, compliance_cautions, additive_blocks. Invalid or duplicate (industry_key, page_template_key) skipped at load.

---

## 5. Batch-ready overlay scaffolding

Scaffolding is **structure only** (no full content). Use when authoring Wave 2+ overlays.

**Per industry, per page_template_key:**

- Create or extend the industry page overlay file (e.g. `PageOnePagerOverlays/overlays-{industry_key}.php`).
- For each new page_template_key in the wave, add one overlay array entry with: industry_key, page_template_key, scope, status (draft until copy ready); placeholders for hierarchy_hints, cta_strategy, lpagery_seo_notes, compliance_cautions, additive_blocks as needed.
- Register in `Industry_Page_OnePager_Overlay_Registry::get_builtin_overlay_definitions()` (or equivalent loader).
- Replace placeholders with real content in a follow-up pass; set status to `active` when ready.

**Page template key source of truth**: Page template registry (Page_Template_Schema, CPT). Use internal_key from the registry for templates that have or will have base one-pagers.

---

## 6. Coverage map (summary)

- **Seeded (T1)**: home, about, contact, services (one or more template keys each). See [industry-page-overlay-coverage-matrix.md](../appendices/industry-page-overlay-coverage-matrix.md).
- **Pending (T2)**: booking, valuation, emergency/service, local/service-area, neighborhood, gallery, financing, trust/certification. Matrix lists these as pending with target family/purpose.
- **Later (T3–T4)**: locations, products, offerings, faq, comparison, profiles, events; privacy, terms, accessibility, other.

---

## 7. Cross-references

- **Schema**: [industry-page-onepager-overlay-schema.md](../schemas/industry-page-onepager-overlay-schema.md).
- **Coverage matrix**: [industry-page-overlay-coverage-matrix.md](../appendices/industry-page-overlay-coverage-matrix.md).
- **Overlay catalog**: [industry-overlay-catalog.md](../appendices/industry-overlay-catalog.md).
- **Authoring**: [industry-pack-authoring-guide.md](industry-pack-authoring-guide.md) §2 One-pager overlays; [industry-pack-author-checklist.md](industry-pack-author-checklist.md).
- **Page taxonomy**: [page-template-category-taxonomy-contract.md](../contracts/page-template-category-taxonomy-contract.md) §3, §5.
