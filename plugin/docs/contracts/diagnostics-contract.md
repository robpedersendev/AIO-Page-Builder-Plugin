# Diagnostics and Logging Contract

**Document type:** Authoritative contract for structured error/log records, severity, categories, and redaction (spec §4.15, §45, §59.3).  
**Governs:** Logger interface, record shape, user-facing vs admin-facing text, redaction rules.  
**Out of scope for this contract:** Custom table storage, reporting delivery, logs screen UI, queue integration, file-based production logger.

---

## 1. Record shape (spec §45.5)

| Field | Required | Type | Description |
|-------|----------|------|-------------|
| `id` | Yes | string | Unique record identifier (e.g. prefixed id or UUID). |
| `category` | Yes | string | One of Log_Categories (validation, dependency, execution, provider, queue, reporting, import_export, security, compatibility). |
| `severity` | Yes | string | One of Log_Severities (info, warning, error, critical). |
| `message` | Yes | string | Sanitized message only; no secrets or raw payloads. |
| `timestamp` | No (defaults to now) | string | ISO 8601 or equivalent. |
| `actor_context` | No | string | Who or what triggered (e.g. user id, role, "cron"). |
| `target_object` | No | string | Affected object (e.g. plan id, job id, "settings"). |
| `remediation_hint` | No | string | Recovery recommendation (spec §45.6). |
| `context_reference` | No | string | Related job/plan/run reference. |

Implementation: `Support\Logging\Error_Record`. Constructor validates category and severity; all string fields are readonly.

---

## 2. User-facing vs admin-facing text

- **User-facing** (spec §45.3): Explains what failed, avoids technical noise, indicates retry possibility, points to next step. Exposed via `Error_Record::get_user_facing_message()` (returns the sanitized message).
- **Admin-facing** (spec §45.4): May include category, target object, remediation hint, log reference. No secrets. Exposed via `Error_Record::get_admin_facing_detail()`.

Redaction applies before data is placed into an Error_Record. Records must not contain secrets, tokens, passwords, raw request headers, or session values.

---

## 3. Redaction rules (spec §45.9)

Before displaying broadly or reporting externally, redaction shall remove or mask:

- Secrets, API keys, tokens, passwords
- Sensitive raw payloads
- Prohibited personal data
- Session-specific values where not needed

Redaction is systematic, not optional. Debug mode must never override redaction rules (spec §45.10).

---

## 4. Severity levels (spec §45.2)

| Severity | Use |
|----------|-----|
| info | Informational events. |
| warning | Recoverable or non-blocking issues. |
| error | Operation failed; may be retried or remediated. |
| critical | Severe failure; high urgency. |

Severity influences UI messaging, logging prominence, and (future) developer reporting behavior.

---

## 5. Categories (spec §45.1)

| Category | Description |
|----------|-------------|
| validation | Validation failures. |
| dependency | Missing or incompatible dependency. |
| execution | Execution/build failure. |
| provider | AI provider error or credential issue. |
| queue | Queue or job system issue. |
| reporting | Outbound reporting failure. |
| import_export | Import/export error. |
| security | Permission, nonce, or security-related. |
| compatibility | Environment or compatibility issue. |

---

## 6. Logger interface and bootstrap

- **Interface:** `Support\Logging\Logger_Interface` with single method `log( Error_Record $record ): void`. Implementation must not throw.
- **Bootstrap sink:** `Support\Logging\Null_Logger` accepts records and performs no persistence. Sink is replaceable when storage contracts exist.
- **Resolver:** Container key `logger` resolves to the bootstrap logger. Container key `diagnostics` may expose logger and message helper for callers.

No persistence assumptions are hard-coded. Local logging remains separable from future outbound reporting.

---

## 7. Relationship to reporting

Outbound developer error reporting consumes **already-redacted** log data (e.g. `Error_Record`). The report payload links to the local log via a log reference (log_id). Payload shape, inclusion/exclusion rules, and redaction for reports are defined in **Reporting Payload Contract** (`docs/contracts/reporting-payload-contract.md`). Severity and category values in this contract align with developer reporting rules (spec §45.7, §46.7).
