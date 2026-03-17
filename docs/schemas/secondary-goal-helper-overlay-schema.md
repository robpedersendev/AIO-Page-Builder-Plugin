# Secondary-Goal Section Helper Overlay Schema (Prompt 543)

**Spec:** secondary-goal-helper-overlay-contract.md; conversion-goal-helper-overlay-schema.md.

**Status:** Additive schema for secondary-goal section-helper overlays. Composition order: **base → industry overlay → subtype overlay → primary goal overlay → secondary goal overlay**.

---

## 1. Purpose

- Define the **canonical shape** of a secondary-goal section-helper overlay object.
- Support **versioned**, **exportable** overlay definitions keyed by (primary_goal_key, secondary_goal_key, section_key).
- Invalid definitions are **skipped** at load; no throw.

---

## 2. Overlay object shape

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **primary_goal_key** | string | Yes | Primary conversion goal key; pattern `^[a-z0-9_-]+$`; max 64. |
| **secondary_goal_key** | string | Yes | Secondary conversion goal key; same set; must be distinct from primary. |
| **section_key** | string | Yes | Section template internal_key. |
| **scope** | string | Yes | `secondary_goal_section_helper_overlay` (fixed). |
| **status** | string | Yes | `draft` \| `active` \| `archived`. Only `active` used at resolution. |
| **version_marker** | string | No | Schema/overlay version; max 32. |
| **tone_notes** | string | No | Max 1024. |
| **cta_usage_notes** | string | No | Max 1024. |
| **compliance_cautions** | string | No | Max 1024. |
| **media_notes** | string | No | Max 512. |
| **seo_notes** | string | No | Max 512. |
| **additive_blocks** | array | No | Array of { block_key, content }. |

---

## 3. Validation rules

- **primary_goal_key**, **secondary_goal_key**: Non-empty; pattern `^[a-z0-9_-]+$`; max 64. Must be distinct; unknown or invalid keys skipped at resolution.
- **section_key**: Non-empty; same pattern as base/industry/subtype section keys.
- **status**: One of `draft`, `active`, `archived`.
- Invalid overlay objects are **skipped** at load; duplicate (primary_goal_key, secondary_goal_key, section_key): first wins.

---

## 4. Registry behavior

- **Secondary_Goal_Section_Helper_Overlay_Registry**: load(array), get(primary_goal_key, secondary_goal_key, section_key), get_for_primary_secondary(primary_goal_key, secondary_goal_key), list_all(). Invalid entries skipped at load.

---

## 5. Composition order (deterministic)

1. Base section helper.
2. Industry section-helper overlay.
3. Subtype section-helper overlay.
4. Conversion goal (primary) section-helper overlay.
5. Secondary goal section-helper overlay (when both goals present and overlay exists).
