# ACF Page Visibility and Field Assignment Rules Contract

**Document type:** Implementation-grade contract for field-group visibility and page assignment (spec §20.13–20.15, §22.11, §59.5).  
**Governs:** Which ACF field groups appear on which pages, under what template/composition context, and derivation logic.  
**Related:** acf-key-naming-contract.md (group keys), acf-field-blueprint-schema.md, section-registry-schema.md, page-template-registry-schema.md, composition-validation-state-machine.md, registry-admin-screen-contract.md (UI for templates/compositions). Assignment map: Assignment_Types::PAGE_FIELD_GROUP, PAGE_TEMPLATE, COMPOSITION_SECTION.

---

## 1. Purpose and scope

This contract defines the rules that determine which ACF field groups become visible on which pages. Visibility is assigned programmatically per built page. Field groups must not appear globally on all pages. The rules are explicit enough for later implementation without UI or data-model guesswork.

**In scope:** Page-template–driven visibility, composition-driven visibility, built-page metadata, deprecated sections, refinement without breaking content, re-assignment triggers.  
**Out of scope:** Actual ACF registration, assignment service implementation, rendering, page builder logic, admin UI.

Visibility derivation is server-authoritative. No client-only visibility state. Future mutation or reassignment calls must be capability-checked by callers.

---

## 2. Core derivation principle

**Visible field groups = union of field groups for each section included on the page.**

- One section template → one field group (per acf-key-naming-contract: `group_aio_{section_key}`).
- Page template defines `ordered_sections` → section keys.
- Composition defines `ordered_section_list` → section keys.
- Built page has a structural source: page template and/or composition.

---

## 3. Rule matrix

| Source type | Page state | Visible group set | Re-assignment trigger | Notes |
|-------------|------------|-------------------|------------------------|--------|
| **Page template** | New page from template | Groups for each section in template's `ordered_sections` | N/A (initial) | Derive from template definition. |
| **Page template** | Existing built page | Stored assignment (PAGE_FIELD_GROUP) or re-derive from PAGE_TEMPLATE | Template changed; page rebuilt; manual sync | Preserve stored assignment for existing content. |
| **Composition** | New page from composition | Groups for each section in composition's `ordered_section_list` | N/A (initial) | Derive from composition definition. |
| **Composition** | Existing built page | Stored assignment or re-derive from composition ref | Composition changed; page rebuilt; manual sync | Composition drift: changed composition may require re-assignment. |
| **Manual / unknown** | Page with no structural source | Empty or last-known assignment | User assigns template/composition | Do not guess; require explicit structural source. |
| **Draft page** | Pre-build draft | Same as target template/composition | Structure change | Derived from intended structure. |
| **Replaced page** | Page rebuilt from new template | Groups from new template | Rebuild triggers full re-assignment | Old assignment replaced. |
| **Deprecated section** | Existing page using it | **Keep** group visible (preserve content) | None for legacy | Historical reference allowed. |
| **Deprecated section** | New page | **Exclude** from eligibility; do not add | N/A | Exclude from new selection per spec. |

---

## 4. Mapping logic

### 4.1 Page template → visible groups

1. Resolve page's structural source: `PAGE_TEMPLATE` assignment (page id → template internal_key).
2. Load page template definition; read `ordered_sections` (array of `{ section_key }`).
3. For each section_key: `group_key = group_aio_{section_key}`.
4. Visible set = `{ group_aio_st01_hero, group_aio_st02_cta, ... }`.

### 4.2 Composition → visible groups

1. Resolve page's structural source: composition id (from post meta or assignment).
2. Load composition definition; read `ordered_section_list` (array of `{ section_key }`).
3. For each section_key: `group_key = group_aio_{section_key}`.
4. Visible set = union of group keys.

### 4.3 Aggregation when both template and composition

- **Primary rule:** Composition overrides when page is composition-driven. Page template may define the initial structure; composition may refine or replace.
- **Single source of truth:** A built page has one canonical structural source: either page template OR composition. Not both simultaneously for derivation.
- **Composition-from-template:** When composition is derived from a page template (`source_template_ref`), the composition's `ordered_section_list` is the authority for that page.

---

## 5. Assignment map usage

The assignment map (Assignment_Map_Service) stores normalized relationships:

| map_type | source_ref | target_ref | scope_ref | Meaning |
|----------|------------|------------|-----------|---------|
| `page_template` | Page post ID | Page template internal_key | — | Page uses this template. |
| `page_field_group` | Page post ID | Field group key (`group_aio_*`) | — | Page has this field group visible. |
| `composition_section` | Composition ID | Section internal_key | — | Composition includes this section. |

**Derivation flow:**
1. For a page: query `page_template` to get template key; or resolve composition id from page meta/assignment.
2. From template: get `ordered_sections`; from composition: get `ordered_section_list` (or use `composition_section` rows).
3. Map each section_key → `group_aio_{section_key}`.
4. Store/update `page_field_group` rows: (page_id, group_key) for each visible group.

---

## 6. Deprecated section rules

| Context | Visibility | Rationale |
|---------|------------|-----------|
| **Existing page** with deprecated section | **Keep** group visible | Preserve content; historical reference allowed. Do not break editing. |
| **New page** (template or composition) | **Exclude** deprecated sections | Deprecated sections are not eligible for new selection. |
| **Reassigning** an existing page | **Keep** groups for deprecated sections already on page | Refinement preserves relationships. |
| **Rebuilding** a page from same template | **Keep** deprecated sections if still in template | Template author may not have removed deprecated sections yet. |
| **Rebuilding** from new template | Use new template's sections only | Fresh assignment. |

---

## 7. Refinement without breaking content

When refining assignments (e.g. template updated, composition changed):

1. **Additive change (new sections added):** Add new group assignments. Do not remove existing.
2. **Subtractive change (sections removed from template/composition):** Option A: Keep groups for backward compat (content remains editable). Option B: Remove assignment only if migration has run to move/archive content. Default: **Keep** assignments unless explicit migration.
3. **Reordering:** No impact on visibility; order does not change which groups are visible.
4. **Section replacement (deprecate A, add B):** New pages get B only. Existing pages with A: keep A visible; optionally add B if composition/template updated to include both during transition.

**Rule:** Prefer preserving interpretability and edit access. Avoid sudden disappearance of needed fields without migration support.

---

## 8. Re-assignment triggers

| Trigger | Action |
|---------|--------|
| Page created from template | Initial assignment: derive groups from template; create `page_field_group` rows. |
| Page created from composition | Initial assignment: derive groups from composition; create `page_field_group` rows. |
| Page rebuilt (governed workflow) | Full re-assignment from new structural source. |
| Template updated (sections added/removed) | Optionally sync pages using that template; apply refinement rules (§7). |
| Composition updated (sections changed) | Optionally sync pages using that composition; apply refinement rules. |
| Manual "Sync field groups" action | Re-derive from current structural source; update `page_field_group`. |
| Page manually assigned template/composition | Re-derive; update assignments. |
| Deprecated section on existing page | No automatic removal. Manual or migration-only. |

---

## 9. Scenario matrix

### 9.1 Page template–driven page

| Step | State | Visible groups |
|------|-------|----------------|
| 1 | Page created from template `pt_landing` (sections: st01_hero, st02_cta, st05_faq) | group_aio_st01_hero, group_aio_st02_cta, group_aio_st05_faq |
| 2 | User edits page | Same; no change. |
| 3 | Template updated to add st03_stats | Optionally sync: add group_aio_st03_stats. Existing groups preserved. |

### 9.2 Composition-driven page

| Step | State | Visible groups |
|------|-------|----------------|
| 1 | Page created from composition `comp_001` (sections: st01_hero, st05_faq) | group_aio_st01_hero, group_aio_st05_faq |
| 2 | Composition updated: add st02_cta | Optionally sync: add group_aio_st02_cta. |
| 3 | Composition updated: remove st05_faq | Refinement: keep group_aio_st05_faq for existing content (unless migration). New pages from composition get st01_hero, st02_cta only. |

### 9.3 Deprecated section on existing page

| Step | State | Visible groups |
|------|-------|----------------|
| 1 | Page has st10_legacy_hero (deprecated) | group_aio_st10_legacy_hero remains visible. |
| 2 | New page from template that previously included st10 | Template should be updated; if not, exclude st10 from new page. |
| 3 | Existing page with st10 | Keep group visible. Content remains editable. |

### 9.4 Changed composition causing re-assignment

| Step | State | Visible groups |
|------|-------|----------------|
| 1 | Page uses composition A (st01, st02) | group_aio_st01_hero, group_aio_st02_cta |
| 2 | User changes page to use composition B (st01, st05) | Re-assignment: remove st02, add st05. New set: group_aio_st01_hero, group_aio_st05_faq. |
| 3 | Content in st02 | Preserved in post meta; no automatic deletion. Fields may become "orphaned" from visible group until migration. Document for implementer. |

### 9.5 Refinement preserving content

| Step | State | Action |
|------|-------|--------|
| 1 | Page has groups A, B, C. Template updated to remove B. | Refinement: keep B's group visible on this page (preserve content). New pages from template get A, C only. |
| 2 | Migration run to move B content | After migration: optionally remove B's group assignment. |
| 3 | No migration | B remains; user can still edit. |

---

## 10. Page state definitions

| State | Definition |
|-------|------------|
| **New page** | Page just created from template or composition; not yet published/edited. |
| **Built page** | Page created by plugin with structural source; may have post_content. |
| **Draft page** | Unpublished; may have intended template/composition. |
| **Manually edited page** | Page created outside plugin or structure changed manually; may lack assignment. |
| **Replaced page** | Page rebuilt from a different template/composition. |
| **Legacy page** | Page built under older template/composition version; may include deprecated sections. |

---

## 11. Prohibited visibility patterns

| Pattern | Reason |
|---------|--------|
| Blanket ACF exposure on all pages | Spec requires page-specific relevance. |
| Visibility from fragile manual page flags only | Must derive from structural source. |
| Client-only visibility state | Server-authoritative. |
| Removing group without migration for pages with content | Breaks editing; preserve interpretability. |
| Guessing structural source when none stored | Require explicit assignment; fail clearly. |
| Exposing hidden admin-only data through visibility metadata | Security. |

---

## 12. Compatibility notes

- **Edit / Reuse / Snapshot (§22.11):** Field content is part of page state. Reuse workflows (duplicate page, duplicate composition) should derive visibility from the duplicated structure. Snapshot of page state may include which groups were visible at snapshot time.
- **Export/import:** Assignment map rows (page_field_group, page_template) are exportable. Restore must reapply visibility rules or re-derive from structural source.
- **Uninstall:** Field group cleanup follows Field Group Cleanup Rules (§20.15); visibility contract does not define uninstall behavior.

---

## 13. Implementation notes for future service

When implementing the assignment service:

1. **Resolve structural source** for a page: `page_template` assignment or composition id from meta/assignment.
2. **Load definition:** Page template or composition from registry.
3. **Extract section keys:** `ordered_sections` or `ordered_section_list`.
4. **Map to groups:** `group_aio_{section_key}` per acf-key-naming-contract.
5. **Apply deprecated filter:** For new assignment only, exclude sections with `eligible_for_new_use` false.
6. **Store:** Create/update `page_field_group` rows. Delete orphaned rows when doing full re-assignment (e.g. rebuild), but apply refinement rules for incremental updates.
7. **ACF location rules:** Use `page_field_group` to build ACF location rule payload (post id in list, or post_type + meta).

---

## 14. Security and governance

- Visibility derivation is server-side only.
- No client-provided visibility state trusted without server validation.
- Capability checks required for any mutation (assign, reassign, sync).
- Do not leak hidden admin-only data through visibility metadata.
- Assignment map payload must not contain secrets.
