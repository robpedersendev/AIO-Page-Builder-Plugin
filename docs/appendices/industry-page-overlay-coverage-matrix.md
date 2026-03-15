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

## 2. Pending overlay coverage (T2 – next waves)

| Page family / purpose | Target use | Example template keys (from registry) | Wave | Notes |
|------------------------|------------|---------------------------------------|------|-------|
| booking-focused | Booking, appointment pages | (Use registry: booking intent or template_family) | 2a | Booking and appointment flows. |
| valuation-focused | Valuation, quote request | (Use registry: valuation/quote intent) | 2a | Valuation and quote CTAs. |
| emergency / service | Emergency, 24/7, service-area | (Use registry: emergency/service-area) | 2b | Emergency and service-area pages. |
| local / service-area | Location, service area, coverage | (Use registry: locations, service-area) | 2b | Local and service-area focus. |
| neighborhood | Neighborhood, market area | (Use registry: realtor/neighborhood) | 2c | Neighborhood and market pages. |
| gallery | Gallery, portfolio | (Use registry: gallery/portfolio) | 2c | Gallery and portfolio pages. |
| financing | Financing, payment, trust | (Use registry: financing/trust) | 2c | Financing and trust pages. |
| trust / certification | Trust-led, certification, authority | (Use registry: trust/certification child_detail) | 2d | Trust and certification pages. |

**Example page_template_key values** must be resolved from the live page template registry. This matrix references family/purpose; when authoring, look up actual internal_key for each template that has a base one-pager and add overlay entries for those keys.

---

## 3. Later waves (T3–T4)

| Page family | Target use | Wave |
|-------------|------------|------|
| locations, products, offerings | Hub and child_detail | T3 |
| faq, comparison, profiles, events | FAQ, comparison, profiles, events | T3 |
| privacy, terms, accessibility | Legal and accessibility | T4 |
| informational, other | Uncategorized | T4 |

---

## 4. Industry × family view (seeded only)

| Industry | home | about | contact | services |
|----------|------|-------|---------|----------|
| cosmetology_nail | ✓ | ✓ | ✓ | ✓ |
| realtor | ✓ | ✓ | ✓ | ✓ |
| plumber | ✓ | ✓ | ✓ | ✓ |
| disaster_recovery | ✓ | ✓ | ✓ | ✓ |

**Pending**: All T2 families (booking, valuation, emergency, local, neighborhood, gallery, financing, trust/certification) have no overlay rows yet. Add rows when overlays are authored per industry-page-onepager-overlay-expansion-plan.

---

## 5. Cross-references

- [industry-page-onepager-overlay-expansion-plan.md](../operations/industry-page-onepager-overlay-expansion-plan.md) — tiers, waves, consistency rules, scaffolding.
- [industry-overlay-catalog.md](industry-overlay-catalog.md) — loading, scope, and relation to base one-pagers.
- [industry-page-onepager-overlay-schema.md](../schemas/industry-page-onepager-overlay-schema.md) — overlay object shape and allowed regions.
- [page-template-category-taxonomy-contract.md](../contracts/page-template-category-taxonomy-contract.md) — template_family and page_purpose_family.
