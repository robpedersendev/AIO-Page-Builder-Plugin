# Industry Subsystem — Multisite and Site-Local Verification (Prompt 397)

**Spec**: industry-lifecycle-hardening-contract §2; master spec multisite assumptions.  
**Purpose:** Verify and document site-local scoping for the industry subsystem in multisite so profiles, pack state, caches, and diagnostics do not bleed across sites.

---

## 1. Scope

- **In scope:** Industry profile, applied preset, override options, disabled packs list, any industry caches/transients, diagnostics snapshot, export/restore.
- **Out of scope:** Network-wide industry management; global admin UIs.

---

## 2. Audit: Stored state and scoping

| State | Storage | Multisite behavior | Scoping guard |
|-------|---------|--------------------|---------------|
| Industry profile | `Option_Names::INDUSTRY_PROFILE` (via Settings_Service) | WordPress options are per-blog in multisite. `get_option`/`update_option` use current blog context. | Native WP; no change. |
| Applied industry preset | `Option_Names::APPLIED_INDUSTRY_PRESET` (Settings_Service + Style_Preset_Application_Service) | Per-blog options. | Native WP. |
| Industry section overrides | `Option_Names::INDUSTRY_SECTION_OVERRIDES` (Industry_Section_Override_Service) | Per-blog options. | Native WP. |
| Industry page template overrides | `Option_Names::INDUSTRY_PAGE_TEMPLATE_OVERRIDES` (Industry_Page_Template_Override_Service) | Per-blog options. | Native WP. |
| Industry build plan item overrides | `Option_Names::INDUSTRY_BUILD_PLAN_ITEM_OVERRIDES` (Industry_Build_Plan_Item_Override_Service) | Per-blog options. | Native WP. |
| Disabled industry packs | `Option_Names::DISABLED_INDUSTRY_PACKS` (Settings_Service) | Per-blog options. | Native WP. |
| Industry caches / transients | None currently in industry domain. | If added: must use keys that include blog id. | Use `Industry_Site_Scope_Helper::scope_cache_key( $base_key )` for any new transient/cache. |

**Conclusion:** All industry stored state uses site options. No transients in industry code at audit time. Future caches must use `Industry_Site_Scope_Helper::scope_cache_key()` so keys are site-local on multisite.

---

## 3. Audit: Diagnostics and health

- **Industry_Diagnostics_Service::get_snapshot():** Reads from Industry_Profile_Repository and registries. Profile comes from options (per-blog). No persistent snapshot cache; each call reads current site. **Site-local.**
- **Industry_Health_Check_Service::run():** Reads profile and registries; no cache. **Site-local.**
- **Export/restore:** Run in current site context; export reads current site options; restore writes current site. **Site-local.**

---

## 4. Verification procedures

| Check | Procedure | Pass condition |
|-------|-----------|----------------|
| Profile per-site | On multisite, set industry profile on site A (e.g. primary_industry_key = realtor). Switch to site B; get profile. Set different profile on B. Switch back to A; get profile. | Site A profile unchanged; site B profile independent. |
| Options per-site | On multisite, inspect option `Option_Names::INDUSTRY_PROFILE` for site A and site B (e.g. via get_option in each blog context). | Values differ per site or one empty. |
| No cross-site read | On multisite, from site A context, read profile then switch_to_blog(B) and read profile. | Second read returns B’s profile, not A’s. |
| Cache key helper | If/when industry adds a transient, verify the key is built with `Industry_Site_Scope_Helper::scope_cache_key()`. | Key contains blog id on multisite. |
| Export/restore | Export from site A; switch to site B; restore same bundle. | Site B gets bundle data; site A unchanged. |

---

## 5. Limitations and assumptions

- **No network-wide industry management:** Industry is not configurable from network admin; each site has its own profile and state.
- **Registry definitions shared:** Pack/overlay definitions are loaded from code; same on every site. Only profile, preset, overrides, and disabled list are per-site.
- **Cron/queue:** If industry-related jobs are added, they must run in a site context (e.g. switch_to_blog when processing a site) so options read/write are for that site.

---

## 6. Cross-references

- [industry-lifecycle-hardening-contract.md](../contracts/industry-lifecycle-hardening-contract.md) §2 — multisite scoping policy.
- [industry-lifecycle-regression-guard.md](industry-lifecycle-regression-guard.md) §2 — multisite checks in QA.
- `Industry_Site_Scope_Helper` — use for any new industry cache/transient keys.
