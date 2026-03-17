# Industry Page One-Pager Overlay Schema

**Spec**: industry-pack-extension-contract.md; spec §16; documentation-object-schema; page-template-registry-schema (one_pager).

**Status**: Schema for industry-specific page one-pager overlays that add vertical-specific page guidance on top of base page-template one-pagers without duplicating the one-pager library.

---

## 1. Purpose

- Overlay objects are **keyed by industry_key + page_template_key** and provide additive or narrowly overriding page-level guidance (hierarchy, CTA strategy, LPagery/SEO, compliance).
- Base one-pagers remain **authoritative**; overlays extend or override only in allowed regions.
- Lookup is **deterministic** by industry + page template key. Exportable and portable.

---

## 2. Overlay object shape

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **industry_key** | string | Yes | Industry pack key (pattern `^[a-z0-9_-]+$`; max 64). |
| **page_template_key** | string | Yes | Page template internal_key (same as base one-pager source_reference.page_template_key). |
| **scope** | string | Yes | `page_onepager_overlay` (fixed for this type). |
| **status** | string | Yes | `draft` \| `active` \| `archived`. Only `active` is used at resolution. |
| **version_marker** | string | No | Schema/overlay version; max 32 chars. |
| **hierarchy_hints** | string | No | Industry-specific hierarchy or parent/child guidance; max 1024 chars. |
| **cta_strategy** | string | No | CTA strategy or conversion flow for this page/industry; max 1024 chars. |
| **lpagery_seo_notes** | string | No | LPagery or local SEO notes; max 512 chars. |
| **compliance_cautions** | string | No | Compliance or industry cautions; max 1024 chars. |
| **additive_blocks** | array | No | Array of { block_key, content } for additional guidance. |

- **page_template_key** should reference a page template that has (or can have) a base one-pager; unknown keys are accepted at storage but resolution may ignore overlays for non-existent pages.
- Invalid overlay objects must **fail safely** at load (skipped).

---

## 3. Allowed override regions

Overlays may **add** or **override** only the following when merging with base one-pager content (future resolver):

- hierarchy_hints, cta_strategy, lpagery_seo_notes, compliance_cautions, additive_blocks.
- Base **content_body** of the one-pager is not replaced wholesale unless a future contract allows it. Overlay regions may reference **shared fragments** (industry-shared-fragment-contract.md) when the consumer scope is `page_onepager_overlay`; resolution is via Industry_Shared_Fragment_Resolver.

---

## 4. Registry behavior

- **Industry_Page_OnePager_Overlay_Registry**: load(array), get(industry_key, page_template_key), get_all(), get_for_industry(industry_key).
- Load validates required fields and key patterns; invalid entries skipped. Duplicate (industry_key, page_template_key): first wins.
- Base one-pager loading and Documentation_Registry are unchanged.

---

## 5. Relation to base one-pagers

- Base page one-pagers are loaded by **Documentation_Loader** / **Documentation_Registry** and keyed by page_template_key.
- Industry overlays are loaded by **Industry_Page_OnePager_Overlay_Registry** and keyed by (industry_key, page_template_key).
- A future resolver can merge overlay regions into base one-pager for display. This prompt does not implement that resolver.

---

## 6. Implementation reference

- **Industry_Page_OnePager_Overlay_Registry**: Domain\Industry\Docs\Industry_Page_OnePager_Overlay_Registry.
- **data-schema-appendix.md**: Summary of industry page one-pager overlay schema.
