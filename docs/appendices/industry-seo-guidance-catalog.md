# Industry SEO Guidance Catalog (Prompt 359)

**Spec:** industry-seo-guidance-schema.md; industry-pack-extension-contract.  
**Purpose:** Lists built-in SEO and entity-guidance rules loaded by Industry_SEO_Guidance_Registry. Packs reference by seo_guidance_ref (guidance_rule_key). Advisory only; no third-party SEO plugin mutation.

---

## 1. Loading

- **Source:** `plugin/src/Domain/Industry/Registry/SEOGuidance/seo-guidance-definitions.php`.
- **Registry:** Industry_SEO_Guidance_Registry::get_builtin_definitions(). Bootstrap (Industry_Packs_Module) registers under CONTAINER_KEY_SEO_GUIDANCE_REGISTRY and calls load() with that list.
- **Validation:** guidance_rule_key, industry_key, version_marker (1), status (active/draft/deprecated) required; invalid or duplicate key skipped.

---

## 2. Rule keys and industries

| guidance_rule_key | industry_key | Local SEO posture |
|-------------------|--------------|-------------------|
| cosmetology_nail | cosmetology_nail | Moderate |
| realtor | realtor | Strong |
| plumber | plumber | Strong |
| disaster_recovery | disaster_recovery | Strong |

---

## 3. Guidance coverage

Per rule: title_patterns, h1_patterns, internal_link_guidance, local_seo_posture, faq_emphasis, review_emphasis, entity_cautions. Optional page_family for scoped rules. Used by docs, one-pagers, planner overlays, and UI explanations; no live metadata injection.

---

## 4. Pack references

- **cosmetology_nail:** seo_guidance_ref => cosmetology_nail.
- **realtor:** seo_guidance_ref => realtor.
- **plumber:** seo_guidance_ref => plumber.
- **disaster_recovery:** seo_guidance_ref => disaster_recovery.

All resolve via Industry_SEO_Guidance_Registry::get( key ).
