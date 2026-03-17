# Industry Subsystem — Cache Invalidation Map (Prompt 435)

**Spec**: industry-cache-contract.md; industry-lifecycle-hardening-contract.

This document maps mutation events to cache invalidation actions so that recommendation and overlay-composition caches do not serve stale data after profile, pack, subtype, bundle, or overlay changes.

---

## 1. Invalidation strategy

The industry read-model cache uses a **single version option** (`Industry_Read_Model_Cache_Service::OPTION_CACHE_VERSION`). All cache keys include this version. When the version is bumped, every existing cache entry becomes a miss (key no longer matches). No per-key delete is required.

**Method**: `Industry_Read_Model_Cache_Service::invalidate_all_industry_read_models()` bumps the version. Call it from the mutation paths listed below.

---

## 2. Mutation events and call sites

| Event | Where it happens | Action |
|-------|-------------------|--------|
| Industry Profile save/update | `Admin_Menu::handle_save_industry_profile()` after successful merge and redirect | Call `invalidate_all_industry_read_models()` before redirect. |
| Subtype selection change | Part of profile save (same as above). | No separate call. |
| Starter bundle selection change | Part of profile save (same as above). | No separate call. |
| Pack activation/deactivation (disabled list) | Industry pack toggle controller / settings save (e.g. `Industry_Pack_Toggle_Controller` or equivalent) | Call `invalidate_all_industry_read_models()` after updating disabled packs option. |
| Overlay or metadata definition change | Code/registry deploy; no runtime mutation. | Optional: bump version via CLI or support; or rely on TTL. |
| Import/restore of industry profile | Export/restore flow when industry profile is restored. | Call `invalidate_all_industry_read_models()` after restoring industry profile to current site. |

---

## 3. Implementation notes

- **Profile save**: Obtain `industry_read_model_cache_service` from container in `Admin_Menu::handle_save_industry_profile()` (or inject via constructor). After `$repo->merge_profile( $partial )` and before `wp_safe_redirect()`, call `$cache_service->invalidate_all_industry_read_models()`.
- **Pack toggle**: Wherever the disabled industry packs list is updated (e.g. after saving settings that change disabled packs), obtain the cache service and call `invalidate_all_industry_read_models()`.
- **Restore**: In the restore handler that applies industry profile to the current site, after writing profile options, call the cache service’s `invalidate_all_industry_read_models()`.
- **Safe failure**: If the cache service is not available (container miss, or not loaded), skip invalidation; the next request may use stale cache until TTL expiry. Prefer logging a warning in development.

---

## 4. Uninstall

The option `Industry_Read_Model_Cache_Service::OPTION_CACHE_VERSION` (`aio_industry_cache_version`) must be removed on uninstall together with other industry options. Add it to the plugin’s uninstall/cleanup list (see industry-uninstall-retained-data-matrix.md and industry-lifecycle-hardening-contract.md). Transient keys include the version; after option removal, existing transients orphan but will expire; no need to enumerate transients on uninstall.

---

## 5. Cross-references

- [industry-cache-contract.md](../contracts/industry-cache-contract.md) — scopes, keying, invalidation triggers.
- [industry-lifecycle-hardening-contract.md](../contracts/industry-lifecycle-hardening-contract.md) — uninstall, multisite.
- [industry-uninstall-retained-data-matrix.md](industry-uninstall-retained-data-matrix.md) — data removed on uninstall.
