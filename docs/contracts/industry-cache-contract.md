# Industry Subsystem — Cache Contract (Prompt 433)

**Spec**: industry-pack-service-map; industry-lifecycle-hardening-contract; industry-section-recommendation-contract; industry-page-template-recommendation-contract; industry-starter-bundle; overlay composition contracts.

This contract defines caching policy for industry read models: scopes, keying, cacheable vs non-cacheable services, invalidation triggers, site-local scoping, safe fallback, and debugging expectations. Caches are bounded, site-local, and invalidatable. No execution state or cross-site leakage.

---

## 1. Scope and objectives

- **Purpose**: Allow high-cost industry read models (recommendations, overlay composition, starter bundle lookups) to be cached so the subsystem scales without drifting into stale or unsafe state.
- **Out of scope for this contract**: Implementing every cache; opaque aggressive caching without documented invalidation; caching mutable execution state (e.g. Build Plan execution job state, rollback snapshots).
- **Principle**: Live approved artifact behavior (e.g. approved Build Plan snapshots) remains distinct from live cached recommendation state. Cache is a performance optimization; correctness is preserved by invalidation and safe miss behavior.

---

## 2. Cache scopes and keying

All industry caches use **site-local** keys. On multisite, keys MUST be built with `Industry_Site_Scope_Helper::scope_cache_key( $base_key )` so that cache entries are per-blog and do not bleed across sites.

| Scope (base key prefix) | Key inputs | Used by |
|--------------------------|------------|--------|
| `section_recommendation` | primary_industry_key, industry_subtype_key (or empty), normalized secondary keys, disabled packs list (or version), hash of section internal_key list | Section recommendation resolver |
| `page_template_recommendation` | primary_industry_key, industry_subtype_key (or empty), normalized secondary keys, disabled packs list (or version), hash of page template internal_key list | Page template recommendation resolver |
| `helper_doc` | section_key, industry_key, subtype_key | Helper doc composer |
| `page_onepager` | page_template_key, industry_key, subtype_key | Page one-pager composer |
| `starter_bundle_list` | industry_key, subtype_key | Starter bundle registry get_for_industry |

Key construction MUST be deterministic: same inputs produce the same key. Inputs MUST be normalized (trimmed, sorted where order does not affect result). Key length MUST remain bounded (e.g. hash for long lists). Implementation: `Industry_Cache_Key_Builder` produces base keys; `Industry_Read_Model_Cache_Service` applies site scoping before get/set/delete.

---

## 3. Cacheable vs non-cacheable services

| Service | Cacheable | Notes |
|---------|-----------|--------|
| Industry_Section_Recommendation_Resolver::resolve() | Yes | Output is deterministic from profile, pack, sections, options. |
| Industry_Page_Template_Recommendation_Resolver::resolve() | Yes | Same. |
| Industry_Helper_Doc_Composer::compose() | Yes | Deterministic from section_key, industry_key, subtype_key. |
| Industry_Page_OnePager_Composer::compose() | Yes | Deterministic from page_template_key, industry_key, subtype_key. |
| Industry_Starter_Bundle_Registry::get_for_industry() | Yes | Deterministic from industry_key, subtype_key (definitions from code). |
| Industry_Starter_Bundle_Registry::get(key) | Optional | Single-bundle lookup; low cost; can be cached with key = bundle_key if desired. |
| Industry_Subtype_Resolver::resolve_from_profile() | No (or very short TTL) | Profile is already in options; resolution is cheap; avoid double-caching. |
| Industry_Profile_Repository::get_profile() | No | Source of truth; not a derived read model. |
| Industry pack/overlay/subtype registries (get/list) | No | Loaded from code; in-memory; no need for transient cache. |
| Build Plan generation / scoring | No | Approval-gated; not a generic read model cache. |
| Execution job state, rollback snapshots | No | Mutable execution state; never cached as read model. |
| Diagnostics / health check snapshots | Optional | If cached, short TTL and explicit invalidation on profile/pack change. |
| What-if simulation | No persistence | Industry_What_If_Simulation_Service does not persist profile or options. Comparison data during simulation may read or populate the same recommendation caches as live flows for the simulated keys; no separate simulation cache namespace required. See industry-what-if-simulation-contract. |

---

## 4. Invalidation triggers

Caches MUST be invalidated when inputs or upstream definitions change so that stale results are not served.

| Trigger | Invalidate |
|---------|------------|
| Industry Profile save/update (primary, secondary, subtype, selected_starter_bundle_key) | section_recommendation, page_template_recommendation, helper_doc, page_onepager, starter_bundle_list (profile drives which industry/subtype/bundle is used). |
| Subtype selection change | Same as profile (subtype is part of profile). |
| Pack activation/deactivation (disabled industry packs list) | section_recommendation, page_template_recommendation (pack affects scoring). |
| Starter bundle selection change | starter_bundle_list (and any caches keyed by bundle context if added). |
| Overlay or metadata definition change (code/registry deploy) | helper_doc, page_onepager. Optionally section_recommendation, page_template_recommendation if overlay affects scoring (currently overlays are doc-only). |
| Import/restore of industry profile | Same as profile save (restored profile may differ). |

Invalidation MUST be **targeted**: prefer invalidation by scope or key pattern rather than global flush of all plugin caches. Global flush only when explicitly justified (e.g. support request). Implementation: `Industry_Read_Model_Cache_Service` exposes `invalidate_scope( $scope )` and/or `invalidate_all_industry_read_models()` using a documented transient key pattern so that uninstall can remove industry caches.

---

## 5. Site-local scoping

- All industry cache keys MUST be site-local. Use `Industry_Site_Scope_Helper::scope_cache_key( $base_key )` for every get/set/delete.
- No cross-site reads or writes. No network-wide cache.
- Aligns with industry-lifecycle-hardening-contract §2 and industry-multisite-verification.md.

---

## 6. Safe fallback behavior

- **Cache miss**: Compute result as if cache were not present; store result in cache (when implementation adds caching); return result. No failure to the caller.
- **Cache read failure** (e.g. corrupt entry, missing key): Treat as miss; compute and return; optionally overwrite corrupt entry with fresh result.
- **Stale cache**: Handled by invalidation. If invalidation is missed, operators can clear caches via documented key pattern or invalidation API.
- No exception or fatal from cache layer to caller; cache is an optimization only.

---

## 7. TTL and bounded size

- **TTL**: Each cache entry MAY have a time-to-live (e.g. 24 hours) so that even without explicit invalidation, entries eventually expire. Implementation may use WordPress transients with expiry.
- **Bounded size**: Cache key set is bounded by (scope × distinct key inputs). No unbounded growth (e.g. do not cache per-request unique keys). Prefer hashing large inputs (section list, template list) into a short suffix.

---

## 8. Security and permissions

- No caching of secrets, API keys, or sensitive user data in read-model caches.
- Cache keys and values are internal (recommendation results, composed docs, bundle lists); no public exposure of cache keys required.
- No cross-user leakage: industry profile is site-level; cache is site-scoped; no user-specific cache keys in this subsystem.

---

## 9. Uninstall and cleanup

Industry-specific transients MUST use a documented key pattern (e.g. prefix `aio_industry_` or `Industry_Site_Scope_Helper::MULTISITE_PREFIX`) so that uninstall can delete them without loading the full industry stack. See industry-lifecycle-hardening-contract §1 and industry-uninstall-retained-data-matrix §2.

---

## 10. Cache debugging expectations

- **Diagnostics**: Support or diagnostics code MAY expose whether cache is enabled and approximate hit/miss counts if the implementation provides them. Not required for MVP.
- **Manual debugging**: Operators can clear industry caches by calling a documented invalidation method or by deleting transients matching the documented key pattern (e.g. `get_transient( 'aio_industry_*' )` pattern or scope-based delete).
- **No contradiction** with multisite/site-local behavior: cache keys must include blog id on multisite; verification in industry-multisite-verification.md.

---

## 11. Cross-references

- [industry-pack-service-map.md](industry-pack-service-map.md) — cache services and keys.
- [industry-lifecycle-hardening-contract.md](industry-lifecycle-hardening-contract.md) — multisite, uninstall, regression guards.
- [industry-multisite-verification.md](../qa/industry-multisite-verification.md) — cache key helper.
- [industry-uninstall-retained-data-matrix.md](../operations/industry-uninstall-retained-data-matrix.md) — removal of industry caches on uninstall.
- [industry-cache-invalidation-map.md](../operations/industry-cache-invalidation-map.md) (Prompt 435) — mapping of mutation events to invalidation actions.
