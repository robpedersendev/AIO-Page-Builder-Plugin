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

---

## 5. Goal page one-pager overlays (Prompt 508)

- **Goals (launch set)**: calls, bookings, estimates, consultations, valuations, lead_capture.
- **Page keys covered**: pt_home_conversion_01, pt_contact_request_01, pt_offerings_compare_01 (high-impact home, contact, valuation/compare).
- **Source directory**: `plugin/src/Domain/Industry/Docs/GoalPageOnePagerOverlays/`.
- **Files**: calls-goal-onepager.php, bookings-goal-onepager.php, estimates-goal-onepager.php, consultations-goal-onepager.php, valuations-goal-onepager.php, lead-capture-goal-onepager.php.
- **Registry**: Goal_Page_OnePager_Overlay_Registry (load, get(goal_key, page_key), get_for_goal). Composition order when composer is extended: base → industry → subtype → goal. See conversion-goal-page-onepager-overlay-contract.md and conversion-goal-page-onepager-overlay-schema.md.

---

## 6. Goal caution rules (Prompt 510)

- **Goals (launch set)**: calls, bookings, estimates, consultations, valuations, lead_capture.
- **Source**: `plugin/src/Domain/Industry/Registry/GoalCautionRules/goal-caution-rule-definitions.php`.
- **Registry**: Goal_Caution_Rule_Registry (load, get(goal_rule_key), get_for_goal(goal_key), get_all). Industry_Compliance_Warning_Resolver::get_for_display( industry_key, subtype_key, goal_key ) appends goal rules when goal_key is non-empty and registry is set. Composition order: industry → subtype → goal. Refinement areas: urgency_language, conversion_pressure, claim_phrasing, form_promises, valuation_estimate_posture. See conversion-goal-caution-rule-contract.md and industry-compliance-rule-catalog.md.

---

## 7. Goal style preset overlays (Prompt 512)

- **Goals (launch set)**: calls, bookings, estimates, consultations, valuations, lead_capture.
- **Source**: `plugin/src/Domain/Industry/Registry/StylePresets/GoalOverlays/goal-style-preset-overlay-definitions.php`.
- **Registry**: Goal_Style_Preset_Overlay_Registry (load, get, get_for_goal, get_overlays_for_preset). Overlays refine target_preset_ref (e.g. realtor_warm, plumber_trust) with optional token_values and component_override_refs. Application merges goal overlay when conversion_goal_key is set and overlay exists for the applied preset. No raw CSS. See conversion-goal-style-preset-contract.md and industry-style-preset-catalog.md.

---

## 8. Secondary-goal starter-bundle overlays (Prompt 541, 542)

- **Purpose:** Bounded mixed-funnel refinement when profile has both primary and secondary conversion goals. Primary-goal overlays remain authoritative; secondary adds low-weight nuance only.
- **Seed pairs:** calls + lead_capture, bookings + consultations, estimates + calls, consultations + lead_capture.
- **Source**: `plugin/src/Domain/Industry/Registry/StarterBundles/SecondaryGoalOverlays/` (calls-lead-capture.php, bookings-consultation.php, estimates-calls.php, consultation-lead-nurture.php).
- **Registry**: Secondary_Goal_Starter_Bundle_Overlay_Registry (container key `secondary_goal_starter_bundle_overlay_registry`). load(array), get(primary_goal_key, secondary_goal_key, bundle_key?), get_for_primary_secondary(), list_all(). Invalid or duplicate overlays skipped at load.
- **Fallback:** When no secondary goal or no matching overlay, primary-goal-only (or base) bundle behavior. See secondary-goal-starter-bundle-contract.md and secondary-goal-starter-bundle-schema.md.

---

## 9. Secondary-goal section-helper overlays (Prompt 543, 544)

- **Purpose:** Mixed-funnel section guidance when profile has both primary and secondary conversion goals. Composition order: base → industry → subtype → primary goal overlay → **secondary goal overlay**.
- **Seed pairs:** calls + lead_capture, bookings + consultations, estimates + calls, consultations + lead_capture. Sections: hero, CTA, contact, lead magnet, consultation, estimate-request families.
- **Source**: `plugin/src/Domain/Industry/Docs/SecondaryGoalSectionHelperOverlays/` (calls-lead-capture.php, bookings-consultation.php, estimates-calls.php, consultation-lead-nurture.php).
- **Registry**: Secondary_Goal_Section_Helper_Overlay_Registry (container key `secondary_goal_section_helper_overlay_registry`). load(array), get(primary_goal_key, secondary_goal_key, section_key), get_for_primary_secondary(), get_all(). Invalid or duplicate overlays skipped at load.
- **Fallback:** When no secondary goal or no overlay for (primary, secondary, section), use prior layer (primary-goal or subtype/industry/base). See secondary-goal-helper-overlay-contract.md and secondary-goal-helper-overlay-schema.md.
