# Industry Page One-Pager Overlay Coverage Matrix

**Spec**: industry-page-onepager-overlay-schema.md; industry-page-onepager-overlay-expansion-plan.md. **Prompt**: 382.

This appendix shows which page families and example template keys have **seeded** industry page one-pager overlays vs **pending** (planned for expansion). Template keys are from the page template registry (internal_key); families from page-template-category-taxonomy-contract §3.

---

## 1. Seeded overlay coverage (T1)

| Page family | Example page_template_key (seeded) | Industries with overlay | Notes |
|-------------|------------------------------------|-------------------------|-------|
| home | pt_home_conversion_01 | cosmetology_nail, realtor, plumber, disaster_recovery | Home/conversion. |
| about | pt_about_story_01 | cosmetology_nail, realtor, plumber, disaster_recovery | About/story. |
| contact | pt_contact_request_01 | cosmetology_nail, realtor, plumber, disaster_recovery | Contact/request. |
| services | pt_services_overview_01 | cosmetology_nail, realtor, plumber, disaster_recovery | Services overview. |

**Source**: industry-overlay-catalog.md §5; PageOnePagerOverlays/ overlays-{industry}.php. Only template keys that actually have overlay definitions in the registry are listed above.

---

## 2. Second-wave overlay coverage (T2 – authored Prompt 402)

| Page family / purpose | Example page_template_key (authored) | Industries with overlay | Notes |
|------------------------|--------------------------------------|-------------------------|-------|
| booking | child_detail_service_booking_01 | cosmetology_nail, plumber | Booking and appointment. |
| pricing / offerings | pt_offerings_overview_01, pt_offerings_compare_01 | cosmetology_nail, plumber, realtor | Pricing and comparison. |
| location / directions | pt_contact_directions_01 | cosmetology_nail | Location and directions. |
| gallery | pt_home_media_01 | cosmetology_nail | Gallery and portfolio. |
| service-detail | child_detail_treatment_detail_01, child_detail_service_conversion_01 | cosmetology_nail, plumber, disaster_recovery | Service/treatment detail. |
| neighborhood | hub_geo_neighborhood_01 | realtor | Neighborhood and market area. |
| buyer / seller | pt_buyer_guide_01, pt_services_value_01 | realtor | Buyer guide and seller value. |
| local-market | hub_geo_coverage_listing_01 | realtor | Coverage and listing. |
| service-area | hub_geo_service_area_01 | plumber, disaster_recovery | Service-area hub. |
| financing / trust | pt_offerings_compare_01, hub_geo_area_trust_01 | plumber, disaster_recovery | Financing and trust. |
| insurance-assistance | pt_support_help_02 | disaster_recovery | Insurance and claims help. |

**Source**: PageOnePagerOverlays/ overlays-{industry}.php (second-wave blocks). Template keys from page template registry (Top Level, Child Detail, Geographic Hub).

---

## 2b. Pending overlay coverage (T2 remainder – next waves)

| Page family / purpose | Target use | Wave | Notes |
|------------------------|------------|------|-------|
| trust / certification (dedicated) | Trust-led, certification child_detail | 2d | Additional trust/certification pages. |
| locations, products, offerings | Hub and child_detail | T3 | As needed. |
| faq, comparison, profiles, events | FAQ, comparison, profiles, events | T3 | As needed. |

**Example page_template_key values** must be resolved from the live page template registry when authoring additional overlays.

---

## 3. Later waves (T3–T4)

| Page family | Target use | Wave |
|-------------|------------|------|
| locations, products, offerings | Hub and child_detail | T3 |
| faq, comparison, profiles, events | FAQ, comparison, profiles, events | T3 |
| privacy, terms, accessibility | Legal and accessibility | T4 |
| informational, other | Uncategorized | T4 |

---

## 4. Industry × family view (T1 + T2 second-wave)

| Industry | home | about | contact | services | booking | pricing | location | gallery | service-detail | neighborhood | buyer/seller | local-market | service-area | financing/trust | insurance |
|----------|------|-------|---------|----------|---------|---------|----------|---------|----------------|---------------|--------------|--------------|--------------|-----------------|-----------|
| cosmetology_nail | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | — | — | — | — | — |
| realtor | ✓ | ✓ | ✓ | ✓ | — | ✓ | — | — | — | ✓ | ✓ | ✓ | — | — | — |
| plumber | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | — | ✓ | — | — | — | ✓ | ✓ | — |
| disaster_recovery | ✓ | ✓ | ✓ | ✓ | — | — | — | — | ✓ | — | — | — | ✓ | ✓ | ✓ |

**T2 second-wave** (Prompt 402): Booking, pricing, location, gallery, service-detail, neighborhood, buyer/seller, local-market, service-area, financing/trust, and insurance-assistance overlays are now covered for the targeted industries.

---

## 5. Cross-references

- [industry-page-onepager-overlay-expansion-plan.md](../operations/industry-page-onepager-overlay-expansion-plan.md) — tiers, waves, consistency rules, scaffolding.
- [industry-overlay-catalog.md](industry-overlay-catalog.md) — loading, scope, and relation to base one-pagers.
- [industry-page-onepager-overlay-schema.md](../schemas/industry-page-onepager-overlay-schema.md) — overlay object shape and allowed regions.
- [page-template-category-taxonomy-contract.md](../contracts/page-template-category-taxonomy-contract.md) — template_family and page_purpose_family.
