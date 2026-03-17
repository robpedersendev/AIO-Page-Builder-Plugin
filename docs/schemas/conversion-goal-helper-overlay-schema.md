# Conversion-Goal Section Helper Overlay Schema (Prompt 505)

**Spec**: conversion-goal-helper-overlay-contract.md; industry-section-helper-overlay-schema; subtype-section-helper-overlay-schema.

**Status**: Additive schema for conversion-goal section helper overlays. Composition order: **base → industry overlay → subtype overlay → goal overlay**.

---

## 1. Purpose

- Define the **canonical shape** of a conversion-goal section helper overlay object.
- Support **versioned**, **exportable** overlay definitions keyed by goal_key + section_key.
- Invalid definitions are **skipped** at load; no throw.

---

## 2. Overlay object shape

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **goal_key** | string | Yes | Conversion goal key; pattern `^[a-z0-9_-]+$`; max 64. Must be from launch goal set or registry. |
| **section_key** | string | Yes | Section template internal_key. |
| **scope** | string | Yes | `conversion_goal_section_helper_overlay` (fixed). |
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

- **goal_key**: Non-empty; pattern `^[a-z0-9_-]+$`; max 64. Unknown keys skipped at resolution.
- **section_key**: Non-empty; same pattern as base/industry/subtype section keys.
- **status**: One of `draft`, `active`, `archived`.
- Invalid overlay objects are **skipped** at load; duplicate (goal_key, section_key): first wins.

---

## 4. Registry behavior

- **Goal_Section_Helper_Overlay_Registry**: load(array), get(goal_key, section_key), get_for_goal(goal_key), list_all(). Invalid entries skipped at load.

---

## 5. Composition order (deterministic)

1. Base section helper.
2. Industry section-helper overlay.
3. Subtype section-helper overlay.
4. Conversion goal section-helper overlay (when goal_key present and overlay exists).
