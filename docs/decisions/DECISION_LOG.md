# Decision Log

This log captures architectural and governance decisions for future reference. Use an ADR-style format for each entry.

## Entry Format

For each decision:

- **Date:** YYYY-MM-DD
- **Title:** Short descriptive title
- **Status:** Proposed | Accepted | Deprecated | Superseded
- **Context:** Why the decision was needed
- **Decision:** What was decided
- **Consequences:** Trade-offs, follow-up actions, constraints introduced

---

## Entries

### 1. Private-Distribution Operational Reporting Exception

- **Date:** (Add when first adopted)
- **Title:** Private-distribution operational reporting exception
- **Status:** Accepted
- **Context:** This plugin is privately distributed. Operational visibility (install notifications, heartbeats, diagnostics) is required for support and deployment management. wordpress.org distribution rules prohibit outbound reporting; private distribution allows a narrow exception under documented constraints.
- **Decision:** Allow install notifications, heartbeat messages, and diagnostics provided that:
  - Reporting code is isolated in its own domain
  - Payloads have schema definitions, redaction rules, retry rules, timeout rules, and audit logs
  - Reporting failure never takes down core plugin behavior
  - Reporting is disclosed in admin-facing documentation and settings
- **Consequences:**
  - Narrow exception; no broadening without a documented decision
  - Reporting module must be maintained separately from core
  - All new reporting types require a new decision log entry
