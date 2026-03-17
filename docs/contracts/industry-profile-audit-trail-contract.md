# Industry Profile Audit Trail Contract (Prompt 465)

**Spec**: Industry Profile contracts; diagnostics/reporting contracts; lifecycle hardening and support docs.  
**Status**: Contract. Defines a bounded audit trail for major industry profile changes. Support-oriented; no public exposure.

---

## 1. Purpose

- **Bounded audit trail**: Record major industry profile state changes so admins and support can understand how primary industry, subtype, bundle, and pack-related selections evolved over time.
- **Support and troubleshooting**: Change-impact analysis, rollback reasoning, recommendation drift investigation.
- **No generic auditing**: Scope is industry profile only; bounded and maintainable. No excessive or noisy logging.

---

## 2. Event shape

Each audit event is a bounded record:

| Field | Type | Description |
|-------|------|-------------|
| event_type | string | One of: primary_industry_changed, secondary_industries_changed, subtype_changed, starter_bundle_changed, profile_replaced. |
| timestamp | string | ISO 8601 or Unix timestamp. |
| old_summary | string | Short human-readable summary of previous state (e.g. "primary: realtor, bundle: bundle_re_01"). |
| new_summary | string | Short human-readable summary of new state. |
| related_refs | array | Optional list of related refs (e.g. primary_industry_key, bundle_key). |
| actor | string | Optional; only if consistent with existing audit patterns (e.g. user id or "system"). |

- Summaries are bounded in length (e.g. 256 chars); no raw PII or secrets. related_refs are identifiers only.

---

## 3. When events are recorded

- **primary_industry_changed**: Primary industry key changed (including set/clear).
- **secondary_industries_changed**: Secondary industry keys list changed.
- **subtype_changed**: Industry subtype key (or subtype field) changed.
- **starter_bundle_changed**: Selected starter bundle key changed.
- **profile_replaced**: Full profile replace (set_profile) where a summary of changes is recorded as one or more events.

Pack activation/deactivation that affects the site may be recorded in the same trail with an event_type such as pack_activation_changed when the profile save path or pack toggle path is extended; optional for initial implementation.

---

## 4. Storage and retention

- **Storage**: Site-local option or bounded table (e.g. option key aio_industry_profile_audit_trail). List of events; newest first or last.
- **Retention**: Cap total events (e.g. 100). When cap is exceeded, drop oldest. No automatic purge by date required by this contract.
- **No rewrite of approved snapshots**: Audit trail does not modify Build Plan or approval history.

---

## 5. Timeline view

- **Surface**: Read-only timeline or summary for admin/support (e.g. on Industry Profile screen or diagnostics). Method such as get_timeline( limit ) returns bounded list of events.
- **Access**: Admin/support only. No public exposure. Same capability as Industry Profile or diagnostics.

---

## 6. Security and privacy

- **Admin/support-only**: Timeline and events are not exposed to front-end or unauthenticated users.
- **Minimal data**: Summaries and related_refs only; no sensitive unrelated data. No logging of secrets or raw API keys.
- **Safe fallback**: Missing or corrupt audit data must not break profile save or timeline read; return empty or partial.

---

## 7. Integration

- **Profile save**: Industry_Profile_Repository (set_profile, merge_profile) calls Industry_Profile_Audit_Trail_Service to record changes after a successful write. Service receives old and new profile and appends one or more events as appropriate.
- **Settings screens**: Optional timeline block or link to "Recent profile changes" when the audit service is available.
- **Support/runbook**: Document that profile change history is available for troubleshooting.

---

## 8. Files

- **Service**: plugin/src/Domain/Industry/Reporting/Industry_Profile_Audit_Trail_Service.php
- **Contract**: docs/contracts/industry-profile-audit-trail-contract.md
