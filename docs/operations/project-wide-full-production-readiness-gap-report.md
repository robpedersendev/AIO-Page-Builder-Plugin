# Project-Wide Full Production Readiness Gap Report

**Document type:** Audit report  
**Updated:** 2026-03-21 (full **rerun** against **`45ab36a`** — `refactor: large-library pagination, preview registries, animation/omission`)  
**Strict standard:** Production-ready = no user-visible deferred-work copy, no misleading surfaces, required security patterns on state-changing paths, docs aligned with code, quality gates green or explicitly waived with owner sign-off.  
**Source:** Codebase + docs; master spec; local gate runs on 2026-03-21; prior resolution narrative still references implementation pass **`74a5f01`**.  
**Previous version:** 2026-03-20 (post **`74a5f01`**).

---

## 1. Title

**AIO Page Builder — Full Production Readiness Gap Report (Strict Definition, Rerun 2026-03-21)**

---

## 2. Executive Summary

The **2026-03-20 implementation pass** (commit **`74a5f01`**) closed **all previously identified production blockers and advisory items** from the earlier gap report (A-1, B-1, B-2, B-4, B-5, C-1, D-1–D-4, and v2 backlog documentation alignment). Subsequent **registry / preview / animation** work landed as **`45ab36a`**; it does **not** reopen the prior product-facing blocker list by inspection, but **does not** clear quality-bar debt.

**Verdict under strict “no deferred UI / no misleading copy” criteria:** **Still met** for the historical A-1 / advisory list — `plugin/src` contains **no** `Coming soon` / `coming soon` string matches (2026-03-21 grep).

**Remaining gaps toward “nothing left to be done” / 100% hard gate:** **Process and quality-bar** items — now **measured** in this rerun:

| Area | Status | Evidence (2026-03-21, local, `plugin/`) |
|------|--------|----------------------------------------|
| **Full PHPUnit suite** | **Gap** | **Exit 2.** Tests: **3,055**; **Errors: 12**; **Failures: 28**; also warnings/deprecations/notices/skips/risky — see §5.1. Runtime: PHP **8.5.1** (CI matrix is **8.1–8.3**; counts may differ on CI). |
| **PHPCS** | **Gap** | **Exit 2.** **402 errors**, **589 warnings** in **144 files**; PHPCBF reports **927** auto-fixable violations (`composer run phpcs`, `phpcs.xml.dist`). |
| **PHPStan** | **Gap / blocked this run** | **Exit 1** — analysis did not start: bootstrap file `vendor/php-stubs/wordpress-stubs/wordpress-stubs.php` **missing** (incomplete `composer install` in this environment: PHP **zip** extension missing). **Not** a claim that the project has zero PHPStan issues. |
| **Plugin Check** | **Not re-run** | No canonical Composer script; **not** executed in this rerun. Treat as **open** until run with WordPress + Plugin Check tooling and recorded here. |
| **Operational hardening** | **Ongoing** | Backup/restore drills, monitoring, runbooks — outside single-plugin code audit. |

**Bottom line:** **Product-facing** gaps from the **2026-03-20** rescan **remain cleared** in scope of that list. **Engineering gates** (PHPUnit / PHPCS / PHPStan / Plugin Check) **are not green** on this evidence — see **R-*** below.

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
|----|------|------------|
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
| **R-1** | Tests | **Measured:** 12 **errors**, 28 **failures** (3,055 tests). **Primary error clusters:** (a) `esc_html__()` undefined in `AI_Run_Detail_Screen` / `AI_Runs_Screen` when exercised by `AI_Run_History_Cost_Display_Test` — bootstrap/stubs; (b) `Industry_Section_Recommendation_Resolver::$cache_service` undefined at `Industry_Section_Recommendation_Resolver.php:192`; (c) `Section_Template_Detail_State_Builder_Test` — undefined array key `helper_doc_route`. Failures span ACF diagnostics, build plan analytics/UI, crawl snapshot, hero batch `industry_affinity`, industry services, etc. | Fix by root-cause cluster; align with TF-1 / waiver policy if any. |
| **R-2** | PHPCS | **Measured:** **402** errors, **589** warnings (**144** files). **High-volume:** `Build_Plan_Workspace_Screen.php`, `Onboarding_Screen.php`, `Admin_Router.php`, `Industry_Bundle_Apply_Service.php`, `Provider_Pricing_Registry.php`, SEO/tokens bulk actions, crawl enqueue, many tests. | PHPCBF where auto-fixable; manual fixes + `phpcs:ignore` only with justification. |
| **R-3** | Plugin Check | Not executed this rerun. | Run Plugin Check; fix or document waivers per project policy. |
| **R-4** | PHPStan | **This run:** config/bootstrap failure (missing `wordpress-stubs` in `vendor`). | Restore dev deps (`composer install` with **zip** enabled or equivalent); then `composer run phpstan` and fix findings. |
| **R-5** | Reporting / monitoring | e.g. `Logs_Monitoring_State_Builder` notes import/export log area as shape-only in places. | Confirm product intent; implement storage or narrow UI claims. |
| **R-6** | Minor doc drift | `Object_Status_Families` **class** docblock still mentions “later attachment” (distinct from B-5 fix on FAMILIES). | Optional wording alignment for consistency. |

### 5.1 PHPUnit rerun detail (2026-03-21)

- **Command:** `vendor/bin/phpunit -c phpunit.xml.dist` (from `plugin/`).  
- **Exit code:** **2** (`ERRORS!`).  
- **Counts:** Tests **3,055**, Assertions **55,296**, **Errors 12**, **Failures 28**, Warnings **8**, Deprecations **8**, PHPUnit Deprecations **16**, PHPUnit Notices **81**, Skipped **11**, Risky **14**.  
- **Note:** PHP **8.5.1** local; **PHP Warning** on duplicate constant `AIOPAGEBUILDER_TEST_PLUGIN_INCLUDES` from `Environment_Validator_Test.php` — address before PHP 9.

---

## 6. Production Blockers (User-Visible / Misleading)

**None** re-identified at **2026-03-21** against the historical A-1 / advisory list; **no** “Coming soon” in `plugin/src` (grep).

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
| Implementation commit (historical) | **`74a5f01`** (`feat: production-readiness pass — build plan export, onboarding warnings, caps, docs`) |
| Latest verification HEAD (this rerun) | **`45ab36a`** (`refactor: large-library pagination, preview registries, animation/omission`) |
| v2 backlog status | `docs/release/v2-scope-backlog.md` |
| Key touched files (historical) | `Build_Plan_Workspace_Screen.php`, `Onboarding_UI_State_Builder.php`, `Onboarding_Prefill_Service.php`, `AI_Providers_Screen.php`, `AI_Runs_Screen.php`, `AI_Run_Detail_Screen.php`, `Execution_Action_Types.php`, `Option_Names.php`, `Object_Status_Families.php`, `Onboarding_Provider.php`, `plugin/tests/bootstrap.php` |
| Canonical commands | `plugin/composer.json` — `composer run phpunit`, `composer run phpcs`, `composer run phpstan` |

---

## 10. Final Production-Readiness Verdict

- **Feature / UX / security hardening (prior gap list):** **Production-candidate** relative to the **2026-03-20** rescan scope, **after `74a5f01`** — **unchallenged** by this rerun’s **user-visible** grep.  
- **“100% nothing left”** in the sense of **all tests + all linters + Plugin Check + PHPStan green:** **Not claimed** — see **R-1–R-4** and measured results in §2 and §5.1.

**Suggested priority order for remaining work:**

1. **R-1** — reduce PHPUnit failures to zero or approved baseline (fix stub/`esc_html__`, industry resolver property, `helper_doc_route` expectation).  
2. **R-2** — drive PHPCS errors to zero on touched high-traffic files, then repo-wide.  
3. **R-3 / R-4** — restore PHPStan dev deps; run Plugin Check; fix or document.  
4. **R-5 / R-6** — product cleanup and doc polish.

---

*End of report. Supersedes the 2026-03-20 post-`74a5f01` version; **R-1–R-4 re-measured on 2026-03-21 at `45ab36a`***.
