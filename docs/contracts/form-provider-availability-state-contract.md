# Form Provider Availability State Contract

**Governs:** Provider availability, form-list caching, and stale-binding state (Prompt 237).  
**Spec:** §0.10.10, §0.10.11, §5.11, §5.11.3, §55.7, §55.8, §59.12.  
**Purpose:** Typed availability state and bounded cache so admin UI stays responsive and supportable when providers are slow, unavailable, or errored.

---

## 1. Availability state (typed payload)

Per-provider state returned by Form_Provider_Availability_Service:

| Field | Type | Description |
|-------|------|-------------|
| provider_key | string | Provider identifier. |
| status | enum | One of: `available`, `unavailable`, `no_forms`, `provider_error`, `cached_fallback`. |
| message | string \| null | Human-readable message for admin/diagnostics; bounded, no secrets. |
| from_cache | bool | True when form list or state is from cache (e.g. after provider failure). |
| checked_at | int \| null | Unix timestamp of last successful check (when relevant). |
| picker_items | list | Normalized picker items when status is available, no_forms, or cached_fallback and provider supports form list. |

**Status semantics:**
- **available** — Provider is registered, adapter is_available(), and (if supports form list) form list fetched successfully.
- **unavailable** — Provider not in registry or adapter is_available() false.
- **no_forms** — Available but supports form list and list is empty.
- **provider_error** — Adapter threw or timed out when fetching form list (or availability check failed).
- **cached_fallback** — Live fetch failed; cached data (if any) is being used for editing.

---

## 2. Picker cache record (bounded)

Form_Provider_Picker_Cache_Service stores per provider_key:

| Field | Type | Description |
|-------|------|-------------|
| provider_key | string | Cache key. |
| items | list | Normalized picker items (item_id, item_label, etc.). |
| outcome | string | `success` or `empty` or `error`. |
| fetched_at | int | Unix timestamp. |

**TTL:** Configurable max age (e.g. 300 seconds). Stale entries are not returned as fresh; they may be used as fallback when live fetch fails. **Invalidation:** Explicit invalidate(provider_key) or clear(). No secrets or raw provider config in cache.

---

## 3. Stale-binding detection

When a provider supports form list and we have a current list, a stored form_id not present in the list is **stale**. Adapter::is_item_stale(form_id) may be used when the provider exposes that check. Availability state can include a **stale_binding** hint for the current form_id when provided by the caller (state builder passes current form_id).

---

## 4. Integration points

- **Form_Provider_Picker_Discovery_Service:** May accept Form_Provider_Availability_Service; get_picker_state_for_provider() merges in availability_status, from_cache, and uses cached form list when appropriate.
- **Form_Section_Field_State_Builder:** Picker state can include availability_status and from_cache per provider; messages reflect unavailable / provider_error / cached_fallback.
- **Diagnostics / support summaries:** Availability summary (per-provider status counts) can be added to support bundles or diagnostics; bounded and role-appropriate.

---

## 5. Security and capability

Cache and availability state are used only in admin context; callers must enforce capability. No secrets or private provider config in state or cache. Error messages are bounded and safe for support.
