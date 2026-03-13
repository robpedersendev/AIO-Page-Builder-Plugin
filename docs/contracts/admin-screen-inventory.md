# Admin Screen Inventory

**Document type:** Contract for admin menu and screen routing (spec §49, §49.11).  
**Governs:** Submenu slugs, screen classes, capability mapping, and Queue & Logs tabs.  
**Guidance:** For operator and support workflows, see [admin-operator-guide.md](../guides/admin-operator-guide.md) and [support-triage-guide.md](../guides/support-triage-guide.md).

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
| AI Providers            | `AI_Providers_Screen` / `aio-page-builder-ai-providers` | `aio_manage_ai_providers` |
| Prompt Experiments      | `Prompt_Experiments_Screen` / `aio-page-builder-prompt-experiments` | `aio_manage_ai_providers` |
| Build Plans             | `Build_Plans_Screen` / `aio-page-builder-build-plans` | `aio_view_build_plans`  |
| Build Plan Analytics   | `Build_Plan_Analytics_Screen` / `aio-page-builder-build-plan-analytics` | `aio_view_build_plans`  |
| **Queue & Logs**        | `Queue_Logs_Screen` / `aio-page-builder-queue-logs` | `aio_view_logs`         |
| **Support Triage**     | `Support_Triage_Dashboard_Screen` / `aio-page-builder-support-triage` | `aio_view_logs`         |

---

## 3. Support Triage screen (spec §49.11, §59.12)

- **Slug:** `aio-page-builder-support-triage`
- **Capability:** `aio_view_logs`
- **Purpose:** Aggregate support view: critical issues, degraded systems, recent failed workflows, plans needing attention, rollback candidates, import/export context, recommended links. No mutation; deep links to Queue & Logs, Build Plans, AI Runs, Import/Export.
- **State:** Built by `Support_Triage_State_Builder` (critical_issues, degraded_systems, recent_failed_workflows, rollback_candidates, import_export_failures, recommended_links, stale_plans). Optional filter by `domain` or `severity` (query args).
- **Redaction:** All data from existing redacted summaries; no secrets.

---

## 4. Build Plan Analytics screen (spec §30, §45, §49.11, §59.12; Prompt 129)

- **Slug:** `aio-page-builder-build-plan-analytics`
- **Capability:** `aio_view_build_plans`
- **Purpose:** Observational analytics over Build Plans: plan review trends (approval/denial rates), common blockers (rejected/failed by category), execution failure trends, rollback frequency summary. Read-only; no mutation. Date-range and plan-type filtering. Links back to Build Plans.
- **State:** Built by `Build_Plan_Analytics_Service` (plan_review_trends, common_blockers, execution_failure_trends, rollback_frequency_summary). Uses existing plan history as authority.
- **Redaction:** Summaries only; no raw secrets or sensitive payloads.

---

## 5. Queue & Logs screen (spec §49.11)

- **Slug:** `aio-page-builder-queue-logs`
- **Capability:** `aio_view_logs`
- **Tabs:** Queue, Execution Logs, AI Runs, Reporting Logs, Import/Export Logs, Critical Errors
- **State:** Built by `Logs_Monitoring_State_Builder` (queue, execution_logs, ai_runs, reporting_logs, import_export_logs, critical_errors)
- **Reporting health:** Built by `Reporting_Health_Summary_Builder` (recent_failures_count, last_heartbeat_month, reporting_degraded, summary_message)
- **Row-to-object:** Queue/Execution rows link to Build Plan when `related_plan_id` present; AI Runs tab links to AI Run detail; Reporting/Critical are read-only
- **Redaction:** All displayed data is from already-redacted or non-secret sources; no raw payloads or secrets in tables

---

## 6. Example payloads (monitoring state)

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

**Support triage payload (excerpt):**
```json
{
  "critical_issues": [
    {
      "severity": "critical",
      "domain": "reporting",
      "title": "Critical error delivery failures",
      "message": "2 developer error report(s) failed to deliver.",
      "link_url": ".../admin.php?page=aio-page-builder-queue-logs&tab=critical",
      "link_label": "View critical errors"
    }
  ],
  "degraded_systems": [],
  "recent_failed_workflows": [],
  "rollback_candidates": [],
  "import_export_failures": [],
  "recommended_links": [
    { "label": "Queue & Logs", "url": "...", "description": "Queue health, execution logs, reporting logs." }
  ],
  "stale_plans": []
}
```
