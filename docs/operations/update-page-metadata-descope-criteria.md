# UPDATE_PAGE_METADATA — De-Scope Criteria

**Decision:** [update-page-metadata-scope-decision.md](update-page-metadata-scope-decision.md)

---

## 1. Purpose

UPDATE_PAGE_METADATA is **out of scope** as an executable action. This document defines the exact code and docs changes required so the system no longer advertises or tracks it as available work.

---

## 2. Code changes required

### 2.1 Bulk_Executor — stop producing update_page_metadata envelopes

**File:** `plugin/src/Domain/Execution/Queue/Bulk_Executor.php`

- **Change:** Remove the mapping of `ITEM_TYPE_SEO` to `UPDATE_PAGE_METADATA` from `ITEM_TYPE_TO_ACTION_TYPE`.
- **Current:** `Build_Plan_Item_Schema::ITEM_TYPE_SEO => Execution_Action_Types::UPDATE_PAGE_METADATA,`
- **Action:** Delete this line. Do **not** add a replacement mapping (e.g. do not map SEO to another action type). SEO items will then be skipped in `collect_eligible_items()` because their `item_type` will no longer be in the map, so no envelopes will be built for them when the queue runs.
- **Result:** Approved SEO plan items will not generate any execution envelope; they remain in the plan for display/review only.

### 2.2 Queue_Health_Summary_Builder — stop listing UPDATE_PAGE_METADATA as a known type

**File:** `plugin/src/Domain/Execution/Queue/Queue_Health_Summary_Builder.php`

- **Change:** Remove `Execution_Action_Types::UPDATE_PAGE_METADATA` from the array of action types used for health/counts (e.g. the list that includes CREATE_PAGE, REPLACE_PAGE, UPDATE_MENU, APPLY_TOKEN_SET).
- **Location:** Around line 160 (in the array of action type constants).
- **Result:** Health summary and any counts by action type will not include update_page_metadata as a handled type.

### 2.3 Queue_Recovery_Service — stop listing UPDATE_PAGE_METADATA as a known type

**File:** `plugin/src/Domain/Execution/Queue/Queue_Recovery_Service.php`

- **Change:** Remove `Execution_Action_Types::UPDATE_PAGE_METADATA` from the array of action types used for recovery (e.g. the list passed or used to classify jobs).
- **Location:** Around line 158.
- **Result:** Recovery logic will not treat update_page_metadata as a supported action type.

### 2.4 Logs_Monitoring_State_Builder — stop listing update_page_metadata in action-type list

**File:** `plugin/src/Domain/Reporting/UI/Logs_Monitoring_State_Builder.php`

- **Change:** Remove `'update_page_metadata'` from the list of action type strings (e.g. the array used for filter options or display).
- **Location:** Around line 283.
- **Result:** Logs/monitoring UI will not present update_page_metadata as a filterable or known action type.

---

## 3. What to leave unchanged

- **Execution_Action_Types:** Keep `UPDATE_PAGE_METADATA` constant and keep it in `ALL`. The contract and enum remain the source of truth for valid action type names; removing it would require contract revision and could break any validation that uses `is_valid()` or `ALL`. The decision is “out of scope for execution,” not “remove the type from the contract.”
- **execution-action-contract.md:** No change required. Optionally add a one-line note under the action types table: “update_page_metadata is not implemented in this version; SEO step is recommendation-only.” (See §4.)
- **Execution_Provider:** No change. It already does not register a handler for UPDATE_PAGE_METADATA.
- **Operational_Snapshot_Schema (page_metadata object family):** Leave as-is for snapshot/rollback schema; it may be used by other flows or future implementation.
- **SEO_Media_Step_UI_Service:** No change. It already states “Shell-only” and “No SEO or media execution.”

---

## 4. Documentation

- **execution-action-contract.md (optional):** Add a short note in §3 (Action Types) or in a “Implementation status” subsection: “In the current version, `update_page_metadata` is not implemented; the SEO/meta step is recommendation-only. The type is defined for contract stability and possible future use.”
- **This decision and de-scope doc:** Reference from the ledger or spec/decision notes so future work knows UPDATE_PAGE_METADATA is intentionally out of scope unless a later product/spec decision brings it in.

---

## 5. Verification

- After changes: (1) No envelope with `action_type === 'update_page_metadata'` is produced by Bulk_Executor when building from a plan that contains approved SEO items. (2) Queue health, recovery, and logs monitoring do not include update_page_metadata in their “known” or “handled” action type lists. (3) Execution_Provider still does not register a handler. (4) SEO step UI and docblocks still state no SEO execution.
