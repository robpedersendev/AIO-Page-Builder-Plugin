# Industry Subtype Section-Helper Overlay Catalog (Prompt 425)

**Spec**: subtype-section-helper-overlay-schema.md; industry-subtype-extension-contract.md; industry-section-helper-overlay-schema.md.

This appendix lists built-in **subtype-specific** section-helper overlays. Subtype overlays layer on top of **base** section helper and **industry** section-helper overlay. Composition order: base → industry overlay → subtype overlay. Allowed regions are the same as industry overlays (tone_notes, cta_usage_notes, compliance_cautions, media_notes, seo_notes, additive_blocks).

---

## 1. Scope and subtypes

- **Subtypes (launch set)**: cosmetology_nail_luxury_salon, cosmetology_nail_mobile_tech; realtor_buyer_agent, realtor_listing_agent; plumber_residential, plumber_commercial; disaster_recovery_residential, disaster_recovery_commercial.
- **Section keys covered**: Hero (hero_conv_02), CTA (cta_booking_01), location (mlp_location_info_01), listing (mlp_listing_01), contact form (gc_contact_form_01), certification (tp_certification_01), reassurance (tp_reassurance_01). Focus on sections materially affected by subtype nuance.
- **Source directory**: `plugin/src/Domain/Industry/Docs/SubtypeSectionHelperOverlays/`.
- **Files**: cosmetology-nail-subtype-overlays.php, realtor-subtype-overlays.php, plumber-subtype-overlays.php, disaster-recovery-subtype-overlays.php.

---

## 2. Loading

The registry loads built-in definitions via `Subtype_Section_Helper_Overlay_Registry::get_builtin_overlay_definitions()`, which reads from the files above. Bootstrap (Industry_Packs_Module) registers the registry under `CONTAINER_KEY_SUBTYPE_SECTION_HELPER_OVERLAY_REGISTRY` and loads that list. Invalid or duplicate (subtype_key, section_key) entries are skipped. Only status `active` overlays are applied. Industry_Helper_Doc_Composer applies subtype overlay when optional third parameter `subtype_key` is provided and subtype overlay registry is set.

---

## 3. Subtype overlay focus (by subtype)

- **Cosmetology/nail — Luxury salon**: Elevated tone, experience and in-salon emphasis; reserve/book experience CTAs; location/visit details.
- **Cosmetology/nail — Mobile tech**: Convenience and “come to you”; service area and travel; book at your location CTAs.
- **Realtor — Buyer agent**: Buyer-focused tone; search support, buyer updates, buyer consultation CTAs; listing section framed for saved homes/showings.
- **Realtor — Listing agent**: Seller-focused tone; home value, list with me, listing consultation CTAs; listing section framed for sold/marketing.
- **Plumber — Residential**: Homeowner-focused; repairs, emergency, small property; request service / schedule repair CTAs.
- **Plumber — Commercial**: Business-focused; maintenance contracts, compliance; request quote, commercial contact CTAs; certification/compliance notes.
- **Disaster recovery — Residential**: Homeowner and insurance focus; rapid response, assessment; emergency/assessment CTAs; reassurance.
- **Disaster recovery — Commercial**: Business continuity, commercial-scale; commercial assessment and 24/7 commercial line CTAs; commercial experience.

---

## 4. Relation to industry overlays

Parent-industry section-helper overlays remain the first overlay layer. Subtype overlays refine only where subtype nuance matters; they do not replace industry overlays. When no subtype is selected or subtype_key is invalid, composition is base + industry only (subtype overlay skipped). See [subtype-section-helper-overlay-schema.md](../schemas/subtype-section-helper-overlay-schema.md) and [industry-subtype-extension-contract.md](../contracts/industry-subtype-extension-contract.md).
