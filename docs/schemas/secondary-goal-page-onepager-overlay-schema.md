# Secondary-Goal Page One-Pager Overlay Schema (Prompt 545)

**Spec:** secondary-goal-page-onepager-overlay-contract.md; conversion-goal-page-onepager-overlay-schema.md.

**Status:** Additive schema for secondary-goal page one-pager overlays. Composition: **base → industry → subtype → goal (primary) → secondary goal**.

---

## 1. Purpose

- Define the **canonical shape** of a secondary-goal page one-pager overlay object.
- Support **versioned**, **exportable** overlay definitions keyed by (primary_goal_key, secondary_goal_key, page_key).
- Invalid definitions are **skipped** at load; no throw.

---

## 2. Overlay object shape

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **primary_goal_key** | string | Yes | Primary conversion goal key; pattern `^[a-z0-9_-]+$`; max 64. |
| **secondary_goal_key** | string | Yes | Secondary conversion goal key; same set; must be distinct from primary. |
| **page_key** | string | Yes | Page template internal_key or page family key. |
| **scope** | string | Yes | `secondary_goal_page_onepager_overlay` (fixed). |
| **status** | string | Yes | `draft` \| `active` \| `archived`. Only `active` used. |
| **version_marker** | string | No | Max 32. |
| **structure_notes** | string | No | Max 1024. |
| **funnel_notes** | string | No | Max 1024. |
| **cta_placement_notes** | string | No | Max 512. |
| **allowed_override_regions** | list&lt;string&gt; | No | Only these regions may be overridden (e.g. structure_notes, funnel_notes). |

---

## 3. Validation and registry

- **primary_goal_key**, **secondary_goal_key**: Non-empty; pattern `^[a-z0-9_-]+$`; max 64. Must be distinct; invalid or same → skip at load.
- **page_key**: Non-empty; same pattern; max 64.
- **status**: One of `draft`, `active`, `archived`.
- Invalid overlay objects are **skipped** at load; duplicate (primary_goal_key, secondary_goal_key, page_key): first wins.
- **Registry:** load(array), get(primary_goal_key, secondary_goal_key, page_key), get_for_primary_secondary(primary_goal_key, secondary_goal_key). Composition order when composer is extended: base → industry → subtype → goal (primary) → secondary goal.
