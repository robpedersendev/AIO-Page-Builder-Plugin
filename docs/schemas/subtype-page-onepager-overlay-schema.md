# Subtype Page One-Pager Overlay Schema (Prompt 426)

**Spec**: industry-page-onepager-overlay-schema.md; industry-subtype-schema.md; industry-subtype-extension-contract.md.

**Status**: Schema for subtype-specific page one-pager overlays that layer sub-variant page guidance on top of base one-pagers and industry overlays. Composition order: **base → industry overlay → subtype overlay**.

---

## 1. Purpose

- Overlay objects are **keyed by subtype_key + page_template_key** and provide additive or narrowly overriding page-level guidance when that subtype is active.
- **Base** one-pagers and **parent-industry** page one-pager overlays remain authoritative; subtype overlays extend or override only in allowed regions.
- Lookup is **deterministic** by subtype_key + page_template_key. Exportable and portable.

---

## 2. Overlay object shape

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **subtype_key** | string | Yes | Industry subtype key (pattern `^[a-z0-9_-]+$`; max 64). Must reference a subtype in Industry_Subtype_Registry. |
| **page_template_key** | string | Yes | Page template internal_key (same as base one-pager and industry overlay). |
| **scope** | string | Yes | `subtype_page_onepager_overlay` (fixed for this type). |
| **status** | string | Yes | `draft` \| `active` \| `archived`. Only `active` is used at resolution. |
| **version_marker** | string | No | Schema/overlay version; max 32 chars. |
| **hierarchy_hints** | string | No | Subtype-specific hierarchy or parent/child guidance; max 1024 chars. |
| **cta_strategy** | string | No | CTA strategy or conversion flow for this page/subtype; max 1024 chars. |
| **lpagery_seo_notes** | string | No | LPagery or local SEO notes; max 512 chars. |
| **compliance_cautions** | string | No | Compliance or industry cautions; max 1024 chars. |
| **additive_blocks** | array | No | Array of { block_key, content } for additional guidance. |

- **page_template_key** should reference a page template that has (or can have) a base one-pager; unknown keys are accepted at storage but resolution may ignore overlays for non-existent pages.
- **subtype_key** should reference a valid subtype (parent_industry_key matches profile primary); invalid refs fail safely at resolution (skip subtype overlay).
- Invalid overlay objects (missing required fields, invalid key pattern) must **fail safely** at load (skipped).

---

## 3. Allowed override regions

Subtype overlays may **add** or **override** only the same regions as industry page one-pager overlays when merging (after industry overlay):

- hierarchy_hints, cta_strategy, lpagery_seo_notes, compliance_cautions, additive_blocks.

Base **content_body** is not replaced. Composition order: base one-pager → industry overlay (industry_key + page_template_key) → subtype overlay (subtype_key + page_template_key). Each layer may override or augment the previous for allowed regions only.

---

## 4. Registry behavior

- **Subtype_Page_OnePager_Overlay_Registry**: load(array of overlay objects), get(subtype_key, page_template_key), get_all(), get_for_subtype(subtype_key).
- Load validates required fields and key patterns; invalid entries skipped. Duplicate (subtype_key, page_template_key): first wins.
- Registry does **not** load or modify base one-pagers or industry overlays.

---

## 5. Composition order (deterministic)

1. **Base** page one-pager (Documentation_Registry by page_template_key).
2. **Industry** page one-pager overlay (Industry_Page_OnePager_Overlay_Registry by industry_key + page_template_key), when industry context present.
3. **Subtype** page one-pager overlay (Subtype_Page_OnePager_Overlay_Registry by subtype_key + page_template_key), when subtype context present and valid.

When subtype overlay is absent or invalid for (subtype_key, page_template_key), output is industry-overlay result (or base-only if no industry overlay). No partial or broken merge.

---

## 6. Implementation reference

- **Subtype_Page_OnePager_Overlay_Registry**: Domain\Industry\Docs\Subtype_Page_OnePager_Overlay_Registry.
- **Industry_Page_OnePager_Composer**: When extended to support subtype, composes in order base → industry → subtype; see industry-page-onepager-overlay-schema.md and this schema.
- **data-schema-appendix.md**: Summary of subtype page one-pager overlay schema.
