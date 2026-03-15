# ACF Registration — Cache Invalidation Matrix

**Prompt**: 300  
**Upstream**: acf-conditional-registration-contract.md

---

## 1. Purpose

Ensures derived section-key caches (Page_Section_Key_Cache_Service) are invalidated whenever page assignments, page templates, or compositions change, so admin never sees stale ACF registration results.

---

## 2. Caches to invalidate

| Cache scope | Keyed by | Invalidated when |
|-------------|----------|------------------|
| **Page** | Page ID | That page's assignment map changes (assign_from_template, assign_from_composition, clear + persist). |
| **Template** | Template internal_key | That page template's definition is saved (ordered_sections or other fields that affect derived section keys). |
| **Composition** | Composition id | That composition's definition is saved (ordered_section_list or other fields that affect derived section keys). |

---

## 3. Change points and hooks

| Change | Hook / event | Listener action |
|--------|--------------|-----------------|
| **Page assignment** | `aio_acf_assignment_changed` (page_id) | Page_Section_Key_Cache_Service invalidates page cache for that page_id. |
| **Page template definition saved** | `aio_page_template_definition_saved` (template_key) | Page_Section_Key_Cache_Service invalidates template cache for that template_key. |
| **Composition definition saved** | `aio_composition_definition_saved` (composition_id) | Page_Section_Key_Cache_Service invalidates composition cache for that composition_id. |

---

## 4. Implementation

- **Assignment**: Page_Field_Group_Assignment_Service fires `aio_acf_assignment_changed` after persist_field_groups (already implemented). Cache service listens and calls invalidate_for_page( $page_id ).
- **Template**: Page_Template_Repository::save_definition() fires `aio_page_template_definition_saved` with internal_key after successful save. Cache service listens and calls invalidate_for_template( $template_key ).
- **Composition**: Composition_Repository::save_definition() fires `aio_composition_definition_saved` with composition_id after successful save. Cache service listens and calls invalidate_for_composition( $composition_id ).
- **Bootstrap**: ACF_Registration_Provider ensures cache service registers all three listeners (assignment, template, composition) when the cache is used on acf/init.

---

## 5. Safe failure

- On cache miss or after invalidation, resolvers recompute from assignment map or template/composition derivation. No full registration fallback.
- Invalidation must not be triggerable from public requests; only repository save and assignment service run in admin/tooling contexts with existing permission checks.

---

## 6. Cross-references

- Page_Section_Key_Cache_Service (listen_for_assignment_changes, listen_for_definition_changes)
- acf-conditional-registration-contract.md §4.2, §4.3
