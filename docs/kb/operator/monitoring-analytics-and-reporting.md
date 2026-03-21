# Monitoring, analytics, diagnostics, and reporting disclosure

**Audience:** Operators and support with the capabilities listed below.  
**Canonical map:** [FILE_MAP.md](../FILE_MAP.md) §3, §8–§10.  
**Related:** [support-triage-guide.md](../../guides/support-triage-guide.md); [admin-operator-guide.md §9–§10](../../guides/admin-operator-guide.md); [REPORTING_EXCEPTION.md](../../standards/REPORTING_EXCEPTION.md) (policy and payload rules for implementers).

This article describes **only what the admin UI exposes today**: read-only analytics, environment diagnostics, Queue & Logs tabs, log export, and the **Privacy, Reporting & Settings** disclosure blocks. It does **not** claim a populated import/export activity log (that store is not wired), a monitored import/export failure feed, or diagnostics-verbosity controls (the builder currently keeps verbosity disabled).

---

## Capabilities (what you can open)

| Area | Typical capability | Notes |
|------|-------------------|--------|
| **Build Plan Analytics** | `aio_view_build_plans` | Trends from plan history; no mutations. |
| **Template Analytics** | `aio_view_logs` | Aggregates with optional filters; no mutations. |
| **Post-Release Health** | `aio_view_logs` | Cross-domain summary + optional JSON download. |
| **Queue & Logs** | `aio_view_logs` | All tabs; **retry/cancel** need `aio_manage_queue_recovery` (see [build-plan-rollback-and-recovery.md](build-plan-rollback-and-recovery.md)). |
| **Export logs** (on Queue & Logs) | `aio_export_data` | Redacted structured JSON only. |
| **Diagnostics** | `aio_view_sensitive_diagnostics` | Environment validator output. |
| **Privacy, Reporting & Settings** | `aio_manage_reporting_and_privacy` | Disclosure, retention text, destination summary, privacy helper. |

Exact slug and cap mapping: [admin-screen-inventory.md](../../contracts/admin-screen-inventory.md).

---

## What analytics are for

Analytics screens are **observational**. They do not change Build Plans, templates, or the queue.

### Build Plan Analytics (`aio-page-builder-build-plan-analytics`)

- Intro copy: trends from Build Plan history; **no changes** to plans or execution.
- **Date range** filter (`date_from` / `date_to`).
- Sections: **Plan review trends** (totals, approved/rejected counts, approval/denial rates, optional per-status counts), **Common blockers**, **Execution failure trends**, **Rollback frequency**.
- Link back to **Build Plans** list.
- If the analytics service is unavailable, sections render with **empty/default summaries** (the screen stays safe-read-only).

### Template Analytics (`aio-page-builder-template-analytics`)

- Same **VIEW_LOGS** gate as other monitoring screens.
- Optional filters: date range, template family, page class.
- Summaries cover usage/recommendation/rejection/outcomes/rollback/composition usage **as returned by** `Template_Analytics_Service` — when historical data is sparse, tables may be mostly empty.
- Links to related library/queue destinations as rendered on the screen.

### Post-Release Health (`aio-page-builder-post-release-health`)

- Intro: **internal operational review** for the selected period; links to authoritative screens; **no automatic product changes**.
- **Period** filter and **Export summary (JSON)** — downloads `post_release_health_summary`, `domain_health_scores`, and `recommended_investigation_items` (plus `exported_at`). Same **`aio_view_logs`** gate as normal render path.
- **Domain health scores** rows (labels and messages come from code): `reporting`, `queue`, `build_plan_review`, `ai_run_validity`, `rollback`, `import_export`, `support_package`.
- **Recommended investigation items** merge Support Triage signals (critical/degraded), high denial rate, and critical-scored domains (capped list).

**Honest limits**

- **Import/export** domain: when no triage-fed failures are present, the UI message states that outcomes are **not aggregated** there and points to **Import / Export** and **Queue & Logs** — do not treat an “OK” row as proof that no import/export operation ever failed.
- **Rollback** row uses rollback data from **Build Plan analytics** for the period; it is not a full global rollback history.

---

## What diagnostics show

### Diagnostics (`aio-page-builder-diagnostics`)

- Runs **`Environment_Validator`**: real checks, not a placeholder.
- Explains that **blocking** issues affect activation and key workflows; non-blocking rows may still be warnings or informational.
- Table: **Category**, **Severity**, **Code**, **Message**, **Blocking** (Yes/No).

Use this screen to confirm PHP/WP/dependency posture **before** chasing queue failures that are really environment-related.

### ACF Field Architecture & Form Provider Health

- **ACF** (`aio-page-builder-acf-diagnostics`) and **Form Provider Health** (`aio-page-builder-form-provider-health`) use their own builders; this guide does not duplicate field-level or provider semantics.
- Deep procedures: ACF [acf-uninstall-preservation-operator-guide.md](../../guides/acf-uninstall-preservation-operator-guide.md); forms [form-provider-operator-guide.md](../../guides/form-provider-operator-guide.md). Slug map: [FILE_MAP.md](../FILE_MAP.md) §3.

---

## Queue & Logs: what each part shows

**Screen:** **AIO Page Builder → Queue & Logs** (`aio-page-builder-queue-logs`).

Header copy states that you monitor queue state, execution logs, **outbound reporting** delivery (install, heartbeat, diagnostics), and **failed developer-diagnostics deliveries**; row links open related plans or runs; **Import/Export Logs** only lists activity when that store exists — for **Reporting Logs** and **Export logs** for operational history today.

### Reporting health banner

Built from **`Reporting_Health_Summary_Builder`**:

- Counts **failed** reporting deliveries in the **last 30 days** (from the local reporting log option).
- Reads **last successful heartbeat month** from heartbeat state.
- **Degraded** if there were recent failures **or** the last successful heartbeat month is **before** the current calendar month.
- User-visible summary strings are the translated messages in code (failure count, heartbeat this month vs not yet).

**Important:** Reporting delivery problems are **monitoring/diagnostics**. They do not, by themselves, stop core plugin workflows; code is designed so reporting failure must not take down product behavior (see edge cases below).

### Queue health banner

From **`Queue_Health_Summary_Builder`** (when the job queue repository is available):

- Counts by status, **stale lock** detection (running/retrying jobs older than about **one hour**), **long-running** hints, **retry-eligible** failed jobs (type allowlist + retry count &lt; 5), **bottleneck** warning when pending count is high.
- Post-Release Health treats **stale locks** as **critical** for the queue domain.

### Tabs

| Tab | Source (conceptually) | Limits / notes |
|-----|------------------------|----------------|
| **Queue** | Job queue rows across statuses | Merged cap ~**100** recent rows; **finalize_plan** rows may show `run_completion_state` when plan repo is wired. |
| **Execution logs** | Completed + failed jobs | **50** rows; redacted; **no raw payloads**. |
| **AI Runs** | Recent AI runs | **20** rows; **run_id**, **status**, **created_at** — **no raw prompts**. |
| **Reporting logs** | `REPORTING_LOG` option | **50** recent entries; **event_type**, **dedupe_key**, **attempted_at**, **delivery_status**, **log_reference**, **failure_reason**. |
| **Import/Export logs** | Placeholder | **`import_export_activity_log_available` is false** in the builder → UI shows the description to use Reporting Logs, Export logs, and **Import / Export** instead. |
| **Critical errors** | Subset of reporting log | Only **`developer_error_report`** events with **delivery_status === failed** — **not** a general PHP error log. |

### Export logs (section above tabs)

- Visible only with **`aio_export_data`**.
- Description: structured JSON, **redacted and filtered**, authorized use only.
- Checkboxes match **`Log_Export_Service`** types: Queue, Execution logs, Reporting logs, Critical errors, AI runs, Template family, Template operation; optional date range and template filters.

---

## Dashboard

**Dashboard** (`Dashboard_State_Builder`) may surface a **queue warning** summary (pending/failed counts, link to Queue & Logs) and a **critical error** summary (subset of the same critical-error feed as the Critical tab, with link to `tab=critical`). Use it as a **pointer**, not a full log viewer.

---

## Privacy, Reporting & Settings: disclosure and helper text

**Screen:** **Privacy, Reporting & Settings** (`aio-page-builder-privacy-reporting`), capability **`aio_manage_reporting_and_privacy`**.

Sections (in render order):

1. **Reporting disclosure** — two blocks from **`Privacy_Settings_State_Builder`**. **UI headings** (WordPress i18n strings) are **Private distribution reporting** and **What may be sent**; paragraph text states that operational reports (install, heartbeat, error reports) may go to an approved destination, are **mandatory** for this distribution model, **cannot be disabled** in-product, and **omit secrets/credentials**; the second block lists **included** vs **excluded** categories (identifier, versions, dependency state, sanitized error summaries vs keys, passwords, personal data, raw logs) and notes local delivery-status logging.
2. **Retention** — reporting log **entry count** sentence; note that entries are retained for diagnostics, governed by product policy, and **local logs do not contain secrets**.
3. **Uninstall / export behavior** — preference summary, built-pages message, ACF preservation pointer, uninstall choice list, optional template lifecycle block (same lifecycle copy as elsewhere).
4. **Environment & version** — plugin, PHP, WordPress; up to **eight** lines from a **persisted diagnostics snapshot** (`generated_at` + check messages) when present.
5. **Report destination** — **Email** transport label and description that install/heartbeat/error reports go to the **approved** destination (no raw address shown in this summary).
6. **Privacy policy helper text** — long block for **site policy drafting**: stored data categories, operational reporting, AI providers, WP Tools export/erase registration, actor-linked fields, erase behavior (redact actor, keep audit), admin export/uninstall, built pages may be preserved.

**Not shown:** The state builder exposes `diagnostics_verbosity_allowed` as **false**; the “Diagnostics verbosity” section does **not** appear unless that flag is enabled in code.

For schema/redaction rules beyond these paragraphs, see [REPORTING_EXCEPTION.md](../../standards/REPORTING_EXCEPTION.md).

---

## What is excluded or redacted (user-facing level)

- **Queue / execution rows:** No raw job payloads; failure reasons and refs are sanitized for display.
- **AI Runs tab:** No prompt or provider payload text.
- **Reporting disclosure:** Explicit list of **excluded** categories (keys, passwords, personal data, raw logs).
- **Log export:** Described in UI as redacted/filtered; implementation uses **`Reporting_Redaction_Service`** and export caps (e.g. **500** rows per export path in the service).
- **Privacy helper:** States export/erase behavior for **actor-linked** records without exposing secrets.

---

## Step-by-step: Reviewing logs

1. Open **Queue & Logs** with **`aio_view_logs`**.
2. Read **Reporting health** and **Queue health** banners for context.
3. Pick the tab that matches the incident: **Execution logs** for finished jobs, **Reporting logs** for outbound delivery attempts, **Critical errors** only for **failed developer-error report** deliveries.
4. Use row links to **Build Plans** or **AI Runs** when present.
5. If you need offline analysis and have **`aio_export_data`**, use **Export logs** with the right types and optional dates — treat files as sensitive operational data.

---

## Step-by-step: Interpreting queue state

1. On **Queue**, sort mentally by **status**: pending/running/retrying vs failed vs completed.
2. Check **related plan** column to tie jobs to a plan workspace.
3. **Retry** appears only when the row is **retry-eligible** (failed, retry count &lt; 5, job type in the allowlist: `create_page`, `replace_page`, `update_menu`, `apply_token_set`, `finalize_plan`, `rollback_action`) and you have **`aio_manage_queue_recovery`**.
4. If **Queue health** shows **stale locks**, treat as high priority: executor may be stuck; compare with [build-plan-rollback-and-recovery.md](build-plan-rollback-and-recovery.md) and Support Triage.
5. **Bottleneck** (very large pending count) suggests throughput or worker issues — confirm environment and cron/async behavior outside this doc if applicable.

---

## Step-by-step: Using diagnostics to decide retry vs escalate

1. Open **Diagnostics** — if **blocking** issues exist, fix environment first; queue retries rarely fix bad PHP/extensions.
2. For **failed jobs**, read **failure_reason** on the Queue tab; if generic, cross-check **Execution logs** and **Build Plan** state.
3. If **reporting** is degraded but the site behaves normally, treat as **non-blocking** (see edge cases); still investigate email/reachability if policy requires delivery.
4. If **Critical errors** tab is empty, that does **not** mean “no site errors” — only that **developer error report** outbound deliveries did not fail in the log sense.
5. Escalate to [support-triage-guide.md](../../guides/support-triage-guide.md) when multiple domains are red on **Post-Release Health** or triage lists critical issues.

---

## Step-by-step: Understanding reporting / privacy notices

1. Open **Privacy, Reporting & Settings** with **`aio_manage_reporting_and_privacy`**.
2. Read **Reporting disclosure** top to bottom — this is the **admin-facing** contract for what the product may send and what it omits.
3. Read **Retention** to set expectations about local reporting log growth.
4. Use **Privacy policy helper** as **starting text** for your site’s public policy, adapted to your jurisdiction and hosting.
5. For uninstall impact on data vs built pages, read **Uninstall / export** and linked lifecycle text; full portability: [PORTABILITY_AND_UNINSTALL.md](../../standards/PORTABILITY_AND_UNINSTALL.md).

---

## Edge cases

| Situation | Guidance |
|-----------|----------|
| **Log line exists but no obvious fix** | Use plan/run links, export redacted JSON for support, check Diagnostics — some entries are informational. |
| **Reporting failure but core behavior continues** | Expected design: outbound reporting must not break editor/plan/queue behavior. Investigate delivery (mail, network) using **Reporting logs**; treat as compliance/ops, not as “site down.” |
| **Monitoring shows warnings but no action** | e.g. heartbeat “not yet this month” early in the month, or empty analytics tables on a new site — confirm again after activity. |
| **Privacy concern about included/excluded data** | Rely on **Reporting disclosure** and **Privacy helper** text; do not assume the UI shows full payloads — it does not. For WP export/erase scopes, follow the helper’s list and WordPress Tools flows. |
| **Import/Export Logs tab looks empty** | By design until `import_export_activity_log_available` is true; follow on-screen directions. |
| **Post-Release “import/export OK”** | Message explicitly says outcomes are **not aggregated** on that screen unless triage supplies issues — do not over-interpret. |

---

## FAQ

**Does disabling reporting exist in the UI?**  
The disclosure states private-distribution reporting is mandatory and not disabled in-product. Admins still see what is sent and excluded in the disclosure blocks.

**Is Critical Errors my PHP error log?**  
No. It lists **failed deliveries** of **developer error report** events in the reporting log.

**Can I see raw AI prompts in Queue & Logs?**  
No. Use **AI Runs** screen and authorized workflows for run detail within product limits.

**Who can export logs?**  
Users with **`aio_export_data`** see the Export logs section.

---

## Troubleshooting

| Symptom | Where to look |
|---------|----------------|
| Jobs stuck | Queue tab + Queue health stale locks; Diagnostics; executor contract [executor-locking-idempotency-contract.md](../../contracts/executor-locking-idempotency-contract.md). |
| “Reporting degraded” | Reporting health message; Reporting logs tab; last heartbeat month. |
| Empty analytics | Date range too narrow or insufficient historical data; service fallback on Build Plan Analytics. |
| Cannot retry job | Capability `aio_manage_queue_recovery`; job type not retryable; retries exhausted. |
| Need human support path | [support-triage-guide.md](../../guides/support-triage-guide.md). |

---

## Related documentation

- [build-plan-overview.md](build-plan-overview.md) — Plan workspace and execution context.  
- [build-plan-rollback-and-recovery.md](build-plan-rollback-and-recovery.md) — Queue retry vs rollback.  
- [import-export-and-restore.md](import-export-and-restore.md) — Package operations (not the Import/Export Logs tab).  
- [admin-operator-guide.md §9–§10](../../guides/admin-operator-guide.md) — Short operator summary.
