# Industry Starter Bundle Catalog

**Spec**: industry-starter-bundle-schema.md; industry-pack-extension-contract. **Prompt**: 387.

This appendix lists built-in starter bundle definitions loaded by the Industry Starter Bundle Registry. Bundles are curated overlays that recommend page families, template refs, section emphasis, and optional CTA/style/LPagery refs for each industry. They do not replace the core section or page template library. To compare two or more bundles side by side (parent, subtype, or alternate variants), use the **Bundle comparison** admin screen (Industry → Bundle comparison; Prompt 450).

---

## 1. Cosmetology / Nail (`cosmetology_nail_starter`)

- **Label**: Salon & Nail Starter  
- **Summary**: A practical starting set for salon and nail businesses: home, services overview, about, and contact with booking and gallery emphasis.  
- **Recommended page families**: home, services, about, contact, offerings.  
- **Recommended page templates**: pt_home_conversion_01, pt_services_overview_01, pt_about_story_01, pt_contact_request_01.  
- **Recommended sections** (emphasis): hero_conv_02, tp_testimonial_02, cta_booking_01, fb_benefit_band_01, mlp_card_grid_01, lpu_contact_panel_01.  
- **Refs**: token_preset cosmetology_elegant, cta_guidance book_now, lpagery cosmetology_nail_01.  
- **Source**: `plugin/src/Domain/Industry/Registry/StarterBundles/cosmetology-nail-starter.php`.

---

## 2. Realtor (`realtor_starter`)

- **Label**: Realtor Starter  
- **Summary**: A practical starting set for real estate agents and brokerages: home, services, about, and contact with valuation and consultation emphasis.  
- **Recommended page families**: home, services, about, contact, resource, buyer_guide.  
- **Recommended page templates**: pt_home_trust_01, pt_services_overview_01, pt_about_team_01, pt_contact_request_01.  
- **Recommended sections** (emphasis): hero_cred_01, tp_testimonial_01, cta_consultation_01, fb_why_choose_01, ptf_faq_01, mlp_team_grid_01, lpu_contact_panel_01.  
- **Refs**: token_preset realtor_warm, cta_guidance valuation_request, lpagery realtor_01.  
- **Source**: `plugin/src/Domain/Industry/Registry/StarterBundles/realtor-starter.php`.

---

## 3. Plumber (`plumber_starter`)

- **Label**: Plumber Starter  
- **Summary**: A practical starting set for plumbing and trade businesses: home, services overview, contact, and FAQ with call-now and scheduled-service CTAs.  
- **Recommended page families**: home, services, contact, faq.  
- **Recommended page templates**: pt_home_conversion_01, pt_services_overview_01, pt_contact_request_01, pt_faq_support_01.  
- **Recommended sections** (emphasis): hero_conv_02, tp_trust_band_01, cta_booking_01, ptf_how_it_works_01, cta_consultation_01, lpu_contact_panel_01.  
- **Refs**: token_preset plumber_trust, cta_guidance call_now, lpagery plumber_01.  
- **Source**: `plugin/src/Domain/Industry/Registry/StarterBundles/plumber-starter.php`.

---

## 4. Disaster Recovery (`disaster_recovery_starter`)

- **Label**: Disaster Recovery Starter  
- **Summary**: A practical starting set for restoration and disaster recovery: home, services, and contact with 24/7 and claim-assistance emphasis.  
- **Recommended page families**: home, services, contact, support.  
- **Recommended page templates**: pt_home_conversion_01, pt_services_overview_01, pt_contact_request_01.  
- **Recommended sections** (emphasis): hero_conv_02, tp_trust_band_01, cta_consultation_01, ptf_how_it_works_01, fb_benefit_band_01, lpu_contact_panel_01.  
- **Refs**: token_preset disaster_recovery_urgency, cta_guidance emergency_dispatch, lpagery disaster_recovery_01.  
- **Source**: `plugin/src/Domain/Industry/Registry/StarterBundles/disaster-recovery-starter.php`.

---

## 5. Subtype starter bundles (Prompt 429)

Subtype-scoped bundles are loaded from **StarterBundles/Subtypes/*.php** and merged with industry bundles. They have an optional **subtype_key**; `get_for_industry(industry_key, subtype_key)` returns subtype-scoped bundles when any exist for that (industry, subtype), otherwise falls back to industry-scoped bundles. When `subtype_key` is empty, only industry-scoped bundles (no subtype_key) are returned.

| Subtype | Bundle key | Label |
|---------|------------|-------|
| cosmetology_nail_luxury_salon | cosmetology_nail_luxury_salon_starter | Luxury Nail Salon Starter |
| cosmetology_nail_mobile_tech | cosmetology_nail_mobile_tech_starter | Mobile Nail Tech Starter |
| realtor_buyer_agent | realtor_buyer_agent_starter | Buyer Agent Starter |
| realtor_listing_agent | realtor_listing_agent_starter | Listing Agent Starter |
| plumber_residential | plumber_residential_starter | Residential Plumber Starter |
| plumber_commercial | plumber_commercial_starter | Commercial Plumber Starter |
| disaster_recovery_residential | disaster_recovery_residential_starter | Residential Restoration Starter |
| disaster_recovery_commercial | disaster_recovery_commercial_starter | Commercial Restoration Starter |

**Source directory**: `plugin/src/Domain/Industry/Registry/StarterBundles/Subtypes/`. See subtype-starter-bundle-contract.md. **Bundle-to-Build Plan:** Subtype-scoped bundles can be converted to draft Build Plans via **Industry_Subtype_Starter_Bundle_To_Build_Plan_Service** (container key `industry_subtype_starter_bundle_to_build_plan_service`); the plan stores `source_starter_bundle_key` and `source_industry_subtype_key` when the bundle is subtype-scoped. Parent-only fallback applies when the requested bundle is missing or inactive and `industry_key` is provided in context.

---

## 6. Secondary-goal starter-bundle overlays (Prompt 541, 542)

When a profile has both primary and secondary conversion goals, optional **secondary-goal starter-bundle overlays** add low-weight refinement (section emphasis, CTA posture, funnel shape). Primary-goal overlays remain authoritative. Seed overlays (bounded mixed-funnel pairs): calls + lead_capture, bookings + consultations, estimates + calls, consultations + lead_capture. **Source directory:** `plugin/src/Domain/Industry/Registry/StarterBundles/SecondaryGoalOverlays/`. **Registry:** Secondary_Goal_Starter_Bundle_Overlay_Registry (container key `secondary_goal_starter_bundle_overlay_registry`). See [secondary-goal-starter-bundle-contract.md](../contracts/secondary-goal-starter-bundle-contract.md) and [industry-goal-overlay-catalog.md](industry-goal-overlay-catalog.md).

---

## 6.1 Combined subtype+goal starter-bundle overlays (Prompt 551, 552)

For **exceptional** (subtype, goal) pairs, optional **combined subtype+goal starter-bundle overlays** may refine a bundle when both industry_subtype_key and conversion_goal_key are set. Combined overlays are **bounded and admission-gated**; they are not the default. Resolution order: subtype bundle → goal overlay(s) → combined overlay (when present). Fallback: when no or invalid combined overlay, use subtype + goal layers only.

**Seed set (Prompt 552):** Mobile nail + booking, buyer-focused realtor + consultation, commercial plumber + estimate, commercial restoration + emergency-call posture.

| Subtype | Goal | Overlay key | Admission rationale |
|---------|------|--------------|----------------------|
| cosmetology_nail_mobile_tech | bookings | cosmetology_nail_mobile_tech_bookings | Mobile nail tech + booking: mobile-first booking flow not expressed by subtype-only or goal-only alone. |
| realtor_buyer_agent | consultations | realtor_buyer_agent_consultations | Buyer agent + consultation: buyer consultation/discovery flow is high-value joint scenario. |
| plumber_commercial | estimates | plumber_commercial_estimates | Commercial plumber + estimate: project scoping, multi-site estimate flow. |
| disaster_recovery_commercial | calls | disaster_recovery_commercial_calls | Commercial restoration + calls: emergency-call/24/7 dispatch posture. |

**Source directory:** `plugin/src/Domain/Industry/Registry/StarterBundles/SubtypeGoalOverlays/`. **Registry:** Subtype_Goal_Starter_Bundle_Overlay_Registry (container key `subtype_goal_starter_bundle_overlay_registry`). See [subtype-goal-starter-bundle-contract.md](../contracts/subtype-goal-starter-bundle-contract.md) and [subtype-goal-starter-bundle-schema.md](../schemas/subtype-goal-starter-bundle-schema.md).

---

## 7. Loading

Bundles are loaded by **Industry_Packs_Module** via `Industry_Starter_Bundle_Registry::get_builtin_definitions()` (sourced from **StarterBundles/Builtin_Starter_Bundles.php**, which merges industry files and **Subtypes/*.php**). Invalid definitions are skipped at load. Resolution of page/section refs and token/CTA/LPagery refs is done by consumers when applying a bundle; the registry only stores and serves definitions.
