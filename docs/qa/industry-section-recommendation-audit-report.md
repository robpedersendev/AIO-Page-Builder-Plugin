# Industry Section Recommendation Engine Correctness Audit Report (Prompt 591)

**Spec:** Section recommendation contracts; subtype/goal/secondary-goal recommendation docs; conflict/precedence docs.  
**Purpose:** Audit of the section recommendation engine so industry, subtype, primary-goal, secondary-goal, bundle, and caution inputs affect scoring in the intended bounded order; recommendations remain explainable, deterministic, and fallback-safe.

---

## 1. Scope audited

- **Resolver:** `plugin/src/Domain/Industry/Registry/Industry_Section_Recommendation_Resolver.php` — `resolve( industry_profile, primary_pack, sections, options )`.
- **Result:** `Industry_Section_Recommendation_Result` (items with section_key, score, fit_classification, explanation_reasons, industry_source_refs, warning_flags).
- **Subtype influence:** `Industry_Subtype_Section_Recommendation_Extender` applied via options after base resolution.
- **Cache:** Industry cache key builder and read-model cache service when provided; key includes profile, sections, subtype_key.

---

## 2. Findings summary

| Area | Result | Notes |
|------|--------|--------|
| **Scoring order** | Verified | Pack preferred (+50) / discouraged (-50); section affinity primary (+30) / discouraged (-30); section affinity secondary (+15/-15); CTA fit (+5). Bounded constants; no hidden order. |
| **Fallback missing/invalid context** | Verified | When `primary_industry_key` is empty, all sections get score 0 and fit_classification `neutral`; no exception. Missing pack yields neutral influence for pack_preferred/pack_discouraged (empty arrays). |
| **Explanation metadata** | Verified | Each item includes `explanation_reasons` (e.g. in_pack_preferred, section_affinity_primary) and `industry_source_refs`; matches actual score inputs. |
| **Full-library availability** | Verified | All input sections are scored and returned; none removed. Sort is by score desc then section_key; full list remains in result. |
| **Determinism** | Verified | Same inputs produce same outputs; cache key includes profile, sections, options_for_key (subtype_key). usort stable by score then key. |
| **Subtype layer** | Verified | Optional `subtype_definition` and `subtype_extender` in options; extender `apply_subtype_influence()` runs after base resolution. Subtype does not remove sections. |
| **Goal/secondary-goal** | Observation | Goal and secondary-goal influence on section recommendation is applied upstream (e.g. via pack or overlay data) or in extender; resolver itself uses industry_profile and primary_pack. Contract alignment: primary goal higher precedence than secondary is a product-level rule; resolver receives already-resolved pack/profile. |

---

## 3. Recommendations

- **No code changes required.** Scoring order, fallback, explanation, and full-library behavior are correct and documented.
- **Tests:** Add or extend regression tests for representative industry/subtype/goal combinations and invalid-context/fallback cases per prompt 591 test requirements.

---

## 4. References

- [industry-implementation-audit-service-map.md](../operations/industry-implementation-audit-service-map.md)
- [industry-audit-remediation-ledger.md](../operations/industry-audit-remediation-ledger.md)
