# Industry Subsystem Implementation-Audit Service Map (Prompt 586)

**Spec:** [industry-implementation-audit-entrypoint-map.md](industry-implementation-audit-entrypoint-map.md); [industry-greenfield-closure-report.md](industry-greenfield-closure-report.md); [industry-pack-service-map.md](../contracts/industry-pack-service-map.md).  
**Purpose:** Real implementation map of industry subsystem entrypoints, registries, resolvers, storage, admin screens, scoring, preview, Build Plan, export, and reporting paths. Use this map for audit prompts 587+ so audits target actual code, not assumptions. Internal-only.

---

## 1. Bootstrap and container wiring

| Item | Location | Notes |
|------|----------|--------|
| **Bootstrap entry** | `plugin/src/Bootstrap/Industry_Packs_Module.php` | Implements `Service_Provider_Interface`; single `register( Service_Container $container )`. |
| **Registration order** | `plugin/src/Bootstrap/Module_Registrar.php` | `Industry_Packs_Module()` is last in `register_bootstrap()` (after Config, Dashboard, Diagnostics, Crawler, Admin_Router, Capability, … ExportRestore, Onboarding, Styling). |
| **Container** | `plugin/src/Infrastructure/Container/Service_Container.php` | All industry keys registered on this container. |
| **Industry-loaded flag** | `Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_LOADED` = `'industry_packs_loaded'` | Registered first; value `true`. |

All industry services are registered in `Industry_Packs_Module::register()` in a single pass; resolution is lazy (factory closures). No separate “boot” or “run” phase; first `$container->get( $key )` triggers factory.

---

## 2. Container keys and actual classes (runtime entrypoints)

Keys below are the **actual** constants and class names. Order in the table matches registration order in the module (conceptually; cross-dependencies resolved on first get).

| Container key | Class / type | File (domain root: plugin/src/Domain/Industry/) |
|---------------|--------------|--------------------------------------------------|
| `industry_packs_loaded` | bool | — |
| `industry_pack_validator` | Industry_Pack_Validator | Registry/Industry_Pack_Validator.php |
| `industry_pack_registry` | Industry_Pack_Registry | Registry/Industry_Pack_Registry.php |
| `industry_profile_validator` | Industry_Profile_Validator | Profile/Industry_Profile_Validator.php |
| `industry_profile_audit_trail_service` | Industry_Profile_Audit_Trail_Service | Reporting/Industry_Profile_Audit_Trail_Service.php |
| `industry_profile_store` | Industry_Profile_Repository \| null | Profile/Industry_Profile_Repository.php |
| `industry_secondary_conversion_goal_resolver` | Secondary_Conversion_Goal_Resolver | Profile/Secondary_Conversion_Goal_Resolver.php |
| `industry_cta_pattern_registry` | Industry_CTA_Pattern_Registry | Registry/Industry_CTA_Pattern_Registry.php |
| `industry_section_helper_overlay_registry` | Industry_Section_Helper_Overlay_Registry | Docs/Industry_Section_Helper_Overlay_Registry.php |
| `subtype_section_helper_overlay_registry` | Subtype_Section_Helper_Overlay_Registry | Docs/Subtype_Section_Helper_Overlay_Registry.php |
| `goal_section_helper_overlay_registry` | Goal_Section_Helper_Overlay_Registry | Docs/Goal_Section_Helper_Overlay_Registry.php |
| `secondary_goal_section_helper_overlay_registry` | Secondary_Goal_Section_Helper_Overlay_Registry | Docs/Secondary_Goal_Section_Helper_Overlay_Registry.php |
| `industry_page_onepager_overlay_registry` | Industry_Page_OnePager_Overlay_Registry | Docs/Industry_Page_OnePager_Overlay_Registry.php |
| `subtype_page_onepager_overlay_registry` | Subtype_Page_OnePager_Overlay_Registry | Docs/Subtype_Page_OnePager_Overlay_Registry.php |
| `goal_page_onepager_overlay_registry` | Goal_Page_OnePager_Overlay_Registry | Docs/Goal_Page_OnePager_Overlay_Registry.php |
| `secondary_goal_page_onepager_overlay_registry` | Secondary_Goal_Page_OnePager_Overlay_Registry | Docs/Secondary_Goal_Page_OnePager_Overlay_Registry.php |
| `subtype_goal_section_helper_overlay_registry` | Subtype_Goal_Section_Helper_Overlay_Registry | Docs/Subtype_Goal_Section_Helper_Overlay_Registry.php |
| `subtype_goal_page_onepager_overlay_registry` | Subtype_Goal_Page_OnePager_Overlay_Registry | Docs/Subtype_Goal_Page_OnePager_Overlay_Registry.php |
| `industry_seo_guidance_registry` | Industry_SEO_Guidance_Registry | Registry/Industry_SEO_Guidance_Registry.php |
| `industry_lpagery_rule_registry` | Industry_LPagery_Rule_Registry | LPagery/Industry_LPagery_Rule_Registry.php |
| `industry_cache_key_builder` | Industry_Cache_Key_Builder | Cache/Industry_Cache_Key_Builder.php |
| `industry_read_model_cache_service` | Industry_Read_Model_Cache_Service | Cache/Industry_Read_Model_Cache_Service.php |
| `industry_starter_bundle_registry` | Industry_Starter_Bundle_Registry | Registry/Industry_Starter_Bundle_Registry.php |
| `secondary_goal_starter_bundle_overlay_registry` | Secondary_Goal_Starter_Bundle_Overlay_Registry | Registry/Secondary_Goal_Starter_Bundle_Overlay_Registry.php |
| `industry_pack_toggle_controller` | Industry_Pack_Toggle_Controller | Admin/Screens/Industry/Industry_Pack_Toggle_Controller.php |
| `industry_compliance_rule_registry` | Industry_Compliance_Rule_Registry | Registry/Industry_Compliance_Rule_Registry.php |
| `industry_shared_fragment_registry` | Industry_Shared_Fragment_Registry | Registry/Industry_Shared_Fragment_Registry.php |
| `industry_shared_fragment_resolver` | Industry_Shared_Fragment_Resolver | Registry/Industry_Shared_Fragment_Resolver.php |
| `subtype_compliance_rule_registry` | Subtype_Compliance_Rule_Registry | Registry/Subtype_Compliance_Rule_Registry.php |
| `goal_caution_rule_registry` | Goal_Caution_Rule_Registry | Registry/Goal_Caution_Rule_Registry.php |
| `secondary_goal_caution_rule_registry` | Secondary_Goal_Caution_Rule_Registry | Registry/Secondary_Goal_Caution_Rule_Registry.php |
| `industry_compliance_warning_resolver` | Industry_Compliance_Warning_Resolver | Docs/Industry_Compliance_Warning_Resolver.php |
| `industry_question_pack_registry` | Industry_Question_Pack_Registry | Onboarding/Industry_Question_Pack_Registry.php |
| `industry_subtype_registry` | Industry_Subtype_Registry | Registry/Industry_Subtype_Registry.php (or equivalent) |
| `industry_subtype_resolver` | Industry_Subtype_Resolver | Profile/Industry_Subtype_Resolver.php |
| **Section recommendation resolver** | Industry_Section_Recommendation_Resolver | Registry/Industry_Section_Recommendation_Resolver.php |
| **Page template recommendation resolver** | Industry_Page_Template_Recommendation_Resolver | Registry/Industry_Page_Template_Recommendation_Resolver.php |
| **Section preview resolver** | Industry_Section_Preview_Resolver | Registry/Industry_Section_Preview_Resolver.php |
| **Page template preview resolver** | Industry_Page_Template_Preview_Resolver | Registry/Industry_Page_Template_Preview_Resolver.php |
| **Helper doc composer** | Industry_Helper_Doc_Composer | Docs/Industry_Helper_Doc_Composer.php |
| **Page one-pager composer** | Industry_Page_OnePager_Composer | Docs/Industry_Page_OnePager_Composer.php |
| **Section library read model builder** | Industry_Section_Library_Read_Model_Builder | Registry/Industry_Section_Library_Read_Model_Builder.php |
| **Page template directory read model builder** | Industry_Page_Template_Directory_Read_Model_Builder | Registry/Industry_Page_Template_Directory_Read_Model_Builder.php |
| **Style preset registry** | Industry_Style_Preset_Registry | Registry/Industry_Style_Preset_Registry.php (or Styling path) |
| **Prompt pack overlay service** | Industry_Prompt_Pack_Overlay_Service | AI/Industry_Prompt_Pack_Overlay_Service.php |
| **Author sandbox service** | Industry_Author_Sandbox_Service | Reporting/Industry_Author_Sandbox_Service.php |
| **Sandbox promotion service** | Industry_Sandbox_Promotion_Service | Reporting/Industry_Sandbox_Promotion_Service.php |
| **Health check service** | Industry_Health_Check_Service | Reporting/Industry_Health_Check_Service.php |
| **Additional reporting / readiness services** | Various | Reporting/* (see §5). |

Exact key names: use `Industry_Packs_Module::CONTAINER_KEY_*` constants in code; see `Industry_Packs_Module.php` for the full list and any keys not in the contract doc.

---

## 3. Registries and resolver dependencies

- **Industry_Pack_Registry:** Depends on `industry_pack_validator`; loads via `Industry_Pack_Registry::get_builtin_pack_definitions()`. No container dependency for pack list; validator may be from container or new instance.
- **Industry_Profile_Repository:** Depends on `settings` (required); optional `industry_profile_audit_trail_service`. Returns `null` if `settings` missing.
- **Industry_Starter_Bundle_Registry:** Depends on `industry_read_model_cache_service`, `industry_cache_key_builder`; loads via `Industry_Starter_Bundle_Registry::get_builtin_definitions()`.
- **Industry_Section_Recommendation_Resolver / Industry_Page_Template_Recommendation_Resolver:** Depend on profile store, pack registry, section/page overlay registries, cache service, cache key builder, shared fragment resolver, subtype resolver, subtype registries, etc., as wired in `Industry_Packs_Module`.
- **Industry_Section_Preview_Resolver / Industry_Page_Template_Preview_Resolver:** Depend on profile repository, pack registry, helper/onepager composers and overlay registries; built inline in module (not separate container keys).
- **Industry_Helper_Doc_Composer / Industry_Page_OnePager_Composer:** Built in module with overlay registries and base doc registry; composed with section/page keys and industry/subtype/goal context.

---

## 4. Persistence and profile paths

| Path | Implementation |
|------|----------------|
| **Profile read/write** | `Industry_Profile_Repository` (Profile/Industry_Profile_Repository.php). Backed by `settings` (options). |
| **Profile validation** | `Industry_Profile_Validator`; used before save. |
| **Audit trail** | `Industry_Profile_Audit_Trail_Service` (Reporting); optional inject into repository. |
| **Secondary goal resolution** | `Secondary_Conversion_Goal_Resolver`; uses profile store. |
| **Subtype resolution** | `Industry_Subtype_Resolver`; uses subtype registry (and optionally pack registry for parent keys). |

Storage is options-based via the global `settings` service; no dedicated industry DB tables in this map.

---

## 5. Admin screens and reporting (real classes)

All under `plugin/src/Admin/Screens/Industry/` unless noted.

| Screen / report | Class | Container usage |
|-----------------|--------|------------------|
| Author dashboard | Industry_Author_Dashboard_Screen | industry_health_check_service, industry_pack_completeness_report_service, industry_coverage_gap_analyzer, industry_scaffold_completeness_report_service |
| Profile settings | Industry_Profile_Settings_Screen | CONTAINER_KEY_INDUSTRY_PROFILE_STORE, SUBTYPE_REGISTRY, PACK_TOGGLE_CONTROLLER, STARTER_BUNDLE_REGISTRY |
| Health report | Industry_Health_Report_Screen | industry_health_check_service |
| Stale content report | Industry_Stale_Content_Report_Screen | — |
| Pack family comparison | Industry_Pack_Family_Comparison_Screen | — |
| Future industry readiness | Future_Industry_Readiness_Screen | industry_pack_completeness_report_service, industry_coverage_gap_analyzer, industry_scaffold_completeness_report_service, industry_scaffold_promotion_readiness_report_service |
| Maturity delta report | Industry_Maturity_Delta_Report_Screen | — |
| Drift report | Industry_Drift_Report_Screen | industry_drift_report_service |
| Scaffold promotion readiness | Industry_Scaffold_Promotion_Readiness_Report_Screen | industry_scaffold_promotion_readiness_report_service |
| Guided repair | Industry_Guided_Repair_Screen | — |
| Subtype comparison | Industry_Subtype_Comparison_Screen | — |
| Starter bundle comparison | Industry_Starter_Bundle_Comparison_Screen | — |
| Bundle import preview | Industry_Bundle_Import_Preview_Screen | CONTAINER_KEY_INDUSTRY_PACK_REGISTRY, STARTER_BUNDLE_REGISTRY, INDUSTRY_PROFILE_STORE |
| Override management | Industry_Override_Management_Screen | — |
| Future subtype readiness | Future_Subtype_Readiness_Screen | industry_scaffold_completeness_report_service, industry_scaffold_promotion_readiness_report_service |
| Style preset | Industry_Style_Preset_Screen | — |
| Style layer comparison | Industry_Style_Layer_Comparison_Screen | — |
| Conversion goal comparison | Conversion_Goal_Comparison_Screen | — |

Dashboard links and slug constants (e.g. `Industry_Author_Dashboard_Screen::SLUG`) are defined on each screen class. Admin menu registration: `plugin/src/Admin/Admin_Menu.php` (industry submenu and screen routing).

---

## 6. Preview, Build Plan, and doc composition paths

| Path | Implementation |
|------|----------------|
| **Section preview** | Industry_Section_Preview_Resolver (Registry/); uses Industry_Helper_Doc_Composer, profile, pack, overlay registries. |
| **Page template preview** | Industry_Page_Template_Preview_Resolver (Registry/); uses Industry_Page_OnePager_Composer, profile, pack, overlay registries. |
| **Helper doc composition** | Industry_Helper_Doc_Composer (Docs/); base + section helper overlay; allowed regions only. |
| **Page one-pager composition** | Industry_Page_OnePager_Composer (Docs/); base + page onepager overlay. |
| **Build Plan / starter bundle to plan** | Industry_Starter_Bundle_To_Build_Plan_Service, Industry_Subtype_Starter_Bundle_To_Build_Plan_Service, Conversion_Goal_Starter_Bundle_To_Build_Plan_Service (Domain/Industry/AI/); wired in Industry_Packs_Module for planning context. |

---

## 7. Export and restore paths

| Path | Implementation |
|------|----------------|
| **Export industry payload** | Export_Generator (Domain/ExportRestore/Export/Export_Generator.php) includes industry profile under `Industry_Export_Restore_Schema::KEY_INDUSTRY_PROFILE`. |
| **Restore industry payload** | Restore_Pipeline (Domain/ExportRestore/Import/Restore_Pipeline.php) reads `Industry_Export_Restore_Schema::KEY_SCHEMA_VERSION`, `KEY_INDUSTRY_PROFILE`; restores profile when schema version compatible. |
| **Schema** | Industry_Export_Restore_Schema (likely Domain/Industry/Export/ or Profile/). |

---

## 8. Sandbox and promotion paths

| Path | Implementation |
|------|----------------|
| **Dry run** | Industry_Author_Sandbox_Service (Reporting/Industry_Author_Sandbox_Service.php) `run_dry_run()`. |
| **Promotion check** | Industry_Sandbox_Promotion_Service (Reporting/Industry_Sandbox_Promotion_Service.php); consumes dry_run result; `check_prerequisites()`. |

---

## 9. Likely audit hotspots and fragile seams

- **Bootstrap:** Industry_Packs_Module runs last; profile store returns `null` if `settings` missing—consumers must null-check. Pack registry and profile store are used by many resolvers and screens.
- **Registry load:** All registries load via static `get_builtin_*()` or file includes; duplicate keys or invalid schema can cause drift. Validators run at load/register time.
- **Profile:** Industry_Profile_Repository read/write and export/restore; invalid industry_key or missing pack must be handled without fatal.
- **Resolvers:** Section/page recommendation and preview resolvers depend on profile, pack registry, overlay registries, cache; null or empty profile must yield neutral/safe fallback.
- **Cache:** Industry_Read_Model_Cache_Service and Industry_Cache_Key_Builder; TTL and invalidation triggers; key scope (site) must not leak.
- **Admin screens:** Many screens use `$this->container->has()` / `get()` and fall back when a service is missing; capability and nonce must be enforced on state-changing actions (profile save, toggle, import).
- **Export/restore:** Schema version and invalid ref handling in Restore_Pipeline and profile restore.
- **Sandbox/promotion:** Dry-run errors must surface; promotion must not auto-apply; prerequisites must align with release gate.

---

## 10. References

- [industry-implementation-audit-entrypoint-map.md](industry-implementation-audit-entrypoint-map.md)
- [industry-greenfield-closure-report.md](industry-greenfield-closure-report.md)
- [industry-pack-service-map.md](../contracts/industry-pack-service-map.md)
- [industry-audit-remediation-ledger.md](industry-audit-remediation-ledger.md)
