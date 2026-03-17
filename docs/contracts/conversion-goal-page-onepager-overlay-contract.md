# Conversion-Goal Page One-Pager Overlay Contract (Prompt 507)

**Spec**: Page one-pager contracts; conversion-goal profile contract; industry and subtype page overlay contracts.

**Status**: Defines the extension layer for optional conversion-goal-aware page one-pager overlays. Additive; does not replace base, industry, or subtype page guidance.

---

## 1. Purpose

- Allow **conversion-goal page one-pager overlays** to refine page-level guidance (structure, funnel intent, CTA placement) based on funnel objective.
- Keep the model **bounded**, **deterministic**, and **reusable**. Goal overlays remain narrow and additive.

---

## 2. Goal page overlay object shape

| Field | Type | Description |
|-------|------|-------------|
| **goal_key** | string | Conversion goal key (e.g. `calls`, `bookings`, `estimates`, `consultations`, `valuations`, `lead_capture`). |
| **page_key** | string | Page template internal_key or page family key (same as base/industry/subtype page guidance). |
| **scope** | string | Fixed: `conversion_goal_page_onepager_overlay`. |
| **status** | string | `draft` \| `active` \| `archived`. Only `active` used at resolution. |
| **version_marker** | string (optional) | Schema/overlay version; max 32. |
| **structure_notes** | string (optional) | Goal-specific page structure; max 1024. |
| **funnel_notes** | string (optional) | Funnel intent for this page/goal; max 1024. |
| **cta_placement_notes** | string (optional) | CTA placement guidance; max 512. |
| **allowed_override_regions** | list&lt;string&gt; (optional) | Only listed regions may be overridden (e.g. structure_notes, funnel_notes). |

Invalid goal_key or page_key must **fail safely** at load (skip overlay). No arbitrary override regions outside the approved schema.

---

## 3. Allowed override regions

Defined in schema. Typical: structure_notes, funnel_notes, cta_placement_notes. Base one-pager **content_body** (or equivalent) is not replaced; only approved regions are overridden or augmented.

---

## 4. Composition order

1. **Base** page one-pager.
2. **Industry** page overlay (when industry present).
3. **Subtype** page overlay (when subtype present and valid).
4. **Conversion goal** page overlay (when conversion_goal_key present and overlay exists for goal_key + page_key).

When no goal overlay exists: output is subtype (or industry or base). Safe fallback.

---

## 5. Safe fallback and security

- No goal set: no goal overlay applied.
- Invalid goal key: skip goal overlay.
- Missing overlay for (goal_key, page_key): skip.
- No public mutation surfaces; invalid overlays fail safely.
- Schema: docs/schemas/conversion-goal-page-onepager-overlay-schema.md.
