# Project-Wide Full Production Readiness Gap Report



**Document type:** Audit report  

**Updated:** 2026-03-21 (evidence rerun against git **`ca94de0`**)  

**Strict standard:** Production-ready = no user-visible deferred-work copy, no misleading surfaces, required security patterns on state-changing paths, docs aligned with code, quality gates green or explicitly waived with owner sign-off.  

**Source:** Codebase + docs; master spec; local gate runs on 2026-03-21; prior resolution narrative still references implementation pass **`74a5f01`**.  

**Previous version:** 2026-03-21 earlier draft (PHPUnit/PHPCS counts superseded by this pass).



---



## 1. Title



**AIO Page Builder — Full Production Readiness Gap Report (Strict Definition, Rerun 2026-03-21)**



---



## 2. Executive Summary



The **2026-03-20 implementation pass** (commit **`74a5f01`**) closed **all previously identified production blockers and advisory items** from the earlier gap report (A-1, B-1, B-2, B-4, B-5, C-1, D-1–D-4, and v2 backlog documentation alignment). Subsequent work through **`ca94de0`** does **not** reopen the prior product-facing blocker list by inspection; **PHPUnit is green** at this HEAD while **PHPCS / Plugin Check / PHPStan** remain **not fully clear**.



**Verdict under strict “no deferred UI / no misleading copy” criteria:** **Still met** for the historical A-1 / advisory list — `plugin/src` contains **no** `Coming soon` / `coming soon` string matches (2026-03-21 grep).



**Remaining gaps toward “nothing left to be done” / 100% hard gate:** **Process and quality-bar** items — now **measured** in this rerun:



| Area | Status | Evidence (2026-03-21, local, `plugin/`, git `ca94de0`) |

|------|--------|----------------------------------------|

| **Full PHPUnit suite** | **Pass (with PHPUnit-reported issues)** | **Exit 0.** Tests: **3,056**; assertions: **55,458**; **5** skipped; **8** deprecations; summary **OK, but there were issues!** See §5.1. Runtime: PHP **8.5.1** (CI matrix **8.1–8.3**). |

| **PHPCS (`src/`)** | **Gap** | **Exit 2.** **9** errors, **11** warnings, **12** files (`php vendor/bin/phpcs --standard=phpcs.xml.dist src --report=summary`). PHPCBF: **11** auto-fixable in that summary. |

| **PHPCS (`tests/`)** | **Pass** | **Exit 0** (`... tests --report=summary`; **476** files, ~55s). |

| **PHPStan** | **Gap / incomplete** | **Exit 1** — parallel worker hit configured **512M** memory limit; PHPStan reported **incomplete** analysis (`composer run phpstan`). |

| **Plugin Check** | **Open (summarized)** | `composer run plugin-check:summarize` on `tools/plugin-check/output/plugin-check-report.json` — summarizer **exit 0**; totals **253** ERROR, **690** WARNING, **194** files with findings. Fresh run still required for release sign-off if policy demands it. |

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



These are **not** “placeholder feature” gaps; **R-2–R-4** (and CI parity for **R-1**) block a **strict all-tools-green / zero-debt** declaration unless formally waived.



| ID | Category | Finding | Required action |

|----|----------|---------|-----------------|

| **R-1** | Tests | **Superseded (2026-03-21):** Full suite **exit 0** — **3,056** tests, **55,458** assertions, **5** skipped, **8** deprecations. Prior failure/error counts from an earlier draft of this report **do not** apply at git `ca94de0`. | Keep CI matrix parity (PHP 8.1–8.3); address skips/deprecations when policy requires. |

| **R-2** | PHPCS | **Measured (`src/`):** **9** errors, **11** warnings, **12** files, **exit 2** (`phpcs.xml.dist`). **`tests/`:** **exit 0** (476 files). | PHPCBF + manual fixes; formal waiver only with owner sign-off. |

| **R-3** | Plugin Check | **Summarized** on archived JSON (2026-03-21): **253** ERROR, **690** WARNING, **194** files (summarizer exit 0). | Triage, fix, or document waivers; run fresh Plugin Check when cutting release if required. |

| **R-4** | PHPStan | **Exit 1** — worker **512M** OOM; **incomplete** result. | Raise `--memory-limit` / script memory; re-run for full baseline. |

| **R-5** | Reporting / monitoring | e.g. `Logs_Monitoring_State_Builder` notes import/export log area as shape-only in places. | Confirm product intent; implement storage or narrow UI claims. |

| **R-6** | Minor doc drift | **Closed 2026-03-21.** `Object_Status_Families` class docblock aligned (bootstrap owns registration; no “later attachment” phrasing). | — |



### 5.1 PHPUnit rerun detail (2026-03-21)



- **Command:** `vendor/bin/phpunit -c phpunit.xml.dist` (from `plugin/`).  

- **Exit code:** **0** (`OK, but there were issues!`).  

- **Counts:** Tests **3,056**, Assertions **55,458**, Skipped **5**, Deprecations **8** (PHPUnit summary line).  

- **Note:** PHP **8.5.1** local; CI matrix remains **8.1–8.3** — re-run there before release claims.



---



## 6. Production Blockers (User-Visible / Misleading)



**None** re-identified at **2026-03-21** against the historical A-1 / advisory list; **no** “Coming soon” in `plugin/src` (grep).



---



## 7. Advisory Queue (From Prior Report)



**Cleared** for items A-1, B-1, B-2, B-4, B-5, C-1, D-1–D-4, and v2 backlog doc — see §4.



New advisory items are limited to **R-*** (§5) where still open; **R-6** is **closed**.



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

| Latest verification HEAD (this rerun) | **`ca94de0`** |

| v2 backlog status | `docs/release/v2-scope-backlog.md` |

| Key touched files (historical) | `Build_Plan_Workspace_Screen.php`, `Onboarding_UI_State_Builder.php`, `Onboarding_Prefill_Service.php`, `AI_Providers_Screen.php`, `AI_Runs_Screen.php`, `AI_Run_Detail_Screen.php`, `Execution_Action_Types.php`, `Option_Names.php`, `Object_Status_Families.php`, `Onboarding_Provider.php`, `plugin/tests/bootstrap.php` |

| Canonical commands | `plugin/composer.json` — `composer run phpunit`, `composer run phpcs`, `composer run phpstan` |



---



## 10. Final Production-Readiness Verdict



- **Feature / UX / security hardening (prior gap list):** **Production-candidate** relative to the **2026-03-20** rescan scope, **after `74a5f01`** — **unchallenged** by this rerun’s **user-visible** grep.  

- **“100% nothing left”** in the sense of **all linters + Plugin Check + PHPStan green:** **Not claimed** — see **R-2–R-4** and measured results in §2 and §5.1. **PHPUnit** is **passing** at this HEAD (with skips/deprecations).



**Suggested priority order for remaining work:**



1. **R-2** — clear PHPCS `src/` findings (exit 2 → 0) or formal waiver.  

2. **R-3 / R-4** — triage Plugin Check report; complete PHPStan run after raising memory.  

3. **R-5** — reporting/monitoring shape vs storage consistency.  

4. **R-1 follow-up** — CI matrix runs; PHPUnit skips/deprecations per policy.



---



## 11. CI parity gate (2026-03-20)



**Workflow reference:** `.github/workflows/ci.yml` — `plugin/` working directory; matrix PHP **8.1** / **8.2** / **8.3**; **8.1** is the only lane where PHPCS, PHPStan, and PHPUnit use `continue-on-error: false` (hard fail).



**Local host (Windows):** PHP **8.5.1** — not a matrix substitute; used for quick regression only.



**PHP 8.1 parity:** `docker run` with image `php:8.1-cli` (PHP **8.1.34**), bind-mount `plugin/` to `/app`, `composer install --prefer-dist --no-progress` inside container (**exit 0**). Tools use mounted `vendor/`.



| Step | Command (as run for this record) | Environment | Exit | Notes |

|------|----------------------------------|-------------|------|--------|

| Install | `composer install --prefer-dist --no-progress` | Host `plugin/` | **0** | Nothing to install (lock current). |

| Install | Same | Docker `php:8.1-cli` /app | **0** | After apt `zip` + Composer installer. |

| PHPUnit | `vendor/bin/phpunit -c phpunit.xml.dist` | Host | **0** | 3056 tests, 55458 assertions, 5 skipped, 8 deprecations, “OK, but there were issues!” |

| PHPUnit | Same | Docker 8.1 | **0** | 3056 tests, 55439 assertions, 11 skipped, 1 deprecation — counts differ from 8.5; still green. |

| PHPCS | `composer run phpcs` (= `phpcs.xml.dist`, full paths) | Host | **2** | **9** errors, **11** warnings, **12** files (summary). |

| PHPCS | `php -d memory_limit=512M vendor/bin/phpcs --standard=phpcs.xml.dist --report=summary` | Docker 8.1 | **2** | Same totals as host (parity). |

| PHPStan | `composer run phpstan` | Host | **1** | **Complete run:** `[ERROR] Found 322 errors` (after `composer.json` script raised to **1G**; previously **512M** OOM’d mid-analysis). |

| PHPStan | `php -d memory_limit=1G vendor/bin/phpstan analyse -c phpstan.neon.dist --memory-limit=1G` | Docker 8.1 | **1** | **322** errors — same order of magnitude as host. |

| Plugin Check | `composer run plugin-check:summarize` | Host | **0** | Summarizer only; **253** ERROR, **690** WARNING, **194** files on archived `tools/plugin-check/output/plugin-check-report.json`. Full run: `tools/plugin-check/PROCEDURE.txt` + CI `plugin-check` job (**continue-on-error: true**). |



**Fix applied in this gate (PHP 8.1 parse):** Removed PHP **8.3+** `(void)` casts in `Section_Validator.php` and `Industry_LPagery_Planning_Advisor.php` and resolved resulting **empty if/elseif** PHPCS violations — without this, PHPUnit on **8.1** fatally parse-errors before tests run (**CI hard-fail**).



**Architect waiver ledger (non-green items):** See project response / release closure; treat **PHPCS exit 2**, **PHPStan 322**, **Plugin Check 253 ERROR** as waiver or fix-now per severity until CI 8.1 is objectively green.



---



*End of report. Supersedes prior 2026-03-21 draft; **gates re-measured on 2026-03-21 at `ca94de0`***; **§11 added 2026-03-20 (CI parity)**.


