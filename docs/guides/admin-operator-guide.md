# AIO Page Builder — Admin & Operator Guide

**Audience:** Site administrators and operators.  
**Spec:** §0.10.7, §49, §60.6, §59.15.  
**Purpose:** Operational guidance for admin screens, provider setup, crawler, AI runs, Build Plans, execution/rollback, reporting disclosure, import/export, and uninstall.  
**Knowledge base:** [KB index](../kb/index.md); [concepts-and-glossary.md](../kb/concepts-and-glossary.md) (terms and permissions); [FILE_MAP.md](../kb/FILE_MAP.md) (menu route → article).

---

## 1. Quick start and menu

- **Menu:** WordPress admin → **AIO Page Builder** (top-level).
- **Submenus (registered order):** Dashboard; Settings; Diagnostics; ACF Field Architecture; Form Provider Health; Onboarding & Profile; Profile History; Crawl Sessions; Crawl Comparison; AI Runs; AI Providers; Prompt Experiments; Build Plans; **Page Templates**; **Section Templates**; Template Compare; Compositions; Build Plan Analytics; Template Analytics; Queue & Logs; Support Triage; Post-Release Health; Privacy, Reporting & Settings; Industry Profile; Industry Overrides; Industry Author Dashboard; Industry Health Report; Stale content report; Pack family comparison; Future industry readiness; Future subtype readiness; Maturity delta report; Drift report; Scaffold promotion readiness; Guided Repair; Subtype comparison; Bundle comparison; Conversion goal comparison; Industry Bundle Import; Industry Style Preset; Style layer comparison; Global Style Tokens; Global Component Overrides; Import / Export.

**Hidden routes (no menu label):** Page Template Detail, Section Template Detail, and Documentation Detail open from **View** / helper links inside the template library. See [template-library-operator-guide.md](template-library-operator-guide.md).

Start at **Dashboard** for readiness and quick links. Use **Onboarding & Profile** to complete brand/business profile before running AI planning. Use **AI Providers** to configure and test the AI connection. For the expanded template library (directories, compare, compositions, previews), see [template-library-operator-guide.md](template-library-operator-guide.md). For **Industry** menus (profile, reports, bundle import, guided repair, etc.), the KB hub is [industry-admin-workflows.md](../kb/industry/industry-admin-workflows.md) (stub until expanded). For **Settings** seed actions (registry batches), see §1.1 and [settings-registry-maintenance.md](../kb/operator/settings-registry-maintenance.md).

### 1.1 Settings (`aio-page-builder-settings`)

- **Capability:** `aio_manage_settings` (typical administrators).
- **Purpose:** Plugin version, link to **Privacy, Reporting & Settings**, and **registry maintenance**: idempotent **seed** actions that install or refresh curated section templates, page templates, and related batches (plus **Seed form section and request page template** for form provider flows — see [form-provider-operator-guide.md](form-provider-operator-guide.md)). Each action shows success or error admin notices after redirect. Re-running a seed generally overwrites definitions for the same internal keys; use only when you intend to refresh registry content.
- **Detail:** [settings-registry-maintenance.md](../kb/operator/settings-registry-maintenance.md) (outline for per-action documentation).

---

## 2. Onboarding and profile

- **Screen:** **AIO Page Builder → Onboarding & Profile** (`aio-page-builder-onboarding`).
- **Capability:** `aio_run_onboarding` (granted to **Administrator** by default; not part of the default **Editor** cap set unless your site adds it).

Complete the guided steps to capture brand and business profile data. This data is used by the planner and AI; incomplete profile can affect plan quality. You can save a draft and return later. After completion, the profile is available for crawl interpretation and Build Plan generation. When the Industry Pack subsystem is enabled, **industry profile** (primary and optional secondary industry) can be set from the same area or from **Industry** submenu; it influences template recommendations, Build Plan scoring, and documentation overlays.

**Deep dive (steps, drafts, prefill, blocking, submission):** [onboarding-and-profile.md](../kb/operator/onboarding-and-profile.md).

---

## 3. Provider setup and connection testing

- **Screen:** **AIO Page Builder → AI Providers** (`aio-page-builder-ai-providers`).
- **Capability:** `aio_manage_ai_providers`.

Configure one or more AI providers (credentials, model defaults). The UI does not display raw API keys; it shows credential status only. Use the **Test connection** action per provider to verify connectivity. Connection test results appear as a notice (success or error message) after redirect. Do not use debug credentials or share keys; documentation must not include real secrets.

**Deep dive (credentials, connection tests, spend caps, enforcement scope):** [ai-providers-credentials-budget.md](../kb/operator/ai-providers-credentials-budget.md).

---

## 4. Crawl interpretation

- **Screens:** **Crawl Sessions** (`aio-page-builder-crawler-sessions`), **Crawl Comparison** (`aio-page-builder-crawler-comparison`).
- **Capability:** `aio_view_sensitive_diagnostics` to view both; **`aio_run_onboarding`** to **Start crawl** / **Retry crawl** (see [concepts-and-glossary.md](../kb/concepts-and-glossary.md) for defaults).

Crawler runs are scoped to this site only. Sessions list shows Run ID, site host, status, discovered/accepted/excluded counts. Open a session to see pages for that run. Crawl rules: public-only, normalized URL identity, meaningful-page focus; no arbitrary host input. Use crawl results to understand site structure before or after AI runs; the planner uses this data when generating Build Plans.

**Deep dive (profiles, enqueue/retry, comparison semantics, list vs table, onboarding freshness):** [crawler-sessions-and-comparison.md](../kb/operator/crawler-sessions-and-comparison.md).

---

## 5. AI Runs review

- **Screen:** **AIO Page Builder → AI Runs** (`aio-page-builder-ai-runs`).
- **Capability:** `aio_view_ai_runs`.

Lists recent AI runs (Run ID, status, provider, model, prompt pack, created). Open a run to see metadata and artifact summaries. Raw prompts and provider responses are restricted; only summarized or normalized data is shown unless sensitive diagnostics are enabled. Run metadata may include a **build plan ref** when linked; open **Build Plans** separately to review the plan workspace.

**Deep dive (list, month-to-date table, detail fields, token/cost behavior):** [ai-runs-and-run-details.md](../kb/operator/ai-runs-and-run-details.md).

---

## 6. Build Plan step review

- **User-facing KB:** [build-plan-overview.md](../kb/operator/build-plan-overview.md) (list, workspace, default nine steps, review vs execution, safety, item status). **Existing + new page review:** [build-plan-review-existing-and-new-pages.md](../kb/operator/build-plan-review-existing-and-new-pages.md).
- **Screen:** **AIO Page Builder → Build Plans** (`aio-page-builder-build-plans`).
- **Capability:** `aio_view_build_plans` to view; `aio_approve_build_plans` to approve/deny steps; `aio_execute_build_plans` to run execution; `aio_execute_rollbacks` to request rollback.

**List:** Plan, Plan ID, status, source run, **Open**. **Open** opens the Build Plan workspace.

**Workspace:** Context rail + **stepper** + main workspace. Default generator order (titles on plan may vary): **Overview** → **Existing page changes** → **New pages** → **Hierarchy & flow** → **Navigation** → **Design tokens** → **SEO** → **Confirm** → **Logs & rollback**. Step indices in URLs are **0-based** (`step=0` … `step=8`). Earlier steps with unresolved items **block** later steps.

**Per-step actions (typical):** Existing pages and new pages: **Approve** / **Deny** and bulk variants; executable item types show **Execute** when approved and permitted. Navigation: **Apply** / **Deny** patterns. Confirmation: finalize-style bulk actions when implemented. **Logs & rollback:** rollback request when eligible.

Planner/executor separation is preserved: the plan is generated by the AI/planner; execution runs only after human review and approval. Do not skip review steps.

---

## 7. Execution and finalization expectations

Execution runs only after Build Plan steps are approved and the user triggers execution (per step flow). Jobs are queued; progress is visible under **Queue & Logs → Queue**. Execution logs appear under **Queue & Logs → Execution Logs**; rows can link to the related Build Plan. Long-running work is offloaded to the queue; do not rely on immediate completion for large plans.

---

## 8. Rollback caveats

Rollback is available from the Build Plan workspace (**Logs & rollback** step—typically the **last** step in the default nine-step stack) when the plan has pre- and post-execution snapshots and the rollback eligibility check passes. Requesting rollback enqueues a rollback job; it does not run synchronously. Rollback can fail or be ineligible (e.g. missing snapshots, wrong handler). The UI shows `rollback_done` or `rollback_error` with short messages (e.g. security check failed, missing snapshot references, ineligible, service unavailable). Do not assume rollback fully restores state in all cases; verify critical content after rollback.

---

## 9. Queue & Logs and reporting visibility

- **Screen:** **AIO Page Builder → Queue & Logs** (`aio-page-builder-queue-logs`).
- **Capability:** `aio_view_logs` to view; `aio_export_data` required for log export.

**Tabs:** Queue, Execution Logs, AI Runs, Reporting Logs, Import/Export Logs, Critical Errors.

- **Reporting health:** Summary at top (e.g. last heartbeat month, recent delivery failures, “reporting current” or “degraded”).  
- **Queue/Execution:** Rows link to Build Plan when `related_plan_id` is present; AI Runs tab rows link to AI Run detail.  
- **Log export (for users with export capability):** Section “Export logs” with log type checkboxes (Queue, Execution logs, Reporting logs, Critical errors, AI runs), optional date from/to, and **Export logs** button. Output is redacted JSON; download via nonce URL. Authorized use only.

All displayed data is from redacted or non-secret sources; no raw payloads or secrets in tables.

---

## 10. Privacy, reporting disclosure, and uninstall/export

- **Screen:** **AIO Page Builder → Privacy, Reporting & Settings** (`aio-page-builder-privacy-reporting`).
- **Capability:** `aio_manage_reporting_and_privacy` (or equivalent as implemented).

**Reporting disclosure:** The plugin is privately distributed and may send operational reports (installation notification, periodic heartbeat, error reports) to an approved destination. Reporting is mandatory and cannot be disabled. Included: site identifier, plugin version, WordPress/PHP versions, dependency state, sanitized error summaries. Excluded: API keys, passwords, personal data, raw logs. Delivery status is recorded locally for diagnostics.

**Retention:** Reporting log entry count and retention note are shown; local logs do not contain secrets.

**Uninstall/export behavior:**  
- Built pages (content created with the builder) remain on the site. Only plugin-owned data (settings, templates, plans, logs) is removed when you continue.  
- Uninstall choices: **Export full backup** (download full backup ZIP, then remove plugin data; built pages remain), **Export settings and profile only** (reduced bundle; built pages remain), **Skip export and continue** (remove plugin data; built pages remain), **Cancel uninstall**.  
- **ACF:** Saved field values are retained. To keep section field groups editable after uninstall, run the handoff before uninstall; see [acf-uninstall-preservation-operator-guide.md](acf-uninstall-preservation-operator-guide.md).

Disclosure and privacy-policy helper text are shown on this screen; use them in admin-facing documentation and help content.

---

## 11. Import / Export usage

- **Screen:** **AIO Page Builder → Import / Export** (`aio-page-builder-export-restore`).
- **Capability:** `aio_export_data` to create/download exports; `aio_import_data` to validate and restore.

**Create export:** Choose **Export mode** (Full operational backup, Pre-uninstall backup, Support bundle, Template only, Plan/artifact export, Uninstall settings/profile only), then **Create export**. Export is generated and listed in **Export history** with **Download** link (nonce-protected).

**Validate package:** Upload a ZIP; validate. Validation summary shows blocking failures, conflicts, warnings, checksum. No silent overwrite.

**Restore:** After validation, choose conflict resolution and run restore with explicit confirmation. Restore result is shown; state is updated per pipeline.

Support bundle and other modes are defined in the export-bundle-structure contract; logs and reporting data in exports are redacted.

---

## 12. Template library (expanded)

- **Screens:** **Page Templates** (`aio-page-builder-page-templates`), **Section Templates** (`aio-page-builder-section-templates`), **Template Compare** (`aio-page-builder-template-compare`), **Compositions** (`aio-page-builder-compositions`). Detail screens are reached via **View** from directories (no menu entry).
- **Purpose:** Browse section and page templates, compare up to 10 per type side-by-side, build governed compositions from section templates. All observational except composition save; page creation is via Build Plans.
- **Guides:** [template-library-operator-guide.md](template-library-operator-guide.md) (operators), [template-library-editor-guide.md](template-library-editor-guide.md) (editors: choosing templates, one-pagers, helper docs), [template-library-support-guide.md](template-library-support-guide.md) (support: diagnostics, appendices, compliance).

---

## 13. Cross-references

| Need | Screen or doc |
|------|----------------|
| Knowledge base index and workflow map | [index.md](../kb/index.md); [FILE_MAP.md](../kb/FILE_MAP.md) |
| Template library operation | [template-library-operator-guide.md](template-library-operator-guide.md) |
| Template choice and one-pagers (editors) | [template-library-editor-guide.md](template-library-editor-guide.md) |
| Template library support and diagnostics | [template-library-support-guide.md](template-library-support-guide.md) |
| Reporting disclosure wording | Privacy, Reporting & Settings |
| Uninstall choices and built-page survivability | Privacy, Reporting & Settings; [PORTABILITY_AND_UNINSTALL.md](../standards/PORTABILITY_AND_UNINSTALL.md) |
| ACF uninstall preservation (values vs groups, handoff) | [acf-uninstall-preservation-operator-guide.md](acf-uninstall-preservation-operator-guide.md); [acf-uninstall-preservation-verification.md](../qa/acf-uninstall-preservation-verification.md) |
| Export modes and bundle structure | Import / Export screen; [export-bundle-structure-contract.md](../contracts/export-bundle-structure-contract.md) |
| Admin screen list and capabilities | [admin-screen-inventory.md](../contracts/admin-screen-inventory.md) |
| Reporting exception and payload rules | [REPORTING_EXCEPTION.md](../standards/REPORTING_EXCEPTION.md) |

---

## 14. Security and permissions

- Every privileged action requires a capability check; state-changing admin requests use nonces.  
- Documentation must not include raw secrets, debug credentials, or unsafe shortcuts.  
- Permission-sensitive workflows (export, import, restore, log export, approve, execute, rollback) are described above with the correct capability names where applicable.
