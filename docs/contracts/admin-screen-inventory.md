# Admin Screen Inventory

**Document type:** Contract for admin menu and screen routing (spec §49, §49.11).  
**Governs:** Submenu slugs, screen classes, capability mapping, and Queue & Logs tabs.

---

## 1. Top-level menu

- **Slug:** `aio-page-builder`
- **Label:** AIO Page Builder

---

## 2. Submenu and screen mapping

| Submenu label           | Screen class / slug                         | Capability              |
|-------------------------|---------------------------------------------|-------------------------|
| Dashboard               | `Dashboard_Screen` / `aio-page-builder`    | (menu default)          |
| Settings                | `Settings_Screen` / `aio-page-builder-settings` | `aio_manage_settings`   |
| Diagnostics             | `Diagnostics_Screen` / `aio-page-builder-diagnostics` | `manage_options`        |
| Onboarding & Profile    | `Onboarding_Screen` / `aio-page-builder-onboarding` | `aio_run_onboarding`    |
| Crawl Sessions          | `Crawler_Sessions_Screen` / slug            | (crawler cap)           |
| Crawl Comparison        | `Crawler_Comparison_Screen` / slug          | (crawler cap)           |
| AI Runs                 | `AI_Runs_Screen` / `aio-page-builder-ai-runs` | `aio_view_ai_runs`      |
| Build Plans             | `Build_Plans_Screen` / `aio-page-builder-build-plans` | `aio_view_build_plans`  |
| **Queue & Logs**        | `Queue_Logs_Screen` / `aio-page-builder-queue-logs` | `aio_view_logs`         |

---

## 3. Queue & Logs screen (spec §49.11)

- **Slug:** `aio-page-builder-queue-logs`
- **Capability:** `aio_view_logs`
- **Tabs:** Queue, Execution Logs, AI Runs, Reporting Logs, Import/Export Logs, Critical Errors
- **State:** Built by `Logs_Monitoring_State_Builder` (queue, execution_logs, ai_runs, reporting_logs, import_export_logs, critical_errors)
- **Reporting health:** Built by `Reporting_Health_Summary_Builder` (recent_failures_count, last_heartbeat_month, reporting_degraded, summary_message)
- **Row-to-object:** Queue/Execution rows link to Build Plan when `related_plan_id` present; AI Runs tab links to AI Run detail; Reporting/Critical are read-only
- **Redaction:** All displayed data is from already-redacted or non-secret sources; no raw payloads or secrets in tables

---

## 4. Example payloads (monitoring state)

**Queue tab (one row):**
```json
{
  "job_ref": "job-uuid-1",
  "job_type": "replace_page",
  "queue_status": "completed",
  "created_at": "2025-03-15 10:00:00",
  "completed_at": "2025-03-15 10:01:00",
  "failure_reason": "",
  "related_plan_id": "plan-abc-123"
}
```

**Reporting health summary:**
```json
{
  "recent_failures_count": 0,
  "last_heartbeat_month": "2025-03",
  "reporting_degraded": false,
  "summary_message": "Reporting current. Heartbeat sent this month.",
  "failed_events_by_type": {}
}
```
