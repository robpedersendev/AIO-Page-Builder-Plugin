# Industry Performance, Selective Loading, and Cache Invalidation Audit Report (Prompt 607)

**Spec:** Performance-oriented docs; cache/versioning docs; industry-implementation-audit-service-map.md.  
**Purpose:** Audit the industry subsystem for unnecessary eager work, slow admin loads, over-broad registry scans, excessive preview/report recomputation, and stale cache behavior.

---

## 1. Scope audited

- **Registry loading:** Industry_Packs_Module registers all services via factory closures; first $container->get($key) triggers creation. No eager "load all registries" at bootstrap; lazy resolution. Industry_Pack_Registry loads via get_builtin_pack_definitions(); other registries load on first use.
- **Industry read-model cache:** Industry_Read_Model_Cache_Service — get/set/delete by base_key; scoped_key_with_version() uses Option_Names::INDUSTRY_CACHE_VERSION; TTL DEFAULT_TTL 24h. invalidate_all_industry_read_models() bumps option version so all keys become stale (next get misses). Used by: Section_Recommendation_Resolver, Page_Template_Recommendation_Resolver, Starter_Bundle_Registry, Helper_Doc_Composer, Page_OnePager_Composer.
- **Invalidation triggers:** (1) Admin_Menu: profile save (aio_save_industry_profile), pack toggle (aio_toggle_industry_pack), style preset apply (aio_apply_industry_style_preset) — each obtains container industry_read_model_cache_service and calls invalidate_all_industry_read_models(). (2) Restore_Pipeline: after successful industry profile restore, calls industry_cache_service->invalidate_all_industry_read_models(). No invalidation on unrelated admin requests.
- **Preview/report caching:** Composer and recommendation resolvers check cache by key (profile + section/page + options); cache key from Industry_Cache_Key_Builder (for_section_recommendation, for_page_template_recommendation, for_helper_doc, for_page_onepager, for_starter_bundle_list). Stale data: version bump invalidates all; no per-key TTL bypass without version change.
- **Dashboard/report hot path:** Dashboard get_view_model() calls health->run(), completeness->generate_report(true), gap->analyze(true). No caching of dashboard aggregates; each page load hits services. Acceptable for internal admin; services themselves may use read-model cache where applicable (e.g. resolvers).

---

## 2. Findings summary

| Area | Result | Notes |
|------|--------|--------|
| **Expensive eager work** | Verified | No global eager registry scan at bootstrap. Lazy container resolution. Dashboard and readiness screens call report services on each render (no dashboard-level cache); report services (e.g. recommendation resolvers) use read-model cache when wired with cache service. |
| **Cache invalidation on state changes** | Verified | Profile save, pack toggle, style preset apply, and restore pipeline invalidate industry read-model caches. Version bump ensures all recommendation/composer/bundle cache entries miss on next request. |
| **Preview/simulation cache staleness** | Verified | Industry read-model cache is versioned; no separate "preview" cache that could leak simulated state. What-if simulation is read-only and does not write to industry read-model cache. Style_Cache_Service invalidates preview cache on style change; separate from industry cache. |
| **Selective loading** | Verified | Registries and resolvers are lazy. No evidence of loading all packs/subtypes on every request outside report generation that intentionally aggregates (e.g. completeness report). |
| **Safe fallback when cache absent** | Verified | Cache service get() returns null on miss or decode failure; callers compute and optionally set. Resolvers/composers use cached value when present else compute. |
| **No cross-user/cross-context leak** | Verified | Industry_Site_Scope_Helper::scope_cache_key() scopes keys; Option_Names::INDUSTRY_CACHE_VERSION is site option. No user ID in cache key; industry data is site-level, not user-level. |

---

## 3. Recommendations

- **No code changes required.** Lazy loading, version-based invalidation, and invalidation on profile/pack/style/restore are in place. No unsafe long-lived caches or correctness trade-offs identified.
- **Tests:** Add cache invalidation tests (profile change, restore) and regression tests for selective-loading behavior per prompt 607.

---

## 4. References

- [industry-implementation-audit-service-map.md](../operations/industry-implementation-audit-service-map.md)
- [industry-audit-remediation-ledger.md](../operations/industry-audit-remediation-ledger.md)
