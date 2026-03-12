# Diff Service Contract

**Document type:** Authoritative contract for the diff service (spec §41.4–41.7, §38.3, §59.11).  
**Governs:** Diff families (content, structure, navigation, token), summary versus detailed diff outputs, object-family-specific fields, usability priorities, and the attachment of rollback availability and snapshot references to diff results.  
**Related:** operational-snapshot-schema.md (pre-change and post-change snapshots are diff inputs); execution-action-contract.md (action types and execution refs).

---

## 1. Purpose and scope

The **diff service** produces **before/after change descriptions** from operational snapshots or comparable state. Diffs support **review, audit, and recovery reasoning**. They are **understandable to users**, not raw internal dumps. Each **diff family** has distinct semantics; rollback availability is **related to but not implied by** the presence of a diff.

**Spec alignment:**

- **§41.4 Content diff rules** — Page-level change understanding: title, slug, section-structure, content replacement indicators, status. Usability over low-level textual complexity.
- **§41.5 Structure diff rules** — Page hierarchy, template assignment, section composition, plan structure relevance. Structure changes prioritized over raw text.
- **§41.6 Navigation diff rules** — Menu additions/removals, label/order/nesting/location changes. Understandable without raw menu-object inspection.
- **§41.7 Token diff rules** — Prior/new token value, role, group, AI-proposed vs user-overridden. Branding changes reviewable.
- **§38.3 Before/after snapshot rules** — Detail view shows before summary, after summary, diff type, rollback availability, snapshot IDs.

**Out of scope for this contract:** Diff implementation, snapshot capture, rollback validation or executor, UI rendering. This document defines the **contract** only.

---

## 2. Diff families and purpose

Every diff result has a **diff_type** that determines which family-specific fields are present and how the diff is interpreted.

| diff_type | Purpose | Primary object family (operational snapshot) |
|-----------|---------|---------------------------------------------|
| `content` | Page-level content and identity changes (title, slug, status, section-structure, content replacement) | page |
| `structure` | Hierarchy, template, section composition, plan-structure relevance | page, hierarchy, build_plan_transition |
| `navigation` | Menu and menu-item changes (add/remove, label, order, nesting, location) | menu |
| `token` | Design token value and metadata changes (prior/new, role, group, provenance) | token_set |

**Required:** diff_type must be one of the above. Validation shall use an allowlist. Implementations must not collapse all diffs into a single generic text comparison; the four families remain **distinct and purposeful**.

---

## 3. Output level: summary versus detail

The diff service may return results at two levels. Field names are **stable and machine-readable**.

| Level | Use | Content |
|-------|-----|--------|
| `summary` | List views, rollback eligibility display, quick scan (spec §38.3) | High-level change description; before/after one-liners; diff_type; rollback and snapshot refs; no deep payload. |
| `detail` | Detail view, audit, before/after comparison | Full family-specific fields; before/after snapshots or excerpts; all change descriptors. |

A single API or method may accept a **level** parameter (`summary` | `detail`) and return the corresponding shape. Summary is the default for list and eligibility checks; detail is for "view detail" (spec §38.3).

---

## 4. Common diff result root (all levels)

Every diff result shares these root fields. All are stable for consumers.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `diff_id` | string | Yes | Stable identifier for this diff result (e.g. UUID or composite key); max 64 chars. |
| `diff_type` | string | Yes | One of §2: `content`, `structure`, `navigation`, `token`. |
| `level` | string | Yes | `summary` or `detail`. |
| `target_ref` | string | Yes | Primary target (e.g. post_id, menu id, token_set id); max 256 chars. |
| `target_type_hint` | string | No | e.g. `post`, `term`, `token_set`; max 32 chars. |
| `before_summary` | string | Yes (summary) / No (detail) | Short human-readable "before" state; max 512 chars. For summary level only when level=summary. |
| `after_summary` | string | Yes (summary) / No (detail) | Short human-readable "after" state; max 512 chars. For summary level only when level=summary. |
| `change_count` | int | No | Number of distinct changes (e.g. fields or items changed). |
| `execution_ref` | string | No | Related execution action id; max 128 chars. |
| `build_plan_ref` | string | No | Related Build Plan id; max 128 chars. |
| `plan_item_ref` | string | No | Related Build Plan item id; max 128 chars. |
| `rollback` | object | No | Rollback and snapshot linkage; see §7. |
| `family_payload` | object | No (summary) / Yes (detail) | Family-specific diff data; see §5. |

**Rule:** Rollback availability is **not** implied by the presence of a diff. It is explicitly carried in `rollback` (§7).

---

## 5. Family-specific fields (family_payload)

When `level` is `detail`, `family_payload` is required and its shape depends on `diff_type`. When `level` is `summary`, `family_payload` may be omitted or minimal.

### 5.1 Content diff (diff_type: content)

Supports page-level change understanding (spec §41.4). Prioritizes usability over low-level textual completeness.

| Field | Type | Description |
|-------|------|-------------|
| `title_before` | string | Page title before change. |
| `title_after` | string | Page title after change. |
| `slug_before` | string | Post name/slug before. |
| `slug_after` | string | Post name/slug after. |
| `status_before` | string | post_status before (e.g. draft, publish). |
| `status_after` | string | post_status after. |
| `section_structure_changed` | bool | True if major section structure (order, add/remove sections) changed. |
| `section_summary_before` | array | Optional list of section identifiers or labels before; for display. |
| `section_summary_after` | array | Optional list after. |
| `content_replacement_indicator` | string | One of: `none`, `full_replace`, `partial`, `unknown`. Indicates whether content was fully replaced, partially updated, or not applicable. |
| `content_excerpt_before` | string | Optional short excerpt (e.g. first 200 chars); redacted; no secrets. |
| `content_excerpt_after` | string | Optional short excerpt after. |

Only fields that actually changed or are relevant need be present. Omit unchanged fields to keep payload small.

### 5.2 Structure diff (diff_type: structure)

Covers page hierarchy, template assignment, section composition, plan structure relevance (spec §41.5).

| Field | Type | Description |
|-------|------|-------------|
| `hierarchy_before` | object | Optional: `parent_id`, `menu_order`, `children_count` or `children_ids`. |
| `hierarchy_after` | object | Same shape after change. |
| `template_before` | string | Template or page template ref before. |
| `template_after` | string | Template ref after. |
| `section_composition_before` | array | Optional: section keys or composition snapshot before. |
| `section_composition_after` | array | Optional: after. |
| `plan_structure_relevance` | string | Optional: e.g. `step_affected`, `item_affected`, `none`. |

Structure changes are often more important than raw text; structure diff is the place for hierarchy and template changes.

### 5.3 Navigation diff (diff_type: navigation)

Menu additions/removals, label/order/nesting/location changes (spec §41.6). Understandable without raw menu-object inspection.

| Field | Type | Description |
|-------|------|-------------|
| `menu_id` | int/string | Menu identifier. |
| `menu_name_before` | string | Menu name before. |
| `menu_name_after` | string | Menu name after. |
| `location_before` | string | Theme location before (e.g. primary, footer). |
| `location_after` | string | Theme location after. |
| `items_added` | array | List of item descriptors added: e.g. `{ "title", "url", "parent" }`. |
| `items_removed` | array | List of item descriptors removed. |
| `items_reordered` | bool | True if order changed. |
| `labels_changed` | array | Optional: list of `{ "item_ref", "label_before", "label_after" }`. |
| `nesting_changed` | array | Optional: list of nesting change descriptors. |

No raw menu object dumps; only fields needed for review and display.

### 5.4 Token diff (diff_type: token)

Prior/new token value, role, group, AI-proposed vs user-overridden (spec §41.7). Branding changes reviewable.

| Field | Type | Description |
|-------|------|-------------|
| `token_set_ref` | string | Token set identifier. |
| `changes` | array | List of token change descriptors. Each element: |
| `changes[].token_key` | string | Token key (e.g. color.primary). |
| `changes[].value_before` | string | Value before. |
| `changes[].value_after` | string | Value after. |
| `changes[].role` | string | Optional: token role (e.g. brand, neutral). |
| `changes[].group` | string | Optional: group (e.g. colors, typography). |
| `changes[].provenance` | string | Optional: `ai_proposed`, `user_overridden`, `system`, `unknown`. |

Only changed tokens need appear. No secrets or prohibited values in token values.

---

## 6. Usability and display priorities

- **Content:** Prefer title, slug, status, and section-structure change indicators over full content diff. Content replacement indicator and short excerpts suffice for review; avoid low-level character diffs unless explicitly requested.
- **Structure:** Emphasize hierarchy and template changes; section composition when relevant. Plan-structure relevance helps tie the diff to the Build Plan.
- **Navigation:** Emphasize add/remove, label, order, and location. No raw menu object inspection required to understand the diff.
- **Token:** Emphasize prior vs new value, role, group, and provenance so branding changes are reviewable.

Fallback when a family-specific comparison is not possible: return a **summary**-level result with `before_summary` and `after_summary` set from snapshot metadata or "Unknown" and `family_payload` empty or minimal; set `diff_type` to the intended family and optionally include a `fallback_reason` (e.g. `snapshot_missing`, `incompatible_format`).

---

## 7. Rollback availability and snapshot references

Rollback availability is **not** implied by the presence of a diff. It is carried explicitly so UI and rollback logic do not guess.

**Root field:** `rollback` (object, optional). When present, shape:

| Field | Type | Description |
|-------|------|-------------|
| `rollback_eligible` | bool | True if this change is considered rollback-eligible (pre snapshot exists, handler exists, target resolvable, etc.; see spec §38.4). |
| `pre_snapshot_id` | string | Operational snapshot_id of the pre-change snapshot; max 64 chars. |
| `post_snapshot_id` | string | Operational snapshot_id of the post-change snapshot; max 64 chars. |
| `rollback_status` | string | Optional: `available`, `used`, `expired`, `invalidated`, `none`. Aligns with operational-snapshot-schema rollback_status. |

Detail view (spec §38.3) shall show: before summary, after summary, diff type, **rollback availability** (from `rollback.rollback_eligible` and `rollback_status`), and **snapshot IDs** (`pre_snapshot_id`, `post_snapshot_id`). Diff service populates these from snapshot and eligibility rules; it does not perform rollback validation.

---

## 8. Unsupported and noisy cases

- **Unsupported:** When before or after state is missing or not in a comparable format, the diff service should return a **summary** result with `before_summary` / `after_summary` set to a safe placeholder (e.g. "Not available") and optionally `fallback_reason`. Do not invent synthetic diffs.
- **Noisy:** Very large content or token sets may be summarized (e.g. change_count and high-level indicators only) in summary level; detail level may cap or paginate family_payload to avoid huge payloads.
- **Secrets:** Diff outputs must not include secrets or prohibited values. Token values and content excerpts must be redacted where policy requires. Diff schema is aligned to snapshot access controls; capability checks apply when serving diffs.

---

## 9. Example: summary diff (content)

```json
{
  "diff_id": "diff-content-abc123",
  "diff_type": "content",
  "level": "summary",
  "target_ref": "42",
  "target_type_hint": "post",
  "before_summary": "About Us (about-us), published",
  "after_summary": "About Our Company (about-our-company), published",
  "change_count": 2,
  "execution_ref": "exec_replace_plan_xyz_0_20250312T100000Z",
  "build_plan_ref": "plan-xyz",
  "plan_item_ref": "item-0",
  "rollback": {
    "rollback_eligible": true,
    "pre_snapshot_id": "op-snap-pre-abc123",
    "post_snapshot_id": "op-snap-post-abc124",
    "rollback_status": "available"
  }
}
```

---

## 10. Example: detailed diff (token)

```json
{
  "diff_id": "diff-token-def456",
  "diff_type": "token",
  "level": "detail",
  "target_ref": "design-tokens-primary",
  "target_type_hint": "token_set",
  "change_count": 2,
  "execution_ref": "exec_apply_tokens_plan_xyz_2_20250312T100500Z",
  "build_plan_ref": "plan-xyz",
  "plan_item_ref": "item-2",
  "rollback": {
    "rollback_eligible": true,
    "pre_snapshot_id": "op-snap-pre-tok1",
    "post_snapshot_id": "op-snap-post-tok2",
    "rollback_status": "available"
  },
  "family_payload": {
    "token_set_ref": "design-tokens-primary",
    "changes": [
      {
        "token_key": "color.primary",
        "value_before": "#1e40af",
        "value_after": "#2563eb",
        "role": "brand",
        "group": "colors",
        "provenance": "ai_proposed"
      },
      {
        "token_key": "color.secondary",
        "value_before": "#475569",
        "value_after": "#64748b",
        "role": "neutral",
        "group": "colors",
        "provenance": "user_overridden"
      }
    ]
  }
}
```

---

## 11. Completeness checklist

- [x] Diff families (content, structure, navigation, token) and purpose.
- [x] Summary versus detailed output level and when each is used.
- [x] Common diff result root (diff_id, diff_type, level, target_ref, before/after_summary, rollback, family_payload).
- [x] Family-specific fields for content, structure, navigation, token.
- [x] Usability and display priorities per family.
- [x] Rollback availability and snapshot refs (rollback object; not implied by diff presence).
- [x] Unsupported/noisy cases and fallback behavior.
- [x] One example summary diff and one example detailed diff.

---

## 12. Relation to operational snapshot schema

Operational snapshots (operational-snapshot-schema.md) provide **pre_change** and **post_change** state. The diff service consumes those snapshots (or equivalent state) and produces diff results per this contract. Snapshot `object_family` aligns with diff_type where applicable: page → content/structure; menu → navigation; token_set → token. Diff results reference snapshots via `rollback.pre_snapshot_id` and `rollback.post_snapshot_id`.
