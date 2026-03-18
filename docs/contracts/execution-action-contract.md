# Execution Action Contract

**Spec**: §39 Planner vs Executor Specification; §39.1–39.8 planner/executor boundaries and authorization rules; §40.1 Execution Job Types; §40.2 Single-Action Execution Flow; §59.10 Execution Engine Phase

**Status**: Contract definition only. No executor implementation. All execution pathways must conform to this contract before performing mutations.

---

## 1. Purpose

This contract defines the **governed action envelope** that every executor pathway must accept. The planner proposes; the executor acts only on **approved and validated** inputs. Execution job types are structured and typed. Raw Build Plan rows or raw AI output must not be passed directly to executors. This contract hardens the planner/executor boundary so execution code cannot drift into ad hoc mutation handlers.

---

## 2. Principles

- **Executor accepts only governed inputs.** No client-authoritative action envelopes; actions are built server-side from approved plan state and validated references.
- **Approval gate and actor permission are both required** where applicable (spec §39.6, §39.7).
- **Snapshot requirements are explicit**, not implied (spec §40.2).
- **Execution is typed, traceable, and bounded.** Malformed, unauthorized, or stale actions must be refused with defined error shapes.

---

## 3. Action Types

Execution action types are a stable enum. Each type maps to a class of mutation and determines required target references, dependency checks, and snapshot rules.

| Action type           | Description                         | Typical target reference   | Snapshot required |
|-----------------------|-------------------------------------|---------------------------|-------------------|
| `create_page`         | Create a new page from plan item    | plan item ref, template   | Yes (N/A pre-create) |
| `replace_page`        | Replace/rebuild existing page       | page ref, plan item ref   | Yes               |
| `update_page_metadata`| Update title, slug, meta only       | page ref, plan item ref   | Optional          |
| `assign_page_hierarchy` | Set parent/order for page        | page ref, parent ref      | Optional          |
| `create_menu`        | Create a new menu                   | plan item ref             | Optional          |
| `update_menu`        | Update menu items/locations         | menu ref, plan item ref   | Optional          |
| `apply_token_set`    | Apply design token values           | token set ref, plan ref   | Yes (pre-apply)  |
| `finalize_plan`      | Mark plan finalization complete     | plan ref                  | As per plan       |
| `rollback_action`    | Roll back a prior execution        | execution event ref       | N/A (recovery)    |

**Stability**: New action types may be added only via contract revision. Executors must ignore or refuse unknown action types.

**Implementation status:** In the current version, `update_page_metadata` is not implemented; the SEO/meta step is recommendation-only. The type is defined for contract stability and possible future use (see update-page-metadata-scope-decision.md).

---

## 4. Execution Action Envelope

The **action envelope** is the normalized input object that an executor receives. It is built server-side from the Build Plan, approval state, and actor context—never from raw client payloads.

### 4.1 Required envelope fields

| Field               | Type     | Description |
|---------------------|----------|-------------|
| `action_id`        | string   | Unique identifier for this execution attempt (idempotency, logging). |
| `action_type`       | string   | One of the action type enum values (§3). |
| `plan_id`          | string   | Build Plan identifier (plan_id from Build_Plan_Schema). |
| `plan_item_id`     | string   | Build Plan item identifier when the action is item-scoped; empty for plan-level actions (e.g. finalize_plan). |
| `target_reference` | object   | Target object reference (§5). Required for all action types except `finalize_plan` where it may be plan-only. |
| `approval_state`   | object   | Approval-gate state (§6). Required for item-scoped actions. |
| `actor_context`    | object   | Who is requesting execution (§7). Required. |
| `created_at`       | string   | ISO 8601 timestamp when the envelope was built. |

### 4.2 Optional envelope fields

| Field                  | Type     | Description |
|------------------------|----------|-------------|
| `dependency_manifest`  | object   | Resolved or declared dependencies (§8). |
| `snapshot_required`    | boolean  | If true, executor must not run until a snapshot is captured (or refuse). |
| `snapshot_ref`         | string   | Reference to an existing pre-execution snapshot when snapshot_required was satisfied. |
| `queue_eligible`       | boolean  | Whether this action may be queued (e.g. for bulk flow). Default false. |
| `idempotency_key`      | string   | Optional key for duplicate-run detection. |
| `payload_snapshot`     | object   | Immutable copy of the minimal payload used to build the action (for audit; no raw AI output). |

### 4.3 Envelope schema (normative)

```json
{
  "action_id": "string, required",
  "action_type": "string, one of action type enum",
  "plan_id": "string, required",
  "plan_item_id": "string, required for item-scoped actions",
  "target_reference": "object, see §5",
  "approval_state": "object, see §6",
  "actor_context": "object, see §7",
  "created_at": "string, ISO 8601",
  "dependency_manifest": "object, optional",
  "snapshot_required": "boolean, optional",
  "snapshot_ref": "string, optional",
  "queue_eligible": "boolean, optional",
  "idempotency_key": "string, optional",
  "payload_snapshot": "object, optional"
}
```

---

## 5. Target Reference

Every action (except plan-level finalize) has a **target_reference** that identifies what is being acted upon.

### 5.1 Required target reference fields (by action class)

| Action type             | Required target keys        | Description |
|-------------------------|-----------------------------|-------------|
| `create_page`           | `plan_item_id`, `template_ref` (or composition_ref) | New page from plan item. |
| `replace_page`          | `page_ref`, `plan_item_id`  | Existing page ID or key; plan item for payload. |
| `update_page_metadata`   | `page_ref`, `plan_item_id`  | Page to update; plan item for new meta. |
| `assign_page_hierarchy` | `page_ref`, `parent_ref` (optional) | Page and new parent. |
| `create_menu`           | `plan_item_id`              | Plan item describing menu to create. |
| `update_menu`           | `menu_ref`, `plan_item_id`  | Menu ID/key and plan item. |
| `apply_token_set`       | `plan_item_ids` or `token_set_ref`, `plan_id` | Token set source; plan scope. |
| `finalize_plan`         | `plan_id`                   | Plan only. |
| `rollback_action`       | `execution_event_id` or `event_ref` | Event to roll back. |

### 5.2 Reference value types

- **page_ref**: `{ "type": "post_id", "value": 123 }` or `{ "type": "internal_key", "value": "page_about" }`.
- **menu_ref**: `{ "type": "menu_id", "value": 42 }` or `{ "type": "term_id", "value": 5 }` (nav menu term).
- **template_ref**: `{ "type": "internal_key", "value": "template_landing" }`.
- **plan_item_id**: string, e.g. `plan_npc_0`, `plan_dt_1`.
- **execution_event_id**: string, from execution history.

All references must be validated (e.g. post exists, plan item exists and is approved) before execution.

---

## 6. Approval State

The **approval_state** object proves that the item or plan has passed the approval gate. Executors must reject envelopes where approval state is missing or invalid for the action type.

### 6.1 Required approval_state fields

| Field              | Type    | Description |
|--------------------|---------|-------------|
| `plan_status`      | string  | Build Plan root status (e.g. approved, in_progress). Must be an executable status per contract. |
| `item_status`      | string  | For item-scoped actions: item status (e.g. approved). Must be terminal approved state. |
| `item_status_source`| string | Optional: "build_plan" to indicate state was read from plan. |
| `verified_at`      | string  | ISO 8601 timestamp when approval state was last verified (freshness). |

### 6.2 Executable states

- **Plan level**: `approved`, `in_progress`. Not `pending_review`, `rejected`, `superseded` for execution.
- **Item level**: `approved` (or contract-defined equivalent). Not `pending`, `rejected`, `skipped` for apply.

Stale approval (e.g. plan updated after `verified_at`) may be rejected by the executor.

---

## 7. Actor Context

**actor_context** identifies who is requesting execution and is used for capability checks and audit.

### 7.1 Required actor_context fields

| Field           | Type   | Description |
|-----------------|--------|-------------|
| `actor_type`   | string | "user" | "system" | "scheduled". |
| `actor_id`     | string | User ID, or system/schedule identifier. |
| `capability_checked` | string | Capability that was checked (e.g. aio_execute_build_plans). |
| `checked_at`   | string | ISO 8601 when capability was checked. |

Execution must require the actor to have the appropriate capability; the envelope carries evidence of that check. Re-check at execution time is allowed and recommended.

---

## 8. Dependency Manifest

**dependency_manifest** lists preconditions that must hold before execution.

### 8.1 Structure

| Field       | Type    | Description |
|-------------|---------|-------------|
| `dependencies` | array | List of dependency entries. |
| `resolved`    | boolean | True if all dependencies were resolved at envelope build time. |
| `resolution_errors` | array | If resolved is false, list of error codes/messages. |

### 8.2 Dependency entry

| Field        | Type   | Description |
|--------------|--------|-------------|
| `kind`       | string | e.g. "parent_page_exists", "template_available", "menu_target_page_exists", "token_system_ready", "no_conflicting_plan_state". |
| `ref`        | object | Optional reference (e.g. parent page ref). |
| `satisfied`  | boolean | Whether this dependency is satisfied. |
| `message`    | string | Optional human-readable message. |

Dependency failure must block or delay execution (spec §40.4). Envelopes with `resolved === false` and non-empty `resolution_errors` must be refused or queued for retry after resolution.

---

## 9. Snapshot Requirements

- **snapshot_required**: When true, the executor must not perform the mutation until a pre-execution snapshot exists. The envelope may carry `snapshot_ref` once the snapshot has been captured.
- **Snapshot preconditions**: For `replace_page` and `apply_token_set`, snapshot requirements must be explicit in the contract per action type. Other types may set `snapshot_required` when the plan or policy requires it.
- Executors must refuse to run when `snapshot_required === true` and `snapshot_ref` is missing or invalid.

---

## 10. Execution Result Shape

After execution (success or failure), the executor produces a **result object**.

### 10.1 Result object (success or failure)

| Field           | Type    | Description |
|-----------------|---------|-------------|
| `action_id`     | string  | Echo of the envelope action_id. |
| `action_type`   | string  | Echo of action_type. |
| `status`        | string  | "completed" | "failed" | "refused" | "partial". |
| `completed_at`  | string  | ISO 8601. |
| `target_ref`    | object  | Echo or resolved target (e.g. created page ref). |
| `message`       | string  | Optional human-readable summary. |
| `error`         | object  | Present when status is failed/refused/partial (§11). |
| `artifacts`     | object  | Optional: created post ID, snapshot ref, etc. |
| `execution_event_id` | string | Optional: for history/rollback linkage. |

### 10.2 Partial success

When status is `partial`, the result must include:
- `error.partial_succeeded`: array of what succeeded.
- `error.partial_failed`: array of what failed.
- Build Plan state must be updated to reflect partial outcome (spec §40.7).

---

## 11. Error Object (Safe-Failure)

When execution is refused or fails, the **error** object is populated.

### 11.1 Error object fields

| Field       | Type   | Description |
|-------------|--------|-------------|
| `code`      | string | Error code category (§11.2). |
| `message`   | string | Human-readable message. |
| `details`   | object | Optional extra context (e.g. field that failed). |
| `refusable` | boolean | True if the envelope was rejected without performing mutation. |
| `partial_succeeded` | array | For partial failure. |
| `partial_failed`     | array | For partial failure. |

### 11.2 Error code categories

| Code                  | Meaning |
|-----------------------|---------|
| `invalid_envelope`    | Malformed envelope (missing required field, invalid action_type). |
| `unauthorized`        | Actor lacks capability or approval state invalid. |
| `stale_approval`      | Plan or item state changed after verification. |
| `dependency_failed`   | One or more dependencies not satisfied. |
| `snapshot_required`   | snapshot_required true but snapshot_ref missing/invalid. |
| `target_not_found`    | Target reference (page, menu, plan item) not found. |
| `execution_failed`    | Mutation attempted but failed (e.g. wp_error). |
| `conflict`            | Conflicting state (e.g. duplicate, concurrent update). |
| `rollback_not_eligible` | Rollback requested but event not rollback-eligible. |

Safe refusal: when the executor refuses (refusable === true), it must not have performed any mutation. Logging and returning the result with error is required.

---

## 12. Safe Refusal Rules

Executors **must** refuse to run when:

1. **action_type** is missing or not in the allowed action type set.
2. Any **required envelope field** is missing or wrong type.
3. **target_reference** is missing or invalid for the action type.
4. **approval_state** is missing, or plan_status/item_status is not in the executable set, or **verified_at** is stale (per policy).
5. **actor_context** is missing or capability_checked not satisfied at execution time.
6. **snapshot_required** is true and **snapshot_ref** is missing or invalid.
7. **dependency_manifest.resolved** is false and policy does not allow deferred execution.
8. **plan_id** or **plan_item_id** does not match an existing plan/item, or the item is not in an approved state.

Refusal must result in a result object with `status: "refused"` and `error.code` set to the appropriate category. No mutation may occur.

---

## 13. Example: Valid Action Envelope

Single approved item: create page from plan item.

```json
{
  "action_id": "exec_create_plan_npc_0_20250311T120000Z",
  "action_type": "create_page",
  "plan_id": "aio-plan-uuid-1",
  "plan_item_id": "plan_npc_0",
  "target_reference": {
    "plan_item_id": "plan_npc_0",
    "template_ref": { "type": "internal_key", "value": "template_landing" }
  },
  "approval_state": {
    "plan_status": "in_progress",
    "item_status": "approved",
    "item_status_source": "build_plan",
    "verified_at": "2025-03-11T11:59:00Z"
  },
  "actor_context": {
    "actor_type": "user",
    "actor_id": "1",
    "capability_checked": "aio_execute_build_plans",
    "checked_at": "2025-03-11T11:59:00Z"
  },
  "created_at": "2025-03-11T12:00:00Z",
  "snapshot_required": false,
  "queue_eligible": true,
  "dependency_manifest": {
    "dependencies": [
      { "kind": "template_available", "ref": { "type": "internal_key", "value": "template_landing" }, "satisfied": true }
    ],
    "resolved": true,
    "resolution_errors": []
  }
}
```

---

## 14. Example: Invalid Action Envelope (Must Be Refused)

Missing required approval_state; item_status would not be approved.

```json
{
  "action_id": "exec_replace_ep_0_20250311T120001Z",
  "action_type": "replace_page",
  "plan_id": "aio-plan-uuid-1",
  "plan_item_id": "plan_ep_0",
  "target_reference": {
    "page_ref": { "type": "post_id", "value": 42 },
    "plan_item_id": "plan_ep_0"
  },
  "approval_state": {
    "plan_status": "pending_review",
    "item_status": "pending",
    "verified_at": "2025-03-11T11:00:00Z"
  },
  "actor_context": {
    "actor_type": "user",
    "actor_id": "1",
    "capability_checked": "aio_execute_build_plans",
    "checked_at": "2025-03-11T12:00:01Z"
  },
  "created_at": "2025-03-11T12:00:01Z"
}
```

**Why invalid**: `plan_status` is `pending_review` and `item_status` is `pending`. Neither is in the executable set. Executor must return status `refused` with `error.code: "unauthorized"` (or `stale_approval` if policy treats non-approved as stale) and perform no mutation.

---

## 15. Cross-References

- **Build Plan state**: Approval state in the envelope is derived from the Build Plan (plan and item status). See Build Plan schema and state machine for status values. Execution does not change plan state arbitrarily; it updates state only as defined by the execution flow (e.g. mark item completed after success).
- **Capabilities**: `aio_execute_build_plans`, `aio_approve_build_plans` (spec §39.7). Nonce and capability checks are required at any HTTP endpoint that submits or triggers execution.
- **Queue**: Envelopes with `queue_eligible: true` may be placed in a queue. Queue consumers must re-validate the envelope before execution.
- **Locking and idempotency**: Lock acquisition, idempotency keys, duplicate suppression, retry eligibility, and stale-lock recovery are defined in **executor-locking-idempotency-contract.md**. Executors must release any acquired lock on completion, refusal, or failure.

---

## 16. Revision History

| Version | Date       | Change |
|---------|------------|--------|
| 1.0     | 2025-03-11 | Initial contract (Prompt 077). |
