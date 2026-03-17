# Secondary-Goal Page One-Pager Overlay Contract (Prompt 545)

**Spec:** page one-pager contracts; secondary-conversion-goal-contract.md; conversion-goal-page-onepager-overlay-contract.md; goal page overlay contracts.

**Status:** Defines the extension layer for optional secondary-goal-aware page one-pager overlays. Additive; does not replace base, industry, subtype, or primary-goal page guidance.

---

## 1. Purpose

- Allow **secondary-goal page one-pager overlays** to refine page-level guidance for mixed-funnel sites (e.g. primary calls + secondary lead capture) on top of base, industry, subtype, and **primary-goal** page overlays.
- Keep the model **bounded**, **deterministic**, and **reusable**. Secondary-goal overlays remain narrow and lower precedence than primary-goal page overlays.

---

## 2. Secondary-goal page overlay object shape

| Field | Type | Description |
|-------|------|-------------|
| **primary_goal_key** | string | Primary conversion goal key (establishes context). |
| **secondary_goal_key** | string | Secondary conversion goal key; must be distinct from primary. |
| **page_key** | string | Page template internal_key or page family key (same as base/industry/subtype/primary-goal). |
| **scope** | string | Fixed: `secondary_goal_page_onepager_overlay`. |
| **status** | string | `draft` \| `active` \| `archived`. Only `active` used at resolution. |
| **version_marker** | string (optional) | Schema/overlay version; max 32. |
| **structure_notes** | string (optional) | Mixed-funnel page structure; max 1024. |
| **funnel_notes** | string (optional) | Funnel intent for primary+secondary; max 1024. |
| **cta_placement_notes** | string (optional) | CTA placement guidance; max 512. |
| **allowed_override_regions** | list&lt;string&gt; (optional) | Only listed regions may be overridden (e.g. structure_notes, funnel_notes). |

Invalid primary_goal_key, secondary_goal_key (e.g. same as primary), or page_key must **fail safely** at load (skip overlay). No arbitrary override regions outside the approved schema.

---

## 3. Allowed override regions

Defined in schema. Same as primary goal page overlay: structure_notes, funnel_notes, cta_placement_notes. Base one-pager **content_body** (or equivalent) is not replaced; only approved regions are overridden or augmented.

---

## 4. Composition order

1. **Base** page one-pager.
2. **Industry** page overlay (when industry present).
3. **Subtype** page overlay (when subtype present and valid).
4. **Conversion goal (primary)** page overlay (when conversion_goal_key present and overlay exists for goal_key + page_key).
5. **Secondary goal** page overlay (when secondary_conversion_goal_key present, valid, distinct from primary, and overlay exists for primary_goal_key + secondary_goal_key + page_key).

When no secondary-goal overlay exists: output is primary-goal result (or prior layer). Safe fallback.

---

## 5. Safe fallback and security

- No secondary goal set: no secondary-goal page overlay applied.
- Invalid secondary key or same as primary: skip secondary-goal overlay.
- Missing overlay for (primary_goal_key, secondary_goal_key, page_key): skip; use previous layer result.
- No public mutation surfaces; invalid overlays fail safely.
- Schema: docs/schemas/secondary-goal-page-onepager-overlay-schema.md.

---

## 6. Cross-references

- [secondary-conversion-goal-contract.md](secondary-conversion-goal-contract.md) — Secondary goal state; allowed combinations.
- [conversion-goal-page-onepager-overlay-contract.md](conversion-goal-page-onepager-overlay-contract.md) — Primary goal page one-pager overlay; composition order.
- [secondary-goal-page-onepager-overlay-schema.md](../schemas/secondary-goal-page-onepager-overlay-schema.md) — Schema for secondary-goal page overlay objects (Prompt 545).
