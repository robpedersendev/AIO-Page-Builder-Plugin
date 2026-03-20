# Project-Wide Full Production Readiness Gap Report

**Document type:** Audit report  
**Updated:** 2026-03-20 (post **commit `74a5f01`** — production-readiness implementation pass)  
**Strict standard:** Production-ready = no user-visible deferred-work copy, no misleading surfaces, required security patterns on state-changing paths, docs aligned with code, quality gates green or explicitly waived with owner sign-off.  
**Source:** Codebase + docs; master spec; gap items from 2026-03-20 rescan; verification against `74a5f01`.  
**Previous version:** 2026-03-20 pre-fix (post v2-features rescan, listed A-1 and advisories as open).

---

## 1. Title

**AIO Page Builder — Full Production Readiness Gap Report (Strict Definition, Post-Fix 2026-03-20)**

---

## 2. Executive Summary

The **2026-03-20 implementation pass** (commit **`74a5f01`**) closed **all previously identified production blockers and advisory items** from the prior gap report (A-1, B-1, B-2, B-4, B-5, C-1, D-1–D-4, and v2 backlog documentation alignment). In particular:

- **A-1 (Export plan):** Real authenticated JSON export from the Build Plan workspace (nonce + `EXPORT_DATA` / `DOWNLOAD_ARTIFACTS`, redaction, structured audit log). No “Coming soon” or disabled fake control.
- **Docblocks / comments:** `Option_Names`, `persist_provider_state_after_test()`, `ASSIGN_PAGE_HIERARCHY`, `Object_Status_Families` FAMILIES block — updated per spec.
- **C-1:** `build_submission_warnings()` implements profile-vs-last-run and stale-crawl warnings from real data sources; tests added.
- **D-1–D-4:** Self-contained capability checks on AI providers handlers; step 4 handler order aligned; `VIEW_AI_RUNS` gates on list and detail screens.
- **`docs/release/v2-scope-backlog.md`:** Header + per-item **FULLY IMPLEMENTED** status, dates, evidence.

**Verdict under strict “no deferred UI / no misleading copy” criteria:** **Met** for the items previously listed as blocking or advisory in §6–§13 of the prior report.

**Remaining gaps toward “nothing left to be done” / 100% hard gate:** These are **process and quality-bar** items, not feature placeholders:

| Area | Status | Notes |
|------|--------|--------|
| **Full PHPUnit suite** | **Gap** | Full run still reports **pre-existing** errors/failures in unrelated tests (historical TF-1 waiver). Targeted tests for this pass pass. |
| **PHPCS (errors)** | **Gap** | Some files still carry **PHPCS errors** (e.g. legacy patterns in large screens); not all introduced by this pass. CI may fail until reduced or baseline updated. |
| **Plugin Check / static analysis** | **Gap** | Treat as required for wordpress.org-style distribution; private distribution may still require explicit sign-off. |
| **Operational hardening** | **Ongoing** | Backup/restore drills, monitoring, runbooks — outside single-plugin code audit. |

**Bottom line:** The **product-facing gap list from the 2026-03-20 rescan is cleared in code**. What remains is **engineering discipline**: test debt, static-analysis debt, and release operations — tracked as **R-*** items below.

---

## 3. Strict Definition of Production-Ready (Unchanged)

1. No deferred work exposed as user-facing “soon” or disabled fake actions for required flows.  
2. No misleading or unavailable behavior on primary admin surfaces.  
3. Required features (per master spec) complete end-to-end.  
4. State-changing paths: capability, nonce, sanitization, structured audit where required.  
5. Quality gates: lint, tests, analysis — pass or documented waiver with owner.

---

## 4. Resolution of Prior Report Items (2026-03-20 Pass)

| ID | Item | Resolution |
|----|------|--------------|
| **A-1** | Build Plan “Export plan” + “(Coming soon)” | **Resolved.** `Build_Plan_Workspace_Screen::maybe_handle_export_plan()` + active link + JSON download; redaction + audit. |
| **B-1** | `Option_Names.php` “Future prompts” | **Resolved.** Stable backward-compatibility wording. |
| **B-2** | `persist_provider_state_after_test()` misattributed docblock | **Resolved.** Accurate description of persisted fields. |
| **B-4** | `ASSIGN_PAGE_HIERARCHY` stale docblock | **Resolved.** Matches handler + `ALL`. |
| **B-5** | `Object_Status_Families` FAMILIES block — “later prompt” | **Resolved.** Bootstrap + validation wording. |
| **C-1** | `build_submission_warnings()` no-op | **Resolved.** Real logic + unit tests. |
| **D-1** | AI Providers test/update handlers — capability | **Resolved.** `MANAGE_AI_PROVIDERS` at handler entry. |
| **D-2** | Step 4 — order of checks | **Resolved.** Capability + nonce before `get_state()` where required. |
| **D-3** | `AI_Runs_Screen` baseline gate | **Resolved.** `VIEW_AI_RUNS` + `wp_die`. |
| **D-4** | `AI_Run_Detail_Screen` baseline gate | **Resolved.** `VIEW_AI_RUNS` + `wp_die`. |
| **v2-backlog doc** | Outdated backlog | **Resolved.** `v2-scope-backlog.md` updated (see also evidence in that file; implementation commit **`74a5f01`**). |

---

## 5. Remaining Gaps (R-*): Quality & Operations

These are **not** “placeholder feature” gaps; they block a **strict CI-green / zero-debt** declaration.

| ID | Category | Finding | Required action |
|----|----------|---------|-----------------|
| **R-1** | Tests | Full PHPUnit suite: pre-existing failures/errors in multiple suites (quantity varies by branch/runtime). | Fix or quarantine with policy; prioritize failing tests by area (industry, templates, etc.). |
| **R-2** | PHPCS | Residual **errors** in `Build_Plan_Workspace_Screen.php` and other files (sanitization comments, array spacing, empty if, etc.). | PHPCBF where auto-fixable; manual fixes + `phpcs:ignore` only with justification. |
| **R-3** | Plugin Check | Not asserted green in this document. | Run Plugin Check; fix or document waivers per project policy. |
| **R-4** | PHPStan | Project uses `phpstan.neon.dist`; full green not re-verified here. | Run `composer phpstan`; fix or baseline new issues. |
| **R-5** | Reporting / monitoring | e.g. `Logs_Monitoring_State_Builder` notes import/export log area as shape-only in places. | Confirm product intent; implement storage or narrow UI claims. |
| **R-6** | Minor doc drift | `Object_Status_Families` **class** docblock still mentions “later attachment” (distinct from B-5 fix on FAMILIES). | Optional wording alignment for consistency. |

---

## 6. Production Blockers (User-Visible / Misleading)

**None** identified at **2026-03-20** after **`74a5f01`** against the prior A-1 / advisory list.

---

## 7. Advisory Queue (From Prior Report)

**Cleared** for items A-1, B-1, B-2, B-4, B-5, C-1, D-1–D-4, and v2 backlog doc — see §4.

New advisory items are limited to **R-*** (§5) and optional micro-doc **R-6**.

---

## 8. Test Coverage — This Pass

| Area | Tests added / relevant |
|------|-------------------------|
| Build plan export payload + regression (no “Coming soon” in source) | `tests/Unit/Admin/BuildPlan/Build_Plan_Export_Action_Test.php` |
| Onboarding submission warnings | `tests/Unit/Domain/AI/Onboarding/Onboarding_UI_State_Builder_Submission_Warnings_Test.php` |
| Bootstrap | `wp_verify_nonce`, `nocache_headers`, `wp_die` stubs for PHPUnit |

---

## 9. Evidence Pointers

| Artifact | Reference |
|----------|-----------|
| Implementation commit | **`74a5f01`** (`feat: production-readiness pass — build plan export, onboarding warnings, caps, docs`) |
| v2 backlog status | `docs/release/v2-scope-backlog.md` |
| Key touched files | `Build_Plan_Workspace_Screen.php`, `Onboarding_UI_State_Builder.php`, `Onboarding_Prefill_Service.php`, `AI_Providers_Screen.php`, `AI_Runs_Screen.php`, `AI_Run_Detail_Screen.php`, `Execution_Action_Types.php`, `Option_Names.php`, `Object_Status_Families.php`, `Onboarding_Provider.php`, `plugin/tests/bootstrap.php` |

---

## 10. Final Production-Readiness Verdict

- **Feature / UX / security hardening (prior gap list):** **Production-candidate** relative to the **2026-03-20 rescan** scope, **after `74a5f01`**.  
- **“100% nothing left”** in the sense of **all tests + all linters + Plugin Check + PHPStan green:** **Not claimed** — see **R-1–R-4**.

**Suggested priority order for remaining work:**

1. **R-1** — reduce PHPUnit failures to zero or approved baseline.  
2. **R-2** — drive PHPCS errors to zero on touched high-traffic files, then repo-wide.  
3. **R-3 / R-4** — Plugin Check + PHPStan clean or documented.  
4. **R-5 / R-6** — product cleanup and doc polish.

---

*End of report. Supersedes the 2026-03-20 pre-fix version; aligns with commit `74a5f01`.*
