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

---

## 5. Subtype page one-pager overlays (Prompt 427)

- **Scope**: Subtype-specific **page** one-pager overlays layer on top of base page one-pagers and **industry** page one-pager overlays. Composition order: base → industry overlay → subtype overlay. Allowed regions match industry page overlays: hierarchy_hints, cta_strategy, lpagery_seo_notes, compliance_cautions, additive_blocks.
- **Subtypes (launch set)**: Same as section overlays (§1). Page families in focus: home (pt_home_conversion_01), contact (pt_contact_request_01), services (pt_services_overview_01) where subtype nuance materially changes page-purpose or CTA emphasis.
- **Source directory**: `plugin/src/Domain/Industry/Docs/SubtypePageOnePagerOverlays/`. Files: cosmetology-nail-subtype-onepager.php, realtor-subtype-onepager.php, plumber-subtype-onepager.php, disaster-recovery-subtype-onepager.php.
- **Loading**: `Subtype_Page_OnePager_Overlay_Registry::get_builtin_overlay_definitions()` returns definitions from those files. Bootstrap registers the registry under `CONTAINER_KEY_SUBTYPE_PAGE_ONEPAGER_OVERLAY_REGISTRY` and passes it into `Industry_Page_OnePager_Composer`. The composer applies the subtype overlay when `compose($page_template_key, $industry_key, $subtype_key)` is called with a non-empty `$subtype_key` and the subtype registry is set. Invalid or duplicate (subtype_key, page_template_key) entries are skipped.
- **Relation to industry page overlays**: Parent-industry page one-pager overlays remain the first overlay layer. Subtype page overlays refine only where subtype nuance matters (e.g. buyer vs seller, mobile vs luxury, residential vs commercial). When no subtype is selected or subtype_key is invalid, composition is base + industry only. See [subtype-page-onepager-overlay-schema.md](../schemas/subtype-page-onepager-overlay-schema.md) and [industry-subtype-extension-contract.md](../contracts/industry-subtype-extension-contract.md).

---

## 6. Combined subtype+goal starter-bundle overlays (Prompt 551, 552)

Exceptional **combined subtype+goal starter-bundle overlays** (subtype_key + conversion_goal_key) refine starter bundles when both are set. They are **not** part of the section-helper or page one-pager overlay layers; they apply at **bundle-to-plan** conversion. Seed set: mobile nail + booking, buyer realtor + consultation, commercial plumber + estimate, commercial restoration + calls. See [industry-starter-bundle-catalog.md](industry-starter-bundle-catalog.md) §6.1 and [subtype-goal-starter-bundle-contract.md](../contracts/subtype-goal-starter-bundle-contract.md).

---

## 7. Combined subtype+goal doc overlays (Prompt 553, 554)

Exceptional **combined subtype+goal section-helper** and **page one-pager** overlays apply when both subtype and conversion goal are set and a matching overlay exists. Composition order: base → industry → subtype → **combined subtype+goal** (when present). Seed set (bounded):

- **Section-helper:** realtor_buyer_agent + consultations (hero, CTA); cosmetology_nail_mobile_tech + bookings (hero, CTA); disaster_recovery_commercial + calls (hero, CTA). Admission: buyer-consultation flows, mobile-booking flows, commercial emergency-response flows.
- **Page one-pager:** realtor_buyer_agent + consultations (pt_contact_request_01); cosmetology_nail_mobile_tech + bookings (pt_contact_request_01); disaster_recovery_commercial + calls (pt_contact_request_01).

**Source directory:** `plugin/src/Domain/Industry/Docs/SubtypeGoalOverlays/`. **Registries:** Subtype_Goal_Section_Helper_Overlay_Registry (CONTAINER_KEY_SUBTYPE_GOAL_SECTION_HELPER_OVERLAY_REGISTRY), Subtype_Goal_Page_OnePager_Overlay_Registry (CONTAINER_KEY_SUBTYPE_GOAL_PAGE_ONEPAGER_OVERLAY_REGISTRY). Industry_Helper_Doc_Composer and Industry_Page_OnePager_Composer apply combined overlay when compose() is called with non-empty subtype_key and conversion_goal_key. **Fallback:** When no combined overlay exists for (subtype_key, goal_key, section_key or page_key), composition uses subtype + goal layers only. See [subtype-goal-doc-overlay-contract.md](../contracts/subtype-goal-doc-overlay-contract.md) and [subtype-goal-doc-overlay-schema.md](../schemas/subtype-goal-doc-overlay-schema.md).
