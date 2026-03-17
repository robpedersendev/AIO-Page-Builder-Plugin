# Industry Conversion-Goal Section-Helper Overlay Catalog (Prompt 506)

**Spec**: conversion-goal-helper-overlay-schema.md; conversion-goal-helper-overlay-contract.md.

This appendix lists built-in **conversion-goal** section-helper overlays. Goal overlays layer on top of base, industry, and subtype overlays. Composition order: base → industry overlay → subtype overlay → goal overlay. Allowed regions: tone_notes, cta_usage_notes, compliance_cautions, media_notes, seo_notes, additive_blocks.

---

## 1. Scope and goals

- **Goals (launch set)**: calls, bookings, estimates, consultations, valuations, lead_capture.
- **Section keys covered**: Hero (hero_conv_02), CTA (cta_booking_01), contact form (gc_contact_form_01). Focus on sections where funnel intent (call vs book vs estimate etc.) changes guidance.
- **Source directory**: `plugin/src/Domain/Industry/Docs/GoalSectionHelperOverlays/`.
- **Files**: calls-goal-overlays.php, bookings-goal-overlays.php, estimates-goal-overlays.php, consultations-goal-overlays.php, valuations-goal-overlays.php, lead-capture-goal-overlays.php.

---

## 2. Loading

The registry loads built-in definitions via `Goal_Section_Helper_Overlay_Registry::get_builtin_overlay_definitions()`, which reads from the files above. Bootstrap (Industry_Packs_Module) registers the registry under `CONTAINER_KEY_GOAL_SECTION_HELPER_OVERLAY_REGISTRY`. Invalid or duplicate (goal_key, section_key) entries are skipped. Only status `active` overlays are applied. When Industry_Helper_Doc_Composer is extended to support goal overlay (future), it will apply goal overlay when conversion_goal_key is present and overlay exists.

---

## 3. Goal overlay focus (by goal)

- **calls**: Call-first CTAs; click-to-call; phone number prominence; callback expectation.
- **bookings**: Book/schedule/reserve CTAs; scheduler or calendar links; availability signals.
- **estimates**: Quote/estimate request CTAs; response expectation; trust cues for quote flow.
- **consultations**: Consultation/schedule-a-call CTAs; value of session; consultation request flow.
- **valuations**: Valuation/CMA/home-value CTAs; valuation tool or lead magnet entry.
- **lead_capture**: Sign up, get the guide, download CTAs; value exchange; form/signup flow.

---

## 4. Relation to other overlays

Base, industry, and subtype section-helper overlays remain authoritative. Goal overlays refine only where conversion goal changes section guidance; they do not replace prior layers. When no conversion goal is set or goal_key is invalid, composition is base + industry + subtype only (goal overlay skipped). See conversion-goal-helper-overlay-contract.md and conversion-goal-helper-overlay-schema.md.
