# Reporting Payload Contract

**Document type:** Authoritative contract for outbound reporting payloads (spec §45, §46, §47, §59.12).  
**Governs:** Installation notification, monthly heartbeat, and developer error report payload schemas; shared envelope; inclusion/exclusion; redaction; deduplication; delivery metadata; disclosure-facing labels.  
**Out of scope:** Email delivery implementation, scheduler, admin UI, retry jobs, logs screen, support bundle export.

---

## 1. Purpose

This contract defines the **exact payload structures** for all mandatory private-distribution reporting events. No reporting pathway may invent its own payload shape. Payloads are stable, versioned, and disclosure-compatible. Redaction is systematic; secrets and prohibited data must never be included. Reporting failure must not block unrelated plugin operation.

---

## 2. Shared Envelope (Root Metadata)

Every report payload MUST be wrapped in a **report envelope**. All outbound reports use the same envelope shape so transport and logging can treat them uniformly.

| Field | Required | Type | Description |
|-------|----------|------|-------------|
| `schema_version` | Yes | string | Payload contract version (e.g. `"1.0"`). Used for compatibility and disclosure. |
| `event_type` | Yes | string | One of: `install_notification`, `heartbeat`, `developer_error_report`. |
| `site_reference` | Yes | string | Opaque site identifier (e.g. domain, hashed site URL). No credentials. Used for deduplication and support correlation. |
| `plugin_version` | Yes | string | Plugin version at time of report (e.g. `"1.2.0"`). |
| `timestamp` | Yes | string | ISO 8601 UTC when the payload was built. |
| `dedupe_key` | Yes | string | Unique key for this report instance (§6). Used to avoid duplicate sends and for idempotent delivery logging. |
| `payload` | Yes | object | Event-specific payload (§4, §5). |
| `delivery_metadata` | No | object | Transport-facing metadata (§7). Populated when queuing or sending. |

**Stability:** New envelope fields require contract revision. Consumers must ignore unknown envelope fields.

---

## 3. Data Inclusion Rules (spec §46.8)

**Allowed** in report payloads (where applicable per report type):

- Severity (info, warning, error, critical)
- Website address (non-secret; e.g. site URL used for support correlation)
- Plugin version
- WordPress version
- PHP version
- Error category (validation, dependency, execution, provider, queue, reporting, import_export, security, compatibility)
- Sanitized error summary (no raw stack, no secrets)
- Expected behavior (short text)
- Actual behavior (short text)
- Related Build Plan / job / run ID (references only, no content)
- Admin contact email (as disclosed in settings)
- Server IP if available (for support correlation)
- Timestamp
- Dependency readiness summary (install only)
- Last successful AI run timestamp (heartbeat)
- Last successful Build Plan execution timestamp (heartbeat)
- Current health summary (heartbeat)
- Current queue warning count (heartbeat)
- Current unresolved critical error count (heartbeat)
- Log reference / local log linkage (§8)

---

## 4. Data Exclusion Rules (spec §46.9)

**Never include:**

- Passwords
- API keys, bearer tokens, auth cookies
- Nonces
- Raw database credentials
- Full unpublished page content
- Full raw AI payloads (unless explicitly requested via a separate support export, which is not part of routine reporting)
- Session IDs, auth tokens
- Prohibited personal data beyond disclosed admin contact email
- Any "temporary debug" fields that bypass redaction

Redaction must be applied **before** payload construction. There is no optional redaction mode.

---

## 5. Redaction Requirements (spec §45.9, §47.8)

Before any field is placed into a report payload:

- **Remove or mask:** secrets, tokens, passwords, sensitive raw payloads, prohibited personal data, session-specific values where not needed.
- **Sanitize messages:** error messages must be summarized or sanitized; no raw stack traces, no request headers, no provider response bodies that might contain secrets.
- **Systematic:** redaction is not optional and must be consistent across logs, reports, and exports.

Payload construction is server-side only. No client-supplied content may be placed into report payloads without validation and redaction.

---

## 6. Deduplication Keys and Log Linkage

### 6.1 Dedupe key format

| Event type | Dedupe key composition | Uniqueness scope |
|------------|------------------------|------------------|
| `install_notification` | `install_{site_reference}_{first_activation_timestamp}` or `install_{site_reference}_{domain_hash}` | One per site per "first install" lifecycle (no resend unless reinstall or domain change). |
| `heartbeat` | `heartbeat_{site_reference}_{YYYY-MM}` | One successful heartbeat per site per calendar month. |
| `developer_error_report` | `error_{log_id}` or `error_{category}_{sanitized_signature}_{YYYY-MM-DD}` | Per event or per 24h window for same signature to avoid spam. |

Implementations must use these (or contract-specified equivalents) so that delivery and retry logic can deduplicate correctly.

### 6.2 Log reference (local log linkage)

Developer error reports MUST include a **log reference** that links the outbound report to the local structured log entry:

| Field | Required | Type | Description |
|-------|----------|------|-------------|
| `log_id` | Yes | string | Local error record ID (e.g. `Error_Record::id`). |
| `log_category` | No | string | Log category (aligns with Log_Categories). |
| `log_severity` | No | string | Log severity (aligns with Log_Severities). |

This allows support to correlate incoming reports with local diagnostics and preserves audit trail.

---

## 7. Delivery Metadata (transport-facing)

When a report is queued or sent, the following metadata may be attached for logging and retry. It is NOT part of the payload sent to the recipient; it is for internal delivery tracking.

| Field | Required | Type | Description |
|-------|----------|------|-------------|
| `delivery_status` | No | string | One of: `pending`, `sent`, `failed`, `skipped`. |
| `delivery_attempt_count` | No | int | Number of send attempts. |
| `last_attempt_at` | No | string | ISO 8601 of last attempt. |
| `failure_reason` | No | string | Sanitized reason if delivery failed (for local log only; not sent outbound). |

Every attempted report SHALL generate a reporting log entry with delivery status (spec §46.12).

---

## 8. Report-Type Payload Schemas

### 8.1 Installation notification (spec §46.3)

**Event type:** `install_notification`

**Subject format (transport):** `Plugin successfully installed on [website address]`

**Payload body fields (required):**

| Field | Type | Description |
|-------|------|-------------|
| `website_address` | string | Site URL or disclosed identifier (no credentials). |
| `plugin_version` | string | Plugin version. |
| `wordpress_version` | string | WordPress version. |
| `php_version` | string | PHP version. |
| `server_ip` | string | Server IP if available; otherwise empty or omitted. |
| `admin_contact_email` | string | Admin contact email from settings (disclosed). |
| `timestamp` | string | ISO 8601 UTC. |
| `dependency_readiness_summary` | string | Short summary (e.g. "all ready", "missing: x"). |

**Ineligibility / validation:** Do not build or send if site_reference is empty, or if a prior install notification for this site (per dedupe rules) was already sent successfully.

**Example payload (envelope + payload):** See §10.1.

---

### 8.2 Monthly heartbeat (spec §46.4, §46.5)

**Event type:** `heartbeat`

**Subject format (transport):** `Heart beat - [website address] - [status of site]`

**[status of site]** enum: `healthy` | `warning` | `degraded` | `critical`

**Payload body fields (required):**

| Field | Type | Description |
|-------|------|-------------|
| `website_address` | string | Site URL or disclosed identifier. |
| `plugin_version` | string | Plugin version. |
| `wordpress_version` | string | WordPress version. |
| `php_version` | string | PHP version. |
| `admin_contact_email` | string | Admin contact email. |
| `server_ip` | string | Server IP if available; otherwise empty or omitted. |
| `last_successful_ai_run_at` | string | ISO 8601 of last successful AI run, or empty. |
| `last_successful_build_plan_execution_at` | string | ISO 8601 of last successful Build Plan execution, or empty. |
| `current_health_summary` | string | Short status: healthy / warning / degraded / critical. |
| `current_queue_warning_count` | int | Count of queue warnings. |
| `current_unresolved_critical_error_count` | int | Count of unresolved critical errors. |
| `timestamp` | string | ISO 8601 UTC. |

**Ineligibility / validation:** Do not send more than one successful heartbeat per site per calendar month (dedupe by `heartbeat_{site_reference}_{YYYY-MM}`).

**Example payload:** See §10.2.

---

### 8.3 Developer error report (spec §45.7, §46.6, §46.7)

**Event type:** `developer_error_report`

**Trigger rules (spec §46.6):** Report when:

- severity = critical, or
- same error repeats 3 times within 24 hours, or
- page replacement action fails after final retry, or
- Build Plan finalization fails at publish stage, or
- import/restore fails after validation passed, or
- queue enters dead/stalled state for more than 15 minutes, or
- a migration fails.

**Severity-based rules (spec §46.7):**

- **info:** local log only.
- **warning:** local log only unless repeated 10+ times in 24 hours.
- **error:** report if tied to plan execution, restore, or queue failure.
- **critical:** report immediately.

**Payload body fields (required):**

| Field | Type | Description |
|-------|------|-------------|
| `severity` | string | One of: info, warning, error, critical. |
| `category` | string | One of Log_Categories (validation, dependency, execution, provider, queue, reporting, import_export, security, compatibility). |
| `sanitized_error_summary` | string | Short summary; no secrets, no raw stack. |
| `expected_behavior` | string | What was expected (short). |
| `actual_behavior` | string | What occurred (short). |
| `website_address` | string | Site identifier. |
| `plugin_version` | string | Plugin version. |
| `wordpress_version` | string | WordPress version. |
| `php_version` | string | PHP version. |
| `admin_contact_email` | string | Admin contact email. |
| `server_ip` | string | If available. |
| `timestamp` | string | ISO 8601 UTC. |
| `log_reference` | object | See §6.2: `log_id`, optionally `log_category`, `log_severity`. |
| `related_plan_id` | string | Build Plan ID if applicable; else empty. |
| `related_job_id` | string | Job ID if applicable; else empty. |
| `related_run_id` | string | AI run ID if applicable; else empty. |

**Ineligibility / validation:** Do not build if severity/category/trigger rules are not met, or if sanitized_error_summary would be empty after redaction, or if log_id is missing.

**Example payload:** See §10.3.

---

## 9. Payload Validation and Ineligibility Rules

- **Install:** Missing site_reference, or duplicate install (already sent for this site/domain lifecycle).
- **Heartbeat:** Missing site_reference; or already sent for this site for the current calendar month.
- **Developer error:** Severity/category not meeting trigger rules; missing log_id; sanitized_error_summary empty after redaction; or dedupe indicates same report already sent in the dedupe window.

When ineligible, do not enqueue and do not send. Log ineligibility locally if needed for diagnostics. Payload construction code MUST validate before producing a sendable payload.

---

## 10. Example Payloads (One per Report Type)

### 10.1 Example: Installation notification

```json
{
  "schema_version": "1.0",
  "event_type": "install_notification",
  "site_reference": "example.com",
  "plugin_version": "1.2.0",
  "timestamp": "2025-03-15T14:00:00Z",
  "dedupe_key": "install_example.com_2025-03-15T14:00:00Z",
  "payload": {
    "website_address": "https://example.com",
    "plugin_version": "1.2.0",
    "wordpress_version": "6.4.2",
    "php_version": "8.2.0",
    "server_ip": "203.0.113.10",
    "admin_contact_email": "admin@example.com",
    "timestamp": "2025-03-15T14:00:00Z",
    "dependency_readiness_summary": "all ready"
  },
  "delivery_metadata": null
}
```

### 10.2 Example: Monthly heartbeat

```json
{
  "schema_version": "1.0",
  "event_type": "heartbeat",
  "site_reference": "example.com",
  "plugin_version": "1.2.0",
  "timestamp": "2025-03-15T00:05:00Z",
  "dedupe_key": "heartbeat_example.com_2025-03",
  "payload": {
    "website_address": "https://example.com",
    "plugin_version": "1.2.0",
    "wordpress_version": "6.4.2",
    "php_version": "8.2.0",
    "admin_contact_email": "admin@example.com",
    "server_ip": "203.0.113.10",
    "last_successful_ai_run_at": "2025-03-10T09:00:00Z",
    "last_successful_build_plan_execution_at": "2025-03-12T11:30:00Z",
    "current_health_summary": "healthy",
    "current_queue_warning_count": 0,
    "current_unresolved_critical_error_count": 0,
    "timestamp": "2025-03-15T00:05:00Z"
  },
  "delivery_metadata": null
}
```

### 10.3 Example: Developer error report (ineligible-at-execution style)

```json
{
  "schema_version": "1.0",
  "event_type": "developer_error_report",
  "site_reference": "example.com",
  "plugin_version": "1.2.0",
  "timestamp": "2025-03-15T16:22:00Z",
  "dedupe_key": "error_execution_plan_finalize_failed_2025-03-15",
  "payload": {
    "severity": "critical",
    "category": "execution",
    "sanitized_error_summary": "Build Plan finalization failed at publish stage.",
    "expected_behavior": "Plan state transitions to finalized; changes published.",
    "actual_behavior": "Publish step returned error; plan left in confirmation state.",
    "website_address": "https://example.com",
    "plugin_version": "1.2.0",
    "wordpress_version": "6.4.2",
    "php_version": "8.2.0",
    "admin_contact_email": "admin@example.com",
    "server_ip": "203.0.113.10",
    "timestamp": "2025-03-15T16:22:00Z",
    "log_reference": {
      "log_id": "err-a1b2c3d4",
      "log_category": "execution",
      "log_severity": "critical"
    },
    "related_plan_id": "plan-uuid-123",
    "related_job_id": "job-456",
    "related_run_id": ""
  },
  "delivery_metadata": null
}
```

---

## 11. Disclosure-Facing Labels (spec §46.11, §47.6)

The following labels are for admin and documentation disclosure. They describe what is sent, not the internal field names.

| Report type | Disclosure label (short) | Disclosure description |
|-------------|---------------------------|-------------------------|
| Installation notification | Installation notification | Sent once after first successful activation. Includes site identifier, plugin/WP/PHP versions, server IP if available, admin contact email, dependency summary. |
| Heartbeat | Monthly heartbeat | Sent once per calendar month per site. Includes site identifier, versions, admin email, last successful AI run and Build Plan execution timestamps, health summary, queue and critical error counts. |
| Developer error report | Error reporting | Sent when severity is critical or when specific failure conditions are met. Includes severity, category, sanitized error summary, expected/actual behavior, site/version info, admin email, log reference, and related plan/job/run IDs. No secrets, no raw payloads. |

**Excluded (disclosure):** Passwords, API keys, tokens, nonces, raw credentials, full unpublished content, full raw AI payloads.

---

## 12. Relationship to Other Contracts

- **Diagnostics and Logging Contract** (diagnostics-contract.md): Defines Error_Record shape, severity, categories, and redaction rules. Report payloads consume **already-redacted** log data (e.g. Error_Record) and must not re-introduce secrets. The developer error report payload aligns with structured error log format (spec §45.5) and links via `log_reference.log_id`.
- **Execution / Queue:** Reporting delivery will use queued jobs where possible (spec §46.12). This contract does not define the queue job format; it defines only the **payload** that will be sent. Queue implementation will reference this contract for payload shape and dedupe keys.

---

## 13. Versioning

- **Schema version:** The envelope field `schema_version` (e.g. `"1.0"`) identifies the contract version. Implementations must set it when building payloads. New required fields or breaking changes require a new schema version and contract revision.
- **Stability:** No reporting pathway may add optional "debug" or "temporary" fields that bypass inclusion/exclusion or redaction. All fields must be documented in this contract or a formal revision.
