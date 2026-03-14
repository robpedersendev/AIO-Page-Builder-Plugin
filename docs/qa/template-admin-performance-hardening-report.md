# Template Admin Performance Hardening Report

**Document type:** QA and hardening report for large-library template admin performance (Prompt 188).  
**Governs:** Query paths, pagination, filtering, preview loading, directory/detail/compare/compositions screens at final target counts.  
**Spec refs:** §49.6 Templates Screen; §49.7 Page Templates Screen; §50.1 Admin UI Design Principles; §55.7 Large Site Handling Rules; §55.8 Large Template Library Handling Rules; §55.10 Logging Volume Handling Rules.

**Authority:** Large_Library_Query_Service, Preview_Cache_Service, directory/detail state contracts, hardening-release-gate-matrix.

---

## 1. Purpose

This report documents the **template-admin performance hardening pass** for the enlarged template ecosystem: Section Templates directory, Page Templates directory, detail screens, Template Compare screen, and Compositions screen. Tuning keeps behavior functionally identical while reducing admin load at 250+ section and 500+ page template scale. No feature removal; registry authority, preview fidelity, and compare/detail usefulness are preserved.

---

## 2. Tuning summary

| Area | Change | Rationale |
|------|--------|-----------|
| **Directory pagination** | Per-page capped at **50** (`Large_Library_Query_Service::MAX_PER_PAGE`). Default remains 25. | Bounds list payload and DOM size; avoids 100-row requests that degrade responsiveness. |
| **Query service** | `query_sections` and `query_page_templates` clamp `$per_page` to `MAX_PER_PAGE` before filtering/slicing. | Single source of truth; UI cannot request more than 50 rows per page. |
| **Section/Page directory screens** | Request `per_page` capped with `MAX_PER_PAGE` (replacing previous 100 cap). | Aligns UI with service limit. |
| **Composition builder** | Section filter pagination uses `MAX_PER_PAGE`; `Composition_Filter_State` caps `per_page` to same. | Consistent list size in builder section picker. |
| **Preview cache** | `get_max_entries()` and `get_cache_entry_count()` added for reporting. Cache budget remains 800 entries; LRU eviction unchanged. | QA and performance summary payloads; no behavior change. |
| **Compare screen** | Compare list already limited to **10** items (`Template_Compare_State_Builder::MAX_COMPARE_ITEMS`). | No code change; documented as part of hardening. |
| **Compositions list** | List view already limited to **100** items (`Compositions_Screen::LIST_LIMIT`). | Documented; keeps composition list bounded. |

---

## 3. Constants and contracts

| Constant / contract | Value / rule |
|--------------------|--------------|
| `Large_Library_Query_Service::MAX_LIBRARY_LOAD` | 1000 (definitions loaded for filtering). |
| `Large_Library_Query_Service::DEFAULT_PER_PAGE` | 25. |
| `Large_Library_Query_Service::MAX_PER_PAGE` | 50 (Prompt 188). |
| `Preview_Cache_Service::DEFAULT_MAX_ENTRIES` | 800. |
| `Template_Compare_State_Builder::MAX_COMPARE_ITEMS` | 10. |
| `Compositions_Screen::LIST_LIMIT` | 100. |

---

## 4. What was not changed

- **Registry schemas and template records:** Unchanged.
- **Detail screen preview loading:** Still uses real renderer and preview cache; no stripping of preview or metadata.
- **Breadcrumb, filter, compare, helper-doc behavior:** Preserved.
- **Permission checks:** No bypass; caching and query tuning remain internal.
- **Appendix generation/access:** No change in this pass; appendix generators remain as-is.

---

## 5. Performance summary payload (example)

For QA or diagnostics, consumers can build a summary from existing services:

```json
{
  "template_admin_performance": {
    "directory": {
      "max_per_page": 50,
      "default_per_page": 25,
      "max_library_load": 1000
    },
    "preview_cache": {
      "max_entries": 800,
      "current_entry_count": 142
    },
    "compare": {
      "max_compare_items": 10
    },
    "compositions_list_limit": 100
  }
}
```

`current_entry_count` comes from `Preview_Cache_Service::get_cache_entry_count()`; other values from constants or `get_max_entries()`.

---

## 6. QA checklist (performance-focused)

- [ ] **Large-result filtering:** With 250+ sections, filter by category/purpose_family returns correct total and paginated slice; rows count ≤ MAX_PER_PAGE.
- [ ] **Directory pagination:** Changing page or per_page (up to 50) updates list and pagination info without errors.
- [ ] **Compare screen loading:** With up to 10 items in compare list, compare screen loads and renders side-by-side without timeout or missing data.
- [ ] **Detail screen:** Section and page template detail screens render metadata and preview; no regression from hardening.
- [ ] **Composition builder filter:** Section picker with filters and pagination responds within acceptable time; per_page capped at 50.

---

## 7. Cross-references

- **Implementation:** `Large_Library_Query_Service`, `Preview_Cache_Service`, `Section_Templates_Directory_Screen`, `Page_Templates_Directory_Screen`, `Template_Compare_Screen`, `Compositions_Screen`, `Section_Template_Directory_State_Builder`, `Page_Template_Directory_State_Builder`, `Composition_Builder_State_Builder`, `Composition_Filter_State`.
- **Release gate:** hardening-release-gate-matrix.md (performance / §55.7, §55.8).
