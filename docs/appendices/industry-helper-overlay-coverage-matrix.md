# Industry Helper Overlay Coverage Matrix

**Spec**: industry-section-helper-overlay-schema.md; industry-helper-overlay-expansion-plan.md. **Prompt**: 381.

This appendix shows which section purpose families and example section keys have **seeded** industry section-helper overlays vs **pending** (planned for expansion). Section keys are from the section registry (internal_key); families from section-template-category-taxonomy-contract §2.

---

## 1. Seeded overlay coverage (T1)

| Section purpose family | Example section_key (seeded) | Industries with overlay | Notes |
|------------------------|------------------------------|-------------------------|------|
| hero | hero_conv_02 | cosmetology_nail, realtor, plumber, disaster_recovery | Hero/conversion. |
| cta | cta_booking_01 | cosmetology_nail, realtor, plumber, disaster_recovery | Primary CTA/booking. |
| proof | tp_badge_01 | cosmetology_nail, realtor, plumber, disaster_recovery | Trust/proof badge. |
| contact | gc_contact_form_01 | cosmetology_nail, realtor, plumber, disaster_recovery | Contact/form. |
| offer | gc_offer_value_01 | cosmetology_nail, realtor, plumber, disaster_recovery | Feature/benefit offer. |

**Source**: industry-overlay-catalog.md; SectionHelperOverlays/ overlays-{industry}.php. Only section keys that actually have overlay definitions in the registry are listed above.

---

## 2. Pending overlay coverage (T2 – next waves)

| Section purpose family | Target use | Example section_key (from registry) | Wave | Notes |
|------------------------|------------|-------------------------------------|------|------|
| listing | Gallery/media | (Use registry: directory_listing, media_gallery category) | 2a | Gallery and media sections. |
| profile | Staff/profile | (Use registry: profile_bio category) | 2a | Staff and bio sections. |
| offer | Pricing/packages | (Use registry: pricing_packages category) | 2b | Pricing and package sections. |
| listing | Service-grid | (Use registry: service-grid or listing sections) | 2b | Service grid and listing. |
| comparison | Comparison | (Use registry: comparison category) | 2c | Comparison/versus sections. |
| timeline / explainer | Process/steps | (Use registry: timeline, process_steps) | 2c | Process and timeline. |
| faq | FAQ | (Use registry: faq category) | 2c | FAQ sections. |
| utility | Map/location | (Use registry: map/location utility sections) | 2d | Map and location. |
| proof | Trust/certification (expand) | Additional proof section keys beyond tp_badge_01 | 2d | More trust/cert sections. |

**Example section_key values** must be resolved from the live section registry (Section_Schema, CPT). This matrix does not invent keys; it references category/purpose family. When authoring, look up actual internal_key for each section that has a base helper and add overlay entries for those keys.

---

## 3. Later waves (T3–T4)

| Section purpose family | Target use | Wave |
|------------------------|------------|------|
| stats | Stats/highlights | T3 |
| legal | Legal/disclaimer | T3 |
| related | Related/recommended content | T3 |
| explainer | Broader explainer sections | T3 |
| utility | Other utility (non-map) | T4 |
| other | Uncategorized | T4 |

---

## 4. Industry × family view (seeded only)

| Industry | hero | cta | proof | contact | offer |
|----------|------|-----|-------|---------|-------|
| cosmetology_nail | ✓ | ✓ | ✓ | ✓ | ✓ |
| realtor | ✓ | ✓ | ✓ | ✓ | ✓ |
| plumber | ✓ | ✓ | ✓ | ✓ | ✓ |
| disaster_recovery | ✓ | ✓ | ✓ | ✓ | ✓ |

**Pending**: All T2 families (listing, profile, offer-pricing, comparison, timeline/explainer, faq, utility-map, proof-expand) have no overlay rows yet. Add rows when overlays are authored per industry-helper-overlay-expansion-plan.

---

## 5. Cross-references

- [industry-helper-overlay-expansion-plan.md](../operations/industry-helper-overlay-expansion-plan.md) — tiers, waves, consistency rules, scaffolding.
- [industry-overlay-catalog.md](industry-overlay-catalog.md) — loading, scope, and relation to base helpers.
- [industry-section-helper-overlay-schema.md](../schemas/industry-section-helper-overlay-schema.md) — overlay object shape and allowed regions.
- [section-template-category-taxonomy-contract.md](../contracts/section-template-category-taxonomy-contract.md) — section_purpose_family and category.
