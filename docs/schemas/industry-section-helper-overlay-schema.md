# Industry Section Helper Overlay Schema

**Spec**: industry-pack-extension-contract.md; spec §15; documentation-object-schema; section-registry-schema (helper_ref).

**Status**: Schema for industry-specific section-helper overlays that layer vertical-specific guidance on top of base section helper docs without duplicating the helper library.

---

## 1. Purpose

- Overlay objects are **keyed by industry_key + section_key** and provide additive or narrowly overriding guidance (tone, CTA usage, compliance, media, SEO) for that section in that industry.
- Base helper docs remain **authoritative**; overlays extend or override only in allowed regions.
- Lookup is **deterministic** by industry + section key. Exportable and portable.

---

## 2. Overlay object shape

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **industry_key** | string | Yes | Industry pack key (pattern `^[a-z0-9_-]+$`; max 64). |
| **section_key** | string | Yes | Section template internal_key (same as base helper source_reference.section_template_key). |
| **scope** | string | Yes | `section_helper_overlay` (fixed for this type). |
| **status** | string | Yes | `draft` \| `active` \| `archived`. Only `active` is used at resolution. |
| **version_marker** | string | No | Schema/overlay version; max 32 chars. |
| **tone_notes** | string | No | Industry-specific tone guidance; max 1024 chars. |
| **cta_usage_notes** | string | No | CTA usage or conversion notes for this section in this industry; max 1024 chars. |
| **compliance_cautions** | string | No | Compliance or regulatory cautions; max 1024 chars. |
| **media_notes** | string | No | Media or asset guidance; max 512 chars. |
| **seo_notes** | string | No | SEO notes for this section/industry; max 512 chars. |
| **additive_blocks** | array | No | Array of { block_key, content } for additional guidance blocks. |

- **section_key** must reference a section that has (or can have) a base helper; unknown section keys are accepted at storage but resolution may ignore overlays for non-existent sections.
- Invalid overlay objects (missing required fields, invalid industry_key/section_key pattern) must **fail safely** at load (skipped).

---

## 3. Allowed override regions

Overlays may **add** or **override** only the following regions when merging with base helper content (future resolver behavior):

- tone_notes → augments or overrides tone guidance
- cta_usage_notes → augments or overrides CTA usage
- compliance_cautions → additive or override
- media_notes → additive or override
- seo_notes → additive or override
- additive_blocks → appended to base content in an agreed order

Base **content_body** of the section helper is not replaced wholesale unless a future contract explicitly allows it. Overlay regions may reference **shared fragments** (industry-shared-fragment-contract.md) when the consumer scope is `section_helper_overlay`; resolution is via Industry_Shared_Fragment_Resolver.

---

## 4. Registry behavior

- **Industry_Section_Helper_Overlay_Registry**: load(array of overlay objects), get(industry_key, section_key), get_all(), get_for_industry(industry_key).
- Load validates required fields and key patterns; invalid entries skipped. Duplicate (industry_key, section_key): first wins.
- Registry does **not** load or modify base helper docs; base Documentation_Registry is unchanged.

---

## 5. Relation to base helper docs

- Base section helpers are loaded by **Documentation_Loader** / **Documentation_Registry** and keyed by section_template_key.
- Industry overlays are loaded by **Industry_Section_Helper_Overlay_Registry** and keyed by (industry_key, section_key).
- A future **resolver** can: 1) resolve base helper by section_key; 2) resolve overlay by industry_key + section_key; 3) merge overlay regions into base for display. This prompt does not implement that resolver.

---

## 6. Implementation reference

- **Industry_Section_Helper_Overlay_Registry**: Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry.
- **data-schema-appendix.md**: Summary of industry section-helper overlay schema.
- **documentation-object-schema.md**: Base helper docs; overlays extend them per this schema.
