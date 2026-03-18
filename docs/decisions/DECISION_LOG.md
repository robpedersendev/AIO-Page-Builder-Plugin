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

### 2. Industry Bundle JSON Import Apply In Scope

- **Date:** 2025-03-18
- **Title:** Industry bundle apply in scope; semantics from contracts
- **Status:** Accepted
- **Context:** Bundle preview existed; confirm/import did not apply bundle content. SPR-007 deferred apply because the master spec did not define apply semantics. Contracts (industry-pack-bundle-format-contract, industry-pack-import-conflict-contract) already define implementation-ready semantics.
- **Decision:** Bundle apply is in scope. Required semantics (what is written, where, overwrite/merge/conflict, validation, safe failure) are defined by the existing industry-pack bundle-format and import-conflict contracts. A spec revision note adds this to the master spec; implementation proceeds against those contracts.
- **Consequences:**
  - Implement apply flow (admin-only, nonce- and capability-gated) that persists bundle content to industry registries/overlays per conflict resolution.
  - SPR-007 satisfied by implementation once done; ledger/closeout can be updated.
  - Acceptance criteria: docs/operations/industry-bundle-apply-acceptance-criteria.md.
  - Full site backup/restore remains via Import / Export (ZIP).
