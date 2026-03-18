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

### 3. Import/Export ZIP Pre-Move Size Limit

- **Date:** 2025-03-18
- **Title:** Explicit plugin cap for Import/Export ZIP upload size
- **Status:** Accepted
- **Context:** Import/Export validate runs after move; no pre-move size check. Spec §43.11 requires file-upload workflows to validate file size; closeout listed pre-move ZIP size limit as optional hardening.
- **Decision:** Enforce an explicit plugin maximum (50 MB) before move_uploaded_file() in the validate flow. Reject oversized uploads with a dedicated error message stating the limit. Satisfies §43.11 and aligns with tightly controlled ZIP import (§43.12).
- **Consequences:**
  - Implement pre-move check in Import_Export_Screen::handle_validate(); use a named constant for the limit.
  - Acceptance criteria: docs/operations/import-export-zip-size-limit-acceptance-criteria.md.
  - Industry bundle JSON remains under its own 10 MB limit.

### 4. Privacy Exporter/Eraser Scope (No Expansion)

- **Date:** 2025-03-18
- **Title:** Privacy exporter/eraser scope remains actor-linked only
- **Status:** Accepted
- **Context:** Clarify whether scope should expand to site-level records, audit records, operational logs, or diagnostics. Current scope: AI runs, job queue, template compare user meta, bundle preview transient (all actor-linked). Ledger §6 already documented out-of-scope: onboarding draft, reporting log, industry profile/audit trail, diagnostics.
- **Decision:** Keep current scope. Do not expand to site-level, audit, operational logs, or diagnostics. WordPress privacy API is per-user; only data attributable to the requestor belongs in export/erase. Site-level data is not “this user’s data”; full site backup is Import/Export (ZIP). Audit need is met by redacting actor while retaining records.
- **Consequences:**
  - Boundary documented in docs/operations/privacy-exporter-eraser-scope-boundary.md.
  - No code change; no implementation criteria (scope unchanged).
  - If future storage is user-keyed (e.g. per-user logs), evaluate for inclusion and update boundary doc.

### 5. Token Application (Build Plan Step) Out of Scope

- **Date:** 2025-03-18
- **Title:** Token application remains recommendation-only; not a user-facing feature
- **Status:** Accepted
- **Context:** APPLY_TOKEN_SET and handler/job exist; Tokens step UI is shell-only (SPR-009) with disabled bulk apply and “not available” copy. Clarify whether token application is in scope as an executable feature.
- **Decision:** Token application is **out of scope** for the current product. Step is recommendation-only. Bulk apply/deny stay disabled; copy must state token application is not available. Handler, job, and rollback are retained as infrastructure but must not be presented as a supported user feature. Runtime styling uses aio_global_style_settings; aio_applied_design_tokens is build plan/rollback only—no sync in place.
- **Consequences:**
  - De-scope criteria: docs/operations/token-application-descope-criteria.md.
  - No implementation criteria (feature not in scope). If brought in scope later: source-of-truth sync, enable UI, validation/preview/rollback per decision record §5.
  - Ledger SPR-009 unchanged; decision referenced for product truth.
