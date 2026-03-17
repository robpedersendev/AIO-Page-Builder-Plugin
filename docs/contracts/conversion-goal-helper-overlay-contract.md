# Conversion-Goal Helper Overlay Contract (Prompt 505)

**Spec**: Helper-doc contracts; conversion-goal profile contract; industry and subtype overlay contracts.

**Status**: Defines the extension layer for optional conversion-goal-aware section helper overlays. Additive; does not replace base, industry, or subtype helper layers.

---

## 1. Purpose

- Allow **conversion-goal helper overlays** to refine section helper guidance (e.g. how to fill a section for calls vs bookings vs estimates) on top of base, industry, and subtype layers.
- Keep the model **narrow**, **deterministic**, and **reusable**. Goal overlays remain additive and bounded.

---

## 2. Goal helper overlay object shape

| Field | Type | Description |
|-------|------|-------------|
| **goal_key** | string | Conversion goal key (e.g. `calls`, `bookings`, `estimates`, `consultations`, `valuations`, `lead_capture`). |
| **section_key** | string | Section template internal_key (same as base/industry/subtype). |
| **scope** | string | Fixed: `conversion_goal_section_helper_overlay`. |
| **status** | string | `draft` \| `active` \| `archived`. Only `active` used at resolution. |
| **version_marker** | string (optional) | Schema/overlay version; max 32. |
| **tone_notes** | string (optional) | Goal-specific tone; max 1024. |
| **cta_usage_notes** | string (optional) | CTA/conversion notes for this section and goal; max 1024. |
| **compliance_cautions** | string (optional) | Cautions; max 1024. |
| **media_notes** | string (optional) | Media/asset guidance; max 512. |
| **seo_notes** | string (optional) | SEO notes; max 512. |
| **additive_blocks** | array (optional) | Array of { block_key, content } for additional blocks. |

Invalid goal_key or section_key must **fail safely** at load (skip overlay). No arbitrary override regions outside the allowed set.

---

## 3. Allowed override regions

Same as industry/subtype section-helper overlays. Goal overlay may **add** or **override** only:

- tone_notes, cta_usage_notes, compliance_cautions, media_notes, seo_notes, additive_blocks

Base **content_body** is not replaced.

---

## 4. Composition order

1. **Base** section helper.
2. **Industry** section-helper overlay (when industry present).
3. **Subtype** section-helper overlay (when subtype present and valid).
4. **Conversion goal** section-helper overlay (when conversion_goal_key present and overlay exists for goal_key + section_key).

When no goal overlay exists for (goal_key, section_key): output is subtype result (or industry or base). Safe fallback.

---

## 5. Safe fallback

- **No goal set**: No goal overlay layer applied.
- **Invalid goal key**: Skip goal overlay.
- **Missing overlay for (goal_key, section_key)**: Skip; use previous layer result.
- Overlays are **exportable** and **versioned**; invalid refs must not throw.

---

## 6. Security and limits

- No public mutation surfaces. Invalid goal helper overlays must fail safely.
- No arbitrary freeform override regions outside approved areas.
- Schema: docs/schemas/conversion-goal-helper-overlay-schema.md.
