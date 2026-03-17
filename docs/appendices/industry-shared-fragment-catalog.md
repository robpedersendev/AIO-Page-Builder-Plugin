# Industry Shared Fragment Catalog (Prompt 476)

**Spec**: [industry-shared-fragment-schema.md](../schemas/industry-shared-fragment-schema.md); [industry-shared-fragment-contract.md](../contracts/industry-shared-fragment-contract.md).

**Purpose**: Catalog of built-in shared fragments for cross-industry reuse. Conservative seed set; industry-specific overlays remain primary.

---

## 1. CTA notes

| fragment_key | Allowed consumers | Purpose |
|--------------|--------------------|---------|
| cta_primary_contact_above_fold | section_helper_overlay, page_onepager_overlay, cta_guidance | Single primary contact/conversion CTA above the fold. |
| cta_lead_capture_consent | section_helper_overlay, page_onepager_overlay, cta_guidance | Lead capture expectations and consent. |
| cta_booking_estimate_clarity | section_helper_overlay, page_onepager_overlay, cta_guidance | Booking/estimate/quote CTA clarity and next-step. |

---

## 2. SEO segments

| fragment_key | Allowed consumers | Purpose |
|--------------|--------------------|---------|
| seo_h1_unique_per_page | section_helper_overlay, page_onepager_overlay, seo_guidance | One primary H1 per page; logical hierarchy. |
| seo_meta_description_length | section_helper_overlay, page_onepager_overlay, seo_guidance | Meta description length and content. |

---

## 3. Caution snippets

| fragment_key | Allowed consumers | Purpose |
|--------------|--------------------|---------|
| caution_testimonial_genuine | section_helper_overlay, page_onepager_overlay, compliance_caution | Testimonials genuine; consent. |
| caution_pricing_disclosure | section_helper_overlay, page_onepager_overlay, compliance_caution | Pricing clarity and conditions. |
| caution_local_accuracy | section_helper_overlay, page_onepager_overlay, compliance_caution | Local/service-area accuracy; NAP. |

---

## 4. Helper and page guidance

| fragment_key | Type | Allowed consumers | Purpose |
|--------------|------|--------------------|---------|
| guidance_trust_proof | helper_guidance | section_helper_overlay | Trust/proof relevance in section. |
| guidance_contact_lead_handling | page_guidance | page_onepager_overlay | Contact/lead page expectations. |

---

## 5. Intended consumers

- **section_helper_overlay**: Industry or subtype section-helper overlay regions (cta_usage_notes, seo_notes, compliance_cautions, etc.).
- **page_onepager_overlay**: Industry or subtype page one-pager overlay regions.
- **cta_guidance**: CTA pattern or pack-level guidance.
- **seo_guidance**: SEO guidance rule or pack reference.
- **compliance_caution**: Compliance rule or overlay compliance_cautions.

Resolution via **Industry_Shared_Fragment_Resolver::resolve( fragment_key, consumer_scope )**. Invalid or out-of-scope refs return null.

---

## 6. Bounded adoption (Prompt 477)

- **cosmetology_nail** section-helper overlay: tp_badge_01 uses **compliance_cautions_fragment_ref** => `caution_testimonial_genuine`. Composed output appends fragment content to compliance_cautions. See [industry-shared-fragment-adoption-review.md](../qa/industry-shared-fragment-adoption-review.md).

---

## 7. Cross-references

- [industry-shared-fragment-schema.md](../schemas/industry-shared-fragment-schema.md)
- [industry-shared-fragment-contract.md](../contracts/industry-shared-fragment-contract.md)
- Built-in definitions: `plugin/src/Domain/Industry/Registry/SharedFragments/builtin-fragments.php`
