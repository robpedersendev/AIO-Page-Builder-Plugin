# Conversion-Goal Page One-Pager Overlay Schema (Prompt 507)

**Spec**: conversion-goal-page-onepager-overlay-contract.md; page one-pager composition contracts.

**Status**: Additive schema for conversion-goal page one-pager overlays. Composition: **base → industry → subtype → goal**.

---

## 1. Purpose

- Define the **canonical shape** of a conversion-goal page one-pager overlay object.
- Support **versioned**, **exportable** overlay definitions keyed by goal_key + page_key.
- Invalid definitions **skipped** at load.

---

## 2. Overlay object shape

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **goal_key** | string | Yes | Conversion goal key; pattern `^[a-z0-9_-]+$`; max 64. |
| **page_key** | string | Yes | Page template internal_key or page family key. |
| **scope** | string | Yes | `conversion_goal_page_onepager_overlay` (fixed). |
| **status** | string | Yes | `draft` \| `active` \| `archived`. Only `active` used. |
| **version_marker** | string | No | Max 32. |
| **structure_notes** | string | No | Max 1024. |
| **funnel_notes** | string | No | Max 1024. |
| **cta_placement_notes** | string | No | Max 512. |
| **allowed_override_regions** | list&lt;string&gt; | No | Only these regions may be overridden (e.g. structure_notes, funnel_notes). |

---

## 3. Validation and registry

- **goal_key**, **page_key**: Non-empty; pattern `^[a-z0-9_-]+$`; max 64. Invalid entries skipped at load.
- **status**: One of `draft`, `active`, `archived`.
- Registry: load(array), get(goal_key, page_key), get_for_goal(goal_key). Duplicate (goal_key, page_key): first wins.
