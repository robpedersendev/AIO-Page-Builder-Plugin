# Section Template Directory Admin IA Extension Contract

**Spec**: §49.3 Screen Hierarchy; §49.4 Screen Entry Points; §49.6 Section Templates Screen; §62.10 Admin Screen Inventory Appendix; §12 Section Template Registry; §15 Helper Paragraph System

**Upstream**: section-template-category-taxonomy-contract.md, template-library-scale-extension-contract.md, template-library-coverage-matrix.md, admin-screen-inventory.md

**Status**: IA contract only. No screen implementation; no preview rendering implementation; no page-template directory in this contract. Directory browsing is **additive**. Sections remain **first-class registry objects** with helper docs and field ownership. The IA must remain clear at **250+ section templates**. Section browsing **emphasizes purpose-family, CTA classification, and variant families** for natural section reuse; it does not copy the page-template tree blindly.

---

## 1. Purpose and scope

This contract extends the admin **information architecture** to support **directory browsing** of section templates: category browsing, **purpose-family** browsing, **CTA-family** browsing, **variant-family** browsing, and section-detail entry points with preview access. It defines menu location, browse tree, breadcrumbs, filters, list/detail, helper-doc links, field blueprint summary links, and preview-link placement. It parallels the page-template directory approach (page-template-directory-ia-extension.md) while respecting **section-registry semantics** and **helper-documentation** linkages.

**Out of scope**: Actual screen implementation; preview rendering implementation; page-template directory. Directory browsing remains **capability-gated**; preview/detail access respects template-management permissions; no raw secret or unsafe preview content. Directory must **not** become a freeform builder or undocumented content catalog.

---

## 2. Menu location and entry point

### 2.1 Menu placement

| Rule | Value |
|------|--------|
| **Parent menu** | AIO Page Builder (top-level slug `aio-page-builder`). |
| **Submenu item** | “Section Templates” (or equivalent label). Single submenu entry for the **section template directory**. Distinct from “Page Templates” (page-template-directory-ia-extension.md). |
| **Screen slug** | Stable slug for the directory root, e.g. `aio-page-builder-section-templates`. Implementation may use a single screen with state (purpose/CTA/variant/section) or multiple screens; slug(s) must be documented in admin-screen-inventory. |
| **Capability** | Template-management capability (e.g. `aio_view_build_plans` or dedicated `aio_manage_templates`). Directory browsing is **capability-gated**. |
| **Spec alignment** | §49.3: “Templates screens remain globally accessible but are conceptually foundational.” §49.6: Section Templates screen includes searchable template table, category filter, status filter, version column, helper-doc access, compatibility summary, deprecation marker. |

### 2.2 Entry from other screens

- **Build Plan / composition**: When user chooses or orders sections (e.g. for a plan or composition), the section picker may **deep-link** into the section directory (e.g. to purpose family or section detail). Directory supports being the **source** of section selection without replacing Build Plan or composition workflows.
- **Page template detail**: When viewing a page template’s ordered sections, “View in section directory” may link to the section detail or purpose-family list in the section directory.
- **Dashboard / quick actions**: Optional “Browse section templates” link to directory root.
- **Helper docs**: Links from directory (section detail, list row) to helper paragraph and field blueprint summary; see §7.

---

## 3. Hierarchical browse tree structure

The directory **tree** is optimized for **section reuse**: **purpose-family** first, then **CTA classification** (for CTA-oriented families) or **variant family** (for variant grouping). It does **not** mirror the page-template category-class → family tree.

### 3.1 Tree levels (order of drill-down)

| Level | Name / concept | Source | Description |
|-------|----------------|--------|-------------|
| **L1** | Section Templates (root) | Directory root | Landing for section template browsing. Shows purpose-family level (L2) or combined nav. |
| **L2** | Purpose family | `section_purpose_family` (section-template-category-taxonomy-contract §2) | Hero, Proof, Offer, Explainer, Legal, Utility, Listing, Comparison, Contact, CTA, FAQ, Profile, Stats, Timeline, Related, Other. Labels are server-authoritative (e.g. “Hero” for `hero`, “CTA” for `cta`). |
| **L3** | CTA classification or Variant family | `cta_classification` (§5) or `variation_family_key` (§8) | For purpose families **cta** and **contact**: L3 = CTA classification (Primary CTA, Contact CTA, Navigation CTA, None). For **all** purpose families: L3 may alternatively be **Variant family** (e.g. “Hero primary”, “Proof cards”) when sections share a `variation_family_key`, or “All” when no variant grouping. So user can browse “CTA Sections > Primary CTA” or “Hero Sections > Hero primary”. |
| **L4** | Section option list | Filtered section list | List/card view of section templates matching L2 (+ L3). Each row/card is a **section option**; selecting one opens **section detail** (L5). |
| **L5** | Section detail | Single section | Detail screen for one section template: metadata, variants, field blueprint summary, helper-doc link, preview link, compatibility, placement tendency. |

### 3.2 Path expression (canonical)

**Canonical path** for a section option in the tree:

```
Section Templates > {PurposeFamilyLabel} > {CTAOrVariantLabel} > [Section Name / internal_key]
```

**Examples:**

```
Section Templates > Hero > Hero primary > [Hero – Default]
Section Templates > Hero > All > [Hero – Compact]
Section Templates > CTA > Primary CTA > [CTA – Signup]
Section Templates > Proof > Proof cards > [Testimonial – Standard]
Section Templates > FAQ > All > [FAQ – Accordion]
```

**Purpose family labels** (L2) are derived from `section_purpose_family` slug (e.g. `hero` → “Hero”, `cta` → “CTA”, `proof` → “Proof”). **CTA classification labels** (L3 when applicable): `primary_cta` → “Primary CTA”, `contact_cta` → “Contact CTA”, `navigation_cta` → “Navigation CTA”, `none` → “None”. **Variant family labels** (L3): from `variation_family_key` (e.g. `hero_primary` → “Hero primary”) or “All” when not grouped by variant. All labels are server-authoritative per section-template-category-taxonomy-contract.

### 3.3 Category, purpose, CTA, and variant grouping rules

| Rule | Requirement |
|------|-------------|
| **Purpose first** | Drill-down is **purpose family (L2) first**. This is the primary grouping for section reuse (hero, proof, CTA, etc.). |
| **L3 for CTA/contact** | When L2 is `cta` or `contact`, L3 **must** offer CTA classification (Primary CTA, Contact CTA, Navigation CTA, None) so “CTA family browsing” is explicit. |
| **L3 for variant family** | For any L2, L3 may offer **variant family** grouping: sections that share the same `variation_family_key` appear under that variant label (e.g. “Hero primary”). Sections with no or unique variation_family_key may appear under “All” or under a key equal to internal_key. |
| **Stable ordering** | Purpose family order: stable sort by slug (e.g. hero, proof, offer, explainer, legal, utility, listing, comparison, contact, cta, faq, profile, stats, timeline, related, other). CTA classification order: primary_cta, contact_cta, navigation_cta, none. Variant families: sort by variation_family_key. |
| **Other / uncategorized** | Sections with `section_purpose_family` = `other` or missing appear under “Other” at L2 so they are reachable. |

### 3.4 Alternative entry: CTA-family or placement view

- **CTA-family shortcut**: A secondary nav (e.g. “By CTA type”) may list “Primary CTA sections”, “Contact CTA sections”, “Navigation CTA sections” and link to filtered lists. Primary tree remains purpose → CTA/variant → list.
- **Placement tendency**: Optional filter or secondary grouping (Openers, Mid-page, CTA Ending, Legal footer, etc.) for composition-aware browsing; see §4.
- **Search**: Global search can jump to section detail or filtered list; breadcrumbs reflect purpose + CTA/variant of the result.

---

## 4. Search and filter expectations

### 4.1 Search

| Requirement | Description |
|-------------|-------------|
| **Scope** | Search applies to section **name**, `internal_key`, `purpose_summary`, `category`, and optionally `section_purpose_family` / `variation_family_key` labels. |
| **Result** | Search results are a **filtered section list** (same row/card shape as L4). Results indicate purpose family and CTA/variant so user can place the section in the tree. |
| **Breadcrumb** | Search results show breadcrumb: e.g. “Section Templates > Search: {query}” or “Section Templates > Search results”. |
| **Performance** | At 250+ sections, search is **server-side** and paginated or limited (e.g. top N matches); no requirement to load all sections client-side. |

### 4.2 Filters

| Filter | Source | Behavior |
|--------|--------|----------|
| **Purpose family** | `section_purpose_family` | Filters to one purpose family (hero, proof, cta, etc.). When applied, L3 shows CTA classification (for cta/contact) or variant families. |
| **CTA classification** | `cta_classification` | Filters to primary_cta, contact_cta, navigation_cta, none. Narrows list to CTA-oriented sections when combined with purpose. |
| **Variant family** | `variation_family_key` | Filters to sections sharing one variation_family_key. |
| **Placement tendency** | `placement_tendency` | Optional: opener, mid_page, cta_ending, legal_footer_adjacent, utility_any, related_any, other. |
| **Category** | `category` (schema required field) | Optional: hero_intro, trust_proof, cta_conversion, etc. |
| **Status** | `status` (draft, active, inactive, deprecated) | Optional on list view; default typically “active” or “all”. |

Filters are **additive** with the tree. Filter state may be reflected in the URL (query args) for shareable/deep links.

---

## 5. Row / list card data for section-option views (L4)

At L4 (section option list), each **row or card** displays a bounded set of fields so the list remains scannable and performant at 250+ sections.

### 5.1 Minimum row/card data

| Field | Source | Purpose |
|-------|--------|---------|
| **Section name / label** | `name` or `purpose_summary` (short) or `internal_key` | Primary identifier. |
| **internal_key** | Registry | Stable key; may be secondary or tooltip. |
| **Purpose family + CTA/variant** | `section_purpose_family`, `cta_classification` or `variation_family_key` | Context in tree. |
| **Status** | `status` | draft, active, inactive, deprecated. |
| **Placement tendency** | `placement_tendency` | Optional; opener, mid_page, cta_ending, etc. |
| **Link to detail** | URL to L5 (section detail) | Primary action for row. |

### 5.2 Optional row/card data

| Field | Source | When useful |
|-------|--------|-------------|
| Version | `version` or schema | For version filter/display. |
| Helper doc link | Helper paragraph ref | Direct link to helper from row. |
| Preview thumbnail / link | Preview ref | Link to preview (§7). |
| Variant count | Number of variants in schema | “N variants”. |
| Category | `category` | Schema category for compatibility. |

### 5.3 List/detail relationship

- **List (L4)** → **Detail (L5)**: Each row has a **primary action** (e.g. click, “View”) that opens the section detail screen for that section’s `internal_key`.
- **Detail (L5)** → **List (L4)**: Detail screen provides “Back to list” or “Back to [Purpose] > [CTA/Variant]” so user returns to the same list context.

---

## 6. Breadcrumb model

### 6.1 Breadcrumb structure

| Screen / state | Breadcrumb segments (example) |
|----------------|-------------------------------|
| Directory root (L1) | Section Templates |
| Purpose family selected (L2) | Section Templates > Hero |
| CTA or variant selected (L3) | Section Templates > Hero > Hero primary |
| Section list (L4) | Same as L3. |
| Section detail (L5) | Section Templates > Hero > Hero primary > [Section Name] |
| Search results | Section Templates > Search: “cta” (or “Search results”) |

### 6.2 Breadcrumb rules

- Each segment except the last is a **link** to that level.
- Last segment is current page (no link) or section name linking to detail if on list.
- Full path (purpose + CTA/variant) must be present when user arrived via tree so “Back” behavior is clear.

---

## 7. Links to section detail, helper docs, field blueprint summary, and preview

### 7.1 Section detail (L5) entry points

- **From list (L4)**: Row primary action → section detail.
- **From Build Plan / composition section picker**: “Browse in directory” → directory at purpose family or direct to section detail.
- **From page template detail** (ordered sections): “View in section directory” → section detail.
- **From search**: Result row → section detail.
- **From deep link**: URL with section `internal_key` (and optional purpose/CTA/variant for breadcrumb) → section detail.

### 7.2 Helper-doc and field blueprint links

| Link type | Placement | Requirement |
|-----------|-----------|-------------|
| **Helper paragraph** | Section detail (L5); optionally on list row | Link to the section’s **helper paragraph** (Spec §15). Helper is the consolidated editing and strategy guidance for that section. |
| **Field blueprint summary** | Section detail (L5) | Link or inline summary of the section’s **field blueprint** (field list, required/optional, token-compatible fields when applicable). Supports “what does this section need?” without opening ACF. |
| **Compatibility** | Section detail | Compatibility notes and deprecation marker per §49.6 and section-registry. |

### 7.3 Preview link

| Rule | Description |
|------|-------------|
| **Placement** | Section detail (L5); optionally thumbnail or “Preview” on list row (L4). |
| **Target** | Preview view for the section template. Preview **rendering** and **dummy-data** rules are defined in **template-preview-and-dummy-data-contract.md**: real section renderer with synthetic ACF data, realistic content by purpose family, preview-safe omission and animation. |
| **Safety** | Preview uses **synthetic dummy data** only (template-preview-and-dummy-data-contract, large-scale-acf-lpagery-binding-contract); no raw secret or unsafe content. |
| **Capability** | Preview access respects same capability as directory. |
| **Detail screen metadata** | Alongside preview, detail screen must show name, description, purpose family/CTA, placement, variants, field blueprint summary, and helper link per **template-preview-and-dummy-data-contract.md** §4.2. |

---

## 8. Pagination and performance expectations for large section libraries

### 8.1 Pagination

| Requirement | Description |
|-------------|-------------|
| **List (L4)** | Section list at L4 must support **pagination** when the number of sections in the selected purpose (+ CTA/variant) or search result exceeds a reasonable page size (e.g. 20–50). |
| **URL state** | Page number (and sort, if offered) in query args for shareable list state. |
| **Count** | Total count (e.g. “Showing 1–20 of 87”) available without loading all rows; server returns total and page slice. |

### 8.2 Performance

| Requirement | Description |
|-------------|-------------|
| **Lazy load** | Purpose family level (L2) may load **counts** or **first page** only; full section list loads when user drills to L4 or applies filters. |
| **No full dump** | At 250+ sections, the directory must **not** require loading all 250 section definitions on initial load. Data fetched per level or per filter. |
| **Caching** | Taxonomy metadata (purpose family, CTA classification, variant family labels) may be cached; section list and detail may be cached with invalidation on registry change. |

---

## 9. Screen states summary

| State | Description | Breadcrumb | Primary action |
|-------|-------------|------------|----------------|
| **L1 Root** | Directory landing; purpose family list. | Section Templates | Select purpose family (L2). |
| **L2 Purpose** | CTA or variant list for selected purpose. | Section Templates > {Purpose} | Select CTA or variant (L3). |
| **L3 CTA/Variant** | Section list for purpose + CTA/variant. | Section Templates > {Purpose} > {CTA/Variant} | Open section detail (L5) from row. |
| **L4 List** | Same as L3; may be paginated. | Same | Open section detail. |
| **L5 Detail** | Single section: metadata, variants, field blueprint summary, helper link, preview, compatibility. | Section Templates > … > [Name] | Back to list; Helper; Field summary; Preview. |
| **Search** | Filtered list from search query. | Section Templates > Search: … | Open section detail from row. |

---

## 10. Filter model (summary)

- **Tree-driven filter**: Selecting purpose (L2) and CTA/variant (L3) is equivalent to filter `section_purpose_family` = X and (optionally) `cta_classification` = Y or `variation_family_key` = Z.
- **Explicit filter UI**: Optional filter bar (purpose, CTA, variant, placement, category, status); URL reflects filter state.
- **Search**: Full-text or key search over name, internal_key, purpose_summary; results are a filtered list with same row shape as L4.

---

## 11. IA scenarios (test requirements)

The following scenarios must be supported by the IA:

| Scenario | Steps | Expected |
|----------|--------|----------|
| **Browse by purpose family** | Open Section Templates → select “Hero”. | L3 shows variant families (e.g. “Hero primary”) and/or “All”. List shows sections with purpose_family=hero. Breadcrumb: Section Templates > Hero. |
| **Browse by CTA family** | Open Section Templates → select “CTA” → select “Primary CTA”. | List shows sections with purpose_family=cta and cta_classification=primary_cta. Breadcrumb: Section Templates > CTA > Primary CTA. |
| **Browse by variant family** | Open Section Templates → select “Proof” → select “Proof cards”. | List shows sections with purpose_family=proof and variation_family_key=proof_cards (or equivalent). |
| **Search** | Open Section Templates → search “testimonial”. | Results list shows sections matching “testimonial”; breadcrumb includes “Search: testimonial” or “Search results”. |
| **Breadcrumb navigation** | From section detail, click breadcrumb “Hero”. | Return to list (L4) for Hero (or L2 Hero with L3 to choose). |
| **Large-library pagination** | Select purpose+variant that has 80+ sections. | List is paginated (e.g. 20 per page); total count shown; next/prev or page numbers. |
| **Detail from list** | From list row, click section name or “View”. | Section detail (L5) opens; breadcrumb shows full path including section name. |
| **Helper doc from detail** | On section detail, click “Helper” or doc link. | Helper paragraph (or doc) opens for that section. |
| **Preview from detail** | On section detail, click “Preview”. | Preview view opens (when implemented); uses preview-safe data. |

---

## 12. Security and permissions

| Requirement | Rule |
|-------------|------|
| **Capability** | Directory browsing (list, detail, breadcrumb nav) is **capability-gated** (e.g. `aio_view_build_plans` or `aio_manage_templates`). |
| **Preview/detail** | Access to section detail and preview respects the same permission. |
| **Preview data** | No raw secret or unsafe preview content; preview uses synthetic/safe data per large-scale-acf-lpagery-binding-contract. |

---

## 13. Cross-references

- **admin-screen-inventory.md**: Menu location, screen slug(s), capability; §2.1 section template directory and taxonomy.
- **section-template-category-taxonomy-contract.md**: section_purpose_family, placement_tendency, cta_classification, variation_family_key; directory grouping semantics; admin directory and preview (§9.2).
- **template-library-scale-extension-contract.md**: 250 section target; scale does not relax schema.
- **template-library-coverage-matrix.md**: Section coverage and counts; directory must support browsing the full library.
- **page-template-directory-ia-extension.md**: Parallel structure (tree, list/detail, breadcrumbs, pagination); section directory is distinct and purpose/CTA/variant-oriented.
- **template-preview-and-dummy-data-contract.md**: Preview fidelity, synthetic data by purpose family, required detail-screen metadata (§4.2), animation and omission in preview.
- **Spec §15**: Helper paragraph system; linkage from directory to helper docs.
- **large-scale-acf-lpagery-binding-contract.md**: Preview dummy-data and preview-safe fallbacks for section preview.

---

## 14. Revision history

| Version | Date | Change |
|---------|------|--------|
| 1 | Prompt 142 | Initial section template directory admin IA extension contract. |
