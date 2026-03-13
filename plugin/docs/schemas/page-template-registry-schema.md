# Page Template Registry Schema

**Document type:** Implementation-grade schema contract for page templates (spec §13, §10.2, §16.1).  
**Governs:** Required/optional fields, ordered section composition, one-pager metadata, compatibility, versioning, and deprecation for registry implementation.  
**Related:** object-model-schema.md (§3.2 Page Template), section-registry-schema.md (section templates referenced by page templates), master spec §13.1–13.13, §16.1–16.5. **Large-library scale:** Minimum targets, variation philosophy, template-family and category coverage, and scale-governance rules for page templates are defined in **template-library-scale-extension-contract.md** (docs/contracts/); that contract enhances this schema and does not replace it. **Category taxonomy:** Four major category classes, subfamilies, hierarchy roles, and purpose/CTA metadata for directory browsing and preview grouping are defined in **page-template-category-taxonomy-contract.md** (docs/contracts/); taxonomy fields below are additive optional metadata. **CTA composition:** Required CTA section counts by template_category_class, mandatory bottom-of-page CTA, absolute non-adjacency of CTA sections, and target non-CTA section range (8–14) are defined in **cta-sequencing-and-placement-contract.md** (docs/contracts/); composition validators and template-generation must enforce that contract; violations produce defined error/warning codes (cta_count_below_minimum, bottom_cta_missing, adjacent_cta_violation, non_cta_count_below_minimum, non_cta_count_above_max).

---

## 1. Purpose and scope

A page template is a **structured plan for how a page should be built**, not just a label. It is a reusable, page-level structural definition composed of **ordered section templates** and exists to express repeatable page types that serve known website purposes. This schema is the single source of truth for:

- Required and optional fields and their types
- Ordered section reference structure and required-vs-optional section designations
- One-pager generation metadata (assembly rules, section order, page-purpose summary)
- Compatibility and purpose tags, SEO default reference block
- Version and deprecation metadata
- **Ineligibility rules:** a page template missing any required field is **incomplete** and **not eligible** for normal use in builds or one-pager generation.

Section references must point only to **registered section template internal keys**. No executable or user-supplied arbitrary code references. AI-planning notes are advisory only. Future editing is capability-gated (documented here; not implemented in this prompt).

---

## 2. Required fields (spec §13.2)

Every page template **shall** include the following. Absence of any required field makes the template **incomplete**.

| Field name | Type | Required | Default | Validation rule | Export | Notes |
|------------|------|----------|---------|------------------|--------|--------|
| `internal_key` | string | Yes | — | Unique, non-empty; pattern `^[a-z0-9_]+$`; max 64 chars; immutable once released | Yes | Stable page-template key (§10.2). |
| `name` | string | Yes | — | Non-empty; max 255 chars; human-readable | Yes | Page-template name. |
| `purpose_summary` | string | Yes | — | Non-empty; max 1024 chars | Yes | Page purpose summary. |
| `archetype` | string | Yes | — | One of allowed archetype slugs (§2.1) | Yes | Category or template archetype (§13.6). |
| `ordered_sections` | array | Yes | — | Non-empty list of section reference items (§3); each `section_key` must resolve to a registered section | Yes | Ordered section list (§13.4). |
| `section_requirements` | object | Yes | — | Map of section_key → `required` (boolean) for each entry in ordered_sections (§4) | Yes | Required vs optional section designations (§13.5). |
| `compatibility` | object | Yes | — | Shape per §6 | Yes | Compatibility metadata (§13.11). |
| `one_pager` | object | Yes | — | Shape per §7 | Yes | One-pager generation metadata (§13.10, §16.1–16.5). |
| `version` | object | Yes | — | Shape per §8 | Yes | Version marker (§13.12). |
| `status` | string | Yes | — | One of: `draft`, `active`, `inactive`, `deprecated` | Yes | Lifecycle status (§10.10). |
| `default_structural_assumptions` | string | Yes | — | Max 1024 chars; may be empty string | Yes | Default structural assumptions (§13.2). |
| `endpoint_or_usage_notes` | string | Yes | — | Max 512 chars; may be empty string | Yes | Endpoint or usage notes where applicable (§13.2, §13.7). |

---

### 2.1 Allowed archetype values (spec §13.6)

Template purpose/archetype is a controlled slug. Suggested set:

| Slug | Description |
|------|--------------|
| `service_page` | Service page |
| `offer_page` | Offer page |
| `pricing_page` | Pricing page |
| `faq_page` | FAQ page |
| `hub_page` | Hub page |
| `sub_hub_page` | Sub-hub page |
| `landing_page` | Landing page |
| `location_page` | Location page |
| `event_page` | Event page |
| `request_page` | Request page |
| `profile_page` | Profile page |
| `directory_page` | Directory page |
| `comparison_page` | Comparison page |
| `informational_detail` | Informational detail page |

---

## 3. Ordered section reference item structure

Each element of `ordered_sections` **shall** be an object with this shape:

| Field | Type | Required | Validation | Notes |
|-------|------|----------|------------|--------|
| `section_key` | string | Yes | Non-empty; max 64 chars; must be a registered section template internal_key | Reference to section template (§13.4). |
| `position` | integer | Yes | Zero-based; unique within template; determines order | Canonical section order. |
| `required` | boolean | Yes | — | True = required section; false = optional (§13.5). |

- **Section key integrity:** `section_key` must resolve to a section template in the section registry. If the referenced section is deprecated, the page template may still reference it for backward compatibility and existing pages; new-build selection may warn or exclude such templates (policy is implementation-defined).
- **Order:** `ordered_sections` shall be ordered by `position` ascending. No duplicate `section_key` unless the section registry allows repeated use of a section type for this template (per §13.4).
- **Consistency:** Every `section_key` in `ordered_sections` must have a corresponding entry in `section_requirements` (key = `section_key`, value = object with at least `required` boolean).

---

## 4. Section requirements metadata (required vs optional)

`section_requirements` is an object mapping each section key (from `ordered_sections`) to a requirement descriptor:

| Field (per section_key) | Type | Required | Notes |
|-------------------------|------|----------|--------|
| `required` | boolean | Yes | True = section defines essential identity or flow; false = section can be omitted without invalidating template. |

Optionality must be explicit, not guessed at runtime. This distinction is used for template validation, custom composition guidance, one-pager generation, and build-plan logic.

---

## 5. Optional fields (spec §13.3)

| Field name | Type | Default | Validation | Export | Notes |
|------------|------|---------|------------|--------|--------|
| `display_description` | string | — | Max 1024 chars | Yes | Display description. |
| `recommended_industries` | array of strings | — | Each max 128 chars | Yes | Recommended industries or business types. |
| `recommended_audience_types` | array of strings | — | Each max 128 chars | Yes | Recommended audience types. |
| `suggested_page_title_patterns` | array of strings | — | Each max 256 chars | Yes | Suggested page title patterns. |
| `suggested_slug_patterns` | array of strings | — | Each max 128 chars | Yes | Suggested slug patterns. |
| `hierarchy_hints` | object | — | Shape per §5.1 | Yes | Hierarchy hints (§13.8). |
| `internal_linking_hints` | string | — | Max 512 chars | Yes | Internal linking hints. |
| `default_token_affinity_notes` | string | — | Max 512 chars | Yes | Default token-affinity notes. |
| `notes_for_ai_planning` | string | — | Max 1024 chars | Yes | Advisory only; not execution authority. |
| `seo_notes` | string | — | Max 1024 chars | Yes | SEO notes. |
| `documentation_notes` | string | — | Max 1024 chars | Yes | Documentation notes. |
| `preview_metadata` | object | — | Max 512 chars total or ref | Yes | Page-template preview metadata. |
| `migration_notes` | string | — | Max 512 chars | Yes | Migration notes. |
| `replacement_template_refs` | array of strings | — | Each: internal_key; max 64 chars | Yes | Replacement template references when deprecated. |
| `seo_defaults` | object | — | Shape per §9 | Yes | SEO default reference block (§13.9). |
| `deprecation` | object | — | Shape per §10 | Yes | Deprecation block when status is deprecated (§13.13). |
| `template_category_class` | string | — | One of: `top_level`, `hub`, `nested_hub`, `child_detail` (per page-template-category-taxonomy-contract) | Yes | Major category class for directory and hierarchy; required when taxonomy is applied. |
| `template_family` | string | — | One of allowed family slugs (e.g. `home`, `services`, `locations`) per taxonomy contract §3 | Yes | Subfamily for directory browsing and preview grouping. |
| `hierarchy_role` | string | — | One of: `root`, `standalone`, `hub`, `nested_hub`, `leaf`, `intermediate` per taxonomy contract §4 | Yes | Hierarchy role; must be consistent with template_category_class. |
| `page_purpose_family` | string | — | One of purpose slugs (e.g. `informational`, `conversion`, `support`) per taxonomy contract §5.1 | Yes | Primary page intent. |
| `cta_intent_family` | string | — | One of CTA slugs (e.g. `primary_conversion`, `contact_request`) per taxonomy contract §5.2 | Yes | Primary CTA orientation for taxonomy/filtering. |

### 5.1 Hierarchy hints object (optional)

| Field | Type | Notes |
|-------|------|--------|
| `likely_top_level` | boolean | Likely top-level usage. |
| `likely_child_page` | boolean | Likely child-page usage. |
| `common_parent_archetypes` | array of strings | Archetype slugs. |
| `common_sibling_archetypes` | array of strings | Archetype slugs. |
| `hierarchy_role` | string | e.g. `hub`, `leaf`, `intermediate`; max 64 chars. |

---

## 6. Compatibility metadata (spec §13.11)

**Required.** Shape:

| Field | Type | Required | Validation | Notes |
|-------|------|----------|------------|--------|
| `site_contexts_appropriate` | array of strings | No | Each max 128 chars | Site contexts where template is appropriate. |
| `site_contexts_inappropriate` | array of strings | No | Each max 128 chars | Site contexts where template is inappropriate. |
| `required_content_assumptions` | array of strings | No | Each max 256 chars | Required supporting content assumptions. |
| `section_variant_incompatibilities` | array of strings | No | Each max 128 chars | Incompatibility with certain section variants. |
| `hierarchy_assumptions` | string | No | Max 512 chars | Hierarchy assumptions. |
| `token_or_layout_dependencies` | string | No | Max 512 chars | Token or layout dependencies. |
| `conflicts_with_purposes` | array of strings | No | Archetype slugs | Conflicts with other page purposes. |

---

## 7. One-pager generation metadata (spec §13.10, §16.1–16.5)

**Required.** Defines how the one-pager is assembled from section helper inputs and page-level notes.

| Field | Type | Required | Validation | Notes |
|-------|------|----------|------------|--------|
| `page_purpose_summary` | string | Yes | Non-empty; max 1024 chars | Page-purpose summary; used as opening in one-pager. |
| `section_helper_order` | string | No | One of: `same_as_template`, `explicit`; default `same_as_template` | Section helper order follows template order (§16.4). |
| `cross_section_strategy_notes` | string | No | Max 1024 chars | Cross-section strategy notes. |
| `optional_section_handling` | string | No | Max 512 chars | How optional sections are described in one-pager. |
| `global_editing_notes` | string | No | Max 1024 chars | Global editing notes. |
| `page_flow_explanation` | string | No | Max 1024 chars | Page-flow explanation. |
| `token_or_visual_notes` | string | No | Max 512 chars | Token or visual-system notes if relevant. |

The one-pager must reflect the actual section order of the page template. Required vs optional section distinction must appear in one-pager assembly.

---

## 8. Version metadata (spec §13.12)

**Required.** Shape:

| Field | Type | Required | Validation | Notes |
|-------|------|----------|------------|--------|
| `version` | string | Yes | Non-empty; max 32 chars | Version marker per page-template definition. |
| `changelog_ref` | string | No | Max 255 chars | Reference to changelog. |
| `section_version_compatibility` | string | No | Max 512 chars | Compatibility with underlying section versions where relevant. |
| `migration_notes_ref` | string | No | Max 255 chars | Migration notes when section order or meaning changes. |
| `stable_key_retained` | boolean | No | Default true | Stable template key preserved. |

---

## 9. SEO default reference block (optional; spec §13.9)

When present, shape:

| Field | Type | Notes |
|-------|------|--------|
| `title_pattern_suggestions` | array of strings | Each max 256 chars. |
| `meta_description_direction` | string | Max 512 chars. |
| `heading_expectations` | string | Max 512 chars. |
| `internal_link_expectations` | string | Max 512 chars. |
| `schema_type_suggestions` | array of strings | Each max 64 chars. |
| `page_intent_classification` | string | Max 128 chars. |
| `keyword_targeting_notes` | string | Max 512 chars. |

SEO defaults are guidance scaffolding and remain adaptable to actual page purpose and content.

---

## 10. Deprecation metadata block (spec §13.13)

**Optional** at root; required when `status === 'deprecated'` for full traceability. Shape:

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| `deprecated` | boolean | No | True when status is deprecated. |
| `reason` | string | No | Max 512 chars. Reason for deprecation. |
| `replacement_template_key` | string | No | Max 64 chars. Recommended replacement page-template internal_key. |
| `interpretability_of_old_plans` | boolean | No | Default true. Continued interpretability of old plans and pages. |
| `exclude_from_new_build_selection` | boolean | No | Default true. Exclusion from standard new-build selection. |

Deprecation must preserve traceability and historical understanding.

---

## 11. Referenced section deprecated or superseded

- **Existing pages:** Page templates that reference a section template which is later deprecated or superseded remain valid for **existing** pages and plans. Rendered pages and stored plans continue to reference the section key; interpretability is preserved.
- **New builds:** Policy for new builds (warn, allow, or exclude when a referenced section is deprecated) is implementation-defined. The schema does not forbid referencing a deprecated section key; the registry or UI may enforce policy.
- **Replacement:** Section-level deprecation defines replacement section suggestions; page templates do not automatically change. A page template may be deprecated and point to a replacement template that uses updated sections.

---

## 12. Ineligibility rules

A page template is **incomplete** and **not eligible** for normal use (builds, one-pager generation, new-build selection) if:

1. Any required field (§2) is missing or empty where non-empty is required.
2. `internal_key` is not unique within the page template registry.
3. `status` is not one of `draft`, `active`, `inactive`, `deprecated`.
4. `archetype` is not in the allowed archetype list.
5. `ordered_sections` is empty or not an array of valid section reference items.
6. Any `section_key` in `ordered_sections` does not have a matching entry in `section_requirements`.
7. `section_requirements` is missing or does not cover every section in `ordered_sections`.
8. `compatibility`, `one_pager`, or `version` is missing or does not satisfy the required shape (e.g. `one_pager.page_purpose_summary` or `version.version` missing).

---

## 13. Completeness checklist (spec §13.2, §10.2)

Use this checklist to verify every required field from the page template object contract is represented:

- [ ] `internal_key` — stable internal page-template key
- [ ] `name` — human-readable page-template name
- [ ] `purpose_summary` — page purpose summary
- [ ] `archetype` — category or template archetype
- [ ] `ordered_sections` — ordered section list (section_key, position, required per item)
- [ ] `section_requirements` — required vs optional section designations
- [ ] `compatibility` — compatibility metadata
- [ ] `one_pager` — one-pager generation metadata
- [ ] `version` — version marker
- [ ] `status` — active/deprecated (and draft/inactive) status
- [ ] `default_structural_assumptions` — default structural assumptions
- [ ] `endpoint_or_usage_notes` — endpoint or usage notes where applicable

---

## 14. Example: valid page template (minimal)

```json
{
  "internal_key": "pt_landing_contact",
  "name": "Landing – Contact",
  "purpose_summary": "Single-purpose landing page for contact or lead capture.",
  "archetype": "landing_page",
  "ordered_sections": [
    { "section_key": "st01_hero", "position": 0, "required": true },
    { "section_key": "st05_cta", "position": 1, "required": true }
  ],
  "section_requirements": {
    "st01_hero": { "required": true },
    "st05_cta": { "required": true }
  },
  "compatibility": {
    "site_contexts_appropriate": ["marketing", "lead_gen"],
    "site_contexts_inappropriate": [],
    "conflicts_with_purposes": []
  },
  "one_pager": {
    "page_purpose_summary": "Contact-focused landing page: hero plus primary CTA.",
    "section_helper_order": "same_as_template"
  },
  "version": {
    "version": "1",
    "stable_key_retained": true
  },
  "status": "active",
  "default_structural_assumptions": "Single column, full-width hero.",
  "endpoint_or_usage_notes": "Campaign landing; query-param tracking where applicable."
}
```

---

## 15. Example: valid page template (with optional and deprecation)

```json
{
  "internal_key": "pt_legacy_landing",
  "name": "Legacy Landing (Deprecated)",
  "purpose_summary": "Older landing pattern; use pt_landing_contact for new pages.",
  "archetype": "landing_page",
  "ordered_sections": [
    { "section_key": "st01_hero", "position": 0, "required": true },
    { "section_key": "st05_cta", "position": 1, "required": false }
  ],
  "section_requirements": {
    "st01_hero": { "required": true },
    "st05_cta": { "required": false }
  },
  "compatibility": {},
  "one_pager": {
    "page_purpose_summary": "Legacy landing; see pt_landing_contact for current pattern."
  },
  "version": { "version": "1", "stable_key_retained": true },
  "status": "deprecated",
  "default_structural_assumptions": "",
  "endpoint_or_usage_notes": "",
  "replacement_template_refs": ["pt_landing_contact"],
  "deprecation": {
    "deprecated": true,
    "reason": "Superseded by pt_landing_contact",
    "replacement_template_key": "pt_landing_contact",
    "interpretability_of_old_plans": true,
    "exclude_from_new_build_selection": true
  }
}
```

---

## 16. Example: invalid page template

**Invalid — missing required field (`one_pager.page_purpose_summary` empty):**

```json
{
  "internal_key": "pt_bad",
  "name": "Bad Template",
  "purpose_summary": "Test.",
  "archetype": "landing_page",
  "ordered_sections": [{ "section_key": "st01_hero", "position": 0, "required": true }],
  "section_requirements": { "st01_hero": { "required": true } },
  "compatibility": {},
  "one_pager": { "page_purpose_summary": "" },
  "version": { "version": "1" },
  "status": "draft",
  "default_structural_assumptions": "",
  "endpoint_or_usage_notes": ""
}
```
→ **Incomplete:** `one_pager.page_purpose_summary` is required and non-empty.

**Invalid — section_requirements missing entry for section in ordered_sections:**

```json
{
  "internal_key": "pt_bad2",
  "name": "Bad Template 2",
  "ordered_sections": [
    { "section_key": "st01_hero", "position": 0, "required": true },
    { "section_key": "st05_cta", "position": 1, "required": false }
  ],
  "section_requirements": { "st01_hero": { "required": true } },
  ...
}
```
→ **Incomplete:** `st05_cta` is in `ordered_sections` but not in `section_requirements`.

**Invalid — empty ordered_sections:**

```json
{
  "internal_key": "pt_bad3",
  "ordered_sections": [],
  ...
}
```
→ **Incomplete:** `ordered_sections` must be non-empty.

---

## 17. Export and security

- **Exportability:** All fields in this schema are exportable in manifests and registry exports unless marked internal by implementation. No secrets in template definitions.
- **Capability:** Schema supports capability-gated editing by admins only; no permission logic is defined in this document.
- **References:** Section keys are references only; they must resolve to the section registry. No executable code or arbitrary user-supplied refs.
- **One-pagers:** Page-template one-pagers are documentation objects (documentation_type = page_template_one_pager); see **documentation-object-schema.md**.
