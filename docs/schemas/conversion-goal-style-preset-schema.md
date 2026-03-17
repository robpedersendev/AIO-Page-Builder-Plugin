# Conversion-Goal Style Preset Overlay Schema (Prompt 511)

**Spec:** conversion-goal-style-preset-contract.md; industry-style-preset-schema.md; styling subsystem contracts (Prompts 242–260); conversion-goal profile contract.

**Status:** Additive schema for conversion-goal style preset overlays. Goal overlays refine existing presets (token values, component refs) by conversion goal. Sanitized and bounded; no arbitrary CSS.

---

## 1. Purpose

- Provide **additive schema support** for goal style preset overlays with goal refs, target preset refs, allowed overrides, status, and versioning.
- Support **composition** with industry (and subtype) presets: industry preset base → goal overlay merge.
- **Safe fallback**: when no conversion goal or invalid goal_key, only base + industry/subtype preset apply.

---

## 2. Goal preset overlay object shape

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **goal_preset_key** | string | Yes | Stable, unique key for the goal overlay (pattern `^[a-z0-9_-]+$`; max 64). |
| **goal_key** | string | Yes | Conversion goal key from launch set (pattern `^[a-z0-9_-]+$`; max 64). |
| **target_preset_ref** | string | Yes | style_preset_key this overlay refines (e.g. realtor_warm, plumber_trust). Max 64. |
| **token_values** | map (string => string) | No | Token name => value. Keys must be allowed --aio-* names; values must pass styling sanitization. No raw CSS. |
| **component_override_refs** | list&lt;string&gt; | No | Optional component ids from pb-style-components-spec; must be approved. Unknown refs stripped at load. |
| **status** | string | Yes | `active`, `draft`, or `archived`. Only `active` is used at resolution. |
| **version_marker** | string | No | Schema/overlay version; max 32 chars. |

- **Invalid overlay objects** must fail safely at load (skipped).
- **goal_preset_key** is unique within the goal preset overlay registry (first wins on duplicate).
- **goal_key** must be from the allowed conversion goal set (e.g. calls, bookings, estimates, consultations, valuations, lead_capture); invalid goal_key causes skip.
- **target_preset_ref** must reference an existing style_preset_key when used at application time; resolution may skip overlay when target preset is not the applied one.

---

## 3. Validation and safety

- **token_values**: Keys must match allowed token names from the style registry (--aio-*). Values must pass the same sanitization as industry presets; invalid names or values are stripped at load. **No raw CSS strings.**
- **component_override_refs**: Each id must exist in the component spec; unknown refs are stripped at load.
- **Styling sanitization rules** remain mandatory for all token values. No exception for goal overlays.

---

## 4. Composition order and fallback

- **Resolution order**: Base styling → industry preset (when applied) → subtype preset (if any) → goal overlay (when conversion_goal_key set and overlay exists for applied preset).
- **Fallback**: When goal_key is empty, invalid, or not in allowed set, **only base + industry/subtype preset** apply. When target_preset_ref does not match the currently applied preset, overlay is not applied.
- **Merge semantics**: Goal overlay token_values override (for same key) or add to the target preset’s effective token set. Component refs are additive per implementation.

---

## 5. Registry behavior (future implementation)

- **Goal style preset overlay registry**: load(array), get(goal_preset_key), get_for_goal(goal_key), get_overlays_for_preset(target_preset_ref), get_all(). Read-only after load.
- Load validates required fields, goal_key against allowed set, token_keys against style registry, and value sanitization; invalid entries skipped. Duplicate goal_preset_key: first wins.
- No public mutation; registry is populated from built-in definitions (StylePresets/GoalOverlays/) or optional import only.

---

## 6. Limits of the system

- **No new token names or selectors.** Goal overlays supply values for existing --aio-* tokens and refs to existing component overrides only.
- **No raw CSS.** All values must pass styling sanitization.
- **Exportable.** Overlays are part of industry subsystem data and may be included in export/restore where applicable.
