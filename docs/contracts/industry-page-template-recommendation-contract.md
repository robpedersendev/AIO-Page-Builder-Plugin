# Industry Page Template Recommendation Contract

**Spec**: page-template-industry-affinity-contract.md; industry-pack-extension-contract.md; industry-section-recommendation-contract.md.

**Status**: Read-only resolver that scores and ranks page templates against the active Industry Profile and Industry Pack. Overlay only; page-template registry remains authoritative.

---

## 1. Purpose

- **Score and rank** page templates by industry fit using page-template industry affinity metadata, pack supported_page_families, hierarchy fit, and LPagery fit.
- Support **recommended**, **allowed but weak fit**, **discouraged**, and **neutral** states for later UI and planner use.
- Remain **read-only** and **deterministic**; no duplication of the page template library or hard lockout.

---

## 2. Inputs

- **Industry profile**: primary_industry_key, optional secondary_industry_keys (from Industry_Profile_Repository or validated snapshot).
- **Industry pack(s)**: Resolved from Industry_Pack_Registry for primary. Pack fields used: supported_page_families.
- **Page template definitions**: List of page template definition arrays (each with internal_key and optional industry_affinity, industry_required, industry_discouraged, industry_hierarchy_fit, industry_lpagery_fit, template_family). Typically from page-template registry.

---

## 3. Recommendation result (per page template)

| Field | Type | Description |
|-------|------|-------------|
| **page_template_key** | string | Page template internal_key. |
| **score** | int | Numeric score (higher = better fit). |
| **fit_classification** | string | `recommended` \| `allowed_weak_fit` \| `discouraged` \| `neutral`. |
| **explanation_reasons** | array | Short reason codes (e.g. template_affinity_primary, pack_family_fit, hierarchy_fit). |
| **industry_source_refs** | array | Industry keys that contributed. |
| **hierarchy_fit** | string | Per-industry hierarchy fit note when present (e.g. top-level, hub, child-detail). |
| **lpagery_fit** | string | Per-industry LPagery fit note when present. |
| **warning_flags** | array | Optional warnings. |

---

## 4. Resolver behavior

- **Recommended**: Template industry_affinity or industry_required contains primary; and/or template_family in pack supported_page_families; high score.
- **Discouraged**: Template industry_discouraged contains primary; low/negative score.
- **Allowed weak fit**: Only secondary industry match or weak signals; medium score.
- **Neutral**: No industry metadata and not in pack family list; default score 0.
- **Hierarchy / LPagery**: Presence of industry_hierarchy_fit or industry_lpagery_fit for primary adds explanation and optional score bonus.
- **Invalid/missing profile**: Fail safely to neutral ranking (no throw).

---

## 5. API

- **Industry_Page_Template_Recommendation_Resolver**: resolve( array $industry_profile, array|null $primary_pack, array $page_templates, array $options = [] ): Industry_Page_Template_Recommendation_Result.
- **Industry_Page_Template_Recommendation_Result**: get_items(), get_ranked_keys(), to_array().

---

## 6. Implementation reference

- **Page_Template_Schema**: FIELD_INTERNAL_KEY, FIELD_INDUSTRY_AFFINITY, FIELD_INDUSTRY_REQUIRED, FIELD_INDUSTRY_DISCOURAGED, FIELD_INDUSTRY_HIERARCHY_FIT, FIELD_INDUSTRY_LPAGERY_FIT, template_family.
- **Industry_Pack_Schema**: FIELD_SUPPORTED_PAGE_FAMILIES.
- page-template-industry-affinity-contract.md: Field semantics.
