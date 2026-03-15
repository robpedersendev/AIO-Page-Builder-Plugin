# Industry Pack Catalog

**Spec**: industry-pack-schema.md; industry-pack-extension-contract. **Prompts**: 349–351.

This appendix lists built-in industry pack definitions loaded by the Industry Pack Registry. Packs are additive config; they reference CTA patterns, SEO guidance, style presets, and LPagery rules. Invalid refs fail safely at resolution time.

---

## 1. Cosmetology / Nail (`cosmetology_nail`)

- **Name**: Cosmetology / Nail Technician  
- **Summary**: Salon, spa, and nail businesses: booking-centered services, gallery and staff profiles, promotions. Local pages optional.  
- **Supported page families**: home, about, services, offerings, contact, faq, support, resource, authority.  
- **CTA**: Preferred `book_now`, `gallery_to_booking`, `consult`; required `book_now`; discouraged `emergency_dispatch`, `claim_assistance`.  
- **Refs**: `seo_guidance_ref` cosmetology_nail, `token_preset_ref` cosmetology_elegant, `lpagery_rule_ref` cosmetology_nail_01.  
- **Source**: `plugin/src/Domain/Industry/Registry/Packs/industry-pack-cosmetology-nail.php`.

---

## 2. Realtor (`realtor`)

- **Name**: Realtor  
- **Summary**: Real estate agents and brokerages: buyer/seller services, valuation CTAs, market-area and neighborhood pages. Local and hierarchy-oriented.  
- **Supported page families**: home, about, services, offerings, contact, faq, resource, authority, comparison, buyer_guide.  
- **CTA**: Preferred `valuation_request`, `consult`, `book_now`; required `valuation_request`; discouraged `emergency_dispatch`, `claim_assistance`.  
- **Refs**: `seo_guidance_ref` realtor, `token_preset_ref` realtor_warm, `lpagery_rule_ref` realtor_01.  
- **Source**: `plugin/src/Domain/Industry/Registry/Packs/industry-pack-realtor.php`.

---

## 3. Plumber (`plumber`)

- **Name**: Plumber  
- **Summary**: Plumbing contractors: emergency and scheduled service, call-now and book-now CTAs, trust and financing emphasis, service-area pages.  
- **Supported page families**: home, about, services, offerings, contact, faq, support, resource, authority, utility.  
- **CTA**: Preferred `call_now`, `book_now`, `emergency_dispatch`; required `call_now`; discouraged `valuation_request`, `gallery_to_booking`.  
- **Refs**: `seo_guidance_ref` plumber, `token_preset_ref` plumber_trust, `lpagery_rule_ref` plumber_01.  
- **Source**: `plugin/src/Domain/Industry/Registry/Packs/industry-pack-plumber.php`.

---

## 4. Loading

The registry loads built-in definitions via `Industry_Pack_Registry::get_builtin_pack_definitions()` from the `Packs/` directory. Bootstrap calls `$registry->load( ... )` with that list. Invalid or duplicate packs are skipped; refs are resolved by respective registries (CTA, SEO, style preset, LPagery) at use time.
