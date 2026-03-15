# Documentation Object Schema

**Document type:** Implementation-grade schema contract for helper paragraphs and one-pagers (spec §10.7, §15.1–15.4, §16.1, §14.6).  
**Governs:** Documentation types, source references, content body model, generated vs human-edited state, version markers, export metadata, and lifecycle before document generation or admin UI is implemented.  
**Related:** object-model-schema.md (§3.7 Documentation Object), section-registry-schema.md (helper_ref), page-template-registry-schema.md (one_pager), composition-validation-state-machine.md.

---

## 1. Purpose and scope

The **Documentation object** represents **helper paragraphs, one-pager materials, or other structured guidance** associated with section templates, page templates, or compositions. Documentation is a **product feature** and deserves explicit representation rather than throwaway text (spec §10.7). Helper paragraphs and one-pagers are **first-class objects** with types, provenance, and lifecycle semantics.

**In scope for this schema:**

- Section helper paragraphs (§15.1–15.4) — per-section editing and strategy guidance.
- Page-template one-pagers (§16.1) — consolidated page-level reference for a page template.
- Composition one-pagers (§14.6) — composition-specific one-pager generated from section helpers and composition order.

**Out of scope:** No document generation service, admin editors, front-end rendering, or export bundle code. Documentation must not become a covert secret-storage channel; content is user-facing or admin-facing guidance only. Future editing is capability-gated; generated content provenance must be transparent.

---

## 2. Documentation types (documentation_type)

Every documentation object has a **documentation_type** that defines its role and expected content shape.

| documentation_type | Description | Source reference | Content expectations |
|--------------------|-------------|------------------|----------------------|
| `section_helper` | Helper paragraph or block set for a section template (§15.1–15.4) | Required: section template internal_key | What section is for, content type, field-by-field instructions, tone, mistakes to avoid, SEO/a11y notes (§15.2–15.3). |
| `page_template_one_pager` | One-pager for a page template (§16.1) | Required: page template internal_key | Page purpose, flow, combined section guidance, page-wide notes. |
| `composition_one_pager` | One-pager for a custom composition (§14.6) | Required: composition id | Composition purpose, section order, section helpers combined, composition-level notes. |

**Required:** documentation_type must be one of the above. New types may be added by schema revision; validation shall use an allowlist.

---

## 3. Required root fields

| Field | Type | Required | Validation | Export | Notes |
|-------|------|----------|------------|--------|--------|
| `documentation_id` | string | Yes | Non-empty; unique; immutable; e.g. UUID or slug; max 64 chars | Yes | Stable identifier (object-model: internal key). |
| `documentation_type` | string | Yes | One of allowed documentation_type enum (§2) | Yes | Section helper, page one-pager, or composition one-pager. |
| `content_body` | string or structured | Yes | Non-empty; format depends on type (see §6) | Yes | Main content; no secrets or API material. |
| `status` | string | Yes | One of: `draft`, `active`, `archived` | Yes | Lifecycle status (object-model §3.7). |

---

## 4. Optional root fields and blocks

| Field | Type | Required | Validation | Export | Notes |
|-------|------|----------|------------|--------|--------|
| `source_reference` | object | Conditional | Shape per §5; required for section_helper (section), page_template_one_pager (page template), composition_one_pager (composition) | Yes | Source template or composition reference. |
| `generated_or_human_edited` | string | No | One of: `generated`, `human_edited`, `mixed` | Yes | Editing posture (§10.7). |
| `version_marker` | string | No | Max 32 chars | Yes | Version marker for migration and traceability. |
| `export_metadata` | object | No | Shape per §8 | Yes | Export category and metadata (§52.5). |
| `provenance` | object | No | Shape per §7 | Yes | Generation context, source refs, last edited. |
| `superseded_by` | string | No | documentation_id of superseding document; max 64 chars | Yes | If this doc was replaced by a newer version. |

**Source reference rule:** For each documentation_type, the corresponding source must be present in `source_reference`. A section_helper must reference a section template; a page_template_one_pager must reference a page template; a composition_one_pager must reference a composition. Missing required source makes the document **incomplete** for that type.

---

## 5. Source reference block

Describes **which template or composition this documentation is for**. Shape depends on documentation_type; use the relevant field(s).

| Field | Type | When required | Notes |
|-------|------|----------------|--------|
| `section_template_key` | string | When documentation_type = section_helper | Section template internal_key; max 64 chars. |
| `page_template_key` | string | When documentation_type = page_template_one_pager | Page template internal_key; max 64 chars. |
| `composition_id` | string | When documentation_type = composition_one_pager | Composition id; max 64 chars. |

Only one of the three is set per document; the others are absent or null. Legacy or alternate field names `source_template_ref` / `source_composition_ref` may map to this block (e.g. source_template_ref = section_template_key or page_template_key; source_composition_ref = composition_id).

---

## 6. Content body expectations

- **Section helper (§15.2–15.3):** Structure should include: what the section is for, user need or page goal, type of content, level of detail, tone, major field/content area instructions, supporting media guidance, mistakes to avoid, SEO or accessibility notes. May be stored as HTML, Markdown, or structured blocks; schema does not mandate format, only that content_body is non-empty and appropriate to type.
- **Page-template one-pager (§16.1):** Page purpose summary, flow explanation, combined section guidance in order, page-wide notes. Same storage flexibility.
- **Composition one-pager (§14.6):** Composition purpose, section order, section helpers combined, composition-level notes, cross-section guidance.

Content is **user-facing or admin-facing guidance only**. No API keys, secrets, or privileged operational notes. Generated content provenance must be transparent (use generated_or_human_edited and provenance).

**Industry overlays:** Industry-specific section-helper and page one-pager overlays are defined in **industry-section-helper-overlay-schema.md** and **industry-page-onepager-overlay-schema.md** (docs/schemas). They are keyed by (industry_key, section_key) or (industry_key, page_template_key) and provide additive or narrowly overriding guidance; base documentation objects remain authoritative.

---

## 7. Provenance block

Optional block for generation context and traceability.

| Field | Type | Notes |
|-------|------|--------|
| `generated_at` | string | ISO 8601 datetime when content was generated; max 32 chars. |
| `generated_by` | string | System or actor ref; max 64 chars. |
| `last_edited_at` | string | ISO 8601 datetime; max 32 chars. |
| `last_edited_by` | string | Actor ref; max 64 chars. |
| `source_input_refs` | array of strings | Refs to inputs used (e.g. section helper ids, template keys); each max 128 chars. |

---

## 8. Export metadata block

Supports exportability (optional category per §52.5).

| Field | Type | Notes |
|-------|------|--------|
| `export_category` | string | e.g. `registries`, `documentation`, `optional`; max 64 chars. |
| `include_in_full_export` | boolean | Whether to include in full export by default. |
| `checksum_or_ref` | string | Optional checksum or storage ref for integrity; max 128 chars. |

---

## 9. Lifecycle and status

- **Status:** `draft` — editable, not yet published. `active` — in use, visible to editors/builders. `archived` — retained but not offered for new use.
- **Transitions:** draft → active → archived. Archived may be restored to draft or active per policy.
- **Supersession:** When a document is replaced by a newer version (e.g. regenerated one-pager), the old document may set `superseded_by` to the new documentation_id and optionally move to `archived`. The new document is the current one for that source. Supersession is optional and implementation-defined.

**Retention:** Documentation may be archived; deletion per policy. Export metadata and version marker support migration and export expectations.

---

## 10. Generated vs human-edited

| Value | Meaning |
|-------|--------|
| `generated` | Content was produced by the system (e.g. one-pager generation). |
| `human_edited` | Content was written or edited by a human. |
| `mixed` | Content was generated and later refined by a human; provenance should reflect both. |

Generated documentation must support **later human refinement** without losing provenance; use `mixed` and provenance blocks when content is edited after generation.

---

## 11. Ineligibility / invalid document

A documentation object is **incomplete or invalid** if:

1. Any required field (§3) is missing or empty (e.g. content_body empty, documentation_type empty).
2. documentation_type is not in the allowed type list.
3. status is not `draft`, `active`, or `archived`.
4. For the given documentation_type, the required source reference is missing (e.g. section_helper without section_template_key, composition_one_pager without composition_id).

---

## 12. Completeness checklist (spec use cases)

- [ ] **Section helper** — documentation_type = section_helper; source_reference.section_template_key; content_body with helper structure (§15.2–15.3).
- [ ] **Page-template one-pager** — documentation_type = page_template_one_pager; source_reference.page_template_key; content_body with one-pager structure (§16.1).
- [ ] **Composition one-pager** — documentation_type = composition_one_pager; source_reference.composition_id; content_body with composition one-pager structure (§14.6).
- [ ] **Generated vs human-edited** — generated_or_human_edited and optional provenance support traceability.
- [ ] **Export** — export_metadata supports optional category and full-export inclusion.

---

## 13. Example: section helper document

```json
{
  "documentation_id": "doc-helper-st01-hero",
  "documentation_type": "section_helper",
  "content_body": "<p>This hero section is the first thing visitors see. Use it to state the main value proposition...</p>",
  "status": "active",
  "source_reference": {
    "section_template_key": "st01_hero"
  },
  "generated_or_human_edited": "human_edited",
  "version_marker": "1",
  "export_metadata": {
    "export_category": "documentation",
    "include_in_full_export": true
  }
}
```

---

## 14. Example: composition-generated one-pager

```json
{
  "documentation_id": "doc-onepager-comp-uuid-12345",
  "documentation_type": "composition_one_pager",
  "content_body": "# Composition editing guide\n\n**Purpose:** Contact-focused landing...\n\n## Section order\n\n1. Hero (st01_hero)...",
  "status": "active",
  "source_reference": {
    "composition_id": "comp-uuid-12345"
  },
  "generated_or_human_edited": "generated",
  "version_marker": "1",
  "provenance": {
    "generated_at": "2025-07-15T10:30:00Z",
    "generated_by": "system",
    "source_input_refs": ["st01_hero", "st05_cta"]
  },
  "export_metadata": {
    "export_category": "documentation",
    "include_in_full_export": true
  }
}
```

---

## 15. Example: invalid — missing source reference

```json
{
  "documentation_id": "doc-bad",
  "documentation_type": "section_helper",
  "content_body": "Some text.",
  "status": "draft",
  "source_reference": {}
}
```
→ **Incomplete:** section_helper requires source_reference.section_template_key.

---

## 16. Example: invalid — undocumented type

```json
{
  "documentation_id": "doc-bad2",
  "documentation_type": "custom_blog_post",
  "content_body": "Text.",
  "status": "draft"
}
```
→ **Invalid:** documentation_type must be one of section_helper, page_template_one_pager, composition_one_pager.
