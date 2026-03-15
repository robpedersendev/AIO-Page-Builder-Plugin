# ACF Conditional Registration — Multisite Verification

**Prompt**: 303  
**Contracts**: acf-conditional-registration-contract.md, acf-registration-cache-invalidation-matrix.md

---

## 1. Purpose

Verifies and documents that the conditional ACF registration retrofit behaves correctly in multisite: no cross-site cache bleed, no cross-site assignment leakage, and no network-wide assumptions. Plugin remains site-level per spec.

---

## 2. Site-level isolation

| Component | Scoping | Implementation |
|-----------|---------|----------------|
| **Section-key cache** | Per-site | `Page_Section_Key_Cache_Service` prefixes transient keys with current blog id when `is_multisite()`. Keys: `aio_acf_sk_p_{blog_id}_{page_id}` (and equivalent for template/composition). Single-site: no suffix. |
| **Assignment map reads** | Site-local | Assignment and page/template/composition data use WordPress `get_post_meta`, `get_post_type`, and CPT queries; these are always current-blog in multisite. No change required. |
| **Diagnostics** | Site-local identity | `ACF_Registration_Diagnostics_Service::record_registration()` adds `site_id` (get_current_blog_id()) to the payload. Benchmark/diagnostics output identifies which site the snapshot came from. |
| **Invalidation hooks** | Site-local | `aio_acf_assignment_changed`, `aio_page_template_definition_saved`, `aio_composition_definition_saved` run in the context of the site where the save occurred; cache service uses get_site_suffix() so only that site’s cache is invalidated. |

---

## 3. What is not done

- No network-wide management or network activation of registration behavior.
- No cross-site assignment or template sharing.
- No change to single-site behavior: on non-multisite, get_site_suffix() returns '' and key format is unchanged.

---

## 4. QA scenarios (multisite)

| Scenario | Steps | Expected |
|----------|--------|----------|
| Cache isolation | On site A, open page edit (cache fills). Switch to site B, open same numeric page ID. | Site B must not see site A’s cached section keys; B resolves from assignment or cache miss. |
| Invalidation per site | On site A, change assignment for a page. On site B, same page ID has different assignment. | Only site A’s cache for that page is invalidated; site B’s cache (if any) is independent. |
| Diagnostics site_id | Run registration on site 2; capture get_last_registration(). | Payload includes site_id 2. |
| Edit on one site | Edit page on site 1. Open site 2 admin. | Site 2’s ACF registration and cache are unaffected. |

---

## 5. Safe failure

- If `get_current_blog_id()` is unavailable (e.g. before multisite loaded), cache service uses empty suffix (single-site key shape); diagnostics use 1 for site_id. Fail safe to no cross-site leakage.

---

## 6. Cross-references

- docs/specs/aio-page-builder-master-spec.md (site-level operation on multisite; no network-wide management)
- acf-registration-cache-invalidation-matrix.md
- acf-conditional-registration-contract.md
