# Operational Snapshot Schema

**Document type:** Implementation-grade schema contract for execution, diff, and rollback snapshots (spec §41.1–41.3, §41.8, §11.5, §59.11).  
**Governs:** Snapshot type families, object scopes, pre-change and post-change structures, identity and reference conventions, payload depth rules, storage references, retention metadata, rollback-capability markers, and relationships to execution logs, jobs, and Build Plans.  
**Related:** version-snapshot-schema.md (registry/version snapshots are distinct); build-plan-schema.md (Build Plan state transitions); execution-action-contract.md (envelope and execution references). **Diff output:** diff-service-contract.md defines how pre-change and post-change snapshots are consumed to produce diff results (content, structure, navigation, token families) and how rollback/snapshot refs attach to diffs.

---

## 1. Purpose and scope

### 1.1 Operational vs. version snapshots

**Operational snapshots** are used for **execution traceability, before/after comparison, and rollback**. They capture **pre-change state** before a rollback-eligible action and **post-change state or result references** after execution. They are **not** the same as:

- **Version / registry snapshots** (version-snapshot-schema.md) — Template registry state, schema versions, build context, composition-linked state. Used for traceability, migration, and composition validation.
- **Crawl snapshots** — Site crawl state for discovery and context.

Operational snapshots focus on **meaningful recoverability** (spec §41.1): pages, page metadata, hierarchy, menus, token sets, and selected Build Plan state transitions. Payloads must remain **queryable and structured**, not opaque blobs.

### 1.2 Spec alignment

- **§41.1 Snapshot scope** — Pages, page metadata, hierarchy, menus, token sets, selected Build Plan state transitions.
- **§41.2 Pre-change snapshot rules** — Before a rollback-eligible change, capture enough for diff, audit, rollback attempt, and failure diagnosis.
- **§41.3 Post-change result recording** — Record post-change state or result reference for before/after comparison, verification, and rollback reasoning.
- **§41.8 Rollback data retention** — Retention considers action importance, plan relationship, storage volume, supersession, and user cleanup.
- **§11.5 Diff / rollback table(s)** — Store before-and-after references, snapshot references, object scope, diff type, rollback eligibility, rollback status, timestamps.

---

## 2. Snapshot type families

Every operational snapshot has a **snapshot_type** that defines its role in the execution lifecycle.

| snapshot_type | Description | When produced | Use |
|---------------|-------------|---------------|-----|
| `pre_change` | State before an execution action | Before a rollback-eligible action runs | Diff baseline; rollback source. |
| `post_change` | State or result after an execution action | After action completes | Diff target; verification; rollback target state. |

**Required:** snapshot_type must be one of the above. Validation shall use an allowlist.

---

## 3. Object scope (object families)

Operational snapshots apply to **object families** that correspond to spec §41.1 scope. Each snapshot targets one or more families via **scope_objects**.

| object_family | Description | Typical identity key | Payload depth guidance |
|---------------|-------------|----------------------|-------------------------|
| `page` | Page post (content, title, slug, status) | post_id or internal plan page ref | Title, slug, post_status, post_type; optional content hash or excerpt; no raw secrets. |
| `page_metadata` | Page meta (SEO, custom fields) | post_id + meta keys or scope id | Key-value or structured meta; redact secrets. |
| `hierarchy` | Parent/child page assignments | post_id or plan item id | parent_id, menu_order, children refs. |
| `menu` | Menu and menu item state | menu term_id or menu slug | Menu name, location, items (id, title, url, parent); no raw credentials in URLs. |
| `token_set` | Design token values | token set id or scope id | Token key, value, role, group; prior/new for diff. |
| `build_plan_transition` | Selected Build Plan state change | plan_id + step/item refs | Plan status before/after; step/item status deltas; completion summary ref. |

**Scope_objects** is an array of scope descriptors. Each descriptor has:

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| `object_family` | string | Yes | One of the table above. |
| `target_ref` | string | Yes | Stable reference to the object (e.g. post_id, menu id, plan_id:item_id). Max 256 chars. |
| `target_type_hint` | string | No | Hint for resolution (e.g. `post`, `term`, `plan_item`). Max 32 chars. |

Only object families relevant to the snapshot need be present. All refs are **references only**; no embedded secrets or prohibited data classes.

---

## 4. Root fields (operational snapshot)

### 4.1 Required root fields

| Field | Type | Validation | Notes |
|-------|------|------------|--------|
| `snapshot_id` | string | Non-empty; unique; immutable; max 64 chars (e.g. UUID) | Stable snapshot identifier. |
| `snapshot_type` | string | One of §2 allowlist | `pre_change` or `post_change`. |
| `object_family` | string | One of §3 object families | Primary object family this snapshot covers (single family per snapshot record for clarity; multi-family can use multiple records). |
| `target_ref` | string | Non-empty; max 256 chars | Primary target reference (e.g. post_id, menu id, plan_id). |
| `created_at` | string | ISO 8601 datetime | When the snapshot was captured. |
| `schema_version` | string | Non-empty; max 32 chars | Operational snapshot schema version (e.g. `1`). |

### 4.2 Pre-change–specific required block

When `snapshot_type` is `pre_change`, the following block is **required**:

| Field | Type | Notes |
|-------|------|--------|
| `pre_change` | object | See §5. |

### 4.3 Post-change–specific required block

When `snapshot_type` is `post_change`, the following block is **required**:

| Field | Type | Notes |
|-------|------|--------|
| `post_change` | object | See §6. |

### 4.4 Optional root fields

| Field | Type | Validation | Notes |
|-------|------|------------|--------|
| `payload_ref` | string | Max 512 chars | Reference to serialized payload or table row (storage pointer). |
| `scope_objects` | array | Shape per §3 | Additional scope descriptors (multi-target). |
| `execution_ref` | string | Max 128 chars | Related execution action id or log ref. |
| `job_ref` | string | Max 128 chars | Related queue job ref. |
| `build_plan_ref` | string | Max 128 chars | Related Build Plan id. |
| `plan_item_ref` | string | Max 128 chars | Related Build Plan item id. |
| `action_type` | string | Max 64 chars | Execution action type (e.g. create_page, update_menu). |
| `retention` | object | Shape per §8 | Retention class and metadata. |
| `rollback_eligible` | boolean | — | Whether this snapshot is eligible for rollback (spec §11.5). |
| `rollback_status` | string | One of: `none`, `available`, `used`, `expired`, `invalidated` | Current rollback status. |
| `provenance` | object | Shape per §9 | Actor, trigger, source. |

---

## 5. Pre-change block structure

Required when snapshot_type is `pre_change`. Preserves enough for diff, audit, rollback attempt, and failure diagnosis (spec §41.2).

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| `captured_at` | string | Yes | ISO 8601; must align with root created_at or be the logical pre-moment. |
| `state_ref` | string | No | Reference to stored state (e.g. table row id) if payload is externalized. |
| `state_snapshot` | object | Conditional | Inline state; required if payload_ref is not set. Shape depends on object_family (§5.1). |

### 5.1 Payload depth by object family (state_snapshot)

- **page:** `post_id`, `post_title`, `post_name`, `post_status`, `post_type`, optional `content_hash`, optional `excerpt` (max 500 chars). No full raw content required for rollback if content_ref is stored.
- **page_metadata:** Key-value or structured object; keys and non-secret values only.
- **hierarchy:** `post_id`, `parent_id`, `menu_order`; optional `children_ids`.
- **menu:** `menu_id`, `name`, `location`, `items` (array of item descriptors: id, title, url, parent, order).
- **token_set:** `token_set_id`, `tokens` (key → value or { value, role, group }).
- **build_plan_transition:** `plan_id`, `plan_status`, optional `step_summaries` (step index → status/count), optional `item_refs` (item ids affected).

Payload depth may vary by action type; capture services must not embed secrets or prohibited data.

---

## 6. Post-change block structure

Required when snapshot_type is `post_change`. Records what actually happened (spec §41.3).

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| `captured_at` | string | Yes | ISO 8601; post-execution time. |
| `result_ref` | string | No | Reference to execution result or stored result (e.g. job result id). |
| `result_snapshot` | object | Conditional | Inline result; required if result_ref is not set or for diff. Shape depends on object_family. |
| `outcome` | string | No | One of: `success`, `partial`, `failed`, `skipped`. |
| `message` | string | No | Max 512 chars; human-readable outcome note. |

### 6.1 Result snapshot shape by object family

- **page:** `post_id` (created or updated), `post_title`, `post_name`, `post_status`; optional `previous_post_id` for replace.
- **page_metadata:** Same as pre-change; key-value or structured.
- **hierarchy:** `post_id`, `parent_id`, `menu_order` after change.
- **menu:** Same structure as pre-change; reflects new state.
- **token_set:** New values applied; same shape as pre-change for comparison.
- **build_plan_transition:** `plan_id`, `plan_status` after, `completion_summary` ref or inline counts.

---

## 7. Identity and reference conventions

- **snapshot_id:** Globally unique; immutable; used as storage and API key.
- **target_ref:** Identifies the primary object (e.g. numeric post ID, `menu:42`, `plan:uuid:item-id`). Resolvable by execution and rollback services.
- **state_ref / result_ref:** Point to externalized payload or result row; optional when state_snapshot/result_snapshot is inline.
- **execution_ref:** Links to execution action id (e.g. envelope action_id) or execution log entry.
- **build_plan_ref / plan_item_ref:** Links to Build Plan and optional item for audit and retention policy.

All references must be **admin-governed**; snapshot access is narrower than general Build Plan visibility where appropriate (spec security).

---

## 8. Retention metadata (spec §41.8)

| Field | Type | Notes |
|-------|------|--------|
| `retention_class` | string | One of: `short` (e.g. 7 days), `medium` (e.g. 30 days), `long` (e.g. 90 days), `plan_linked` (tied to plan lifecycle), `user_managed`. |
| `expires_at` | string | ISO 8601; optional; when retention suggests cleanup. |
| `retention_notes` | string | Max 256 chars; policy or user-selected cleanup note. |
| `superseded_by` | string | snapshot_id of a newer snapshot that makes this one obsolete for rollback. |

Retention rules consider action importance, plan relationship, storage volume, and whether newer changes make old rollback impractical.

---

## 9. Provenance block

| Field | Type | Notes |
|-------|------|--------|
| `actor_ref` | string | User or system ref; max 64 chars. |
| `trigger` | string | e.g. `pre_execution`, `post_execution`, `rollback_capture`; max 64 chars. |
| `source_ref` | string | Optional; execution_ref or job_ref; max 128 chars. |

---

## 10. Rollback-capability markers

- **rollback_eligible** (boolean): True if this snapshot is designed to support rollback (spec §11.5). False for audit-only or non-reversible actions.
- **rollback_status:** `none` (default), `available`, `used`, `expired`, `invalidated`. Updated when rollback is attempted or retention expires.
- Pre-change snapshots with `rollback_eligible === true` and valid retention are candidates for rollback source; post-change snapshots support verification and diff.

---

## 11. Relationship to execution and Build Plans

- **Execution logs:** execution_ref links snapshot to action; logs remain queryable for support and rollback reasoning.
- **Jobs:** job_ref links to queue job when snapshot is taken in job context.
- **Build Plans:** build_plan_ref and plan_item_ref link to plan and item; retention_class `plan_linked` ties lifecycle to plan.

---

## 12. Valid example: pre-change page snapshot

```json
{
  "snapshot_id": "op-snap-pre-abc123",
  "snapshot_type": "pre_change",
  "object_family": "page",
  "target_ref": "42",
  "created_at": "2025-03-12T10:00:00Z",
  "schema_version": "1",
  "pre_change": {
    "captured_at": "2025-03-12T10:00:00Z",
    "state_snapshot": {
      "post_id": 42,
      "post_title": "About Us",
      "post_name": "about-us",
      "post_status": "publish",
      "post_type": "page",
      "content_hash": "sha256:abc..."
    }
  },
  "execution_ref": "exec_replace_plan_xyz_0_20250312T100000Z",
  "build_plan_ref": "plan-xyz",
  "plan_item_ref": "item-0",
  "action_type": "replace_page",
  "rollback_eligible": true,
  "rollback_status": "available",
  "retention": {
    "retention_class": "plan_linked",
    "retention_notes": "Retain until plan archived"
  },
  "provenance": {
    "actor_ref": "user:1",
    "trigger": "pre_execution",
    "source_ref": "exec_replace_plan_xyz_0_20250312T100000Z"
  }
}
```

---

## 13. Valid example: post-change token_set snapshot

```json
{
  "snapshot_id": "op-snap-post-def456",
  "snapshot_type": "post_change",
  "object_family": "token_set",
  "target_ref": "design-tokens-primary",
  "created_at": "2025-03-12T10:05:00Z",
  "schema_version": "1",
  "post_change": {
    "captured_at": "2025-03-12T10:05:00Z",
    "result_snapshot": {
      "token_set_id": "design-tokens-primary",
      "tokens": {
        "color.primary": { "value": "#2563eb", "role": "brand", "group": "colors" },
        "color.secondary": { "value": "#64748b", "role": "neutral", "group": "colors" }
      }
    },
    "outcome": "success",
    "message": "Token set applied."
  },
  "execution_ref": "exec_apply_tokens_plan_xyz_2_20250312T100500Z",
  "build_plan_ref": "plan-xyz",
  "plan_item_ref": "item-2",
  "action_type": "apply_token_set",
  "rollback_eligible": true,
  "rollback_status": "available",
  "retention": {
    "retention_class": "medium",
    "expires_at": "2025-04-11T10:05:00Z"
  },
  "provenance": {
    "actor_ref": "user:1",
    "trigger": "post_execution"
  }
}
```

---

## 14. Invalid example: missing required field

```json
{
  "snapshot_id": "op-snap-bad1",
  "snapshot_type": "pre_change",
  "object_family": "page",
  "target_ref": "42",
  "created_at": "2025-03-12T10:00:00Z"
}
```

→ **Invalid:** Root `schema_version` is required. Block `pre_change` is required when snapshot_type is `pre_change`.

---

## 15. Invalid example: unknown snapshot_type

```json
{
  "snapshot_id": "op-snap-bad2",
  "snapshot_type": "during_change",
  "object_family": "page",
  "target_ref": "42",
  "created_at": "2025-03-12T10:00:00Z",
  "schema_version": "1"
}
```

→ **Invalid:** `snapshot_type` must be `pre_change` or `post_change`.

---

## 16. Invalid example: unknown object_family

```json
{
  "snapshot_id": "op-snap-bad3",
  "snapshot_type": "pre_change",
  "object_family": "widget",
  "target_ref": "99",
  "created_at": "2025-03-12T10:00:00Z",
  "schema_version": "1",
  "pre_change": { "captured_at": "2025-03-12T10:00:00Z", "state_snapshot": {} }
}
```

→ **Invalid:** `object_family` must be one of: page, page_metadata, hierarchy, menu, token_set, build_plan_transition.

---

## 17. Completeness checklist

- [x] Snapshot type families (pre_change, post_change).
- [x] Object scope and object families (page, page_metadata, hierarchy, menu, token_set, build_plan_transition).
- [x] Pre-change block and payload depth guidance per family.
- [x] Post-change block and result snapshot shape per family.
- [x] Identity and reference conventions (snapshot_id, target_ref, execution_ref, job_ref, build_plan_ref, plan_item_ref).
- [x] Storage pointers (payload_ref, state_ref, result_ref).
- [x] Retention metadata and retention_class.
- [x] Rollback-capability markers (rollback_eligible, rollback_status).
- [x] Relationship to execution logs and Build Plans.
- [x] Valid and invalid examples; two full example snapshot shapes.

---

## 18. Relation to version snapshot schema

**Version snapshots** (version-snapshot-schema.md) preserve **registry state, schema versions, build context, and composition-linked state**. They are used for traceability, migration, and composition validation. **Operational snapshots** preserve **execution-relevant before/after state** for pages, menus, tokens, hierarchy, and Build Plan transitions, and are used for diff and rollback. Both may coexist; capture services and storage may be separated by schema type.

**Relation to diff service:** The diff service (diff-service-contract.md) consumes operational snapshots (or equivalent state) and produces diff results by family (content, structure, navigation, token). Diff results reference snapshots via `rollback.pre_snapshot_id` and `rollback.post_snapshot_id`; rollback availability is explicit on the diff result, not inferred from snapshot presence alone.
