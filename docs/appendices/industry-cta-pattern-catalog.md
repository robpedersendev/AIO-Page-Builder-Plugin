# Industry CTA Pattern Catalog (Prompt 358)

**Spec:** industry-cta-pattern-contract.md; industry-pack-schema.md.  
**Purpose:** Lists built-in CTA patterns loaded by Industry_CTA_Pattern_Registry. Packs reference these by pattern_key in preferred_cta_patterns, required_cta_patterns, discouraged_cta_patterns.

---

## 1. Loading

- **Source:** `plugin/src/Domain/Industry/Registry/CTAPatterns/cta-pattern-definitions.php`.
- **Registry:** Industry_CTA_Pattern_Registry::get_builtin_definitions(). Bootstrap (Industry_Packs_Module) registers the registry under CONTAINER_KEY_CTA_PATTERN_REGISTRY and calls load() with that list.
- **Validation:** Invalid or duplicate pattern_key skipped (first wins). Required: pattern_key (^[a-z0-9_]+$, max 64), name.

---

## 2. Pattern keys and purpose

| pattern_key | Name | Typical use |
|-------------|------|-------------|
| book_now | Book now / Appointment | Scheduling, appointments; cosmetology, plumber, many verticals. |
| gallery_to_booking | Gallery to booking | After gallery/portfolio; cosmetology, creative services. |
| consult | Consultation request | Discovery call, professional services; realtor, cosmetology. |
| valuation_request | Valuation / Quote request | Property or project valuation; realtor. |
| call_now | Call now | Immediate phone; plumber, local service, support. |
| emergency_dispatch | Emergency / 24/7 dispatch | Disaster recovery, plumbing emergency. |
| claim_assistance | Claims / Insurance assistance | Disaster restoration, insurance workflow. |
| scheduled_service | Scheduled service request | Non-emergency visit; plumber, trades. |
| quote_request | Quote / Estimate request | Generic quote; reusable across industries. |

---

## 3. Pack references (first four industries)

- **cosmetology_nail:** preferred book_now, gallery_to_booking, consult; required book_now; discouraged emergency_dispatch, claim_assistance.
- **realtor:** preferred valuation_request, consult, book_now; required valuation_request; discouraged emergency_dispatch, claim_assistance.
- **plumber:** preferred call_now, book_now, emergency_dispatch; required call_now; discouraged valuation_request, gallery_to_booking.
- **disaster_recovery:** preferred emergency_dispatch, call_now, claim_assistance; required emergency_dispatch, call_now; discouraged valuation_request, gallery_to_booking.

All of the above keys must exist in the registry for pack references to resolve. See industry-pack-*.php under Industry/Registry/Packs/.
