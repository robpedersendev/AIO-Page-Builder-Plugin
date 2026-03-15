# Styling Cache and Versioning Contract

**Spec**: [styling-subsystem-contract.md](styling-subsystem-contract.md)  
**Status**: Prompt 256. Style cache service and version markers; invalidation aligned with style data changes.

---

## 1. Purpose

This contract defines the **cache and versioning layer** for the styling subsystem: a single version marker for style output, invalidation on global and per-entity style changes, and coordination with the preview cache so frontend and preview style behavior stay coherent.

---

## 2. Version marker

| Aspect | Description |
|--------|-------------|
| **Option key** | `aio_style_cache_version` (Style_Cache_Service::OPTION_VERSION). |
| **Value** | Non-empty string (e.g. Unix timestamp after invalidation). |
| **Use** | Asset versioning for the base stylesheet and inline style output so browsers and proxies fetch fresh CSS after style data changes. |
| **Read** | Style_Cache_Service::get_version(). |
| **Write** | Only via Style_Cache_Service::invalidate() (no direct option writes by other code). |

---

## 3. Invalidation rules

| Trigger | Behavior |
|---------|----------|
| **Global style settings change** | `update_option_aio_global_style_settings` → Style_Cache_Service::invalidate(). |
| **Per-entity style payload change** | `update_option_aio_entity_style_payloads` → Style_Cache_Service::invalidate(). |
| **Restore / migration** | After restoring styling category, call Style_Cache_Service::invalidate(). |
| **invalidate()** | Bump version (update_option aio_style_cache_version); call Preview_Cache_Service::invalidate_all() when preview cache is injected. |

Invalidation must not be triggerable by unauthenticated users; it is driven only by option updates (admin or restore) or explicit post-restore invalidation.

---

## 4. Preview cache coordination

- **Preview_Cache_Service** holds cached preview HTML for section/page templates. That output can include inline styles and style blocks from the styling subsystem.
- When style data changes, preview cache must be cleared so the next preview render uses current global and per-entity styling.
- **Style_Cache_Service** accepts an optional **Preview_Cache_Service**. When invalidate() is called, it also calls Preview_Cache_Service::invalidate_all() so preview and frontend style behavior remain coherent.
- Hooks that fire on `update_option_aio_global_style_settings` and `update_option_aio_entity_style_payloads` call Style_Cache_Service::invalidate() (not Preview_Cache_Service directly), so a single place owns invalidation.

---

## 5. Frontend asset versioning

- **Frontend_Style_Enqueue_Service** registers the base stylesheet with a version. When **Style_Cache_Service** is available, it uses Style_Cache_Service::get_version() for that version so that after invalidation the next request gets a new version query and fresh CSS.
- Conditional loading (should_load_base_styles) is unchanged; only the version parameter is driven by the style cache when provided.

---

## 6. Failure and security

- **Cache rebuild failure**: If invalidation fails (e.g. option write fails), the next get_version() continues to return the previous value; no unsafe fallback. Failure to rebuild cache must fail safely (no emission of stale or corrupt data beyond existing behavior).
- **Cached output**: Only sanitized style output is ever emitted; the cache/version layer does not introduce new content.
- **Permission**: Export/restore and option updates that trigger invalidation remain capability-gated and nonce-protected elsewhere.

---

## 7. Export / restore and migration

- After **restore** of the styling category, the restore pipeline calls Style_Cache_Service::invalidate() so that style caches and preview cache reflect restored data. See export/restore and styling-export-restore-verification docs.
- **Migration**: Future schema version changes for global or per-entity styling may require migration steps; after those steps, invalidation must be called so version and preview cache are updated. Migration paths for styling schema version changes are documented in the styling subsystem and export/restore contracts.

---

## 8. Cross-references

- [styling-subsystem-contract.md](styling-subsystem-contract.md)
- [global-styling-settings-contract.md](global-styling-settings-contract.md)
- [per-entity-style-payload-contract.md](per-entity-style-payload-contract.md)
- Style_Cache_Service, Frontend_Style_Enqueue_Service, Preview_Cache_Service
