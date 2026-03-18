# Token Application (Build Plan Step) — Scope Decision

**Date:** 2025-03-18  
**Status:** Accepted  
**Sources:** [aio-page-builder-master-spec.md](../specs/aio-page-builder-master-spec.md) §35, §40.1, §40.2, §42.2; [security-privacy-remediation-ledger.md](security-privacy-remediation-ledger.md) SPR-009; [global-styling-settings-contract.md](../contracts/global-styling-settings-contract.md); [data-schema-appendix.md](../appendices/data-schema-appendix.md); Tokens_Step_UI_Service, Apply_Token_Set_Handler, Token_Set_Job_Service, Bulk_Executor.

---

## 1. Objective

Resolve whether **token application** (apply token set from the Build Plan design-tokens step) is in scope as an executable, user-facing feature or remains recommendation-only.

---

## 2. Current state

- **Execution path:** `APPLY_TOKEN_SET` exists. `Bulk_Executor` maps `ITEM_TYPE_DESIGN_TOKEN` → `APPLY_TOKEN_SET`. `Apply_Token_Set_Handler` delegates to `Token_Set_Job_Service`, which validates group/name and writes to option `aio_applied_design_tokens`. `Rollback_Token_Set_Handler` exists. Snapshot/rollback support is wired for `apply_token_set`.
- **Tokens step UI:** `Tokens_Step_UI_Service` builds recommendation rows and summaries. Bulk actions (“Apply all”, “Apply to selected”, “Deny all”) are **disabled** (`enabled => false`). Revert/history section states: “Token application is not available in this version. Recommendations are for review only.” (SPR-009.)
- **Source-of-truth split:** `aio_applied_design_tokens` is written by `Token_Set_Job_Service` (plan execution). Runtime global styling is **aio_global_style_settings** (Global_Style_Settings_Repository, settings UI). Contracts state the two are **not** merged; the styling subsystem reads from `aio_global_style_settings`. “Build plan and rollback only; not repurposed as runtime styling store” (data-schema-appendix). So even if apply runs, applied values do not feed the live styling subsystem unless a separate “copy to global settings” flow is added (contract: “future prompt may add”).

---

## 3. Spec and placeholder status

- **Spec §40.1:** Execution engine “shall support” job types including “apply token set.” §42.2 lists “token application job” as a queueable type. So the **architecture** defines the job type and flow.
- **SPR-009 (ledger):** “Shell-only UI per spec; make copy explicit that token application is not available.” Implemented: UI copy and disabled bulk actions. Ledger §6: “Do not treat as gaps unless the master spec **later** requires full implementation (token application, …).” So the **product** stance is: token application is **not** required in this version; the step is intentionally recommendation-only.
- **Conclusion:** Spec defines the job type and execution model; remediation explicitly deferred user-facing token application. The execution path exists as infrastructure; the intended product truth is that token application is **not** an available feature in the current version.

---

## 4. Decision

**Outcome: B — Token application is out of scope; step remains recommendation-only.**

- **Rationale:** (1) SPR-009 and the ledger state that token application is not available in this version and the UI is shell-only. (2) Runtime styling source of truth is `aio_global_style_settings`; `aio_applied_design_tokens` is “build plan and rollback only.” Enabling apply in the UI would still not connect to live styling without additional product/contract work. (3) No product requirement to expose token apply in this release. (4) Handler and job are retained for architecture consistency, rollback support, and possible future use; they must not be presented as a user-facing feature.
- **Intended product truth:** The design-tokens step shows AI-generated token **recommendations** for review. Users cannot apply or deny them from this step; bulk apply/deny are disabled. Token application is not available in this version. Execution infrastructure (APPLY_TOKEN_SET, handler, job, rollback) exists but is not part of the supported user flow for this release.

---

## 5. If outcome had been A (in scope)

If token application had been approved as in scope, the following would be required:

- **Source of truth:** Decide whether applied tokens live in `aio_applied_design_tokens` only (rollback/plan artifact) or must sync to `aio_global_style_settings` (runtime). Contract currently separates them; a one-way “apply plan tokens to global settings” or emitter reading from applied would need to be defined.
- **Application targets:** Token group/name pairs (color, typography, spacing, radius, shadow, component per Token_Set_Job_Service::ALLOWED_GROUPS); values written to the chosen store(s).
- **Validation/sanitization:** Group/name allowlist (already in job); value format/length per token spec; no raw CSS or selectors.
- **Preview/approval:** Step shows recommendations; user approves/denies; approved items queued; execution applies only approved items. Rollback uses pre-change snapshot.
- **Rollback/audit:** Rollback_Token_Set_Handler restores from snapshot; execution and rollback logged.

This outcome was not chosen; it is recorded here for traceability only.
