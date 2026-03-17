# Secondary-Goal Helper Overlay Contract (Prompt 543)

**Spec:** helper-doc contracts; secondary-conversion-goal-contract.md; conversion-goal-helper-overlay-contract.md; goal helper overlay contracts.

**Status:** Defines the bounded extension layer for optional secondary-goal-aware section helper guidance. Additive; does not replace base, industry, subtype, or primary-goal helper layers.

---

## 1. Purpose

- Allow **secondary-goal helper overlays** to refine section helper guidance for mixed-funnel sites (e.g. primary calls + secondary lead capture) on top of base, industry, subtype, and **primary-goal** helper layers.
- Keep the model **deterministic**, **narrow**, and **reusable**. Secondary-goal overlays remain additive and lower precedence than primary-goal overlays.

---

## 2. Secondary-goal helper overlay object shape

| Field | Type | Description |
|-------|------|-------------|
| **secondary_goal_key** | string | Secondary conversion goal key (same set as primary; must be distinct from primary when resolved). |
| **primary_goal_key** | string | Primary conversion goal key (establishes context; overlay applies only when this primary + this secondary are both set). |
| **section_key** | string | Section template internal_key (same as base/industry/subtype/primary-goal). |
| **scope** | string | Fixed: `secondary_goal_section_helper_overlay`. |
| **status** | string | `draft` \| `active` \| `archived`. Only `active` used at resolution. |
| **version_marker** | string (optional) | Schema/overlay version; max 32. |
| **tone_notes** | string (optional) | Mixed-funnel tone; max 1024. |
| **cta_usage_notes** | string (optional) | CTA/conversion notes for secondary nuance; max 1024. |
| **compliance_cautions** | string (optional) | Cautions; max 1024. |
| **media_notes** | string (optional) | Media/asset guidance; max 512. |
| **seo_notes** | string (optional) | SEO notes; max 512. |
| **additive_blocks** | array (optional) | Array of { block_key, content } for additional blocks. |

Invalid primary_goal_key, secondary_goal_key (e.g. same as primary), or section_key must **fail safely** at load (skip overlay). No arbitrary override regions outside the allowed set.

---

## 3. Allowed override regions

Same as primary conversion-goal section-helper overlays. Secondary-goal overlay may **add** or **override** only:

- tone_notes, cta_usage_notes, compliance_cautions, media_notes, seo_notes, additive_blocks

Base **content_body** is not replaced.

---

## 4. Composition order

1. **Base** section helper.
2. **Industry** section-helper overlay (when industry present).
3. **Subtype** section-helper overlay (when subtype present and valid).
4. **Conversion goal (primary)** section-helper overlay (when conversion_goal_key present and overlay exists for goal_key + section_key).
5. **Secondary goal** section-helper overlay (when secondary_conversion_goal_key present, valid, distinct from primary, and overlay exists for primary_goal_key + secondary_goal_key + section_key).

When no secondary-goal overlay exists for (primary_goal_key, secondary_goal_key, section_key): output is primary-goal result (or prior layer). Safe fallback.

---

## 5. Safe fallback

- **No secondary goal set:** No secondary-goal overlay layer applied.
- **Invalid secondary key or same as primary:** Skip secondary-goal overlay.
- **Missing overlay for (primary, secondary, section_key):** Skip; use previous layer result.
- Overlays are **exportable** and **versioned**; invalid refs must not throw.

---

## 6. Security and limits

- No public mutation surfaces. Invalid secondary-goal helper overlays must fail safely.
- No arbitrary freeform override regions outside approved areas.
- Schema: docs/schemas/secondary-goal-helper-overlay-schema.md.

---

## 7. Cross-references

- [secondary-conversion-goal-contract.md](secondary-conversion-goal-contract.md) — Secondary goal state; allowed combinations.
- [conversion-goal-helper-overlay-contract.md](conversion-goal-helper-overlay-contract.md) — Primary goal section-helper overlay; composition order.
- [secondary-goal-helper-overlay-schema.md](../schemas/secondary-goal-helper-overlay-schema.md) — Schema for secondary-goal helper overlay objects (Prompt 543).
