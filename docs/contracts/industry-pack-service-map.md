# Industry Pack Service Map

**Spec**: industry-pack-extension-contract.md; aio-page-builder-master-spec.md.

**Status**: Directory layout and service categories for the Industry Pack subsystem. Services are placeholder or scaffold only until later prompts implement them.

---

## 1. Directory structure

All Industry Pack domain code lives under:

```
plugin/src/Domain/Industry/
├── Registry/       # Pack definitions, schema, validation, load/save
├── Profile/        # Site/user industry profile (primary/secondary industry)
├── Overlays/       # Helper and one-pager overlay resolution
├── AI/             # Industry-aware AI rules and planning hooks
├── LPagery/        # Token presets and LPagery rules per industry
└── Docs/           # Industry-specific doc refs and inventory
```

- **Registry**: Industry pack object schema, validation, persistence, and registry service. Section/page template registries remain authoritative; this registry holds industry pack definitions only.
- **Profile**: Storage and resolution of the site’s (or user’s) primary/secondary industry. Feeds onboarding and template ranking.
- **Overlays**: Resolution of industry-specific helper refs and one-pager refs applied on top of existing section/page doc refs.
- **AI**: Hooks and rules for AI planning when an industry pack is active (e.g. industry_rule_ref from pack). Does not replace the core AI planner.
- **LPagery**: Token presets and LPagery rule references per industry. Must not change LPagery token naming.
- **Docs**: Optional industry-specific documentation inventory or refs; extends existing helper/one-pager system.

---

## 2. Service categories and responsibilities

| Category   | Responsibility |
|-----------|-----------------|
| **Registry** | Define and validate industry pack objects; load/save packs (PHP definitions, option, or DB) per persistence contract; expose pack list and by-key lookup. |
| **Profile**  | Store and retrieve industry site profile (primary industry key, optional secondary keys); integrate with onboarding/settings. |
| **Overlays** | Given active industry, merge or reorder helper refs and one-pager refs for sections/pages. |
| **AI**       | Apply industry AI rules (e.g. ai_rule_ref) in planning context; keep contract with existing AI pillar. |
| **LPagery**  | Resolve token_preset_ref and lpagery_rule_ref for active industry; remain LPagery-compatible. |
| **Docs**     | Industry-specific doc inventory or refs; link to existing docs system. |
| **Cache**    | Bounded, site-local, invalidatable caching for high-cost read models (recommendations, overlay composition, starter bundle lookups). See [industry-cache-contract.md](industry-cache-contract.md). |

---

## 3. Container keys (bootstrap)

The following keys are registered by **Industry_Packs_Module**:

| Key | Purpose | Implementation |
|-----|---------|----------------|
| `industry_packs_loaded` | Dependency flag; industry subsystem is bootstrapped. | `true`. |
| `industry_pack_validator` | Validates single pack or bulk; duplicate-key detection. | Industry_Pack_Validator. |
| `industry_pack_registry` | Registry: load(), get(key), get_all(), list_by_status(status). | Industry_Pack_Registry; loaded with empty list until a pack loader is added. |
| `industry_profile_store` | Site industry profile (primary/secondary, subtype, service/geo model). | Industry_Profile_Repository when `settings` is available; else null. |
| `industry_cta_pattern_registry` | CTA pattern definitions for industry packs (preferred/discouraged/required). | Industry_CTA_Pattern_Registry; loaded with empty list until patterns are added (industry-cta-pattern-contract). |
| `industry_question_pack_registry` | Onboarding question packs per industry (cosmetology_nail, realtor, plumber, disaster_recovery). | Industry_Question_Pack_Registry; loads Industry_Question_Pack_Definitions::default_packs(). |
| `industry_prompt_pack_overlay_service` | Builds industry overlay for prompt-pack assembly (industry-prompt-pack-overlay-contract). | Industry_Prompt_Pack_Overlay_Service; optional Industry_Pack_Registry. |
| `industry_section_recommendation_resolver` | Scores and ranks section templates by industry fit (industry-section-recommendation-contract). | Industry_Section_Recommendation_Resolver; read-only; returns Industry_Section_Recommendation_Result. |
| `industry_page_template_recommendation_resolver` | Scores and ranks page templates by industry fit (industry-page-template-recommendation-contract). | Industry_Page_Template_Recommendation_Resolver; read-only; returns Industry_Page_Template_Recommendation_Result. |
| `industry_style_preset_registry` | Read-only registry of industry style presets (industry-style-preset-schema.md); token values and component override refs. | Industry_Style_Preset_Registry; load(), get(key), get_all(), list_by_industry(), list_by_status(). |
| `industry_seo_guidance_registry` | Read-only registry of industry SEO/entity guidance rules (industry-seo-guidance-schema.md). | Industry_SEO_Guidance_Registry; load(), get(key), get_all(), list_by_industry(), list_by_status(). |
| `industry_lpagery_rule_registry` | Read-only registry of industry LPagery rules (industry-lpagery-rule-schema.md); posture, token refs, hierarchy. | Industry_LPagery_Rule_Registry; load(), get(key), get_all(), list_by_industry(), list_by_status(). |
| `industry_helper_doc_composer` | Composes base section helper + industry overlay into effective helper doc (industry-section-helper-overlay-schema). | Industry_Helper_Doc_Composer; compose(section_key, industry_key) → Composed_Helper_Doc_Result; depends on Documentation_Registry and Industry_Section_Helper_Overlay_Registry. |
| `industry_page_onepager_composer` | Composes base page one-pager + industry overlay (industry-page-onepager-overlay-schema). | Industry_Page_OnePager_Composer; compose(page_template_key, industry_key) → Composed_Page_OnePager_Result; depends on Documentation_Registry and Industry_Page_OnePager_Overlay_Registry. |
| `industry_section_library_read_model_builder` | Admin read model for section library with recommendation state and view modes (industry-section-recommendation-contract). | Industry_Section_Library_Read_Model_Builder; build(profile, pack, sections, view_mode) → list of Industry_Section_Library_Item_View. |
| `industry_page_template_directory_read_model_builder` | Admin read model for page template directory with recommendation, hierarchy/LPagery fit (industry-page-template-recommendation-contract). | Industry_Page_Template_Directory_Read_Model_Builder; build(profile, pack, page_templates, view_mode) → list of Industry_Page_Template_Directory_Item_View. |
| `industry_cache_key_builder` | Builds deterministic, bounded cache base keys for industry read models (industry-cache-contract). | Industry_Cache_Key_Builder; scope + inputs → base_key. |
| `industry_read_model_cache_service` | Site-local get/set/delete for industry read-model caches; TTL; safe fallback on miss (industry-cache-contract). | Industry_Read_Model_Cache_Service; uses Industry_Site_Scope_Helper for key scoping. |

Additional keys can be added in later prompts.

---

## 4. Dependency flow (future)

- **Bootstrap**: Industry_Packs_Module registers `industry_packs_loaded` and placeholder keys.
- **Registry** may depend on: section/page template registries (read-only) for affinity or key validation; storage abstraction.
- **Profile** may depend on: options or user meta; onboarding step keys.
- **Overlays** may depend on: industry pack registry, profile (active industry), existing helper/one-pager registries.
- **AI** may depend on: industry pack registry, profile, existing AI prompt/planning services.
- **LPagery** may depend on: industry pack registry, profile, existing LPagery token/rule contracts.
- **Docs** may depend on: industry pack registry, existing docs inventory.

No dependency from core section/page registries, rendering, or execution to Industry Pack is required for baseline behavior; industry is additive.

---

## 5. Alignment with plugin pillars

- **Registries**: Industry pack registry is a separate registry (pack definitions only). Section and page template registries remain authoritative.
- **Onboarding**: Profile integrates with onboarding; industry selection or primary industry can be added as an overlay step or field.
- **Documentation**: Overlays and Docs extend helper/one-pager refs; no replacement of existing docs.
- **AI**: AI category extends planning with industry rules; no replacement of AI provider or prompt pack system.
- **Export/restore**: When implemented, industry pack definitions and industry profile must be included in export and restored; see industry-pack-extension-contract and PORTABILITY_AND_UNINSTALL.
