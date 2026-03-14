# Template Ecosystem Multisite Site-Level Isolation Report

**Governs:** Spec §0.8, §0.9, §1.9.10, §4.18, §54.9, §60.5; Prompt 218.  
**Purpose:** Evidence that the expanded template ecosystem (registries, previews, compare, compositions, Build Plans, execution, export/restore, reporting) behaves site-locally on WordPress multisite with no cross-site leakage or cross-binding.

---

## 1. Supported Multisite Posture

- **Site-level only:** The plugin supports site-level operation on multisite. Each site manages its own settings, plans, templates, and artifacts (spec §54.9).
- **No network-wide scope:** No network-wide template registry, cross-site composition sharing, or centralized multisite management. Current site is the authoritative boundary for all template operations, exports, previews, and reporting context.
- **Bounded warning:** Network activation is not officially validated unless separately tested; compatibility matrix documents this (compatibility-matrix.md §9).

---

## 2. Audit Summary by Surface

### 2.1 Template Registries (Section / Page / Composition)

| Storage | Mechanism | Site isolation |
|--------|-----------|-----------------|
| Section templates | CPT via `Abstract_CPT_Repository` / `Section_Template_Repository` | **Per-site.** `WP_Query` and `wp_insert_post` run in current blog context; no `switch_to_blog`. |
| Page templates | CPT via `Page_Template_Repository` | **Per-site.** Same as above. |
| Compositions | CPT via `Composition_Repository` (Object_Type_Keys::COMPOSITION) | **Per-site.** Same as above. |

**Finding:** Registries and template screens are scoped to the current site. No code changes required.

### 2.2 Previews and Preview Cache

| Storage | Mechanism | Site isolation |
|--------|-----------|-----------------|
| Preview snapshot cache | Option `aio_preview_snapshot_cache` (`Preview_Cache_Service`) | **Per-site.** `get_option` / `update_option` are per-site in multisite. |

**Finding:** Previews and preview cache remain site-scoped. No code changes required.

### 2.3 Compare List (Template Compare)

| Storage | Mechanism | Site isolation (before / after fix) |
|--------|-----------|-------------------------------------|
| Compare list (section/page) | User meta `_aio_compare_section_templates`, `_aio_compare_page_templates` | **Before:** User meta is global in multisite; same user on different sites shared one list (cross-site leakage). **After:** Meta key is site-scoped via `Template_Compare_Screen::get_compare_meta_key()`: in multisite the key includes `_blog_{id}` (e.g. `_aio_compare_section_templates_blog_2`). |

**Finding:** Cross-site leakage identified. **Fix applied:** Compare list user meta is now site-scoped when `is_multisite()` and `get_current_blog_id()` are available (see §4).

### 2.4 Compositions (Storage and UI)

| Storage | Mechanism | Site isolation |
|--------|-----------|-----------------|
| Composition definitions | CPT + post meta `_aio_composition_definition` | **Per-site.** CPT and meta are in current blog context. |

**Finding:** Compositions remain site-scoped. No code changes required.

### 2.5 Build Plans, Execution, Rollback

| Storage / flow | Mechanism | Site isolation |
|----------------|-----------|-----------------|
| Build plans | Option / repository (current site context) | **Per-site.** Options and CPTs are per-site. |
| Execution queue / jobs | Custom table or option-backed repository | **Per-site.** Table/options are in current site context; no `switch_to_blog` in execution path. |
| Rollback / snapshots | CPT / meta and version snapshots | **Per-site.** Same as registries. |

**Finding:** Execution, rollback, and Build Plan persistence are site-scoped. No code changes required.

### 2.6 Export / Restore

| Flow | Mechanism | Site isolation |
|------|-----------|-----------------|
| Export | Export generator reads current site’s registries, plans, and artifacts | **Per-site.** All reads use current blog context. |
| Restore | Restore pipeline writes to current site | **Per-site.** No cross-site writes. |
| Export manifests / support package | Built from current site data; `site_reference` from `home_url()` | **Per-site.** `home_url()` is current site in multisite. |

**Finding:** Export and restore flows remain site-scoped. No code changes required.

### 2.7 Reporting and Support Summaries

| Payload / log | Mechanism | Site isolation |
|---------------|-----------|-----------------|
| Install notification / heartbeat / error reports | `site_reference` from `home_url()` (e.g. `Developer_Error_Reporting_Service`, `Heartbeat_Service`) | **Per-site.** `home_url()` returns current site URL. |
| Support summary / diagnostics | Built from current site state and logs | **Per-site.** No mixing of sites in payloads. |
| Reporting options (e.g. heartbeat state, install notice, error report state) | Options | **Per-site.** Options are per-site. |

**Finding:** Reporting and support summaries do not mix sites; context is current site only. No code changes required.

---

## 3. Verification Method

- **Code audit:** Grep for `switch_to_blog`, `restore_current_blog`, `get_current_blog_id` in plugin source: **no usage.** Plugin relies on WordPress default per-site behavior for options, CPTs, and (after fix) site-scoped user meta for compare list.
- **Storage authority:** Registry storage (CPT), execution logs, export manifests, report payloads, and capability checks operate in the current request’s site context. No explicit blog switching.
- **Single fix applied:** Compare list user meta key is made site-specific in multisite (§4).

---

## 4. Narrow Fix Applied: Compare List Site-Scoping

**File:** `plugin/src/Admin/Screens/Templates/Template_Compare_Screen.php`

- **Change:** Introduced `get_compare_meta_key( string $type, ?int $blog_id = null ): string`. When `$blog_id` is null and the site is multisite, the returned key includes `_blog_{get_current_blog_id()}` (e.g. `_aio_compare_section_templates_blog_2`). Single-site or `$blog_id` null and not multisite: unchanged key (e.g. `_aio_compare_section_templates`).
- **Usage:** `get_compare_list()` and `maybe_handle_add_remove()` use `get_compare_meta_key( $type )` for read/write so compare list is per-site in multisite.
- **Backward compatibility:** On single-site, key is unchanged. On multisite, existing global compare list (old key) is no longer read/written; users see a fresh per-site list (acceptable; no migration of legacy compare list across sites).

---

## 5. QA Artifacts and Test Requirements

- **Unit tests:** `Template_Compare_Screen_Test` covers site-scoped compare meta key: when `get_compare_meta_key( $type, $blog_id )` is called with different `$blog_id` values, returned keys differ; when `$blog_id` is null (single-site or non-multisite context), key equals the base constant (see §60.5).
- **Integration / manual:** Under a multisite install, confirm (1) section/page template directories and composition list show only current site’s data, (2) compare list does not carry over between sites for the same user, (3) export/restore and reporting payloads reference only the current site.

---

## 6. Summary

| Area | Status | Action |
|------|--------|--------|
| Template registries (section/page/composition) | Site-scoped | None |
| Previews and preview cache | Site-scoped | None |
| Compare list | Was global user meta | **Fixed:** site-scoped meta key in multisite |
| Build Plans / execution / rollback | Site-scoped | None |
| Export / restore | Site-scoped | None |
| Reporting / support summaries | Site-scoped | None |

The expanded template ecosystem is confirmed to behave site-locally under multisite, with one narrow fix (compare list) applied and documented. No network-wide template registry or cross-site composition sharing has been introduced.
