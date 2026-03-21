# Concepts and glossary

**Audience:** Anyone new to AIO Page Builder before opening task-specific guides.  
**Related:** [WRITING_STANDARD.md](WRITING_STANDARD.md); [FILE_MAP.md](FILE_MAP.md); [index.md](index.md).

This page uses the same words you see in the WordPress admin menu, screen titles, and buttons. Capability names (`aio_*`) are the technical gate; your site may map them to roles differently—defaults are described below.

---

## 1. Core concepts

### Profile (brand / business profile)

Structured **brand and business data** collected in **Onboarding & Profile** (`aio-page-builder-onboarding`). The planner and AI use it to tailor recommendations. You can **Save draft** and return later. It is separate from **Industry Profile** (industry packs), which is configured under **Industry Profile** and related Industry menus.

### Onboarding

The **Onboarding & Profile** screen flow: guided steps to complete or update the profile. Gated by `aio_run_onboarding`. It does not, by itself, run AI providers; it prepares data for planning.

### Crawl session

A **Crawl Sessions** entry represents a **site crawl run**: discovered pages, status, and counts for that run. Open a session from **Crawl Sessions** to see pages for that run. **Crawl Comparison** compares crawl outputs. Both screens use `aio_view_sensitive_diagnostics`; **Start crawl** / **Retry crawl** require `aio_run_onboarding`. Crawls are scoped to this site; they are used to understand structure for planning. **Deep dive:** [crawler-sessions-and-comparison.md](operator/crawler-sessions-and-comparison.md).

### Section template

A **reusable block pattern** (hero, proof, CTA, FAQ, etc.) stored in the plugin registry with fields and metadata. Browsed under **Section Templates** (`aio_manage_section_templates`). Individual rows open **Section Template Detail** (hidden route). Section templates are composed into page templates and plans—not “the whole page” by themselves.

### Page template

A **reusable full-page pattern** built from an ordered set of section templates. Browsed under **Page Templates** (`aio_manage_page_templates`). Detail is a hidden route from **View**. Used when plans recommend **new pages** or template changes.

### Composition

A **governed custom ordering of section templates** (custom page assembly) managed under **Compositions** (`aio_manage_compositions`): list view and **build** view with ordering rules and CTA warnings. Distinct from a single section template or a fixed page template from the registry.

### Build Plan

A **reviewable package of proposed work** generated from AI/planning (often linked to an **AI Run**). Listed under **Build Plans**; opening a plan shows the **workspace** with a **stepper** (multiple **steps**). Plan records use statuses such as `pending_review`, `approved`, `in_progress`, and `completed` in the data model—labels in the list table match the UI. **Operator overview:** [build-plan-overview.md](operator/build-plan-overview.md).

### Plan item

A **single row** inside a Build Plan **step** (e.g. one existing-page update, one new page line item, one navigation change). Items are **approved** or **denied** (or bulk-approved/denied) before execution, depending on the step.

### Approve / deny

**Human review actions** on plan items. Require `aio_approve_build_plans` for the mutation. Only approved items proceed toward execution. Denied items are excluded from the executed set. Navigation steps use the same review pattern with labels such as **Apply** / **Deny** where shown.

### Execute / retry

**Execute** (`aio_execute_build_plans`): enqueue **real site changes** from an approved plan (asynchronous **queue** jobs—not instant for large work). Some steps may be advisory only; the UI states when no direct execution path exists.

**Retry** (Queue tab): for eligible failed jobs, a **Retry** control appears when the user has `aio_manage_queue_recovery`. This is **job retry**, not “re-run the AI plan.”

**Finalize**-style actions on the confirmation step use `aio_finalize_plan_actions` where implemented.

### Rollback

A **request to undo** executed changes using stored **version snapshots**, when eligible. Requested from the **Logs & rollback** step (typically the **last** step in the default plan stack). Requires `aio_execute_rollbacks`; the rollback itself is **queued** and may fail or be ineligible (missing snapshots, validation, etc.).

### Snapshot

**Version snapshot** (stored object type): a **point-in-time capture** of site state used for rollback and history. Separate from **profile snapshots** on **Profile Snapshot History** (menu label **Profile History**), which version the onboarding/profile store for restore.

### Artifact

Two related meanings in the product:

1. **Build Plan artifacts:** Structured data attached to the plan (e.g. advisory SEO guidance text refers to being **stored in Build Plan artifacts** in the UI).
2. **AI Run artifacts:** Outputs associated with an AI run; highly restricted in the admin UI. Viewing sensitive plan-related detail may require `aio_view_sensitive_diagnostics` (workspace flag `can_view_artifacts`). Downloading some exports may use `aio_export_data` or `aio_download_artifacts` where implemented.

### Diagnostics

**Diagnostics** (`aio_view_sensitive_diagnostics`): environment and dependency validation summary. **ACF Field Architecture** and **Form Provider Health** use `aio_view_logs` for the base screen. **ACF** repair/regeneration links and actions require `aio_manage_section_templates`.

### Reporting

**Outbound operational reporting** (install/heartbeat/diagnostics) for this **privately distributed** product. Described on **Privacy, Reporting & Settings** (`aio_manage_reporting_and_privacy`). Reporting is **mandatory** and does not expose raw secrets in the admin UI. Local **Reporting** tabs under **Queue & Logs** show delivery status summaries.

---

## 2. Permissions (default behavior)

Capabilities are registered at plugin activation (`Capability_Registrar`). **Administrator** receives **all** `aio_*` capabilities. **Editor** receives only this default subset: `aio_view_build_plans`, `aio_approve_build_plans`, `aio_view_logs`. **Author**, **Contributor**, and **Subscriber** receive none unless a site adds them.

Custom roles, membership plugins, or manual cap edits override these defaults. If a button is missing, assume the current user lacks the cap.

| I need to… | Typical capability | Notes |
|------------|-------------------|--------|
| Open the top-level menu and **Dashboard** | `aio_view_logs` | Same cap gates the parent menu. |
| Use **Onboarding & Profile** | `aio_run_onboarding` | Not included in default Editor. |
| Change **Settings** (seeds, etc.) | `aio_manage_settings` | Some **seed** buttons also check other caps in the handler (e.g. form template seed requires `aio_manage_section_templates` and `aio_manage_page_templates`). |
| View **Profile Snapshot History** (menu: **Profile History**) | `aio_manage_settings` | Restore actions use the same cap. |
| View **Diagnostics** (environment) | `aio_view_sensitive_diagnostics` | |
| View **Crawl Sessions** / **Crawl Comparison** | `aio_view_sensitive_diagnostics` | |
| View **ACF Field Architecture** / **Form Provider Health** | `aio_view_logs` | ACF repair/regeneration: `aio_manage_section_templates`. |
| Configure **AI Providers** / **Prompt Experiments** | `aio_manage_ai_providers` | |
| List **AI Runs** | `aio_view_ai_runs` | |
| Open **Build Plans** (list + workspace) | `aio_view_build_plans` | |
| **Approve** / **deny** plan items | `aio_approve_build_plans` | Included for default Editor. |
| **Execute** plan work | `aio_execute_build_plans` | Not included for default Editor. |
| **Rollback** | `aio_execute_rollbacks` | |
| **Finalize** (confirmation step actions) | `aio_finalize_plan_actions` | |
| **Section Templates** / detail / helper **Documentation** detail | `aio_manage_section_templates` | Documentation detail screen uses this cap. |
| **Page Templates** / detail | `aio_manage_page_templates` | |
| **Template Compare** | `aio_manage_page_templates` | |
| **Compositions** | `aio_manage_compositions` | |
| Analytics (**Build Plan Analytics**, **Template Analytics**) | `aio_view_build_plans` | |
| **Queue & Logs**, **Support Triage**, **Post-Release Health**, many **Industry** reports | `aio_view_logs` | |
| **Industry Profile** and **Industry Overrides** | `aio_manage_settings` | Other Industry screens may use `aio_view_logs`; see [industry-admin-workflows.md](industry/industry-admin-workflows.md). |
| **Global Style Tokens** / **Global Component Overrides** | `aio_manage_settings` | |
| **Privacy, Reporting & Settings** | `aio_manage_reporting_and_privacy` | |
| Open **Import / Export** | `aio_export_data` **or** `aio_import_data` | Screen allows either. |
| Create/download exports | `aio_export_data` | |
| Validate/restore import | `aio_import_data` | |
| Export logs from **Queue & Logs** | `aio_export_data` | |
| **Retry** / cancel queue jobs | `aio_manage_queue_recovery` | |
| Export Build Plan JSON from workspace | `aio_export_data` **or** `aio_download_artifacts` | |

---

## 3. How to read the UI

| Area in **AIO Page Builder** | What it is for |
|------------------------------|----------------|
| **Dashboard** | Overview and links; read-only entry point. |
| **Settings** | Plugin maintenance, registry **seed** actions, version; link to privacy/reporting screen. |
| **Diagnostics** | Environment/dependency summary. |
| **ACF Field Architecture** / **Form Provider Health** | Structured health and drift-style views for ACF and form-backed templates. |
| **Onboarding & Profile** | Brand/business profile and onboarding flow. |
| **Profile History** | Profile **snapshot** history and restore (title on screen: **Profile Snapshot History**) — [profile-snapshots-and-history.md](operator/profile-snapshots-and-history.md). |
| **Crawl Sessions** / **Crawl Comparison** | Crawl results and comparisons. |
| **AI Runs** / **AI Providers** / **Prompt Experiments** | Run history, provider config, experiments. |
| **Build Plans** | Plan list; opening a plan loads the **workspace** (stepper + lists). |
| **Page Templates** / **Section Templates** / **Template Compare** / **Compositions** | Template library and compare; compositions builder. |
| **Build Plan Analytics** / **Template Analytics** | Read-only trends. |
| **Queue & Logs** | Queue, execution, AI, reporting, import/export tab (placeholder until activity store exists), critical errors; reporting health banner — [monitoring-analytics-and-reporting.md](operator/monitoring-analytics-and-reporting.md). |
| **Support Triage** / **Post-Release Health** | Aggregated operational views — same guide. |
| **Privacy, Reporting & Settings** | Disclosure, retention, uninstall/export choices. |
| **Industry** submenu | Industry profile, overrides, reports, bundle import, guided repair, style tools (caps vary). |
| **Global Style Tokens** / **Global Component Overrides** | Site-wide styling defaults. |
| **Import / Export** | Backups, support bundles, validate/restore — [import-export-and-restore.md](operator/import-export-and-restore.md). |

Hidden routes: **Page Template Detail**, **Section Template Detail**, **Documentation Detail**—opened from links inside the template library, not from the sidebar.

---

## 4. Which guide should I use?

| I am… | Start here |
|--------|------------|
| Editor reviewing plans | [end-user-workflow-guide.md](../guides/end-user-workflow-guide.md) |
| Site admin / operator | [admin-operator-guide.md](../guides/admin-operator-guide.md) |
| Template library (browse/compare/compose) | [template-library-operator-guide.md](../guides/template-library-operator-guide.md) |
| Choosing templates / helper docs | [template-library-editor-guide.md](../guides/template-library-editor-guide.md) |
| Support / logs / bundles | [support-triage-guide.md](../guides/support-triage-guide.md) |
| Template support appendices | [template-library-support-guide.md](../guides/template-library-support-guide.md) |
| Form provider | [form-provider-operator-guide.md](../guides/form-provider-operator-guide.md) |
| Uninstall / ACF preservation | [acf-uninstall-preservation-operator-guide.md](../guides/acf-uninstall-preservation-operator-guide.md) |
| Every screen mapped to one doc | [FILE_MAP.md](FILE_MAP.md) |

---

## 5. FAQ — easy mix-ups

**“Profile” vs “Industry Profile”**  
**Profile** here usually means **Onboarding & Profile** data (brand/business). **Industry Profile** is the industry-pack selection and related settings under its own menu.

**“Profile History” vs “Profile Snapshot History”**  
The sidebar label is **Profile History**; the screen heading is **Profile Snapshot History**—same screen (`aio-page-builder-profile-snapshots`).

**“Template” (WordPress) vs section/page template**  
WordPress theme **templates** are unrelated. **Section template** and **page template** mean **registry entries** inside this plugin.

**“Build Plan” vs “AI Run”**  
An **AI Run** is a generation/validation event. A **Build Plan** is the structured, step-by-step **human-reviewed** work package—often created from a run but edited and approved separately.

**“Execute” vs “Approve”**  
**Approve** selects what *may* run. **Execute** actually **queues** changes. You can approve without executing if your role or step flow does not run execution yet.

**“Rollback” vs “Restore” (import)**  
**Rollback** uses execution **snapshots** from a plan context. **Import / Export restore** applies a **backup package**—different pipeline and caps (`aio_import_data`).

**“Diagnostics” vs “Support Triage”**  
**Diagnostics** is environment validation. **Support Triage** aggregates issues and links across the plugin; both can inform investigation.

**Why can’t I see Onboarding or Execute?**  
Default **Editor** users have **view + approve + logs** only. An administrator must grant caps such as `aio_run_onboarding` or `aio_execute_build_plans` if those roles should perform those actions.

**Why can’t I open AI Providers or change API keys?**  
That screen requires **`aio_manage_ai_providers`**. See [ai-providers-credentials-budget.md](operator/ai-providers-credentials-budget.md).

**Why can’t I see AI Runs?**  
The list and detail views require **`aio_view_ai_runs`**. See [ai-runs-and-run-details.md](operator/ai-runs-and-run-details.md).

---

## 6. Terminology alignment notes

- Build Plan **step types** in code include: overview, existing page changes, new pages, hierarchy flow, navigation, design tokens, SEO, confirmation, logs/rollback. Default **nine** steps and safe navigation: [build-plan-overview.md](operator/build-plan-overview.md). Trust the **labels on screen** if a plan omits or reorders steps.
- **Documentation** detail requires `aio_manage_section_templates`; editors without that cap may need another role or link path to view helper content.
