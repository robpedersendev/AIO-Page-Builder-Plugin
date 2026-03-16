# Subtype Section Helper Overlay Schema (Prompt 424)

**Spec**: industry-section-helper-overlay-schema.md; industry-subtype-schema.md; industry-subtype-extension-contract.md.

**Status**: Schema for subtype-specific section-helper overlays that layer sub-variant guidance on top of base helper docs and industry overlays. Composition order: **base → industry overlay → subtype overlay**.

---

## 1. Purpose

- Overlay objects are **keyed by subtype_key + section_key** and provide additive or narrowly overriding guidance for that section when that subtype is active.
- **Base** helper docs and **parent-industry** section-helper overlays remain authoritative; subtype overlays extend or override only in allowed regions.
- Lookup is **deterministic** by subtype_key + section_key. Exportable and portable.

---

## 2. Overlay object shape

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **subtype_key** | string | Yes | Industry subtype key (pattern `^[a-z0-9_-]+$`; max 64). Must reference a subtype in Industry_Subtype_Registry. |
| **section_key** | string | Yes | Section template internal_key (same as base helper and industry overlay). |
| **scope** | string | Yes | `subtype_section_helper_overlay` (fixed for this type). |
| **status** | string | Yes | `draft` \| `active` \| `archived`. Only `active` is used at resolution. |
| **version_marker** | string | No | Schema/overlay version; max 32 chars. |
| **tone_notes** | string | No | Subtype-specific tone guidance; max 1024 chars. |
| **cta_usage_notes** | string | No | CTA usage or conversion notes for this section in this subtype; max 1024 chars. |
| **compliance_cautions** | string | No | Compliance or regulatory cautions; max 1024 chars. |
| **media_notes** | string | No | Media or asset guidance; max 512 chars. |
| **seo_notes** | string | No | SEO notes for this section/subtype; max 512 chars. |
| **additive_blocks** | array | No | Array of { block_key, content } for additional guidance blocks. |

- **section_key** must reference a section that has (or can have) a base helper; unknown section keys are accepted at storage but resolution may ignore overlays for non-existent sections.
- **subtype_key** should reference a valid subtype (parent_industry_key matches profile primary); invalid refs fail safely at resolution (skip subtype overlay).
- Invalid overlay objects (missing required fields, invalid key pattern) must **fail safely** at load (skipped).

---

## 3. Allowed override regions

Subtype overlays may **add** or **override** only the same regions as industry section-helper overlays when merging (after industry overlay):

- tone_notes
- cta_usage_notes
- compliance_cautions
- media_notes
- seo_notes
- additive_blocks

Base **content_body** is not replaced. Composition order: base helper → industry overlay (industry_key + section_key) → subtype overlay (subtype_key + section_key). Each layer may override or augment the previous for allowed regions only.

---

## 4. Registry behavior

- **Subtype_Section_Helper_Overlay_Registry**: load(array of overlay objects), get(subtype_key, section_key), get_all(), get_for_subtype(subtype_key).
- Load validates required fields and key patterns; invalid entries skipped. Duplicate (subtype_key, section_key): first wins.
- Registry does **not** load or modify base helper docs or industry overlays.

---

## 5. Composition order (deterministic)

1. **Base** section helper (Documentation_Registry by section_key).
2. **Industry** section-helper overlay (Industry_Section_Helper_Overlay_Registry by industry_key + section_key), when industry context present.
3. **Subtype** section-helper overlay (Subtype_Section_Helper_Overlay_Registry by subtype_key + section_key), when subtype context present and valid.

When subtype overlay is absent or invalid for (subtype_key, section_key), output is industry-overlay result (or base-only if no industry overlay). No partial or broken merge.

---

## 6. Implementation reference

- **Subtype_Section_Helper_Overlay_Registry**: Domain\Industry\Docs\Subtype_Section_Helper_Overlay_Registry.
- **Industry_Helper_Doc_Composer**: When extended to support subtype, composes in order base → industry → subtype; see industry-section-helper-overlay-schema.md and this schema.
- **data-schema-appendix.md**: Summary of subtype section-helper overlay schema.
