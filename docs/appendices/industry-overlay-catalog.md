# Industry Section-Helper Overlay Catalog

**Spec**: industry-section-helper-overlay-schema.md; industry-pack-extension-contract. **Prompt**: 353.

This appendix lists built-in industry section-helper overlays loaded by the Industry Section Helper Overlay Registry. Overlays add or override allowed regions (tone_notes, cta_usage_notes, compliance_cautions, media_notes, seo_notes, additive_blocks) on top of base section helper docs. Base content_body remains authoritative.

---

## 1. Scope and section families

- **Industries**: cosmetology_nail, realtor, plumber, disaster_recovery.
- **Section families covered**: Hero (hero_conv_02), CTA (cta_booking_01), Proof/Trust (tp_badge_01), Contact/Form (gc_contact_form_01), Feature/benefit (gc_offer_value_01).
- **Source directory**: `plugin/src/Domain/Industry/Docs/SectionHelperOverlays/`.
- **Files**: overlays-cosmetology-nail.php, overlays-realtor.php, overlays-plumber.php, overlays-disaster-recovery.php.

---

## 2. Loading

The registry loads built-in definitions via `Industry_Section_Helper_Overlay_Registry::get_builtin_overlay_definitions()`. Bootstrap (Industry_Packs_Module) registers the registry under `CONTAINER_KEY_SECTION_HELPER_OVERLAY_REGISTRY` and calls `load( ... )` with that list. Invalid or duplicate (industry_key, section_key) entries are skipped. Resolution is by industry_key + section_key; only status `active` overlays are applied by Industry_Helper_Doc_Composer.

---

## 3. Overlay content focus

- **Cosmetology/nail**: Booking and consultation CTAs; warm tone; compliance for licenses; gallery/staff context.
- **Realtor**: Valuation and consultation CTAs; professional tone; MLS/board compliance notes; buyer/seller and local focus.
- **Plumber**: Call-now and emergency vs scheduled; trust and licensing; jurisdiction compliance; service-area focus.
- **Disaster recovery**: Emergency and 24/7 response; insurance/claims assistance; certification (e.g. IICRC); urgency and service-area.

---

## 4. Relation to base helpers

Base section helpers live in the Documentation_Registry (section_template_key in source_reference). Industry overlays do not replace base content_body; they merge only in allowed regions. Unknown section_key at resolution is safe (no overlay applied).
