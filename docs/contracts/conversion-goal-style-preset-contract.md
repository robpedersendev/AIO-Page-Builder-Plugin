# Conversion-Goal Style Preset Overlay Contract (Prompt 511)

**Spec:** Styling subsystem contracts (Prompts 242–260); industry style preset contracts; conversion-goal profile contract.

**Status:** Extension contract for optional conversion-goal-aware style preset overlays. Goal overlays refine presentation emphasis (e.g. CTA, proof, scheduling prominence) within the existing styling contract. Sanitized, bounded; no arbitrary CSS.

---

## 1. Purpose

- Define the **extension layer** for conversion-goal style preset overlays layered on top of base, industry, and subtype style presets.
- Support **allowed token/component refinement areas**: CTA emphasis, proof emphasis, scheduling prominence, call prominence, consultation/valuation posture—without introducing new token names or raw CSS.
- Keep the model **sanitized, bounded, and styling-contract-compliant**. Application remains optional and reversible.

---

## 2. Goal preset overlay object (summary)

Per [conversion-goal-style-preset-schema.md](../schemas/conversion-goal-style-preset-schema.md):

- **goal_preset_key**, **goal_key**, **target_preset_ref** (style_preset_key this overlay refines), **status** (active | draft | archived), **version_marker** (optional).
- **Allowed overrides**: token_values (map of --aio-* => value only), component_override_refs (list of approved component ids). No new token names; no raw CSS.
- Invalid or duplicate goal preset overlays are skipped at load.

---

## 3. Allowed refinement areas

Goal overlays may target (for documentation and scoping; resolution uses target_preset_ref and overrides only):

- **CTA emphasis** — Token/component tweaks that increase CTA visibility or weight when goal is calls, bookings, or lead capture.
- **Proof emphasis** — Testimonial/social-proof prominence when goal benefits from trust.
- **Scheduling prominence** — Calendar/scheduling cues when goal is bookings or consultations.
- **Call prominence** — Phone/click-to-call emphasis when goal is calls.
- **Consultation/valuation posture** — Tone tokens or component refs for consultation or valuation-focused goals.

All overrides must pass the same styling sanitization as industry presets. Invalid token names or values are stripped at load.

---

## 4. Composition order

1. **Base** styling (global tokens and component spec).
2. **Industry preset** (when industry pack has token_preset_ref and preset is applied).
3. **Subtype preset** (when subtype has a preset overlay; if modeled).
4. **Goal preset overlay** (when conversion_goal_key is set and a goal overlay exists for the active preset). Goal overlay **merges** token_values and optional component_override_refs onto the target preset; it does not replace the target.

Deduplication: later layers override earlier values for the same token/key; component refs are additive per implementation.

---

## 5. Safe fallback

- When **no conversion goal** is set or **goal_key is invalid**: only base + industry (and subtype) preset apply; goal overlay is not applied.
- When **target_preset_ref** does not match the currently applied preset: goal overlay is skipped for application (no cross-preset injection).
- **Invalid goal_preset_key, goal_key, or target_preset_ref** at load: entry skipped; no throw. Invalid token names or values in token_values: stripped; no raw CSS ever applied.

---

## 6. Registry and resolution (future)

- **Goal style preset overlay registry**: Read-only after load. Methods: load(array), get(goal_preset_key), get_for_goal(goal_key), get_overlays_for_preset(target_preset_ref), get_all().
- **No public mutation.** Overlays are loaded from built-in definitions (e.g. StylePresets/GoalOverlays/) or optional import path.
- **Fail-safe:** Invalid overlay or invalid token/value causes skip; no throw.
- **Application:** When applying style (e.g. Industry_Style_Preset_Application_Service or equivalent), if conversion_goal_key is set, resolve goal overlays for the target preset and merge token_values (and optional component_override_refs) after industry/subtype, then run full sanitization before write.

---

## 7. Limits

- **No arbitrary CSS.** Goal overlays supply only token values and approved component refs.
- **No new token names or selectors.** Same styling-contract constraints as industry presets.
- **Exportable and versioned.** Overlays are part of industry subsystem data; schema supports version_marker and status.

---

## 8. Cross-references

- [conversion-goal-style-preset-schema.md](../schemas/conversion-goal-style-preset-schema.md) — Full schema.
- [industry-style-preset-schema.md](industry-style-preset-schema.md) — Base preset schema.
- [industry-style-preset-application-contract.md](industry-style-preset-application-contract.md) — Application path.
- [conversion-goal-profile-contract.md](conversion-goal-profile-contract.md) — Profile conversion_goal_key.
