# Build Plan State Machine Contract

**Document type:** Authoritative contract for Build Plan status model and state machine (spec §30.4, §30.11–30.12, §31.2, §31.3, §31.8, §31.10).  
**Governs:** Root plan statuses, step statuses, item statuses, allowed transitions, blocking states, completion recognition, denial handling, partial review/execution, resumption rules, and user-visible status messaging.  
**Related:** build-plan-schema.md (root/step/item schema), object-model-schema.md (§3.4 Build Plan).

---

## 1. Purpose and scope

Build Plan **viewing is not approval**. **Approval is not execution**. **Execution is not rollback authority**. **Denial is not failure**. This contract defines:

- **Root plan statuses** and **allowed transitions**
- **Step statuses** and their relationship to unresolved items and blockers
- **Per-item statuses** and transition rules (review phase and execution phase)
- **Completion recognition** — when a plan is considered completed (all actionable items resolved: executed, skipped, or denied)
- **Denial handling** — plan-level rejection vs item-level denial
- **Resumption** — how to resume a partially reviewed or partially executed plan
- **User-visible status messaging** expectations (spec §31.8)

Status mutations are **server-authoritative** and capability-gated by future callers. No client-authoritative state toggles.

**Out of scope:** Build Plan generation, UI implementation, execution jobs, rollback, queue integration.

---

## 2. Root plan statuses (spec §30.4)

| Status | Meaning | Viewing | Approval | Execution |
|--------|---------|---------|----------|-----------|
| `pending_review` | Plan created; awaiting user approval or denial of the plan as a whole | Allowed | User may approve or reject | Not allowed |
| `approved` | User has approved the plan (full or partial); eligible for execution | Allowed | Already approved | Allowed (when capability present) |
| `rejected` | User has denied the plan | Allowed (read-only history) | N/A — terminal | Not allowed |
| `in_progress` | Execution has started; not yet completed | Allowed | N/A | In progress; resumable |
| `completed` | Execution finished; all actionable items resolved | Allowed (read-only history) | N/A | N/A — terminal |
| `superseded` | Plan replaced by a newer plan | Allowed (read-only history) | N/A | N/A — terminal |

**Terminal statuses:** `rejected`, `completed`, `superseded`. No transition out of these.

**Denial vs failure:** `rejected` = **user denied** the plan. Execution **failure** (e.g. a step or item failed during execution) does **not** set root status to a separate "failed"; the plan remains `in_progress` until the system or user resolves (e.g. mark remaining items skipped/failed and then complete, or supersede). Item-level `failed` and step-level blocking reflect execution failure; root status stays `in_progress` until completion logic runs.

---

## 3. Root plan transition table

| From | To | Condition / note |
|------|-----|------------------|
| `pending_review` | `approved` | User approves plan (full or partial approval). |
| `pending_review` | `rejected` | User rejects plan. Terminal. |
| `approved` | `in_progress` | Execution started (server-authoritative). |
| `approved` | `superseded` | Newer plan created; this plan replaced (e.g. new AI run). Terminal. |
| `in_progress` | `completed` | Completion recognition satisfied (§7). Terminal. |
| `in_progress` | `superseded` | Plan replaced while in progress (e.g. user abandoned and created new plan). Terminal. |
| `rejected` | — | No outgoing transitions. |
| `completed` | — | No outgoing transitions. |
| `superseded` | — | No outgoing transitions. |

**Invalid (examples):** `rejected` → `approved`; `completed` → `pending_review`; `approved` → `rejected` (plan-level rejection happens only from `pending_review`). Item-level denials do not change root from `approved` to `rejected`; root stays `approved` with items in `rejected` or `skipped`.

---

## 4. Step statuses

Steps group items. Step status is **derived or stored** for UI and progress tracking (spec §31.3, §31.10).

| Step status | Meaning | Relationship to items |
|-------------|---------|------------------------|
| `pending` | Step not yet reviewed or not yet executed | At least one item in step is `pending` (or step has no actionable items). |
| `in_progress` | Step under review or execution in progress | At least one item `pending` or `in_progress`; at least one item already `approved`/`completed`/`skipped`/`rejected`. |
| `blocked` | Step cannot proceed because a dependency or blocker is unresolved | One or more items have `depends_on_item_ids` pointing to items not yet in a terminal state, or a prior step has blocking items not resolved. |
| `reviewed` | All actionable items in the step have a review outcome (approved/rejected/skipped) | No item in step is `pending` for review; used in review phase. |
| `completed` | All actionable items in the step are in a terminal execution state | All items `completed`, `skipped`, or `rejected`; no item `pending` or `in_progress` or `failed` unresolved. |
| `skipped` | Step was skipped (e.g. no items or user skipped step) | Step has no items or all items `skipped`. |

**Blocking:** A step is **blocked** when (a) any item in the step has `depends_on_item_ids` and at least one dependency has status not in `{ completed, skipped, rejected }`, or (b) a previous step (by `order`) has a blocking item (`blocking: true`) that is not yet `completed` or `skipped`. Blocked steps are not eligible for execution until blockers are resolved.

---

## 5. Step transition rules

Step status is typically **computed** from item statuses within the step and dependency graph; transitions are not stored as a separate state machine but derived.

| Scenario | Step status result |
|----------|--------------------|
| All items `pending` | `pending` |
| Any item `in_progress` or mixed pending + resolved | `in_progress` |
| Dependencies unresolved (see §4) | `blocked` |
| All items have review outcome (approved/rejected/skipped); review phase | `reviewed` |
| All items in execution terminal state (completed/skipped/rejected/failed) | `completed` |
| No items or all items `skipped` | `skipped` or `completed` |

---

## 6. Item statuses

Items have **review-phase** and **execution-phase** semantics. Not every item is executable (e.g. `overview_note`); for those, only review-phase status applies.

### 6.1 Item status enum

| Item status | Phase | Meaning |
|-------------|-------|---------|
| `pending` | Review / Execution | Item not yet reviewed or not yet executed; default initial state. |
| `approved` | Review | User approved this item for execution. |
| `rejected` | Review | User denied this item (do not execute). Terminal for item. |
| `skipped` | Review / Execution | User or system skipped this item. Terminal. |
| `in_progress` | Execution | Execution of this item has started. |
| `completed` | Execution | Item executed successfully. Terminal. |
| `failed` | Execution | Execution of this item failed. May be retried or marked skipped by policy. |

**Terminal item statuses:** `rejected`, `skipped`, `completed`, `failed` (when treated as terminal by policy).

### 6.2 Item transition table (review phase)

Plan root status is `pending_review`. Items may be approved or rejected per item (partial review).

| From | To | Condition |
|------|-----|-----------|
| `pending` | `approved` | User approves item. |
| `pending` | `rejected` | User rejects item. |
| `pending` | `skipped` | User or system skips item. |
| `approved` | `pending` | Allowed only before plan-level approval (revert review). |
| `rejected` | `pending` | Allowed only before plan-level approval (revert review). |
| `skipped` | `pending` | Allowed only before plan-level approval (revert). |

After plan root transitions to `approved`, item review states are **locked** for execution purposes (approved items are candidates for execution; rejected/skipped are not executed).

### 6.3 Item transition table (execution phase)

Plan root status is `approved` or `in_progress`. Only items in `approved` (or `pending` if execution treats pending as runnable) are executed.

| From | To | Condition |
|------|-----|-----------|
| `approved` | `in_progress` | Executor started this item. |
| `in_progress` | `completed` | Item executed successfully. |
| `in_progress` | `failed` | Execution failed; may retry or mark skipped. |
| `in_progress` | `skipped` | Execution abandoned for this item. |
| `failed` | `in_progress` | Retry (if policy allows). |
| `failed` | `skipped` | Mark skipped after failure. |

Items that were `rejected` or `skipped` in review remain so; they are not executed.

---

## 7. Completion recognition (spec §30.12)

A plan is **completed** (root status → `completed`) when:

1. Root status is `in_progress`, and  
2. **All actionable items** are in a **terminal state**: `completed`, `skipped`, `rejected`, or `failed` (with `failed` treated as terminal when retry is not allowed or user marked skipped).  
3. Non-actionable items (e.g. `overview_note`, `confirmation`) do not block completion.

**Actionable items** are items that represent work to be done (e.g. `existing_page_change`, `new_page`, `menu_change`, `design_token`, `seo`). Overview and confirmation items are not executed; they are excluded from the "all items terminal" check or are considered terminal by definition.

**Completion is meaningful:** The plan record is not mutated after completion; history is preserved. `completed_at` is set when transitioning to `completed`.

**Partial execution:** If the user or system stops execution before all items are terminal, the plan remains `in_progress` and is **resumable** (§9). Completion is not asserted until all actionable items are resolved.

---

## 8. Denial handling (spec §30.11)

- **Plan-level denial:** User rejects the **entire plan** at the confirmation step. Root status transitions `pending_review` → `rejected`. No execution. Plan is retained for history; denial is a **legitimate outcome**, not an error.
- **Item-level denial:** User rejects **specific items** during review. Those items have status `rejected`; they are not executed. Plan can still be approved (root → `approved`) with some items rejected. Execution runs only for `approved` items.
- **Denial is not failure:** `rejected` (plan or item) means user said no. `failed` (item) means execution attempted and failed. Messaging must distinguish "You rejected this" from "Execution failed."

---

## 9. Resumption behavior

- **Partially reviewed plan:** Root remains `pending_review` until user confirms at confirmation step. User may navigate away and return; step and item review state are persisted. Resumption: show plan in same step/item state; user continues review and eventually approves or rejects.
- **Partially executed plan:** Root is `in_progress`. Some items are `completed`/`skipped`/`failed`; others still `approved` or `in_progress`. Resumption: executor (future) resumes from next runnable item or retries `failed` items per policy. Progress tracking (§31.10) should show which steps/items are done and which remain.
- **Blocked step:** If a step is `blocked` due to dependencies, resumption does not execute that step until dependencies are resolved. UI may show "Blocked" and which dependency is missing.

---

## 10. When a plan becomes archived or superseded

- **Superseded:** Root status is set to `superseded` when a **newer plan** is created (e.g. new AI run produced a new plan) and the product policy replaces the current plan. Optionally, a reference from the new plan to the superseded plan is stored. `completed_at` (or a `superseded_at` field) may be set. Terminal.
- **Archived:** "Archived" is a **retention/history** concept, not a root status. Plans with root status `completed` or `superseded` may be **archived** (e.g. `history_retention` = `archive`) for storage policy. Root status remains `completed` or `superseded`; archived means the record is retained but may be moved to cold storage or marked for limited access. User-visible messaging may say "Archived" as a label for completed/superseded plans in an archive view.

---

## 11. User-visible status messaging (spec §31.8)

Messaging must be consistent and distinguish viewing, approval, execution, and finalization.

| Context | Message pattern (example) |
|---------|---------------------------|
| Plan pending review | "Plan is pending your review." / "Review and approve or reject this plan." |
| Plan approved, not started | "Plan approved. Ready to execute." |
| Plan in progress | "Execution in progress. X of Y items completed." |
| Plan completed | "Plan completed on [date]." |
| Plan rejected | "Plan was rejected on [date]." (Do not say "failed.") |
| Plan superseded | "This plan was superseded by a newer plan." |
| Item rejected | "You rejected this item." (Not "failed.") |
| Item failed (execution) | "Execution failed for this item." / "Retry or skip." |
| Step blocked | "This step is blocked. Complete [dependency] first." |
| Partial review | "X of Y steps reviewed." |

Sensitive states (e.g. execution eligibility, rollback) must not be inferred from UI view access alone; capability checks govern actions.

---

## 12. Progress tracking patterns (spec §31.10)

- **Review phase:** Track per-step or overall "steps reviewed" count; highlight steps with `pending` items. Show confirmation step last; "Approve plan" / "Reject plan" actions only at confirmation.
- **Execution phase:** Track "items completed" vs "items total" (actionable only); show step order and blocked state. Resumption shows last executed step/item and next runnable.
- **History:** Completed/rejected/superseded plans are read-only; show status, completed_at/rejected_at, and summary. No edit or execute actions.

---

## 13. Scenario matrix

| Scenario | Root status | Step status (example) | Item status (example) | Notes |
|----------|-------------|------------------------|------------------------|-------|
| **Draft / pending review** | `pending_review` | Steps `pending` or `in_progress` (review) | Items `pending` | User reviewing; not yet approved or rejected. |
| **Partially reviewed** | `pending_review` | Some steps `reviewed`, some `pending` | Some items `approved`/`rejected`/`skipped`, rest `pending` | User can resume and complete review. |
| **Approved, not started** | `approved` | Steps `reviewed` or N/A | Items `approved` or `rejected`/`skipped` | Ready for execution; no execution yet. |
| **Partially executed** | `in_progress` | Some steps `completed`, some `in_progress` or `blocked` | Mix of `completed`, `in_progress`, `approved`, `failed`, `skipped` | Resumable. |
| **Blocked step** | `in_progress` | One step `blocked` | Dependency item not terminal | Executor cannot run blocked step until dependency done. |
| **Full completion** | `completed` | All steps `completed` or `skipped` | All actionable items `completed`/`skipped`/`rejected`/`failed` (terminal) | completed_at set; history retained. |
| **Plan rejected** | `rejected` | — | — | User denied plan at confirmation. Terminal. |
| **Superseded plan** | `superseded` | — | — | Replaced by newer plan. Terminal. |
| **Item denial (partial)** | `approved` | Steps `reviewed` | Some items `rejected`, some `approved` | Plan approved; only approved items executed. |
| **Execution failure (item)** | `in_progress` | Step may show `in_progress` or item `failed` | One or more items `failed` | Root stays in_progress; retry or skip item per policy. |

---

## 14. Blocker rules summary

1. **Step blocked:** Step has at least one item with `depends_on_item_ids` and at least one dependency item is not in a terminal state (`completed`, `skipped`, `rejected`, `failed` when terminal).
2. **Step blocked (order):** A step with `order` N is blocked if a step with `order` < N has an item with `blocking: true` that is not yet `completed` or `skipped`.
3. **Execution order:** Executor (future) must not execute an item until its dependencies (depends_on_item_ids) are terminal and any prior blocking items are resolved.
4. **Review phase:** No blocker semantics for review; user may review steps in any order (UI may still suggest order per §31.3).

---

## 15. Cross-references

- **Schema:** build-plan-schema.md — root `status`, step `status`, item `status` field names; `approval_denial_state`, `remaining_work_status`, `execution_status`; completion/history semantics.
- **Object model:** object-model-schema.md §3.4 — Build Plan lifecycle and transitions.
- **Navigation / stepper:** Spec §31.2, §31.3 — stepper layout and navigation model; state machine supports step order and blocked state for UI.
