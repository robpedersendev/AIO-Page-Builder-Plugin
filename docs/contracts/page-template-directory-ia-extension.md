# Page Template Directory Admin IA Extension Contract

**Spec**: §49.3 Screen Hierarchy; §49.4 Screen Entry Points; §49.7 Page Templates Screen; §62.10 Admin Screen Inventory Appendix; §13 Page Template Registry; §16 Page Template One-Pager System

**Upstream**: page-template-category-taxonomy-contract.md, template-library-scale-extension-contract.md, template-library-coverage-matrix.md, admin-screen-inventory.md

**Status**: IA contract only. No screen implementation; no preview rendering implementation; no section directory in this contract. Directory browsing is **additive** and does not replace Build Plan or composition workflows. Page templates remain **registry objects**. The IA must remain clear at **500+ templates**.

---

## 1. Purpose and scope

This contract extends the admin **information architecture** to support **directory browsing** of page templates: hierarchical category browsing, family browsing, template-option lists, and template-detail entry points. It encodes the **requested path structure** explicitly (e.g. Page Templates > Top Level Page Templates > Home Page Templates > [Template Option]) and defines menu location, browse tree, breadcrumbs, category/family filters, detail-screen entry points, preview-link placement, and one-pager/documentation links.

**Out of scope**: Actual screen implementation; preview rendering implementation; section template directory (separate contract/screen). Directory browsing remains **capability-gated**; preview/detail access respects template-management permissions; no raw secret-bearing preview data.

---

## 2. Menu location and entry point

### 2.1 Menu placement

| Rule | Value |
|------|--------|
| **Parent menu** | AIO Page Builder (top-level slug `aio-page-builder`). |
| **Submenu item** | “Page Templates” (or equivalent label). Single submenu entry for the **page template directory**. |
| **Screen slug** | Stable slug for the directory root, e.g. `aio-page-builder-page-templates`. Implementation may use a single screen with state (category/family/template) or multiple screens; slug(s) must be documented in admin-screen-inventory. |
| **Capability** | Template-management capability (e.g. `aio_view_build_plans` or dedicated `aio_manage_templates`). Directory browsing is **capability-gated**. |
| **Spec alignment** | §49.3: “Templates screens remain globally accessible but are conceptually foundational.” §49.7: Page Templates screen includes page template list, composition list toggle/tab, section-order preview, status/version, one-pager access, compatibility notes. |

### 2.2 Entry from other screens

- **Build Plan / template selection**: When user chooses a page template (e.g. for a new plan or composition), the picker may **deep-link** into the directory (e.g. to category/family or to template detail). Directory IA supports being the **source** of template selection without replacing the Build Plan workflow.
- **Dashboard / quick actions**: Optional “Browse page templates” link to directory root.
- **One-pager / documentation**: Links from directory (template detail, list row) to one-pager and helper docs; see §7.

---

## 3. Hierarchical browse tree structure

The directory **tree** is explicit. The user’s requested path structure is **encoded** as follows. No flattening into one unstructured list.

### 3.1 Tree levels (order of drill-down)

| Level | Name / concept | Source | Description |
|-------|----------------|--------|-------------|
| **L1** | Page Templates (root) | Directory root | Landing for page template browsing. Shows category-class level (L2) or combined category + family navigation. |
| **L2** | Category class | `template_category_class` (page-template-category-taxonomy-contract §2) | Top Level Page Templates, Hub Page Templates, Nested Hub Page Templates, Child/Detail Page Templates. Labels are server-authoritative from taxonomy (e.g. “Top Level Page Templates” for `top_level`). |
| **L3** | Family (subfamily) | `template_family` (§3) | Within a category, group by family. E.g. “Home Page Templates”, “Services Page Templates”, “Locations Page Templates”. Labels map from family slug (e.g. `home` → “Home Page Templates”). |
| **L4** | Template option list | Filtered template list | List/card view of page templates that match the selected category + family. Each row/card is a **template option**; selecting one opens **template detail** (L5). |
| **L5** | Template detail | Single template | Detail screen for one page template: metadata, ordered sections, one-pager link, preview link, composition provenance, compatibility. |

### 3.2 Path expression (canonical)

**Canonical path** for a template option in the tree:

```
Page Templates > {CategoryClassLabel} > {FamilyLabel} > [Template Name / internal_key]
```

**Example:**

```
Page Templates > Top Level Page Templates > Home Page Templates > [Home – Default]
Page Templates > Hub Page Templates > Services Page Templates > [Services Hub – Full]
Page Templates > Child/Detail Page Templates > Locations Page Templates > [Location Detail – Standard]
```

**Category class labels** (L2) are fixed per taxonomy:

- `top_level` → “Top Level Page Templates”
- `hub` → “Hub Page Templates”
- `nested_hub` → “Nested Hub Page Templates”
- `child_detail` → “Child/Detail Page Templates”

**Family labels** (L3) are derived from `template_family` slug (e.g. `home` → “Home Page Templates”, `services` → “Services Page Templates”). Labels are server-authoritative; see page-template-category-taxonomy-contract §3.

### 3.3 Category and subfamily path rules

| Rule | Requirement |
|------|-------------|
| **Category first** | Drill-down is **category class (L2) first**, then family (L3). So “Top Level” then “Home”, not “Home” then “Top Level”. |
| **Family within category** | Only families that have at least one template in the selected category are shown at L3. Empty family nodes may be hidden or shown with count zero. |
| **Stable ordering** | Category order: top_level, hub, nested_hub, child_detail. Family order: stable sort by family slug or by display order defined in taxonomy. |
| **Other / uncategorized** | Templates with `template_family` = `other` or missing family appear under “Other” or “Uncategorized” at L3 so they are still reachable. |

### 3.4 Alternative entry: direct to family or search

- **Shortcut to family**: If the UI supports it, a secondary navigation (e.g. “By family”) may show **family first**, then category within family. The **primary** tree remains category → family → list (§3.1).
- **Search**: Global search (see §4) can jump to template detail or to a filtered list without going through the full tree; breadcrumbs still reflect the logical path (category + family of the result).

---

## 4. Search and filter expectations

### 4.1 Search

| Requirement | Description |
|-------------|-------------|
| **Scope** | Search applies to page template **name**, `internal_key`, `purpose_summary`, and optionally `template_family` / `template_category_class` labels. |
| **Result** | Search results are a **filtered template list** (same row/card shape as L4). Results should indicate category + family so user can place the template in the tree. |
| **Breadcrumb** | Search results screen shows breadcrumb: e.g. “Page Templates > Search: {query}” or “Page Templates > Search results”. |
| **Performance** | At 500+ templates, search may be **server-side** and paginated or limited (e.g. top N matches); no requirement to load all templates client-side. |

### 4.2 Filters

| Filter | Source | Behavior |
|--------|--------|----------|
| **Category** | `template_category_class` | Filters to one of top_level, hub, nested_hub, child_detail. When applied, L3 shows only families that have templates in that category. |
| **Family** | `template_family` | Filters to one family slug. When applied with category, list shows templates matching both. |
| **Status** | `status` (draft, active, inactive, deprecated) | Optional filter on list view; default typically “active” or “all”. |
| **Archetype** | `archetype` | Optional; narrows by template archetype (e.g. service_page, location_page). |

Filters are **additive** with the tree: e.g. “Category = Top Level” + “Family = Home” yields the same set as navigating Page Templates > Top Level Page Templates > Home Page Templates. Filter state may be reflected in the URL (query args) for shareable/deep links.

---

## 5. Row / list card data for template-option views (L4)

At L4 (template option list), each **row or card** displays a bounded set of fields so the list remains scannable and performant.

### 5.1 Minimum row/card data

| Field | Source | Purpose |
|-------|--------|---------|
| **Template name / label** | `name` or `purpose_summary` (short) or `internal_key` | Primary identifier. |
| **internal_key** | Registry | Stable key; may be secondary or tooltip. |
| **Category + family** | `template_category_class`, `template_family` | Context in tree; breadcrumb consistency. |
| **Status** | `status` | draft, active, inactive, deprecated. |
| **Section count** | Length of `ordered_sections` | Quick structural hint. |
| **Link to detail** | URL to L5 (template detail) | Primary action for row. |

### 5.2 Optional row/card data

| Field | Source | When useful |
|-------|--------|-------------|
| Version | `version` or schema | For version filter/display. |
| One-pager link | One-pager ref | Direct link to one-pager from row. |
| Preview thumbnail / link | Preview ref | Link to preview (see §7). |
| Composition count | If compositions reference this template | “Used in N compositions”. |

### 5.3 List/detail relationship

- **List (L4)** → **Detail (L5)**: Each row has a **primary action** (e.g. click, “View”) that opens the template detail screen for that template’s `internal_key`.
- **Detail (L5)** → **List (L4)**: Detail screen provides “Back to list” or “Back to [Category] > [Family]” so user returns to the same list context.
- **Detail (L5)** → **One-pager, Preview, Composition**: See §7.

---

## 6. Breadcrumb model

### 6.1 Breadcrumb structure

Breadcrumbs reflect the **current location** in the directory and support one-click navigation to any ancestor.

| Screen / state | Breadcrumb segments (example) |
|----------------|--------------------------------|
| Directory root (L1) | Page Templates |
| Category selected (L2) | Page Templates > Top Level Page Templates |
| Family selected (L3) | Page Templates > Top Level Page Templates > Home Page Templates |
| Template list (L4) | Same as L3 (list is “within” that category + family). |
| Template detail (L5) | Page Templates > Top Level Page Templates > Home Page Templates > [Template Name] |
| Search results | Page Templates > Search: “services” (or “Search results”) |

### 6.2 Breadcrumb rules

- Each segment except the last is a **link** to that level (root, category, family, or list).
- Last segment is current page (no link) or template name linking to detail if on list.
- Breadcrumb must **not** flatten to “Page Templates > Template name” only when the user arrived via category > family; the full path (category + family) must be present so “Back” behavior is clear.

---

## 7. Links to template detail, one-pager, preview, and composition provenance

### 7.1 Template detail (L5) entry points

- **From list (L4)**: Row primary action → template detail.
- **From Build Plan template picker**: “Browse in directory” or similar → directory at appropriate category/family or direct to template detail.
- **From search**: Result row → template detail.
- **From deep link**: URL with template `internal_key` (and optional category/family for breadcrumb) → template detail.

### 7.2 One-pager and documentation links

| Link type | Placement | Requirement |
|-----------|-----------|-------------|
| **One-pager** | Template detail (L5); optionally on list row | Link to the page template’s one-pager (Spec §16). One-pager is the consolidated editing and strategy reference for that template. |
| **Helper / compatibility** | Template detail | Links to section helper docs or compatibility notes for the template’s ordered sections, per registry and §49.7. |

### 7.3 Preview link

| Rule | Description |
|------|-------------|
| **Placement** | Template detail (L5); optionally thumbnail or “Preview” on list row (L4). |
| **Target** | Preview view for the page template. Preview **rendering** and **dummy-data** rules are defined in **template-preview-and-dummy-data-contract.md**: real renderer with synthetic ACF data, realistic content by family, preview-safe omission and animation. |
| **Safety** | Preview must use **synthetic dummy data** only (template-preview-and-dummy-data-contract, large-scale-acf-lpagery-binding-contract); no raw secret-bearing or production data. |
| **Capability** | Preview access respects same capability as directory (template-management). |
| **Detail screen metadata** | Alongside preview, detail screen must show name, description, used sections, differentiation notes, purpose/CTA direction, and one-pager link per **template-preview-and-dummy-data-contract.md** §4.1. |

### 7.4 Composition provenance

| Rule | Description |
|------|-------------|
| **On template detail (L5)** | If the template is referenced by **compositions**, show “Used in N compositions” and optionally list composition IDs or links to composition detail. |
| **From composition** | When viewing a composition, “Source template” or “Based on template” may link back to directory template detail. |

---

## 8. Pagination and performance requirements for large libraries

### 8.1 Pagination

| Requirement | Description |
|-------------|-------------|
| **List (L4)** | Template list at L4 must support **pagination** when the number of templates in the selected category+family (or search result) exceeds a reasonable page size (e.g. 20–50). |
| **URL state** | Page number (and sort, if offered) should be in query args so list state is shareable and back/forward works. |
| **Count** | Total count (e.g. “Showing 1–20 of 127”) should be available without loading all rows; server returns total and page slice. |

### 8.2 Performance

| Requirement | Description |
|-------------|-------------|
| **Lazy load** | Category and family levels (L2, L3) may load **counts** or **first page** only; full template list loads when user drills to L4 or applies filters. |
| **No full dump** | At 500+ templates, the directory must **not** require loading all 500 template definitions on initial load. Data fetched per level or per filter. |
| **Caching** | Taxonomy metadata (category/family labels, allowed values) may be cached; template list and detail may be cached with invalidation on registry change. |

---

## 9. Screen states summary

| State | Description | Breadcrumb | Primary action |
|-------|-------------|------------|----------------|
| **L1 Root** | Directory landing; category list or combined nav. | Page Templates | Select category (L2). |
| **L2 Category** | Family list for selected category. | Page Templates > {Category} | Select family (L3). |
| **L3 Family** | Template list for category + family. | Page Templates > {Category} > {Family} | Open template detail (L5) from row. |
| **L4 List** | Same as L3; may be paginated. | Same | Open template detail. |
| **L5 Detail** | Single template: metadata, sections, one-pager, preview, composition. | Page Templates > … > [Name] | Back to list; One-pager; Preview. |
| **Search** | Filtered list from search query. | Page Templates > Search: … | Open template detail from row. |

---

## 10. Filter model (summary)

- **Tree-driven filter**: Selecting category (L2) and family (L3) is equivalent to filter `template_category_class` = X and `template_family` = Y.
- **Explicit filter UI**: Optional filter bar (category, family, status, archetype) may set or override tree selection; URL reflects filter state.
- **Search**: Full-text or key search over name, internal_key, purpose_summary; results are a filtered list with same row shape as L4.

---

## 11. IA scenarios (test requirements)

The following scenarios must be supported by the IA (implementation will verify):

| Scenario | Steps | Expected |
|----------|--------|----------|
| **Browse by category** | Open Page Templates → select “Top Level Page Templates” → select “Home Page Templates”. | List shows templates with category=top_level, family=home. Breadcrumb: Page Templates > Top Level Page Templates > Home Page Templates. |
| **Browse by subfamily** | Same as above; “Home Page Templates” is the subfamily (L3). | Only templates in that category+family. |
| **Search** | Open Page Templates → search “services”. | Results list shows templates matching “services”; breadcrumb includes “Search: services” or “Search results”. |
| **Breadcrumb navigation** | From template detail, click breadcrumb “Home Page Templates”. | Return to list (L4) for Top Level + Home. |
| **Large-library pagination** | Select category+family that has 100+ templates. | List is paginated (e.g. 20 per page); total count shown; next/prev or page numbers. |
| **Detail from list** | From list row, click template name or “View”. | Template detail (L5) opens; breadcrumb shows full path including template name. |
| **One-pager from detail** | On template detail, click “One-pager” or doc link. | One-pager (or doc) opens for that template. |
| **Preview from detail** | On template detail, click “Preview”. | Preview view opens (when implemented); uses preview-safe data. |

---

## 12. Security and permissions

| Requirement | Rule |
|-------------|------|
| **Capability** | Directory browsing (list, detail, breadcrumb nav) is **capability-gated** (e.g. `aio_view_build_plans` or `aio_manage_templates`). |
| **Preview/detail** | Access to template detail and preview respects the same permission; no exposure of template metadata to users without template-management capability. |
| **Preview data** | No raw secret-bearing preview data; preview uses synthetic/safe data per large-scale-acf-lpagery-binding-contract. |

---

## 13. Cross-references

- **admin-screen-inventory.md**: Menu location, screen slug(s), capability; §2.1 page template directory and taxonomy.
- **page-template-category-taxonomy-contract.md**: template_category_class, template_family, hierarchy_role; category and family labels; directory grouping semantics.
- **template-library-scale-extension-contract.md**: 500 page template target; scale does not relax schema.
- **template-library-coverage-matrix.md**: Coverage and counts; directory must support browsing the full library.
- **build-plan-admin-ia-contract.md**: Build Plan entry points; template selection may link to directory; directory does not replace Build Plan workflow.
- **template-preview-and-dummy-data-contract.md**: Preview fidelity, synthetic data, required detail-screen metadata (§4.1), animation and omission in preview.
- **Spec §16**: One-pager purpose and linkage.

---

## 14. Revision history

| Version | Date | Change |
|---------|------|--------|
| 1 | Prompt 141 | Initial page template directory admin IA extension contract. |
