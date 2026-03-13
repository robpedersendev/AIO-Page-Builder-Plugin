# Section Registry Schema

**Document type:** Implementation-grade schema contract for section templates (spec §12, §10.1).  
**Governs:** Required/optional fields, sub-manifests, validation rules, and completeness for registry implementation.  
**Related:** object-model-schema.md (§3.1 Section Template), master spec §12.1–12.15. Page templates reference section templates by `internal_key`; see **page-template-registry-schema.md** for the page template schema. The `field_blueprint_ref` points to a blueprint defined per **acf-field-blueprint-schema.md**. The `css_contract_ref` points to a section CSS manifest that **must** conform to the global **css-selector-contract.md** (docs/contracts/). **Large-library scale:** Minimum targets, variation philosophy, category coverage, and scale-governance rules for section templates are defined in **template-library-scale-extension-contract.md** (docs/contracts/); that contract enhances this schema and does not replace it.

---

## 1. Purpose and scope

A section template is a **complete system definition**, not just a visual layout. It includes structure, fields, usage guidance, compatibility assumptions, and fixed internal contracts. This schema is the single source of truth for:

- Required and optional fields and their types
- Sub-manifest shapes (assets, CSS contract, accessibility, compatibility, versioning, deprecation)
- Allowed status values and render mode classifications
- Variant representation and default/baseline
- **Incompleteness rules:** a section template missing any required field is **incomplete** and **not eligible for normal use** in page templates or custom compositions.

No secrets may appear in section definitions. Asset declarations are references only, not executable code. AI planning notes inform planning only and must not become execution authority.

---

## 2. Required fields (spec §12.2)

Every section template **shall** include the following. Absence of any required field makes the template **incomplete**.

| Field name | Type | Required | Default | Validation rule | Export | Notes |
|------------|------|----------|---------|------------------|--------|--------|
| `internal_key` | string | Yes | — | Unique, non-empty; pattern `^[a-z0-9_]+$` (e.g. `st01`, `hero_section_v1`); max 64 chars; immutable once released | Yes | Stable registry key (§12.4). Not AI-generated at runtime. |
| `name` | string | Yes | — | Non-empty; max 255 chars; human-readable | Yes | Display name (§12.5). |
| `purpose_summary` | string | Yes | — | Non-empty; max 1024 chars | Yes | What the section is for. |
| `category` | string | Yes | — | One of allowed category slugs (§2.1 Categories) | Yes | Section category. |
| `structural_blueprint_ref` | string | Yes | — | Non-empty reference to structural blueprint; max 255 chars | Yes | Defines wrapper, containers, regions, slots, variant logic (§12.8). |
| `field_blueprint_ref` | string | Yes | — | Non-empty reference to field-group blueprint; max 255 chars | Yes | Field-to-slot mapping, ACF alignment (§12.8). Must equal `blueprint_id` in **acf-field-blueprint-schema.md**. |
| `helper_ref` | string | Yes | — | Non-empty reference to helper paragraph/block set; max 255 chars | Yes | Usage guidance (§12.9). |
| `css_contract_ref` | string | Yes | — | Non-empty reference to CSS contract manifest; max 255 chars | Yes | Selector/styling contract (§12.11). May be same as manifest id. |
| `default_variant` | string | Yes | — | Non-empty; must be one of `variants` keys or the sole variant key | Yes | Baseline variant for render (§12.7). |
| `compatibility` | object | Yes | — | Shape per §4 Compatibility metadata | Yes | Compatibility rules (§12.13). |
| `version` | object | Yes | — | Shape per §5 Version metadata | Yes | Version marker (§12.14). |
| `status` | string | Yes | — | One of: `draft`, `active`, `inactive`, `deprecated` | Yes | Lifecycle status (§10.10, object-model-schema §2). |
| `render_mode` | string | Yes | — | One of allowed render modes (§2.2) | Yes | Render mode classification. |
| `asset_declaration` | object | Yes | — | Shape per §3 Asset dependency declaration; may be `{ "none": true }` | Yes | Asset needs (§12.10). |

---

### 2.1 Allowed category values (spec §12.6)

Categories are controlled slugs. Suggested set (extensible by registry policy):

| Slug | Description |
|------|--------------|
| `hero_intro` | Hero / intro |
| `trust_proof` | Trust / proof |
| `feature_benefit` | Feature / benefit |
| `process_steps` | Process / steps |
| `pricing_packages` | Pricing / packages |
| `faq` | FAQ |
| `media_gallery` | Media / gallery |
| `comparison` | Comparison |
| `cta_conversion` | CTA / conversion |
| `form_embed` | Form embed (form provider shortcode/block) |
| `directory_listing` | Directory / listing |
| `profile_bio` | Profile / bio |
| `stats_highlights` | Stats / highlights |
| `timeline` | Timeline |
| `navigation_jump` | Navigation / jump links |
| `related_recommended` | Related / recommended content |
| `legal_disclaimer` | Legal / disclaimer support |
| `utility_structural` | Utility / structural support |

### 2.2 Allowed render mode values

| Value | Description |
|-------|--------------|
| `block` | Standard block-level section |
| `full_width` | Full-width layout |
| `contained` | Contained (max-width) layout |
| `inline` | Inline or inline-block context |
| `nested` | Intended for nesting inside another section |

(Registry may extend with additional values; schema validation shall use an allowlist.)

---

## 3. Asset dependency declaration (spec §12.10)

**Required.** Each section must declare asset needs explicitly. Shape:

| Field | Type | Required | Validation | Notes |
|-------|------|----------|------------|--------|
| `none` | boolean | Conditional | If `true`, all other flags false or absent | No special assets beyond global. |
| `frontend_css` | boolean | No | — | Section needs front-end CSS. |
| `admin_css` | boolean | No | — | Section needs admin-only CSS. |
| `frontend_js` | boolean | No | — | Section needs front-end JavaScript. |
| `admin_js` | boolean | No | — | Section needs admin-only JavaScript. |
| `icons` | boolean | No | — | Depends on icon assets. |
| `media_patterns` | boolean | No | — | Depends on media/shared resources. |
| `shared_resources` | array of strings | No | Each element max 128 chars; reference ids only | Other shared resource refs. |

- If `none` is `true`, no other asset flags are required; other flags should be false.
- If `none` is false or omitted, at least one of the boolean flags or `shared_resources` may be set.
- Assets are **references only**; no executable code in the declaration.

---

## 4. Compatibility metadata (spec §12.13)

**Required.** Shape:

| Field | Type | Required | Validation | Notes |
|-------|------|----------|------------|--------|
| `may_precede` | array of strings | No | Each element: section internal_key or pattern; max 64 chars | Sections that may precede this one. |
| `may_follow` | array of strings | No | Same | Sections that may follow. |
| `avoid_adjacent` | array of strings | No | Same | Sections that should not appear adjacent. |
| `duplicate_purpose_of` | array of strings | No | Same | Sections with same purpose; avoid stacking without reason. |
| `variant_conflicts` | array of strings | No | Same | Variant keys that conflict. |
| `requires_page_context` | string | No | Max 128 chars | e.g. "landing", "inner" (optional). |
| `requires_token_surface` | boolean | No | — | Dependency on token/surface assumptions. |
| `requires_content_availability` | string | No | Max 256 chars | e.g. "media-heavy layouts need assets". |

---

## 5. Version metadata (spec §12.14)

**Required.** Shape:

| Field | Type | Required | Validation | Notes |
|-------|------|----------|------------|--------|
| `version` | string | Yes | Non-empty; e.g. semver or `1`, `1.0`; max 32 chars | Version marker. |
| `changelog_ref` | string | No | Max 255 chars | Reference to changelog or notes. |
| `breaking_change` | boolean | No | — | If true, structural breaking change. |
| `migration_notes_ref` | string | No | Max 255 chars | Reference to migration guidance. |
| `stable_key_retained` | boolean | No | Default true | Internal key unchanged across revision. |

---

## 6. CSS contract manifest reference block (spec §12.11)

The section references a **CSS contract manifest** (by ref id). The manifest content **must align with the global selector contract**: see **docs/contracts/css-selector-contract.md** for stable naming rules, approved data attributes, token hook points, prohibited patterns, and the compliance checklist. The manifest defines (per that contract):

| Concept | Description |
|---------|-------------|
| Base section class | Primary wrapper class; pattern `aio-s-{section_key}` (global contract). |
| Section ID strategy | How section IDs are generated, if applicable; pattern per global contract. |
| Inner wrapper classes | Inner container; pattern `aio-s-{section_key}__inner` when used. |
| Major structural child classes | Child region classes; approved element roles only (global contract §3.4). |
| Modifier classes | Explicit modifiers; variant pattern `aio-s-{section_key}--variant-{variant_key}`. |
| Variant class rules | Class rules per variant. |
| State classes | e.g. expanded, collapsed; pattern `aio-s-{section_key}--is-{state}`. |
| Approved data attributes | Only `data-aio-*` from global contract approved list. |
| Token hook points | Design token variable names (`--aio-*`); values are variable. |
| Prohibited selector patterns | As defined in global contract; no override. |

The schema only requires `css_contract_ref` (string); the manifest content is a separate structural contract and must comply with **css-selector-contract.md**.

---

## 7. Accessibility contract reference block (spec §12.12)

The section must define **baseline accessibility responsibilities**. These may live in the helper or a dedicated block; the schema records a reference or inline block. Shape when stored as structured data:

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| `heading_expectations` | string | No | Max 512 chars. |
| `landmark_expectations` | string | No | Structural landmark; max 512 chars. |
| `image_alt_expectations` | string | No | Max 512 chars. |
| `button_link_clarity` | string | No | Max 512 chars. |
| `keyboard_interaction` | string | No | Max 512 chars. |
| `list_semantics` | string | No | Max 512 chars. |
| `details_accordion_requirements` | string | No | Max 512 chars. |
| `contrast_token_considerations` | string | No | Max 512 chars. |
| `avoid_patterns` | string | No | Inaccessible patterns to avoid; max 512 chars. |

If stored as a single reference (e.g. to helper or doc), use `accessibility_contract_ref` (optional string) instead.

---

## 8. Version metadata block (summary)

See §5. Required at top level: `version` object with at least `version` string.

---

## 9. Deprecation metadata block (spec §12.15)

**Optional** at definition level; required when `status === 'deprecated'` for full traceability. Shape:

| Field | Type | Required | Validation | Notes |
|-------|------|----------|------------|--------|
| `deprecated` | boolean | No | — | True when status is deprecated. |
| `reason` | string | No | Max 512 chars | Reason for deprecation. |
| `replacement_section_key` | string | No | Max 64 chars | Recommended replacement section internal_key. |
| `retain_existing_references` | boolean | No | Default true | Old refs kept for existing pages. |
| `exclude_from_new_selection` | boolean | No | Default true | Omit from normal new-template selection. |
| `preserve_rendered_pages` | boolean | No | Default true | Do not break existing rendered pages. |

---

## 10. Optional fields (spec §12.3)

| Field name | Type | Default | Validation | Export | Notes |
|------------|------|---------|------------|--------|--------|
| `short_label` | string | — | Max 64 chars | Yes | Display alias. |
| `preview_description` | string | — | Max 512 chars | Yes | Preview text. |
| `preview_image_ref` | string | — | Max 255 chars | Yes | Preview image or markup reference. |
| `suggested_use_cases` | array of strings | — | Each max 256 chars | Yes | Suggested use cases. |
| `prohibited_use_cases` | array of strings | — | Each max 256 chars | Yes | Prohibited use cases. |
| `notes_for_ai_planning` | string | — | Max 1024 chars | Yes | Informs planning only; not execution authority. |
| `hierarchy_role_hints` | string | — | Max 256 chars | Yes | Role in page hierarchy. |
| `seo_relevance_notes` | string | — | Max 512 chars | Yes | SEO relevance. |
| `token_affinity_notes` | string | — | Max 512 chars | Yes | Token-affinity notes. |
| `lpagery_mapping_notes` | string | — | Max 512 chars | Yes | LPagery mapping. |
| `accessibility_warnings_or_enhancements` | string | — | Max 512 chars | Yes | Extra a11y guidance. |
| `migration_notes` | string | — | Max 512 chars | Yes | Migration notes. |
| `deprecation_notes` | string | — | Max 512 chars | Yes | Deprecation notes (narrative). |
| `replacement_section_suggestions` | array of strings | — | Each: internal_key; max 64 chars | Yes | Alternative replacement keys. |
| `dependencies_sections_or_context` | array of strings | — | Each max 256 chars | Yes | Dependencies on other sections or layout context. |

---

## 11. Variants and default baseline (spec §12.7)

| Field | Type | Required | Validation | Notes |
|-------|------|----------|------------|--------|
| `variants` | object | Yes | Non-empty; keys = variant keys (e.g. `default`, `media_left`, `compact`) | Map of variant key → variant descriptor. |
| `default_variant` | string | Yes | Must equal one key in `variants` | Baseline configuration. |

Variant descriptor (per key) may include:

| Field | Type | Notes |
|-------|------|--------|
| `label` | string | Human-readable variant name. |
| `description` | string | Optional short description. |
| `css_modifiers` | array of strings | Optional class/modifier list. |

Variants must be explicit; same structural family, core purpose, and related field model. CSS contract remains stable via modifiers or token differences.

---

## 12. Incompleteness rules

A section template is **incomplete** and **not eligible for normal use** in page templates or custom compositions if:

1. Any required field (§2) is missing or empty where "non-empty" is required.
2. `internal_key` is not unique within the section registry.
3. `status` is not one of `draft`, `active`, `inactive`, `deprecated`.
4. `category` is not in the allowed category list.
5. `render_mode` is not in the allowed render mode list.
6. `default_variant` is not a key in `variants`.
7. `variants` is empty or missing.
8. `asset_declaration` is missing or invalid (e.g. neither `none: true` nor at least one asset flag/reference).
9. `compatibility` or `version` object is missing or does not satisfy the required shape (e.g. `version.version` missing).

Registry code **shall** treat incomplete templates as ineligible for selection in new page templates or compositions and may exclude them from normal listing.

---

## 13. Completeness checklist (spec §12.2)

Use this checklist to verify every required field from §12.2 is represented:

- [ ] `internal_key` — stable internal section key
- [ ] `name` — human-readable section name
- [ ] `purpose_summary` — purpose summary
- [ ] `category` — section category
- [ ] `structural_blueprint_ref` — blueprint definition reference (structural)
- [ ] `field_blueprint_ref` — field-group blueprint reference
- [ ] `helper_ref` — helper paragraph reference
- [ ] `css_contract_ref` — CSS contract manifest reference
- [ ] `default_variant` — default variant or baseline configuration
- [ ] `compatibility` — compatibility metadata
- [ ] `version` — version marker
- [ ] `status` — active/deprecated (and draft/inactive) status
- [ ] `render_mode` — render mode classification
- [ ] `asset_declaration` — asset dependency declaration (including "none")
- [ ] `variants` — variant set (required so that default_variant can resolve)

---

## 14. Example: valid section definition (minimal)

```json
{
  "internal_key": "st01_hero",
  "name": "Hero",
  "purpose_summary": "Primary hero section with headline, subhead, and optional CTA.",
  "category": "hero_intro",
  "structural_blueprint_ref": "blueprint_st01_structure",
  "field_blueprint_ref": "acf_blueprint_st01",
  "helper_ref": "helper_st01",
  "css_contract_ref": "css_manifest_st01",
  "default_variant": "default",
  "variants": {
    "default": { "label": "Default", "description": "Centered, single column" }
  },
  "compatibility": {
    "may_precede": [],
    "may_follow": ["st02_faq", "st03_cta"],
    "avoid_adjacent": [],
    "duplicate_purpose_of": []
  },
  "version": {
    "version": "1",
    "stable_key_retained": true
  },
  "status": "active",
  "render_mode": "block",
  "asset_declaration": {
    "none": true
  }
}
```

---

## 15. Example: valid section definition (with optional and deprecation)

```json
{
  "internal_key": "st10_legacy_hero",
  "name": "Legacy Hero (Deprecated)",
  "purpose_summary": "Older hero layout; use st01_hero for new pages.",
  "category": "hero_intro",
  "structural_blueprint_ref": "blueprint_st10_structure",
  "field_blueprint_ref": "acf_blueprint_st10",
  "helper_ref": "helper_st10",
  "css_contract_ref": "css_manifest_st10",
  "default_variant": "default",
  "variants": {
    "default": { "label": "Default" }
  },
  "compatibility": {
    "may_precede": [],
    "may_follow": [],
    "avoid_adjacent": [],
    "duplicate_purpose_of": ["st01_hero"]
  },
  "version": {
    "version": "1",
    "breaking_change": false,
    "stable_key_retained": true
  },
  "status": "deprecated",
  "render_mode": "block",
  "asset_declaration": { "none": true },
  "short_label": "Legacy Hero",
  "deprecation_notes": "Replaced by st01_hero; existing pages unchanged.",
  "replacement_section_suggestions": ["st01_hero"],
  "deprecation": {
    "deprecated": true,
    "reason": "Superseded by st01_hero",
    "replacement_section_key": "st01_hero",
    "retain_existing_references": true,
    "exclude_from_new_selection": true,
    "preserve_rendered_pages": true
  }
}
```

---

## 16. Example: invalid definitions

**Invalid — missing required field (`helper_ref` empty):**

```json
{
  "internal_key": "st99_bad",
  "name": "Bad Section",
  "purpose_summary": "Test.",
  "category": "utility_structural",
  "structural_blueprint_ref": "bp",
  "field_blueprint_ref": "fp",
  "helper_ref": "",
  "css_contract_ref": "css",
  "default_variant": "default",
  "variants": { "default": {} },
  "compatibility": {},
  "version": { "version": "1" },
  "status": "draft",
  "render_mode": "block",
  "asset_declaration": { "none": true }
}
```
→ **Incomplete:** `helper_ref` is required and non-empty.

**Invalid — default_variant not in variants:**

```json
{
  "internal_key": "st98_bad",
  "name": "Bad Variant",
  "purpose_summary": "Test.",
  "category": "utility_structural",
  "structural_blueprint_ref": "bp",
  "field_blueprint_ref": "fp",
  "helper_ref": "hp",
  "css_contract_ref": "css",
  "default_variant": "missing_variant",
  "variants": { "default": {} },
  "compatibility": {},
  "version": { "version": "1" },
  "status": "draft",
  "render_mode": "block",
  "asset_declaration": { "none": true }
}
```
→ **Incomplete:** `default_variant` must be a key in `variants`.

**Invalid — invalid status:**

```json
{
  "internal_key": "st97_bad",
  "name": "Bad Status",
  "status": "published",
  ...
}
```
→ **Invalid:** `status` must be one of `draft`, `active`, `inactive`, `deprecated`.

---

## 17. Export and security

- **Exportability:** All fields in this schema are exportable in manifests and registry exports unless marked internal by implementation. No secrets in section definitions.
- **Capability:** Schema supports later capability-gated editing by admins only; no permission logic is defined in this document.
- **References:** Asset declarations and blueprint/helper/CSS refs are references only; no executable code or user-controlled paths without validation.
- **Helper paragraphs:** Section helper content is stored as documentation objects (documentation_type = section_helper); see **documentation-object-schema.md**.
