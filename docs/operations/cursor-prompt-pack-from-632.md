# Cursor Prompt Pack — Generated From Prompt 632

This prompt pack was generated from the prioritized implementation queue produced by **Prompt 632** ([implementation-queue-from-631.md](implementation-queue-from-631.md)). Only queue items that were marked as needing a **new focused implementation prompt**, **cleanup/de-scope prompt**, or **QA/regression prompt** are included. Items blocked on spec/product decision, explicitly deferred, requiring no new prompt, or to be handled manually outside Cursor are excluded and listed at the end.

**Execution order:** Run prompts in numerical order (633, then 634 when unblocked, etc.). Phase 1 prompt is 633; Phase 2 prompts are generated only after prerequisites are met (see Blocked Items).

---

# Prompt 633 — Industry Recommendation Regression Guard

## 1. Prompt Number

633

## 2. Prompt Title

Industry Recommendation Regression Guard — Run and Extend Tests

## 3. Purpose

Execute and extend the industry recommendation regression guard so that launch-industry and recommendation invariants are protected by automated tests. Prompt 632 identified this as the only unblocked implementation-capable work in the queue and designated it as Phase 1, requiring a QA/regression prompt.

## 4. Context

The AIO Page Builder plugin includes an industry subsystem with launch industries (cosmetology_nail, realtor, plumber, disaster_recovery), pack and overlay registries, section/page recommendation resolvers, and a substitute suggestion engine. The project has a regression guard specification (industry-recommendation-regression-guard.md) that defines checks for pack/ref integrity, scoring expectations, no-industry fallback, and substitute suggestion quality. Existing unit tests exist for the benchmark service and substitute engine; this prompt ensures the guard is fully implemented and runnable.

## 5. Controlling Sources

- [implementation-queue-from-631.md](implementation-queue-from-631.md) (Prompt 632) — Phase 1, §2 Prioritized Implementation Queue row 1, §7 Prompts Still Needed.
- [industry-recommendation-regression-guard.md](../qa/industry-recommendation-regression-guard.md) — Regression checks §3.1–3.4, test locations §4.
- [industry-recommendation-benchmark-protocol.md](../qa/industry-recommendation-benchmark-protocol.md) — Harness and scenario shape.
- [execution-brief-620-630.md](execution-brief-620-630.md) (Prompt 631) — §9 Final Recommendation (run guard once before or after next release).

## 6. Dependencies / Prerequisites

None. This prompt can run immediately. The plugin must already have Industry_Pack_Registry, Industry_Recommendation_Benchmark_Service, section/page recommendation resolvers, and Industry_Substitute_Suggestion_Engine; built-in pack definitions must be loadable.

## 7. Current State

The guard document exists and defines four check areas (pack/ref integrity, scoring expectations, no-industry fallback, substitute suggestion quality). Existing tests include Industry_Recommendation_Benchmark_Service_Test and Industry_Substitute_Suggestion_Engine_Test. The queue states that regression tests should be run or extended to satisfy the guard; some assertions may already exist; any gaps per §3–4 of the guard doc must be added.

## 8. Problem Statement

Without automated regression tests that match the industry recommendation regression guard, code or metadata changes could silently break launch-industry pack resolution, scoring invariants, no-industry fallback behavior, or substitute suggestion quality. The guard specification is defined but the full set of runnable checks may not be implemented or passing.

## 9. Objective

Ensure all regression checks described in docs/qa/industry-recommendation-regression-guard.md §3.1–3.4 are implemented as unit or regression tests, run them, and fix any failures so the guard passes. Prefer contract-level assertions over brittle exact-score assertions.

## 10. In Scope

- Industry_Pack_Registry loaded from built-in definitions; launch industry keys (cosmetology_nail, realtor, plumber, disaster_recovery) resolving to a pack; required pack schema fields and refs (token_preset_ref, preferred_section_keys) valid or empty.
- Industry_Recommendation_Benchmark_Service (or equivalent) producing scenarios with expected structure; at least one launch industry with non-empty top keys or non-all-zero fit_distribution when pack present (or documented neutral-only exception).
- Section and page template recommendation resolvers with empty profile or unknown industry_key returning neutral; no exception; benchmark run with null/empty profile not throwing.
- Industry_Substitute_Suggestion_Engine: empty candidates → empty array, no throw; when recommended candidates exist and original is discouraged/weak_fit, suggestions from recommended (or allowed_weak_fit per policy); deterministic ordering.
- Test files: plugin/tests/Unit/ (Industry_Recommendation_Benchmark_Service_Test, Industry_Recommendation_Regression_Guard_Test if added, Industry_Page_Template_Recommendation_Resolver_Test, Industry_Section_Recommendation_Resolver_Test, Industry_Substitute_Suggestion_Engine_Test).

## 11. Out of Scope

- Changing recommendation algorithms or metadata beyond what is required to fix failing guard assertions.
- Asserting exact score numbers or exact key order unless defined as stable in contract.
- Industry bundle apply, Step 2 Deny, or any other feature work.
- Security/privacy spot-check or ZIP upload cap smoke test (manual per Prompt 632).

## 12. Assumptions

- Built-in industry pack definitions exist and are loadable; LAUNCH_INDUSTRIES (or equivalent) constant/list is defined.
- Existing test classes and recommendation contracts are stable; new tests should align with industry-section-recommendation-contract, industry-page-template-recommendation-contract, industry-substitute-suggestion-contract.
- A documented “neutral-only” launch industry is acceptable only if explicitly documented in the guard or test.

## 13. Requirements

- All four launch industry keys resolve to a pack when built-in packs are loaded; pack has required schema fields; refs do not cause resolution to fatal.
- For each launch industry, benchmark run produces at least one scenario with non-zero total_evaluated and fit_distribution not all zero (or documented exception).
- Resolvers with empty profile or unknown industry_key return neutral for all items; benchmark with null/empty profile completes without throw.
- suggest_section_substitutes / suggest_template_substitutes: empty candidates → empty array; when recommended exist, suggestions from recommended/allowed_weak_fit items; ordering deterministic.

## 14. Non-Functional Requirements

- Tests must be deterministic and fast enough for normal CI/unit runs.
- Prefer contract-level assertions (fit_classification in allowed set, result shape) over brittle numeric assertions.
- If a benchmark snapshot is used, document how to refresh it and when (e.g. after intentional metadata improvement) per guard §4.

## 15. Constraints

- Do not remove or relax existing passing tests unless they conflict with the guard.
- Do not change production recommendation logic except to fix clear bugs that cause guard failure.
- Follow project test naming and structure; use existing test classes where the guard doc specifies (e.g. Industry_Recommendation_Benchmark_Service_Test, Industry_Substitute_Suggestion_Engine_Test).

## 16. Implementation Instructions

1. Read docs/qa/industry-recommendation-regression-guard.md in full, especially §3 (Regression checks) and §4 (Test locations and maintenance).
2. Locate or add the test classes referenced in the guard: Industry_Recommendation_Benchmark_Service_Test, Industry_Page_Template_Recommendation_Resolver_Test, Industry_Section_Recommendation_Resolver_Test, Industry_Substitute_Suggestion_Engine_Test; add Industry_Recommendation_Regression_Guard_Test if the guard recommends a dedicated test for pack refs and launch industries.
3. Implement or extend tests for §3.1 Critical pack/ref integrity: all four launch industry keys resolve to a pack; required pack fields present; refs valid or empty; resolution does not fatal.
4. Implement or extend tests for §3.2 Representative scoring expectations: benchmark run per launch industry; at least one scenario with non-zero total_evaluated and non-all-zero fit_distribution when pack_found (or document neutral-only exception); structure assertions.
5. Implement or extend tests for §3.3 No-industry fallback: resolvers with empty profile or unknown industry_key return neutral; no exception; benchmark with null/empty profile does not throw.
6. Implement or extend tests for §3.4 Substitute suggestion quality: empty candidates → empty array, no throw; one case where recommended candidates exist and suggestions are from recommended/allowed_weak_fit; ordering deterministic.
7. Run the full unit test suite for the industry recommendation and substitute engine tests; fix any failing tests or code only as needed to satisfy the guard.
8. Ensure no new linter or static analysis violations; follow project coding standards.

## 17. Validation / QA Checks

- All new or modified tests pass.
- Existing industry-related unit tests that were passing remain passing unless explicitly superseded by guard requirements.
- Guard document §3.1–3.4 check list can be traced to at least one automated test each.
- Run plugin test suite (or industry test subset) and confirm no regressions.

## 18. Deliverables

- Passing unit/regression tests that cover all four guard areas (pack/ref integrity, scoring expectations, no-industry fallback, substitute suggestion quality).
- Any new test file or test methods added and committed; existing test files updated only as needed to satisfy the guard.
- No production code changes unless necessary to fix a bug that causes a guard check to fail (document any such change briefly).

## 19. Output / File Handling

- Write or update test files under plugin/tests/Unit/; use existing test class names per guard §4.
- Do not create new documentation files unless the guard explicitly requires documenting a neutral-only exception or benchmark snapshot refresh process.
- In chat, do not paste full test file contents; summarize what was added or changed (file paths, test method names, and pass/fail outcome).

## 20. Completion Criteria

- All regression checks in docs/qa/industry-recommendation-regression-guard.md §3.1–3.4 are covered by automated tests.
- The relevant test suite (industry recommendation and substitute engine tests) runs and passes.
- Summary of changes (files touched, tests added/extended, any production fix) is reported in chat per §21.

## 21. Reporting Back

After finishing, report in chat: (1) which test file(s) were added or modified and how (e.g. added Industry_Recommendation_Regression_Guard_Test with pack integrity and launch-industry checks; extended Industry_Substitute_Suggestion_Engine_Test with empty-candidates and suggestions-from-recommended cases). (2) That the industry recommendation regression guard suite passes (or list any remaining failure with cause). (3) Any production code change made to satisfy the guard and why. Keep the report to a short paragraph; no full file dumps.

---

# Excluded / Blocked Items

The following were **not** turned into prompts in this pack because they are blocked on spec/product decision or are to be handled outside Cursor:

| Item | Reason excluded |
|------|------------------|
| **Industry bundle apply** | Blocked on persistence/store or registry-merge design and on insertion of industry-bundle-apply-spec-note text into the master spec. Per Prompt 632: do not generate prompts for items blocked on spec/product decision. Once you define persistence and add the spec note, a separate implementation prompt should be created (e.g. Prompt 634) using docs/operations/industry-bundle-apply-acceptance-criteria.md. |
| **Build Plan Step 2 Deny / workspace detail–table improvements** | No decision record; scope unclear. Per Prompt 632: do not generate prompts for blocked items. If you add a decision and acceptance criteria, generate a new implementation prompt then. |
| **Security/privacy remediation spot-check re-verification** | Prompt 632 §7: "No new prompt; complete manually from close report." Owner QA/you; manual. |
| **ZIP upload cap optional smoke test** | Prompt 632 §7: "No new prompt; manual." Owner QA. |

Deferred items (token application, privacy scope expansion, profile snapshot, cost_placeholder, rollback from workspace) are not in the queue and were not considered for prompt generation.

---

# Prompts Not Needed

- **Security/privacy spot-check** — Implement directly from existing approved context (close report + ledger); manual/QA.
- **ZIP cap smoke test** — Manual; optional.
- **Industry bundle apply** — Needs a new focused implementation prompt **after** unblock (persistence + spec note); not generated in this pack so that the pack stays execution-ready for unblocked work only.

---

# Recommended Execution Order

1. **Prompt 633** — Industry Recommendation Regression Guard (run now; Phase 1).
2. After you unblock industry bundle apply (persistence design + spec note): create and run a new implementation prompt (e.g. 634) for industry bundle apply using docs/operations/industry-bundle-apply-acceptance-criteria.md.
3. If you add a decision and acceptance criteria for Build Plan Step 2 Deny / workspace: create and run a new implementation prompt for that work.
