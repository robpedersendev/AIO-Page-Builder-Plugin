# Token Application — De-Scope Criteria (Recommendation-Only)

**Decision:** [token-application-scope-decision.md](token-application-scope-decision.md)  
**Ledger:** [security-privacy-remediation-ledger.md](security-privacy-remediation-ledger.md) SPR-009

---

## 1. Purpose

Token application is **out of scope** as a user-facing feature. This document defines what must remain true so the product state is clearly recommendation-only, and what must be documented or gated so the execution path is not presented as available.

---

## 2. UI and copy (must remain)

- **Tokens step (Build Plan workspace):** Bulk action controls “Apply all tokens”, “Apply to selected”, “Deny all” must remain **disabled** (`enabled => false` in `Tokens_Step_UI_Service::placeholder_bulk_states()` or equivalent). No production UI may enable these for the design_tokens step.
- **Revert/history section:** The detail panel must continue to state that **“Token application is not available in this version. Recommendations are for review only.”** (or equivalent approved wording). No copy may imply that users can apply tokens from this step.
- **Step messages:** Messaging may refer to “pending review” and “recommendations”; must not imply that apply/deny actions are available or will take effect.

---

## 3. Execution path (retain but do not expose)

- **APPLY_TOKEN_SET, Apply_Token_Set_Handler, Token_Set_Job_Service, Rollback_Token_Set_Handler:** Retained as implemented. They support rollback compatibility, snapshot/restore behavior, and future use. No requirement to remove or stub them.
- **Bulk_Executor mapping:** Mapping of `ITEM_TYPE_DESIGN_TOKEN` → `APPLY_TOKEN_SET` may remain. Token items may exist in plans and in the queue; the **UI** must not offer a path for the user to approve token items for execution in a way that suggests token apply is a supported feature. If the queue is run (e.g. from another step or a “run plan” flow), token items could theoretically execute; product may either (a) leave as-is and rely on “no UI to approve tokens for run” or (b) add an explicit gate so token items are skipped or not enqueued when executing the plan. Decision record does not mandate (b); current state is (a).
- **Documentation:** Code and contract docs (e.g. Tokens_Step_UI_Service, SPR-009, global-styling-settings-contract) must state that token application is **not available** in this version and that the step is recommendation-only. Handler/job docblocks may state that they exist for rollback and architecture; they must not imply that token apply is a supported user feature.

---

## 4. Docs and ledger

- **Ledger SPR-009:** Keep as implemented: “Shell-only UI per spec; make copy explicit that token application is not available.” No change to status.
- **This decision and de-scope doc:** Reference from ledger §6 or spec/decision notes so future work knows token application is intentionally out of scope unless a later product/spec decision brings it in.

---

## 5. What must NOT be done (unless scope changes)

- **Do not** enable bulk apply/deny for the design_tokens step without a new product decision that token application is in scope.
- **Do not** add UI that suggests users can “apply” or “execute” token recommendations from the Build Plan workspace without that decision.
- **Do not** remove or alter the “Token application is not available in this version” (or equivalent) copy without a decision to bring token application in scope.
- **Do not** repurpose or remove `Apply_Token_Set_Handler`, `Token_Set_Job_Service`, or `Rollback_Token_Set_Handler` as part of de-scope; they are retained as infrastructure.

---

## 6. Future in-scope (if later approved)

If a later decision brings token application in scope, implementation will need to address at least:

- Source of truth: sync from `aio_applied_design_tokens` to `aio_global_style_settings` (or emitter reading applied) per global-styling-settings-contract.
- Enabling bulk apply/deny and wiring approval to queue for token items.
- Validation, sanitization, preview/approval, and rollback/audit as outlined in the decision record §5.
