# Industry Helper Overlay Coverage Matrix

**Spec**: industry-section-helper-overlay-schema.md; industry-helper-overlay-expansion-plan.md. **Prompt**: 381.

This appendix shows which section purpose families and example section keys have **seeded** industry section-helper overlays vs **pending** (planned for expansion). Section keys are from the section registry (internal_key); families from section-template-category-taxonomy-contract §2.

To prioritize missing coverage per industry or subtype, use the internal **Industry_Coverage_Gap_Analyzer**; see [industry-coverage-gap-analysis-guide.md](../operations/industry-coverage-gap-analysis-guide.md).

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

## 2. Second-wave overlay coverage (T2 – authored Prompt 401)

| Section purpose family | Example section_key (authored) | Industries with overlay | Notes |
|------------------------|--------------------------------|-------------------------|------|
| listing (gallery/media) | mlp_gallery_01 | cosmetology_nail | Gallery/media. |
| offer (pricing/packages) | fb_package_summary_01 | cosmetology_nail, plumber | Pricing and package sections. |
| profile (staff/profile) | mlp_profile_cards_01, mlp_profile_summary_01 | cosmetology_nail, realtor | Staff and profile sections. |
| utility (map/location) | mlp_location_info_01 | cosmetology_nail, realtor, plumber, disaster_recovery | Map and location. |
| listing (listing) | mlp_listing_01 | realtor | Listing-adjacent. |
| proof (certification) | tp_certification_01 | realtor, plumber, disaster_recovery | Trust/certification expansion. |
| proof (trust band) | tp_trust_band_01 | plumber, disaster_recovery | Trust band. |
| comparison | mlp_comparison_cards_01 | plumber, disaster_recovery | Comparison/versus. |
| proof (reassurance) | tp_reassurance_01 | disaster_recovery | Urgency-proof. |

**Source**: SectionHelperOverlays/ overlays-{industry}.php (second-wave blocks). Section keys are from section registry (MediaListingProfileBatch, FeatureBenefitBatch, TrustProofBatch).

---

## 2b. Pending overlay coverage (T2 remainder – next waves)

| Section purpose family | Target use | Example section_key (from registry) | Wave | Notes |
|------------------------|------------|-------------------------------------|------|------|
| listing | Service-grid | (Use registry: service-grid or listing sections) | 2b | Service grid and listing. |
| timeline / explainer | Process/steps | ptf_steps_01, ptf_timeline_01, ptf_faq_01 | 2c | Process, timeline, FAQ. |
| faq | FAQ | ptf_faq_01, ptf_faq_accordion_01 | 2c | FAQ sections. |
| stats | Stats/highlights | (T3) | T3 | Stats/highlights. |
| legal | Legal/disclaimer | (T3) | T3 | Legal/disclaimer. |

**Example section_key values** must be resolved from the live section registry (Section_Schema, CPT). When authoring additional overlays, look up actual internal_key for each section that has a base helper and add overlay entries for those keys.

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

## 4. Industry × family view (T1 + T2 second-wave)

| Industry | hero | cta | proof | contact | offer | listing (gallery) | profile | location | pricing | comparison | proof (cert/trust) |
|----------|------|-----|-------|---------|-------|-------------------|---------|----------|---------|------------|---------------------|
| cosmetology_nail | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ (mlp_gallery_01) | ✓ (mlp_profile_cards_01) | ✓ | ✓ (fb_package_summary_01) | — | — |
| realtor | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ (mlp_listing_01) | ✓ (mlp_profile_summary_01) | ✓ | — | — | ✓ (tp_certification_01) |
| plumber | ✓ | ✓ | ✓ | ✓ | ✓ | — | — | ✓ | ✓ (fb_package_summary_01) | ✓ (mlp_comparison_cards_01) | ✓ (tp_trust_band_01, tp_certification_01) |
| disaster_recovery | ✓ | ✓ | ✓ | ✓ | ✓ | — | — | ✓ | — | ✓ (mlp_comparison_cards_01) | ✓ (tp_trust_band_01, tp_certification_01, tp_reassurance_01) |

**T2 second-wave** (Prompt 401): Gallery, pricing, profile, location, listing, comparison, and expanded proof (certification, trust band, reassurance) are now covered for the targeted industries. **Pending**: Additional T2/T3 families (timeline, FAQ, stats, legal) per industry-helper-overlay-expansion-plan.

---

## 5. Cross-references

- [industry-helper-overlay-expansion-plan.md](../operations/industry-helper-overlay-expansion-plan.md) — tiers, waves, consistency rules, scaffolding.
- [industry-overlay-catalog.md](industry-overlay-catalog.md) — loading, scope, and relation to base helpers.
- [industry-section-helper-overlay-schema.md](../schemas/industry-section-helper-overlay-schema.md) — overlay object shape and allowed regions.
- [section-template-category-taxonomy-contract.md](../contracts/section-template-category-taxonomy-contract.md) — section_purpose_family and category.
