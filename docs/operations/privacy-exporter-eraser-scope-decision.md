# Privacy Exporter/Eraser Scope — Decision

**Date:** 2025-03-18  
**Status:** Accepted  
**Sources:** [aio-page-builder-master-spec.md](../specs/aio-page-builder-master-spec.md) §47; [security-privacy-remediation-ledger.md](security-privacy-remediation-ledger.md) §6 (SPR-004); [security-privacy-audit-close-report.md](../qa/security-privacy-audit-close-report.md); Personal_Data_Exporter, Personal_Data_Eraser (plugin/src/Infrastructure/Privacy).

---

## 1. Objective

Clarify whether the WordPress privacy exporter/eraser scope should expand beyond the currently covered **actor-linked** data to include site-level records, audit records, operational logs, or diagnostic records.

---

## 2. Current scope (SPR-004 outcome)

**In scope (exportable/erasable per user):**

| Data class | Export | Erase | Notes |
|------------|--------|-------|------|
| AI run records | Yes | Redact actor; retain record | Keyed by actor (user ID). |
| Job queue records | Yes | Redact actor_ref; retain row | Keyed by actor_ref (user:N). |
| Template compare user meta | Yes | Delete | User-scoped compare lists. |
| Bundle preview transient | Yes (note) | Delete | Per-user transient key. |

**Out of scope (documented in ledger §6):**

- Onboarding draft (site option; not keyed by user).
- Reporting log (operational; not keyed by user).
- Industry profile / audit trail (site-level).
- Diagnostics (not keyed by user).
- Execution log table (schema has actor_ref but no writes in codebase; UI uses job queue).

---

## 3. Spec and API constraints

- **§47.3 Personal data:** Administrator contact email, user references in logs, actor identifiers on approvals/execution, user-provided business contact information. Plugin must treat personal data intentionally.
- **§47.9 Exporter:** “Support exporter integration for **personal data categories it controls**.” Relevance, permission, practical interpretability.
- **§47.10 Eraser:** “Support eraser integration for **personal-data categories it controls**, subject to **retention and audit needs**.” Must not destroy system integrity.
- **WordPress privacy API:** Export and Erase are invoked **per requestor** (one user, by email). The API expects data that can be attributed to that user. Site-wide data is not “this user’s personal data” in that sense.

---

## 4. Should scope expand?

**Site-level records (onboarding draft, industry profile, site options):** No. They are not keyed by user. Including them in “Export/Erase data for user X” would either (a) attach site data to every user’s export, or (b) require a different flow (e.g. “export all plugin data”) which is already served by Import/Export (ZIP). The WP privacy tools are per-user; site-level stays out of exporter/eraser scope.

**Audit records:** Current design already redacts actor on AI runs and job queue while retaining records for audit. Other audit-style data (e.g. industry profile/audit trail) is site-level. No expansion needed for “audit” as a category; the boundary is “actor-linked vs not.”

**Operational logs / diagnostic records:** Ledger states diagnostics are “not keyed by user.” If in the future the plugin stores logs or diagnostics that are explicitly keyed by user (e.g. per-user activity log), those could be added to exporter/eraser in a future revision. The spec does not currently require operational or diagnostic logs to be included in the WP privacy exporter/eraser. §47.8 (redaction) applies to how we redact when producing logs/reports/exports; it does not mandate that every log be exportable via the privacy API.

**Conclusion:** None of the listed categories (site-level, audit, operational logs, diagnostics) should be brought into exporter/eraser scope at this time. The current boundary (actor-linked only) is correct and aligns with the spec and the WP privacy API.

---

## 5. Decision

**Outcome: A — Keep current scope and document the boundary clearly.**

- **Rationale:** (1) WP privacy API is per-user; scope is correctly limited to data attributable to the requestor. (2) Site-level and non–user-keyed data do not belong in a per-user export/erase. (3) Audit needs are met by redacting actor while retaining records. (4) Spec §47.9/47.10 refer to “personal data categories it controls” and eraser “subject to retention and audit needs,” which match the current implementation.
- **No expansion** of exporter/eraser to site-level records, generic audit records, operational logs, or diagnostic records. If future storage introduces user-keyed logs or other user-attributable data, scope can be revisited.

---

## 6. If outcome had been B (expand scope)

If the decision had been to expand scope, the following would be required:

- **Define exactly which data classes** become exportable/erasable (e.g. “reporting log entries that reference user X”).
- **Constraints:** Redaction (no secrets, no credentials in export); auditability (what is retained vs removed); secret exclusion (per §47.8 and Security-and-Privacy rules). Implementation criteria would cover new exporter groups, eraser behavior (delete vs redact), and tests.

This outcome was not chosen; it is recorded here for traceability only.
