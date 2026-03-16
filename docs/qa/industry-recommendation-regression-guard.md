# Industry Recommendation Regression Guard (Prompt 393)

**Spec**: recommendation contracts; industry-recommendation-benchmark-protocol; industry-pack-release-gate.  
**Purpose:** Regression guards so future code or metadata changes cannot silently degrade industry recommendation quality, break critical pack references, or reintroduce obviously wrong default rankings for launch industries. Guards protect key quality invariants without freezing all recommendations or blocking legitimate metadata improvements.

---

## 1. Scope

- **Launch industries:** cosmetology_nail, realtor, plumber, disaster_recovery.
- **Guarded invariants:** Critical pack/ref integrity; representative scoring expectations; no-industry fallback; substitute suggestion quality.
- **Out of scope:** Freezing every ranking nuance; brittle tests for minor score shifts; blocking legitimate metadata improvements.

---

## 2. Acceptable change vs regression

| Change type | Acceptable | Regression |
|------------|------------|------------|
| **Metadata improvement** | Adding industry_affinity, preferred_section_keys, or pack refinements that improve fit. | Removing or inverting pack refs so a launch industry loses its pack or top recommendations. |
| **Scoring tune** | Adjusting score weights within contract range; reordering within same fit_classification. | Launch industry with pack present returns all neutral; or recommended items become discouraged without justification. |
| **New industry** | Adding a new industry pack and scenarios. | Breaking existing launch industry pack registration or refs. |
| **Fallback behavior** | No profile / unknown industry_key → neutral, no throw. | Resolver throws or returns non-neutral when it should fall back. |
| **Substitute engine** | Empty candidates → empty list; discouraged/weak_fit → suggestions when recommended exist. | Throwing on empty candidates; returning non-empty when no recommended candidates. |
| **Registry refs** | New refs; deprecation with safe fallback. | Critical refs (e.g. token_preset_ref, supported_page_families) removed or broken so resolution fails. |

---

## 3. Regression checks

### 3.1 Critical pack/ref integrity

- **Check:** All four launch industry keys resolve to a pack from the registry when built-in packs are loaded.
- **Check:** Each pack has required schema fields (industry_key, name, summary, status, version_marker); version_marker in supported list.
- **Check:** Pack refs (e.g. token_preset_ref, preferred_section_keys) are either empty or valid format; resolution does not fatal.
- **Automation:** Unit test with Industry_Pack_Registry loaded from built-in definitions; assert all LAUNCH_INDUSTRIES have pack_found true and no invalid refs.

### 3.2 Representative scoring expectations (launch industries)

- **Check:** For each launch industry, page (and optionally section) recommendation run produces at least one scenario with non-zero total_evaluated; fit_distribution includes at least one of recommended, neutral, discouraged, or allowed_weak_fit (not all zero).
- **Check:** No launch industry with pack present returns “all neutral” for both page and section unless metadata intentionally has no affinity (documented exception).
- **Check:** Top N keys are deterministic for same inputs; ranked order stable.
- **Automation:** Regression test using Industry_Recommendation_Benchmark_Service with real or fixture registries; assert per-scenario structure and that at least one launch industry has non-empty top_template_keys or non-all-zero fit_distribution when pack_found (or document known “neutral-only” industry).

### 3.3 No-industry fallback

- **Check:** Section and page template recommendation resolvers with empty profile or unknown industry_key return neutral for all items; no exception.
- **Check:** Benchmark service run with null/empty profile does not throw; scenarios complete with neutral results.
- **Automation:** Existing tests (e.g. test_invalid_or_incomplete_profile_yields_neutral); add or extend test for unknown industry_key.

### 3.4 Substitute suggestion quality

- **Check:** suggest_section_substitutes / suggest_template_substitutes with no recommended candidates return empty array; no throw.
- **Check:** When original is discouraged/weak_fit and recommended candidates exist, suggestions returned are from result items with fit recommended (or allowed_weak_fit when policy allows); ordering deterministic.
- **Automation:** Existing Industry_Substitute_Suggestion_Engine_Test; add regression test for empty candidates and for at least one “returns suggestions when recommended exist”.

---

## 4. Test locations and maintenance

- **Pack integrity:** Extend Industry_Recommendation_Benchmark_Service_Test or add Industry_Recommendation_Regression_Guard_Test (pack refs, launch industries present).
- **Scoring / benchmark:** Industry_Recommendation_Benchmark_Service_Test; add assertions for representative expectations (structure, non-empty top keys or non-zero fit where pack present).
- **Fallback:** Industry_Page_Template_Recommendation_Resolver_Test, Industry_Section_Recommendation_Resolver_Test (no profile, unknown industry).
- **Substitute:** Industry_Substitute_Suggestion_Engine_Test (empty candidates; suggestion shape).
- **Maintainability:** Prefer contract-level assertions (resolver returns result, fit_classification in allowed set, no exception). Avoid asserting exact score numbers or exact key order unless defined as stable contract. When benchmark snapshot is used, document how to refresh and when (e.g. after intentional metadata improvement).

---

## 5. Release gate and risk register

- **Release gate:** [industry-pack-release-gate.md](../release/industry-pack-release-gate.md) references this guard; recommendation quality row can require “regression guards run and pass” or documented waiver.
- **Known risks:** [known-risk-register.md](../release/known-risk-register.md) IND-1/IND-2; mitigations include this regression guard to catch drift before release.

---

## 6. Cross-references

- [industry-recommendation-benchmark-protocol.md](industry-recommendation-benchmark-protocol.md) — harness and scenario shape.
- [industry-lifecycle-regression-guard.md](industry-lifecycle-regression-guard.md) — lifecycle and fallback.
- [industry-section-recommendation-contract.md](../contracts/industry-section-recommendation-contract.md), [industry-page-template-recommendation-contract.md](../contracts/industry-page-template-recommendation-contract.md), [industry-substitute-suggestion-contract.md](../contracts/industry-substitute-suggestion-contract.md).
