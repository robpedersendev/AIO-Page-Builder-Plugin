# AIO Page Builder — Support Triage Guide

**Audience:** Support staff and operators performing diagnostics and issue triage.  
**Spec:** §59.15, §60.6, §46 (reporting), security and redaction standards.  
**Purpose:** Logs, support package usage, and issue triage; redaction and safe handling.

---

## 1. Logs and where to find them

- **Screen:** **AIO Page Builder → Queue & Logs** (`aio-page-builder-queue-logs`).  
- **Capability:** `aio_view_logs` to view; `aio_export_data` to export logs.

**Tabs:**

| Tab | Content |
|-----|--------|
| Queue | Job ref, type, status, created, completed, failure reason; link to Build Plan when present. |
| Execution Logs | Execution log rows; link to plan when related_plan_id present. |
| AI Runs | AI run summary rows; link to AI Run detail. |
| Reporting Logs | Reporting delivery attempts and status (redacted). |
| Import/Export Logs | Import/export operation entries. |
| Critical Errors | Critical error log entries (redacted). |

**Reporting health:** At top of Queue & Logs: last heartbeat month, recent delivery failures, and whether reporting is current or degraded.

All data shown in the UI is from already-redacted or non-secret sources. No raw payloads or secrets are displayed in tables. When the Industry Pack subsystem is loaded, the Support Triage state may include an **industry_snapshot** (primary industry, overlay counts, applied preset, recommendation mode, warnings); it is bounded and safe for support use. See [industry-subsystem-diagnostics-checklist.md](../qa/industry-subsystem-diagnostics-checklist.md) for verification. For a single exportable summary of profile, pack/bundle selections, override counts, health state, and major warnings (for support handoffs or migration review), use **Industry_Documentation_Summary_Export_Service::generate()**; see [industry-documentation-summary-export-contract.md](../contracts/industry-documentation-summary-export-contract.md). For internal training on industry concepts, troubleshooting, and escalation, see [industry-support-training-packet.md](../operations/industry-support-training-packet.md) and [industry-operator-curriculum.md](../operations/industry-operator-curriculum.md). For internal performance measurement of the industry subsystem (e.g. before/after tuning), see [industry-performance-benchmark-protocol.md](../qa/industry-performance-benchmark-protocol.md); benchmark tooling is internal-only and not exposed to end users.

---

## 2. Log export (for authorized users)

- **Where:** Queue & Logs screen, section **Export logs** (only if user has `aio_export_data`).
- **Form:** Log types (checkboxes): Queue, Execution logs, Reporting logs, Critical errors, AI runs. Optional date from / date to. Button **Export logs**.
- **Output:** Structured JSON file, redacted. Filename pattern: `aio-log-export-YYYYMMDD-HHMMSS.json`. Download via nonce-protected link after export.
- **Use:** Authorized use only; for support or diagnostics. Do not share exports that might contain site- or environment-specific data without redaction and policy approval.

---

## 3. Support package (export bundle)

- **Where:** **AIO Page Builder → Import / Export**.
- **Action:** Create export → **Export mode** → **Support bundle** → **Create export** → **Download**.

**Support bundle contents (per export-bundle-structure contract):** Settings (redacted), profile (redacted), registries, plans, token sets; optional logs, reporting_history (redacted). No raw AI artifacts. No secrets.

Use support bundle for diagnostics when you need configuration and structure without sensitive data. Do not request or ship full backups containing secrets.

---

## 4. Redaction rules (support guidance)

- **Logs and exports:** Failure reasons and error text may be redacted (e.g. via Reporting_Redaction_Service). API keys, passwords, tokens, and personal data must never appear in logs, exports, or reports.
- **Reporting payloads:** Only schema-defined, non-sensitive fields. Delivery outcomes (success/failure) are logged locally for diagnostics.
- **Support bundle:** Settings and profiles in the bundle are redacted; no raw credentials.

When triaging issues, assume any user-provided log or export may still contain identifiers (e.g. site URL, plan IDs); handle per privacy and data-handling policy.

---

## 5. Issue triage flow

1. **Reproduction:** Note WordPress/PHP versions, plugin version, and steps. Check [compatibility-matrix.md](../qa/compatibility-matrix.md) and [known-risk-register.md](../release/known-risk-register.md).
2. **Logs:** Queue & Logs → relevant tab (Queue for job failures, Execution Logs for execution issues, Critical Errors for crashes, Reporting Logs for delivery failures).
3. **Reporting health:** If the issue is “reports not received,” check Reporting health on Queue & Logs and the Reporting Logs tab.
4. **Support bundle:** For config or environment issues, request a **Support bundle** export (not full backup) and validate per export-bundle-structure contract.
5. **Log export:** If deeper analysis is needed and the user has export capability, they can produce a log export (redacted) with appropriate date/log-type filters.
6. **Rollback issues:** Confirm pre/post snapshots exist and eligibility; check rollback_done / rollback_error in Build Plan workspace and Queue for rollback job status.

Do not ask for or store raw API keys, passwords, or unredacted logs.

---

## 6. Diagnostics screen

- **Screen:** **AIO Page Builder → Diagnostics** (`aio-page-builder-diagnostics`).  
- **Current state:** The Diagnostics screen is registered in the admin menu but does not yet surface environment or validation summaries in the UI. Structured logging is available internally and exportable via Support Bundle. This screen is de-scoped for v1; detailed diagnostics are available through Queue and Logs and the Support Bundle export.

Until the Diagnostics screen is expanded, use Queue and Logs (Critical Errors tab) for internal error summaries, and Import / Export (Support Bundle) for operator diagnostics.
---

## 7. Cross-references

| Topic | Doc |
|-------|-----|
| Reporting disclosure and payload rules | [REPORTING_EXCEPTION.md](../standards/REPORTING_EXCEPTION.md) |
| Export modes and bundle structure | [export-bundle-structure-contract.md](../contracts/export-bundle-structure-contract.md) |
| Security and redaction review | [security-redaction-review.md](../qa/security-redaction-review.md) |
| Admin screens and capabilities | [admin-screen-inventory.md](../contracts/admin-screen-inventory.md); [admin-operator-guide.md](admin-operator-guide.md) |
| Industry diagnostics and lifecycle | [industry-subsystem-diagnostics-checklist.md](../qa/industry-subsystem-diagnostics-checklist.md); [industry-lifecycle-hardening-contract.md](../contracts/industry-lifecycle-hardening-contract.md); [industry-lifecycle-regression-guard.md](../qa/industry-lifecycle-regression-guard.md) |
