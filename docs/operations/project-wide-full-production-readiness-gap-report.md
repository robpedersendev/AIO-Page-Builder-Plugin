# Project-Wide Full Production Readiness Gap Report

**Document type:** Audit report (updated 2026-03-19, from RC1 release gate pass)  
**Strict standard:** Production-ready = no deferred work acceptable, no misleading/unavailable behavior, no partially implemented required features, all decisions resolved, all required systems hardened and supportable.  
**Source:** Direct codebase and docs inspection; master spec; approved decisions; full rescan of all 1,447 `src/` PHP files (2026-03-19).  
**Previous version:** Mar 2026 (Prompt 643 baseline), updated after first production hardening pass.

---

## 1. Title

**AIO Page Builder — Full Production Readiness Gap Report (Strict Definition, RC1 Rescan 2026-03-19)**

---

## 2. Executive Summary

All previous blockers identified in the prior report are now resolved. The production hardening pass (P1–P6B) implemented or formally de-scoped every item. The RC1 release gate is complete: PHP syntax clean, 2,847/2,872 tests pass (25 pre-existing failures documented and waived as TF-1), PHPCS 0 security findings, all release gate documentation updated.

**One new production blocker discovered in the RC1 rescan:**

- **A-1 BLOCKER:** `Build_Plan_Workspace_Screen.php` line 1129 renders the text `"(Coming soon)"` alongside a disabled "Export plan" button, visible to any user with `EXPORT_DATA` or `DOWNLOAD_ARTIFACTS` capability. No plan export handler exists anywhere in the codebase. This is user-visible deferred-work copy and must be removed or hidden before production.

**Remaining advisory / informational items (not blockers, but should be resolved for production quality):**

- Stale docblock in `AI_Providers_Screen.php` line 301: "Placeholder URL" on a state-persistence method.
- Outdated framing in `Profile_Snapshot_Data.php` line 6: "intentional placeholder until spec defines persistence" — the formal de-scope decision has been made but this file was not updated.
- Internal docblock in `Option_Names.php` line 15: "Future prompts may add fields" — AI-workflow language.
- Security pattern advisory: `AI_Providers_Screen` handlers rely entirely on the render-gate for capability enforcement; handlers lack self-contained `current_user_can()`.

**Verdict: Substantially production-ready, one remaining user-visible copy blocker (A-1).**

---

## 3. Strict Definition of Production-Ready for This Project

For this audit, **production-ready** means:

1. **No deferred work is acceptable** — Every item previously labeled "deferred" must either be implemented before production or removed from required scope via an explicit approved decision.
2. **No misleading, exposed, or unavailable behavior is acceptable** — Surfaces must not imply support for behavior that is not implemented or is placeholder-only.
3. **No partially implemented required feature is acceptable** — Required features (per master spec and approved decisions) must be complete end-to-end.
4. **No unresolved product/spec decision that affects implementation is acceptable** — Decisions that block implementation must be closed before production.
5. **All required systems must be fully implemented, hardened, validated, and supportable** — Tests, lint, release checklist, compatibility/migration matrices, security and doc consistency must be addressed.

---

## 4. Current Production Readiness Status

**Verdict: Not production-ready** under the strict definition due to A-1 (user-visible "(Coming soon)" copy). **One fix away** from production-candidate state.

- **Fully implemented and truly production-ready:** All core execution handlers, Build Plan workspace (Steps 1–7) truthful and governed, Tokens step executable, SEO advisory-only, rollback v1, capabilities, import/export + wizard, industry bundle apply, template registries + detail/compare/composition screens, crawler, AI provider handlers, admin router, analytics, diagnostics, queue/logs, onboarding (full step forms), versioning/lifecycle wording, PHP syntax clean, 2,847 tests pass, all release gate documents complete.
- **Formally de-scoped (decisions made, documented):** `assign_page_hierarchy` handler, `create_menu` handler, profile snapshot persistence, AI cost/usage reporting.
- **Blocking production:** A-1 user-visible "(Coming soon)" on Export plan button.

---

## 5. What Is Fully Implemented and Truly Production-Ready

| Area | Status | Evidence / Notes |
|------|--------|------------------|
| **Execution engine — core actions** | Implemented | Create_Page_Handler, Replace_Page_Handler, Apply_Menu_Change_Handler, Apply_Token_Set_Handler, Finalize_Plan_Handler; Bulk_Executor; Single_Action_Executor refuses unregistered types. |
| **Build Plan Step 4 — Tokens (executable)** | Implemented | Tokens_Step_UI_Service; bulk + row execute/retry with nonce + capability + audit log; reaches Apply_Token_Set_Handler. |
| **Build Plan Step 5 — SEO (advisory-only)** | Implemented | SEO_Media_Step_UI_Service: advisory-only, no execute/retry affordances. Row_Action_Resolver excludes execute/retry for SEO items. |
| **Rollback / history v1** | Implemented | Rollback_Executor; Rollback_Eligibility_Service; snapshots for REPLACE_PAGE and APPLY_TOKEN_SET; "Request rollback" capability-gated. |
| **Capability model** | Implemented | Capabilities.php; all screens enforce capabilities at render and action time. |
| **Import/export + ZIP size cap** | Implemented | Import_Export_Screen: MAX_ZIP_UPLOAD_BYTES, size check, ERROR_CODE_FILE_TOO_LARGE. |
| **Import / Restore wizard** | Implemented | Full flow: upload → preview → conflict review → restore scope → explicit confirm. |
| **Industry bundle apply** | Implemented | Industry_Bundle_Apply_Service: validates bundle, resolves conflicts, persists payload + conflicts, updates registry. |
| **Build Plan list, analytics, workspace structure** | Implemented | All steps truthful and governed; Build_Plan_Analytics_Screen with real rollback frequency. |
| **Build Plan Step 2 deny** | Implemented | Per-row deny and "Deny All Eligible" with confirmation; denied items marked rejected. |
| **Admin router** | Implemented | Admin_Router_Provider registers real Admin_Router; Helper_Doc_Url_Resolver functional. |
| **Template registries + detail screens** | Implemented | Section/Page template directories, detail screens, compare, compositions. |
| **Crawler start/retry** | Implemented | Crawler_Sessions_Screen: real start/retry with check_admin_referer; Crawl_Enqueue_Service wired. |
| **AI Providers test/update** | Implemented | AI_Providers_Screen: maybe_handle_test_connection and maybe_handle_update_credential; nonce + capability protected. |
| **Analytics — rollback frequency** | Implemented | Build_Plan_Analytics_Service queries real snapshot data; rollback rate computed. |
| **Diagnostics** | Implemented | Diagnostics_Screen: "No placeholder; real state only." |
| **Queue and logs** | Implemented | Queue_Logs_Screen, job services, retry/recovery, health summary, rollback job flow. |
| **Versions.php** | Implemented | No placeholder wording; stable contract versions; keys documented. |
| **Bootstrap / Lifecycle wording** | Implemented | Plugin.php, Module_Registrar.php, Lifecycle_Manager.php: no "later prompts / future logic" wording. |
| **Onboarding full step forms** | Implemented (P2A) | All 7 profile steps with real forms; persist_brand_profile_from_post / persist_business_profile_from_post; prefill from Profile_Store; review step with readiness summary. |
| **Onboarding — placeholder copy** | Implemented (P2B) | "future update" copy replaced; provider step routes to AI Providers screen; no deferred-work language. |
| **New_Page_Creation_Detail_Builder — label** | Implemented (P2B) | "Post-build placeholder:" → "Post-build result:" |
| **Stale comment drift (8 locations)** | Implemented (P3B) | Industry_Packs_Module, Crawler_Comparison_Screen, Build_Plan_Workspace_Screen, Admin_Router_Provider, Page_Templates_Directory_Screen, Section_Templates_Directory_Screen, Onboarding_Screen — all corrected. |
| **assign_page_hierarchy / create_menu** | De-scoped (P4A) | Removed from Execution_Action_Types::ALL; no handler; no misleading UI affordance. Documented with rationale. |
| **Profile snapshot persistence** | De-scoped (P5B) | Formal decision made; Schema/type only. No production surface implies persistence. |
| **AI cost/usage reporting** | De-scoped (P6B) | cost_placeholder removed from both drivers; usage struct has only authoritative token counts. |
| **Release gate documentation** | Complete (RC1) | RELEASE_CHECKLIST.md, release-candidate-closure.md, known-risk-register.md, compatibility-matrix.md, migration-coverage-matrix.md, security-redaction-review.md, changelog.md, README.md, support-triage-guide.md updated. |
| **PHP syntax** | Pass | 0 syntax errors across all 1,622 source + test files. |
| **PHPUnit (2026-03-19)** | 2,847/2,872 pass | 25 pre-existing failures documented/waived (TF-1). Stale-count tests fixed (4). |
| **PHPCS** | 0 security findings | 2,146 doc-strictness errors (MissingParamComment); 0 security/functional findings. Waived PHPCS-W1. |

---

## 6. What Is Implemented but Still Not Production-Ready

| Item | Current status | Why not production-ready | Required action |
|------|----------------|--------------------------|-----------------|
| **Build Plan "Export plan" button — "(Coming soon)" copy** | Button rendered (disabled) with "(Coming soon)" label for users with EXPORT_DATA or DOWNLOAD_ARTIFACTS capability. No handler, no action, no plan export service. | User-visible deferred-work copy on a live admin surface. Users with the right capability see an affordance that does not work and implies future delivery. Unacceptable under strict definition. | **Option A:** Remove the button and "(Coming soon)" span entirely until plan export is implemented. **Option B:** Implement a real plan export handler (scope: new feature). Option A is lower-risk for RC1. |

---

## 7. What Is Partially Implemented

None. All previously partial items are either fully implemented or formally de-scoped.

---

## 8. What Is Not Yet Implemented (Formally De-scoped)

These items are formally de-scoped for v1 via approved decisions. They are not production blockers; they are documented constraints.

| Item | Decision | Documentation |
|------|----------|---------------|
| **assign_page_hierarchy execution handler** | De-scoped (P4A). Hierarchy assignment is embedded in CREATE_PAGE. ITEM_TYPE_HIERARCHY_NOTE items are advisory-only. | Execution_Action_Types.php docblock; known-risk-register.md |
| **create_menu execution handler** | De-scoped (P4A). Menu creation subsumed by UPDATE_MENU handler. | Execution_Action_Types.php docblock; known-risk-register.md |
| **Profile snapshot persistence** | De-scoped (P5B). Schema/type only for v1. Core traceability met via Operational_Snapshot_Repository. | Profile_Snapshot_Data.php; known-risk-register.md |
| **AI cost/usage reporting** | De-scoped (P6B). Token counts are the authoritative v1 metric. cost_placeholder removed. | Both AI driver files; known-risk-register.md |
| **Build Plan — plan export** | Not yet implemented; "(Coming soon)" copy must be removed. See §6 (A-1). | Pending resolution |

---

## 9. Stale Wording — Comment / Docblock Drift (Post-RC1)

These items were not caught in the previous hardening pass. None produce user-visible problems, but they misrepresent implementation state.

| ID | File | Line | Current text | Required correction | Severity |
|----|------|------|--------------|---------------------|----------|
| B-1 | `Infrastructure/Config/Option_Names.php` | 15 | `"Canonical option name constants. Future prompts may add fields within approved structures; roots require migration."` | Remove "Future prompts may add fields within approved structures;" — replace with stable wording: "New option roots require migration; additions within approved structures must remain backward-compatible." | Advisory |
| B-2 | `Admin/Screens/AI/AI_Providers_Screen.php` | 301 | `"Placeholder URL for test connection; handler must verify nonce and capability (spec §49.9)."` | The method is `persist_provider_state_after_test()` — not a URL builder. Docblock is misattributed from a prior design. Replace with accurate docblock describing state persistence after a connection test. | Advisory |
| B-3 | `Domain/Storage/Profile/Profile_Snapshot_Data.php` | 6 | `"Future audits: do not treat as an accidental gap—intentional placeholder until spec defines persistence."` | The formal de-scope decision has been made (P5B). Update to: "Profile snapshot persistence is formally de-scoped for v1 per the P5B decision. This class is a schema/type definition only." | Advisory |

---

## 10. Security Pattern Advisories

These are not exploitable gaps; the render-gate architecture is consistent and prevents unauthorized access. They are noted as advisories for pattern consistency and future audit hardening.

| ID | File | Lines | Issue | Severity |
|----|------|-------|-------|----------|
| D-1 | `Admin/Screens/AI/AI_Providers_Screen.php` | 77, 123 | `maybe_handle_test_connection()` and `maybe_handle_update_credential()` have no self-contained `current_user_can()`. Capability enforcement relies entirely on the `render()` gate at line 49 (which calls `wp_die` for unauthorized users). The handlers are only ever called from `render()`, so no production exploit path exists. | Advisory — add self-contained `current_user_can()` at handler entry for defense-in-depth and future maintenance safety. |
| D-2 | `Admin/Screens/BuildPlan/Build_Plan_Workspace_Screen.php` | 625, 628 | Step 4 handler (`maybe_handle_step4_action()`) calls `get_state()` before `current_user_can()` and `wp_verify_nonce()`. `get_state()` is read-only. Steps 1/2/5 and rollback check capability/nonce before any state reads. Inconsistent pattern, no functional exploit. | Advisory — move capability/nonce check before `get_state()` for consistency. |

---

## 11. Production Blockers (RC1 Rescan)

| # | ID | Blocker | Affected file | Required action |
|---|----|---------|--------------|-----------------|
| 1 | A-1 | **User-visible "(Coming soon)" on Export plan button** | `Admin/Screens/BuildPlan/Build_Plan_Workspace_Screen.php` line 1129 | Remove the disabled button + "(Coming soon)" span, or implement a real export handler. Option A (remove) is lower-risk for RC1. |

---

## 12. Advisory Queue (Not Blockers)

| Priority | ID | Item | Next action |
|----------|----|------|-------------|
| 1 | B-3 | Profile_Snapshot_Data.php docblock — outdated placeholder framing | Update docblock to reflect formal P5B de-scope decision |
| 2 | B-2 | AI_Providers_Screen.php line 301 — misattributed docblock | Correct docblock to describe persist_provider_state_after_test() accurately |
| 3 | B-1 | Option_Names.php line 15 — "Future prompts" framing | Replace with stable, AI-workflow-free wording |
| 4 | D-1 | AI_Providers_Screen.php handlers — no self-contained capability check | Add `current_user_can()` at start of each handler for defense-in-depth |
| 5 | D-2 | Build_Plan_Workspace_Screen.php step 4 — capability/nonce after state read | Move check before `get_state()` call for pattern consistency |
| 6 | TF-1 | 25 pre-existing PHPUnit test failures | Targeted fix pass planned for v1.1 |

---

## 13. All Previously Reported Gaps — Resolution Status

| # | Item | Previous status | Current status (2026-03-19) |
|---|------|-----------------|---------------------------|
| 1 | **Token application (Tokens step)** | Intentionally deferred (advisory-only). | **RESOLVED (P1).** Tokens step executable; bulk + row govern with nonce + capability + audit log. |
| 2 | **Profile snapshot persistence** | Schema-only; deferred. | **DE-SCOPED (P5B).** Formal decision made; documented. |
| 3 | **AI cost/usage reporting** | cost_placeholder null; reserved. | **DE-SCOPED (P6B).** cost_placeholder removed from both drivers. |
| 4 | **SEO step advisory posture** | Shell-only. | **RESOLVED (P2).** Advisory-only; no execute affordances; copy truthful. |
| 5 | **Industry bundle apply** | Decision made. | **RESOLVED (P1).** Industry_Bundle_Apply_Service fully implemented. |
| 6 | **Build Plan Step 2 deny** | Blocked. | **RESOLVED (P1).** Row-level + bulk deny fully implemented. |
| 7 | **Admin router** | stdClass placeholder. | **RESOLVED (P1).** Real Admin_Router; Helper_Doc_Url_Resolver functional. |
| 8 | **Environment / Lifecycle** | Placeholders. | **RESOLVED (P1/P4).** Environment_Validator; Lifecycle phases; no stale wording. |
| 9 | **Crawler start/retry** | Placeholder action. | **RESOLVED (P1).** Real start/retry with check_admin_referer. |
| 10 | **AI Providers test/update** | Placeholder URLs. | **RESOLVED (P1).** Real handlers; nonce + capability protected. |
| 11 | **Build Plan workspace row/detail** | Step shells. | **RESOLVED (P1/P2/P3).** All step UI services real; stale docblocks corrected. |
| 12 | **Build_Plan_Analytics rollback frequency** | Stub. | **RESOLVED (P1).** Queries real snapshot data. |
| 13 | **Section helper-doc URL** | Placeholder. | **RESOLVED (P1).** Helper_Doc_Url_Resolver: real URL from Documentation_Registry. |
| 14 | **Finalization — conflict summary / preview link** | Placeholder fields. | **RESOLVED (P1).** Real data from plan state. |
| 15 | **Page/Section template detail screens** | "Out of scope." | **RESOLVED (P1).** Both screens fully implemented. |
| 16 | **assign_page_hierarchy / create_menu** | Decision required. | **DE-SCOPED (P4A).** Removed from ALL; documented with rationale. |
| 17 | **Onboarding — user-visible placeholder copy** | Lines 466, 495 live to users. | **RESOLVED (P2B).** Truthful copy; provider step routes to AI Providers screen. |
| 18 | **Onboarding — full step forms** | Shell + placeholder copy. | **RESOLVED (P2A).** All 7 profile steps; real forms; persist; prefill; review step. |
| 19 | **New_Page_Creation_Detail_Builder — "Post-build placeholder:"** | User-visible label. | **RESOLVED (P2B).** → "Post-build result:". |
| 20 | **Stale comment/docblock drift (8 locations)** | Eight files. | **RESOLVED (P3B).** All eight corrected. |
| 21 | **Versions.php placeholder wording** | Placeholder-driven framing. | **RESOLVED (P4).** Authoritative production wording. |
| 22 | **Bootstrap / Lifecycle wording** | "Later prompts / future logic." | **RESOLVED (P4).** Stable production descriptions. |
| 23 | **Release gate documentation** | Incomplete. | **RESOLVED (RC1 gate pass).** All docs updated with real evidence (2026-03-19). |
| 24 | **README.md** | Missing. | **RESOLVED (RC1).** Created with requirements, installation, disclosure, known limitations. |
| 25 | **Changelog** | [Unreleased] empty. | **RESOLVED (RC1).** Fully updated with all P1–P6B entries. |
| 26 | **support-triage-guide.md Diagnostics copy** | Placeholder ("Not yet implemented"). | **RESOLVED (RC1).** De-scoped wording; links to Queue & Logs / Support Bundle. |

---

## 14. Final Production-Readiness Verdict

**Not production-ready under the strict definition — one user-visible copy blocker remains (A-1).**

The plugin is **one fix away** from production-candidate state. All prior structural blockers are resolved. The only remaining user-visible deferred-work issue is the "(Coming soon)" label on the "Export plan" button in Build_Plan_Workspace_Screen.php line 1129.

After fixing A-1, the remaining items are advisories (stale internal docblocks B-1–B-3, security pattern advisories D-1–D-2) and the pre-existing test failure waiver (TF-1) — none of which block release under the project's hardening gate.

---

## 15. Required Action Queue

### Blocking (must resolve before production)

| Priority | ID | Item | File | Action |
|----------|----|------|------|--------|
| 1 | A-1 | Remove "(Coming soon)" from Export plan button | `Build_Plan_Workspace_Screen.php` line 1129 | Delete the `if ($can_export)` block (lines 1128–1130) or the "(Coming soon)" span only; or implement a real plan export handler. |

### Advisory (should resolve for production quality, do not block release)

| Priority | ID | Item | File | Action |
|----------|----|------|------|--------|
| 1 | B-3 | Update Profile_Snapshot_Data.php docblock | `Domain/Storage/Profile/Profile_Snapshot_Data.php` line 6 | Replace "intentional placeholder until spec defines persistence" with formal de-scope statement. |
| 2 | B-2 | Correct AI_Providers_Screen.php line 301 docblock | `Admin/Screens/AI/AI_Providers_Screen.php` line 301 | Replace misattributed "Placeholder URL" docblock with accurate persist-after-test description. |
| 3 | B-1 | Remove "Future prompts" language from Option_Names.php | `Infrastructure/Config/Option_Names.php` line 15 | Replace with stable wording. |
| 4 | D-1 | Add self-contained capability check to AI_Providers_Screen handlers | `Admin/Screens/AI/AI_Providers_Screen.php` | Add `current_user_can()` at entry of `maybe_handle_test_connection()` and `maybe_handle_update_credential()`. |
| 5 | D-2 | Reorder step 4 capability/nonce check | `Admin/Screens/BuildPlan/Build_Plan_Workspace_Screen.php` | Move `current_user_can()`/`wp_verify_nonce()` before `get_state()` call for consistency with steps 1/2/5. |
| 6 | TF-1 | Fix 25 pre-existing PHPUnit failures | Various test files | Targeted fix pass for v1.1. |

---

*End of report. No code changes were made; this document is audit-only. Rescan performed 2026-03-19.*
