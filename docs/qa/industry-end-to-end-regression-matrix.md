# Industry Subsystem End-to-End Integration and Regression Matrix (Prompt 609)

**Spec:** Major subsystem contracts (Prompts 318–585); audit service map; release docs; QA docs.  
**Purpose:** Full end-to-end regression matrix for the industry subsystem so representative flows from onboarding through recommendation, bundle selection, previews, Build Plans, reporting, export/restore, and scaffold tooling are verified as a coherent system.

---

## 1. Scope and coverage

This matrix defines **journeys** and **scenarios** that integration or E2E tests should cover. It does not replace unit tests; it complements them by asserting cross-feature behavior.

---

## 2. Representative journeys

| Journey | Steps | Key contracts | Priority |
|--------|--------|----------------|----------|
| **Onboarding / profile save** | Load onboarding or profile settings → set industry_key (and optionally subtype, conversion goal) → save → verify profile persisted; optional: no industry → neutral behavior. | Industry_Profile_Repository, Industry_Profile_Validator, Industry_Profile_Schema; nonce + capability. | P0 |
| **Section recommendation** | Load profile with launch industry → request section recommendations for library → receive scored list with fit_distribution; no profile / unknown industry → neutral. | Industry_Section_Recommendation_Resolver, cache, pack registry, overlay precedence. | P0 |
| **Page template recommendation** | Same as section with page template library; verify at least one launch industry has non-all-neutral when pack present. | Industry_Page_Template_Recommendation_Resolver; industry-recommendation-regression-guard. | P0 |
| **Bundle selection and comparison** | Resolve starter bundle for industry (and subtype) → get bundle list; compare bundles; no pack → safe fallback. | Industry_Starter_Bundle_Registry, overlay; Industry_Starter_Bundle_Comparison_Screen (read-only). | P1 |
| **Preview / detail** | Request section helper doc or page one-pager for industry + section/page + subtype/goal → receive composed content; missing overlay → fallback. | Industry_Helper_Doc_Composer, Industry_Page_OnePager_Composer; allowed regions only. | P1 |
| **Build Plan review** | Generate or load Build Plan from starter bundle → score/explain items → approve/deny (gated); dry-run only until explicit approval. | Industry_Starter_Bundle_To_Build_Plan_Service, scoring, approval gates; no auto-execution. | P1 |
| **What-if simulation** | Run simulation with alternate profile/bundle → compare results; verify read-only, no profile overwrite. | Industry_What_If_Simulation_Service; comparison parity. | P1 |
| **Reporting (health, completeness, gap, drift)** | Load dashboard or readiness screens → verify counts/severities from health, completeness, gap, scaffold services; missing container → zeros and links. | Industry_Author_Dashboard_Screen, Future_Industry_Readiness_Screen, report services. | P1 |
| **Export / restore** | Export with industry profile → restore on same or test site → validate schema version and profile shape; invalid payload → restore fails safely. | Industry_Export_Restore_Schema, Export_Generator, Restore_Pipeline; cache invalidation after restore. | P1 |
| **Scaffold / promotion readiness** | Request scaffold completeness and promotion-readiness reports → verify advisory only; no activation or promotion. | Industry_Scaffold_Completeness_Report_Service, Industry_Scaffold_Promotion_Readiness_Report_Service. | P2 |
| **Override and conflict** | Record override (section/page/build_plan_item) → run conflict detector → verify stale/missing target reported; repair suggestion bounded. | Industry_Override_*_Service, Industry_Override_Conflict_Detector, Industry_Repair_Suggestion_Engine. | P2 |

---

## 3. Failure and fallback scenarios

| Scenario | Expected behavior | Verification |
|----------|-------------------|--------------|
| **No profile / empty industry_key** | Section and page recommendation return neutral; no throw. | Integration test with null/empty profile. |
| **Unknown industry_key** | Neutral recommendations; safe fallback. | Unit/regression (industry-recommendation-regression-guard). |
| **Invalid restore payload** | Restore pipeline does not apply; no partial overwrite. | Restore failure-path test. |
| **Missing container / missing service** | Dashboard and readiness screens show zeros; links still valid; no fatal. | Dashboard view model with null container. |
| **Nonce or capability failure on mutation** | Redirect or wp_die; no state change. | Security audit (608); optional failure-path test. |
| **Preview/simulation** | Never write to profile or overrides. | Assert no mutation in simulation/preview paths. |

---

## 4. Existing coverage (reference)

- **Unit:** Industry_* unit tests cover resolvers, composers, registries, override services, profile repository, export/restore, scaffold services, LPagery, conflict detector, repair engine, etc. (see plugin/tests/Unit/Industry_*).
- **Regression:** Industry_Recommendation_Benchmark_Service and industry-recommendation-regression-guard.md define launch-industry and fallback checks.
- **Integration:** Rendering_Survivability_Integration_Test and FormProviderIntegrationRegressionHarness exist; industry-specific integration tests for full journeys are the gap this matrix addresses.

---

## 5. Recommended test additions

- **Integration:** One or more tests that run "profile save → section recommendation → page recommendation" and "bundle resolution → Build Plan summary" with real container and fixture profile.
- **Restore failure:** Test Restore_Pipeline with invalid schema version or malformed industry payload; assert no apply and optional industry cache not invalidated on failure path.
- **Dashboard/readiness:** Test Industry_Author_Dashboard_Screen::get_view_model() (or equivalent) with null container and with mocked services returning known counts; assert link keys and zero counts.
- **Permission failure:** Test that mutation actions (e.g. override save) with invalid nonce or insufficient capability do not persist changes.

---

## 6. References

- [industry-implementation-audit-service-map.md](../operations/industry-implementation-audit-service-map.md)
- [industry-recommendation-regression-guard.md](industry-recommendation-regression-guard.md)
- [industry-audit-remediation-ledger.md](../operations/industry-audit-remediation-ledger.md)
