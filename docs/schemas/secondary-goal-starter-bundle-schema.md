# Secondary-Goal Starter Bundle Overlay Schema (Prompt 541)

**Spec:** secondary-goal-starter-bundle-contract.md; conversion-goal-starter-bundle-contract.md; secondary-conversion-goal-schema.md.

**Status:** Additive schema for secondary-goal starter-bundle overlay objects. Overlays refine bundle-to-plan conversion when both primary and secondary conversion goals are set; primary-goal overlays remain higher precedence.

---

## 1. Purpose

- Define the **canonical shape** of a secondary-goal starter-bundle overlay object.
- Support **versioned**, **exportable** overlay definitions keyed by (primary_goal_key, secondary_goal_key) and optionally target_bundle_ref.
- Invalid definitions are **skipped** at load; no throw.

---

## 2. Overlay object shape

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **overlay_key** | string | Yes | Stable unique key; pattern `^[a-z0-9_-]+$`; max 64. |
| **primary_goal_key** | string | Yes | Primary conversion goal key (launch set). |
| **secondary_goal_key** | string | Yes | Secondary conversion goal key; must be distinct from primary. |
| **target_bundle_ref** | string | No | When set, overlay applies only to this bundle key; empty = any bundle. |
| **allowed_overlay_regions** | list&lt;string&gt; | Yes | Only these regions may be refined: section_emphasis, cta_posture, funnel_shape, page_family_emphasis. |
| **section_emphasis** | list&lt;string&gt; | No | Section refs to add or emphasize (additive). |
| **cta_posture** | string | No | CTA posture hint; max 128. |
| **funnel_shape** | string | No | Funnel intent hint; max 128. |
| **page_family_emphasis** | list&lt;string&gt; | No | Page families to add (additive). |
| **precedence_marker** | string | Yes | Fixed: `secondary`. |
| **status** | string | Yes | `active`, `draft`, or `deprecated`. Only `active` used at resolution. |
| **version_marker** | string | Yes | Schema version (e.g. `1`). |

---

## 3. Validation rules

- **overlay_key:** Non-empty; pattern `^[a-z0-9_-]+$`; max 64. Unique within registry.
- **primary_goal_key**, **secondary_goal_key:** Non-empty; from allowed goal set; must be distinct. Same key or invalid → skip at load.
- **target_bundle_ref:** When present, pattern `^[a-z0-9_-]+$`; max 64.
- **allowed_overlay_regions:** Non-empty array; each element one of: section_emphasis, cta_posture, funnel_shape, page_family_emphasis. No arbitrary regions.
- **status:** One of `active`, `draft`, `deprecated`.
- **version_marker:** Must match supported schema version (e.g. `1`). Unsupported → skip at load.
- **precedence_marker:** Must be `secondary`.

Invalid overlay objects are **skipped** at load; duplicate (primary_goal_key, secondary_goal_key, target_bundle_ref): first wins.

---

## 4. Registry behavior

- **Secondary_Goal_Starter_Bundle_Overlay_Registry** (or equivalent): load(array), get(primary_goal_key, secondary_goal_key, bundle_key?), get_for_primary_secondary(primary_goal_key, secondary_goal_key), list_all(). Invalid entries skipped at load.
- Resolution: When resolving bundle refinement for (primary_goal, secondary_goal, bundle_key), first apply primary-goal overlay (if any), then apply matching secondary-goal overlay. Invalid refs → safe fallback to primary-only or base bundle.

---

## 5. Safe failure

- Invalid primary or secondary goal key: skip overlay.
- Primary equals secondary: skip overlay.
- Unsupported version: skip overlay.
- No execution logic in overlay data; invalid refs must not throw.
