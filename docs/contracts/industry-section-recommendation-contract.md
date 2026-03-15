# Industry Section Recommendation Contract

**Spec**: section-industry-affinity-contract.md; industry-pack-extension-contract.md; section-registry-schema.md.

**Status**: Read-only resolver that scores and ranks section templates against the active Industry Profile and Industry Pack. Overlay only; section registry remains authoritative.

---

## 1. Purpose

- **Score and rank** section templates by industry fit using section-industry affinity metadata, pack preferred/discouraged lists, and CTA fit.
- Support **recommended**, **allowed but weak fit**, **discouraged**, and **neutral** states for later UI and planner use.
- Remain **read-only** and **deterministic**; no duplication of the section library or hard lockout of sections unless later prompts authorize it.

---

## 2. Inputs

- **Industry profile**: primary_industry_key, optional secondary_industry_keys (from Industry_Profile_Repository or validated snapshot).
- **Industry pack(s)**: Resolved from Industry_Pack_Registry for primary (and optionally secondary). Pack fields used: preferred_section_keys, discouraged_section_keys, default_cta_patterns.
- **Section definitions**: List of section definition arrays (each with internal_key and optional industry_affinity, industry_discouraged, industry_cta_fit, industry_notes). Typically from section registry or directory query.

---

## 3. Recommendation result (per section)

| Field | Type | Description |
|-------|------|-------------|
| **section_key** | string | Section template internal_key. |
| **score** | int | Numeric score (higher = better fit). Range product-defined (e.g. -100 to 100). |
| **fit_classification** | string | `recommended` \| `allowed_weak_fit` \| `discouraged` \| `neutral`. |
| **explanation_reasons** | array | Short reason codes or messages (e.g. in_pack_preferred, section_affinity, in_pack_discouraged). |
| **industry_source_refs** | array | Industry keys that contributed (primary, secondary). |
| **warning_flags** | array | Optional warnings (e.g. cta_mismatch). |

---

## 4. Resolver behavior

- **Recommended**: Section in pack preferred_section_keys and/or section industry_affinity contains primary; high score.
- **Discouraged**: Section in pack discouraged_section_keys and/or section industry_discouraged contains primary; low/negative score.
- **Allowed weak fit**: Section has no strong signal or only secondary industry match; medium score.
- **Neutral**: No industry metadata and not in pack lists; default score (e.g. 0).
- **Multi-industry**: Secondary industries add smaller weight; primary dominates. Deterministic ordering when scores tie (e.g. by section_key).
- **Invalid/missing profile**: Fail safely to neutral ranking (all sections neutral, no throw).

---

## 5. API

- **Industry_Section_Recommendation_Resolver**: resolve( array $industry_profile, array|null $primary_pack, array $sections, array $options = [] ): Industry_Section_Recommendation_Result.
- **Industry_Section_Recommendation_Result**: get_items() (list of per-section result objects), get_ranked_keys() (ordered section_key list), to_array().

---

## 6. Implementation reference

- **Section_Schema**: FIELD_INTERNAL_KEY, FIELD_INDUSTRY_AFFINITY, FIELD_INDUSTRY_DISCOURAGED, FIELD_INDUSTRY_CTA_FIT, FIELD_INDUSTRY_NOTES.
- **Industry_Pack_Schema**: FIELD_PREFERRED_SECTION_KEYS, FIELD_DISCOURAGED_SECTION_KEYS.
- section-industry-affinity-contract.md: Field semantics.
