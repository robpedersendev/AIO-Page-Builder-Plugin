# Project-Wide Full Production Readiness Gap Report

**Document type:** Audit report (updated Mar 2026, from Prompt 643 baseline)  
**Strict standard:** Production-ready = no deferred work acceptable, no misleading/unavailable behavior, no partially implemented required features, all decisions resolved, all required systems hardened and supportable.  
**Source:** Direct codebase and docs inspection; master spec; approved decisions; full rescan covering all src/ files.

---

## 1. Title

**AIO Page Builder — Full Production Readiness Gap Report (Strict Definition, Mar 2026 Rescan)**

---

## 2. Executive Summary

Significant progress since the Prompt 643 baseline. The following major blockers are now resolved: industry bundle apply handler + persistence, admin router (real implementation), helper-doc URL resolver, Build Plan Analytics rollback frequency, crawler start/retry actions, AI Providers test/update credential handlers, Versions.php placeholder wording, Bootstrap/Lifecycle wording, Build Plan Steps 4 (Tokens) and 5 (SEO), Industry Bundle preview screen stale copy, finalization step placeholder fields, and page/section template detail screens.

The plugin is **still not production-ready** under the strict definition. The remaining blockers are a smaller, tighter set.

**Summary of remaining gaps:**

- **User-visible placeholder copy (high priority):** Onboarding step UI displays "will be added in a future update" copy to users; `New_Page_Creation_Detail_Builder` shows "Post-build placeholder:" as a label in the admin.
- **Stale production wording (comment/docblock drift):** Six comment or docblock locations still contain "future prompts", "nonce reserved for future", "placeholders in this prompt", or "out of scope" framing.
- **Unimplemented functionality:** Onboarding full step forms (decision/implementation required); `assign_page_hierarchy` and `create_menu` handlers (decision required); profile snapshot persistence (deferred by decision, intentional); AI cost modeling (deferred by decision, intentional).
- **Hardening/QA/release:** Release checklist, Plugin Check, compatibility/migration matrices, security review, doc-to-UI consistency remain as release blockers.

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

**Verdict: Not production-ready** under the strict definition above, but significantly closer than the Prompt 643 baseline.

- **Fully implemented and truly production-ready:** Core execution engine (create_page, replace_page, update_menu, apply_token_set, finalize_plan), rollback/history v1, capability model, import/export with ZIP size cap, Import/Restore wizard, Build Plan structure (Steps 1–7) with Tokens step execution, SEO step advisory-only, Step 2 deny, template registries, section/page template detail screens, composition screens, diagnostics, queue/logs, ACF/industry/restore stabilization, crawler start/retry, AI provider test/update handlers, admin router, helper-doc URL resolver, industry bundle apply, analytics rollback frequency, and broad unit tests.
- **Blocking production:** User-visible placeholder copy in onboarding; stale comment drift in six files; onboarding full step forms unimplemented; `assign_page_hierarchy`/`create_menu` handler absence; and incomplete hardening/QA/release gates.

---

## 5. What Is Fully Implemented and Truly Production-Ready

| Area | Status | Evidence / Notes |
|------|--------|------------------|
| **Execution engine — core actions** | Implemented | Create_Page_Handler, Replace_Page_Handler, Apply_Menu_Change_Handler, Apply_Token_Set_Handler, Finalize_Plan_Handler; Bulk_Executor maps plan items; Single_Action_Executor refuses unregistered types. |
| **Build Plan Step 4 — Tokens (executable)** | Implemented | Tokens_Step_UI_Service updated; bulk + row execute/retry with nonce + capability + audit log; execution reaches Apply_Token_Set_Handler. |
| **Build Plan Step 5 — SEO (advisory-only)** | Implemented | SEO_Media_Step_UI_Service: advisory-only, no execute/retry affordances, truthful copy. Row_Action_Resolver excludes execute/retry for SEO items. |
| **Rollback / history v1** | Implemented | Rollback_Executor; Rollback_Eligibility_Service; operational snapshots for REPLACE_PAGE and APPLY_TOKEN_SET; History step shows rollback-capable rows; "Request rollback" capability-gated. |
| **Capability model** | Implemented | Capabilities.php; all screens enforce capabilities at render and action time. |
| **Import/export + ZIP size cap** | Implemented | Import_Export_Screen: MAX_ZIP_UPLOAD_BYTES, size check, ERROR_CODE_FILE_TOO_LARGE. |
| **Import / Restore wizard** | Implemented | Full flow: upload → preview → conflict review → restore scope → explicit confirm. |
| **Industry bundle apply** | Implemented | Industry_Bundle_Apply_Service: validates bundle, resolves conflicts with explicit decisions, persists payload + conflicts as options, updates registry and merge state. |
| **Build Plan list, analytics, workspace structure** | Implemented | Build_Plans_Screen, Build_Plan_Analytics_Screen (including real rollback frequency from snapshot data), Build_Plan_Workspace_Screen with step routing and per-step UI services for all steps. |
| **Build Plan Step 2 deny** | Implemented | Per-row deny and "Deny All Eligible" with confirmation; denied items marked rejected. |
| **Admin router** | Implemented | Admin_Router_Provider registers real Admin_Router (not stdClass); Helper_Doc_Url_Resolver registered and resolves URLs from Documentation_Registry. |
| **Helper-doc URL resolver** | Implemented | Helper_Doc_Url_Resolver: resolves by section_key from Documentation_Registry; returns truthful UNAVAILABLE_MESSAGE when doc absent. |
| **Template registries + detail screens** | Implemented | Section/Page template directories, Section_Template_Detail_Screen, Page_Template_Detail_Screen (metadata, field summary, one-pager, rendered preview); Compositions screen. |
| **Crawler start/retry** | Implemented | Crawler_Sessions_Screen: aio_pb_start_crawl and aio_pb_retry_crawl with check_admin_referer; Crawl_Enqueue_Service wired. |
| **AI Providers test/update** | Implemented | AI_Providers_Screen: maybe_handle_test_connection and maybe_handle_update_credential; nonce + capability protected; redirects with result notice. |
| **Analytics — rollback frequency** | Implemented | Build_Plan_Analytics_Service.get_rollback_frequency_summary: queries snapshot data; computes completed_rollbacks, eligible_executions, rollback_rate. |
| **Diagnostics** | Implemented | Diagnostics_Screen: "No placeholder; real state only." |
| **Queue and logs** | Implemented | Queue_Logs_Screen, job services, retry/recovery, health summary, rollback job flow. |
| **Versions.php** | Implemented | No placeholder wording; stable contract versions; keys documented. |
| **Bootstrap / Lifecycle wording** | Implemented | Plugin.php, Module_Registrar.php, Lifecycle_Manager.php: no "later prompts / future logic" wording; stable production descriptions. |
| **UPDATE_PAGE_METADATA de-scope** | Implemented | Removed from executable mapping; type constant retained for contract stability. |
| **Industry bundle preview screen** | Implemented | No stale "preview-only / apply not implemented" copy; screen matches real flow. |
| **Finalization step** | Implemented | No conflict_summary_placeholder or preview_link_placeholder; real data: finalization buckets, conflict count/messages, completion summary from plan state. |
| **Environment_Validator** | Implemented | Real checks: platform, required_dependency, optional_integration, theme_posture, runtime_readiness, extension_pack. |
| **Unit tests (broad)** | Implemented | Execution, Rollback, BuildPlan, Import/Export, Industry, and domain tests; rollback and snapshot tests updated. |

---

## 6. What Is Implemented but Still Not Production-Ready

| Item | Current status | Why not production-ready | Next action |
|------|----------------|---------------------------|-------------|
| **Onboarding screen — user-visible placeholder copy** | Screen renders step shell and draft save/load. Step forms show "will be added in a future update." | Users see steps and click Next, but see deferred-work copy (`line 466, 495`). Misleading in production. | Either implement real step forms or rework copy to say "form fields not yet available" without implying "future update" phrasing; remove `render_step_placeholder` method comment "out of scope for this prompt" (line 447). |
| **New_Page_Creation_Detail_Builder — "Post-build placeholder:" label** | Detail panel shows "Post-build placeholder: [status]" as a user-visible admin label (line 295). | User-visible placeholder label in a production admin panel is unacceptable. | Rename to a truthful label: "Post-build status:" or "Build result:". |
| **Industry_Packs_Module.php — stale constant comment** | CONTAINER_KEY_INDUSTRY_PACK_REGISTRY constant has comment "Placeholder until implemented." (line 30). The registry IS registered with a real Industry_Pack_Registry. | Comment misrepresents the implementation state. | Update constant docblock to describe the registered Industry_Pack_Registry. |

---

## 7. What Is Partially Implemented

| Item | Location | What exists | What is missing | Why it blocks production |
|------|----------|-------------|-----------------|---------------------------|
| **Onboarding full step forms** | Onboarding_Screen.php | Step shell, draft save/load, prefill, provider readiness. | Full step forms for each step (brand, industry profile, AI setup, business context, submission). "form fields will be added in a future update." | Spec §23 requires guided onboarding; users see steps but cannot complete real intake. User-visible deferred-work copy is unacceptable. |

---

## 8. What Is Not Yet Implemented

| Item | Where it matters | Why it blocks production |
|------|------------------|---------------------------|
| **assign_page_hierarchy execution handler** | Execution_Action_Types (constant exists); dispatcher. | Type defined; no handler registered. Single_Action_Executor refuses unregistered types — no silent wrong behavior — but plan items of this type cannot execute. If spec requires hierarchy assignment, handler must be implemented or type explicitly de-scoped. |
| **create_menu execution handler** | Execution_Action_Types (constant exists); dispatcher. | Type defined; no handler registered (update_menu is registered). Same posture as assign_page_hierarchy. |
| **Profile snapshot persistence** | Profile_Snapshot_Data; profile-snapshot-schema.md. | Schema/type only; explicitly documented as intentional: "intentional placeholder until spec defines persistence." No persistence or UI. | No production surface exposes or implies profile snapshot capability. Acceptable as-is if no spec requirement is exposed. Requires formal out-of-scope decision or implementation if spec §22.11 requires it. |
| **AI cost/usage reporting** | Concrete_AI_Provider_Driver, Additional_AI_Provider_Driver (cost_placeholder => null). | SPR-010: cost modeling not implemented; reserved for future. | No UI implies cost reporting. Acceptable as-is if admin copy makes no cost claims. Requires formal out-of-scope decision or implementation. |

---

## 9. What Still Needs A Decision

| # | Item | Why decision needed | Options |
|---|------|----------------------|--------|
| 1 | **Onboarding — full forms vs shell-only** | Spec §23 requires guided onboarding; current UI is step shell + placeholder copy. User-visible "future update" copy is a release blocker. | (A) Implement full step forms per spec §23. (B) Formally de-scope to "intake shell only" in spec/revision; update UI copy to state capability clearly without "future update" language. |
| 2 | **assign_page_hierarchy / create_menu** | Action types exist; no handlers. | (A) Implement handlers if spec requires. (B) Explicitly exclude from executable set (like UPDATE_PAGE_METADATA) and document the exclusion. |
| 3 | **Profile snapshot persistence** | Schema-only; intentionally deferred. | (A) Implement per spec §22.11. (B) Formal decision that profile snapshot storage is out of scope for v1; update Profile_Snapshot_Data comment to reflect the decision. |
| 4 | **AI cost/usage reporting** | cost_placeholder null; reserved. | (A) Implement cost modeling. (B) Formal decision that cost reporting is out of scope; update driver comments to reflect the decision. |

---

## 10. Stale Wording — Comment / Docblock Drift

These items do not produce user-visible problems but misrepresent implementation state in the codebase. Each must be corrected before production for maintainability and audit compliance.

| File | Line(s) | Current text | Required correction |
|------|---------|--------------|---------------------|
| `Bootstrap/Industry_Packs_Module.php` | 4 | "so future prompts have a stable home" | Stable module description without "future prompts". |
| `Bootstrap/Industry_Packs_Module.php` | 30 | "Placeholder until implemented." (CONTAINER_KEY_INDUSTRY_PACK_REGISTRY constant) | Describe the registered Industry_Pack_Registry (already implemented). |
| `Admin/Screens/Crawler/Crawler_Comparison_Screen.php` | 18 | "nonce reserved for future" | "Read-only screen; no mutating actions." |
| `Admin/Screens/BuildPlan/Build_Plan_Workspace_Screen.php` | 33 | "Row/detail and step-specific tables are placeholders in this prompt." | Remove or replace with accurate description (all step UI services are real). |
| `Infrastructure/Container/Providers/Admin_Router_Provider.php` | class docblock | "Registers admin router placeholder." | Reflects real Admin_Router implementation. |
| `Admin/Screens/Templates/Page_Templates_Directory_Screen.php` | 26 | "one-pager and composition are capability-gated links/placeholders" | Remove "placeholders"; Page_Template_Detail_Screen exists and is fully implemented. |
| `Admin/Screens/Templates/Section_Templates_Directory_Screen.php` | 27 | "detail screen is out of scope" | Remove; Section_Template_Detail_Screen exists and is fully implemented. |
| `Admin/Screens/AI/Onboarding_Screen.php` | 447 | "Full step forms are out of scope for this prompt." (method docblock) | Remove this docblock phrase or replace with a truthful description of the method's role. |

---

## 11. What Must Be Removed or Reworked Before Production

| Item | Current state | Required action |
|------|---------------|-----------------|
| **Onboarding user-visible "future update" copy** | Lines 466, 495 of Onboarding_Screen.php: "Provider setup UI will be added in a future update" and "form fields will be added in a future update." | Replace with either (a) real forms or (b) truthful copy that does not imply "future update" (e.g., "This step is not yet available." or redirect to existing AI Providers screen). |
| **"Post-build placeholder:" label** | New_Page_Creation_Detail_Builder.php line 295: user-visible in admin detail panel. | Rename to "Post-build status:" or equivalent truthful label. |
| **Stale docblocks/comments (§10 above)** | Eight locations. | Correct each comment to reflect actual implemented state. |

---

## 12. All Previously Deferred Items — Resolution Status

| # | Item | Previous status | Current status |
|---|------|-----------------|----------------|
| 1 | **Token application (user-facing)** | Intentionally deferred (advisory-only). | **CHANGED:** Tokens step is now EXECUTABLE (Step 4). Bulk + row execute/retry governed with nonce + capability + audit log. Real handler (Apply_Token_Set_Handler). |
| 2 | **Profile snapshot persistence** | Schema-only; no persistence (SPR-010). | **Unchanged.** Intentionally deferred; no production surface claims otherwise. Formal out-of-scope decision still required. |
| 3 | **Cost/usage reporting (AI)** | cost_placeholder null; reserved for future (SPR-010). | **Unchanged.** No UI implies it. Formal out-of-scope decision still required. |
| 4 | **History/Rollback step** | Was "shell only" in earlier baseline. | **Resolved.** History step with rollback entries and "Request rollback" fully implemented. |
| 5 | **Industry bundle apply** | Blocked on design; then decision made (Outcome A). | **RESOLVED.** Industry_Bundle_Apply_Service: validate, conflict scan, decisions, persist, registry update. |
| 6 | **Build Plan Step 2 Deny** | Blocked on spec/product decision. | **Resolved.** Step 2 deny is fully implemented (row-level + bulk deny). |
| 7 | **Privacy scope expansion** | Intentionally deferred. | **Unchanged.** Out of scope; no resolution needed unless product changes. |
| 8 | **Onboarding full step forms** | Placeholder; "out of scope for this prompt." | **Unchanged.** Step shell exists; forms still missing; user-visible deferred copy still present. **Resolution required.** |
| 9 | **Admin router** | Placeholder (stdClass). | **RESOLVED.** Real Admin_Router class; Helper_Doc_Url_Resolver registered and functional. |
| 10 | **Environment / Lifecycle** | Various placeholders. | **RESOLVED.** Environment_Validator has real checks; Lifecycle_Manager phases implemented; no stale wording. |
| 11 | **Crawler start/retry** | Placeholder action; nonce placeholder. | **RESOLVED.** Crawler_Sessions_Screen: real start/retry with check_admin_referer. |
| 12 | **AI Providers test/update** | Placeholder URLs; handlers missing. | **RESOLVED.** Real handlers with nonce + capability protection. |
| 13 | **Build Plan workspace row/detail completeness** | Step shells; some placeholder row/detail. | **SUBSTANTIALLY RESOLVED.** All step UI services real. Tokens step execution live. SEO advisory posture truthful. Finalization buckets and conflicts from real data. Stale docblock comment remains (§10). |
| 14 | **Build_Plan_Analytics rollback frequency** | Stub; plan history only. | **RESOLVED.** Queries real operational snapshot data. |
| 15 | **Section helper-doc URL** | Placeholder until resolver available. | **RESOLVED.** Helper_Doc_Url_Resolver: real URL from Documentation_Registry. |
| 16 | **Finalization — conflict summary / preview link** | conflict_summary_placeholder, preview_link_placeholder. | **RESOLVED.** Real data from plan state: finalization buckets, conflict messages, completion summary. |
| 17 | **Page/Section template detail screens** | Directory screen said "out of scope" / "links/placeholders". | **RESOLVED.** Both Section_Template_Detail_Screen and Page_Template_Detail_Screen fully implemented. |
| 18 | **assign_page_hierarchy / create_menu** | Types exist; no handlers. | **Unchanged.** Decision still required: implement handlers or explicitly de-scope. |

---

## 13. Production Blockers

The following **block production readiness** under the strict definition:

1. **User-visible placeholder / deferred-work copy:** Onboarding screen (lines 466, 495) shows "will be added in a future update"; New_Page_Creation_Detail_Builder shows "Post-build placeholder:" label.
2. **Incomplete required feature:** Onboarding full step forms — users see step navigation with no real forms; decision and implementation required.
3. **Unresolved handler decisions:** assign_page_hierarchy and create_menu action types exist with no handlers; decision required before production.
4. **Stale comment drift:** Eight docblock/comment locations misrepresent implementation state (§10).
5. **Hardening/QA/release:** Release checklist; Plugin Check; compatibility/migration matrices; release-candidate closure; doc-to-UI consistency; security redaction review.

---

## 14. Required Decision Blocker Queue

| Priority | Decision | Owner | Next action |
|----------|----------|--------|-------------|
| 1 | **Onboarding — full forms vs shell-only** | Product/spec | Close decision; either implement full step forms or revise spec and update UI copy to remove deferred-work language. |
| 2 | **assign_page_hierarchy / create_menu** | Product/spec | Implement handlers or explicitly exclude from executable set (document exclusion like UPDATE_PAGE_METADATA). |
| 3 | **Profile snapshot persistence** | Product/spec | Formal out-of-scope decision or implementation per spec §22.11. |
| 4 | **AI cost/usage reporting** | Product/spec | Formal out-of-scope decision or implementation. |

---

## 15. Required Implementation Blocker Queue

| Priority | Item | Affected files/subsystems | Next action |
|----------|------|---------------------------|-------------|
| 1 | **Onboarding full step forms (if in scope)** | Onboarding_Screen.php, onboarding state, profile/brand intake. | Implement step forms per spec §23 or de-scope and update copy per decision. |
| 2 | **"Post-build placeholder:" user label** | New_Page_Creation_Detail_Builder.php line 295. | Rename to "Post-build status:" or similar truthful label. |
| 3 | **assign_page_hierarchy handler (if in scope)** | Execution_Action_Types, dispatcher. | Implement or explicitly de-scope per decision §14.2. |
| 4 | **create_menu handler (if in scope)** | Execution_Action_Types, dispatcher. | Implement or explicitly de-scope per decision §14.2. |
| 5 | **Stale comment/docblock corrections** | Eight files listed in §10. | Correct comments to reflect actual implementation state. |

---

## 16. Required Validation / QA / Hardening / Release Blocker Queue

| Priority | Item | Reference | Next action |
|----------|------|-----------|-------------|
| 1 | **Release checklist completion** | docs/qa/RELEASE_CHECKLIST.md | Run lint, tests, Plugin Check; fix critical/warning; complete all checklist sections. |
| 2 | **Plugin Check** | Release checklist | Run; address critical and warning findings. |
| 3 | **Compatibility matrix** | docs/qa/compatibility-matrix.md | Execute and update (WP/PHP/dependency combinations); release-note snippet. |
| 4 | **Migration/upgrade matrix** | docs/qa/migration-coverage-matrix.md | Execute and update; release-note migration/compatibility notes. |
| 5 | **Release-candidate closure** | docs/qa/release-candidate-closure.md | Complete performance posture, QA evidence, gate status, release-note inputs. |
| 6 | **Known-risk register** | docs/release/known-risk-register.md | Update with known risks and mitigations. |
| 7 | **Doc-to-UI consistency** | admin-operator-guide, template guides, support-triage-guide, end-user-workflow-guide | Consistency pass; align docs with current UI/scope. |
| 8 | **Security redaction review** | docs/qa/security-redaction-review.md | Capability, nonce, import/export safety, redaction. |
| 9 | **Changelog and README** | Release checklist | Update for release. |
| 10 | **Reporting disclosure** | Release checklist | Disclosure in admin docs, settings, and help content (reporting implemented). |

---

## 17. Recommended Final Order of Work

1. **Fix user-visible placeholder copy immediately (§11):** Remove "future update" copy from Onboarding_Screen (lines 466, 495) and rename "Post-build placeholder:" label in New_Page_Creation_Detail_Builder.
2. **Close decision blockers (§14):** Onboarding scope; assign_page_hierarchy/create_menu; profile snapshot; AI cost.
3. **Implement onboarding step forms (§15.1) if in scope** — the single largest remaining feature gap.
4. **Correct stale comments/docblocks (§10):** Eight files; pure wording/correctness fixes.
5. **Implement execution handlers (§15.3, §15.4)** for assign_page_hierarchy/create_menu if required by spec.
6. **Complete hardening/QA/release queue (§16):** Lint, tests, Plugin Check, compatibility/migration matrices, release-candidate closure, known-risk register, doc-to-UI consistency, security review, changelog/README.

---

## 18. Final Production-Readiness Verdict

**Not production-ready** under the strict definition, but substantially more complete than the Prompt 643 baseline.

The plugin now has a fully implemented execution engine (all five core handlers), rollback v1, capabilities, import/export + Import/Restore wizard, industry bundle apply, Build Plan workspace with all steps truthful and governed, section/page template detail screens, crawler, AI provider handlers, admin router, analytics, diagnostics, and broad unit tests. The main remaining release blockers are: user-visible placeholder copy in onboarding, the onboarding step forms decision, the assign_page_hierarchy/create_menu handler decision, stale comment drift in eight files, and the validation/QA/release gate queue.

---

## 19. Final Recommendation

1. **Immediate (before any release candidate):** Remove user-visible "future update" copy from Onboarding_Screen lines 466 and 495; rename "Post-build placeholder:" to a truthful label.
2. **Short term:** Close the onboarding decision (forms or shell-only); correct the eight stale comment locations; make assign_page_hierarchy/create_menu handler decision.
3. **If onboarding is in scope:** Implement full step forms per spec §23.
4. **Before release:** Complete full release checklist (lint, tests, Plugin Check, compatibility/migration matrices, release-candidate closure, doc-to-UI consistency, security review); update changelog and README.
5. **Formal decisions for deferred items:** Close profile snapshot and AI cost decisions (out-of-scope or implement) to prevent perpetual carry-forward.

---

*End of report. No code changes were made; this document is audit-only. Rescan performed March 2026.*
