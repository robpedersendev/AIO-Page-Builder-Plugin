# Industry Starter Bundle Catalog

**Spec**: industry-starter-bundle-schema.md; industry-pack-extension-contract. **Prompt**: 387.

This appendix lists built-in starter bundle definitions loaded by the Industry Starter Bundle Registry. Bundles are curated overlays that recommend page families, template refs, section emphasis, and optional CTA/style/LPagery refs for each industry. They do not replace the core section or page template library.

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

## 5. Loading

Bundles are loaded by **Industry_Packs_Module** via `Industry_Starter_Bundle_Registry::get_builtin_definitions()` (sourced from **StarterBundles/Builtin_Starter_Bundles.php**). Invalid definitions are skipped at load. Resolution of page/section refs and token/CTA/LPagery refs is done by consumers when applying a bundle; the registry only stores and serves definitions.
