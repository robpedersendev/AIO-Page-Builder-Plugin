# Page Template Batch Validation Report (Prompt 167)

**Document type:** Validation and reconciliation report for page-template batches (Prompts 155–166).  
**Spec refs:** §13 Page Template Registry; §16 One-Pager System; §56.2 Unit Test Scope; §60.4 Exit Criteria; §60.5 Acceptance Test Requirements.  
**Contracts:** template-library-coverage-matrix.md, template-library-inventory-manifest.md, cta-sequencing-and-placement-contract.md, template-library-compliance-matrix.md.

---

## 1. Executive summary

- **Total page templates (actual):** 225 across 12 batches (PT-01, PT-02, PT-03, PT-04, PT-06, PT-07, PT-08, PT-09, PT-10, PT-11, PT-12, PT-13). PT-05 is not implemented in code; geographic and product/offering/directory hubs are covered by PT-03, PT-04, and PT-12.
- **Validation:** All 194 page-template-related unit tests pass (schema, CTA rules, one-pager, preview metadata, differentiation notes, export fragment, hierarchy metadata). No corrective code changes were required for CTA or schema compliance.
- **Progress toward 500:** 225 / 500 (45%). Gaps are explicit in §4 and in the updated coverage matrix and inventory manifest.
- **Category distribution:** top_level 77, hub 43, nested_hub 29, child_detail 76. Max share: top_level 34.2% (below 45% cap); no single class exceeds 45%.
- **CTA and one-pager:** All batches enforce min CTA by class (top_level ≥3, hub/nested_hub ≥4, child_detail ≥5), last section CTA, no adjacent CTAs, and non-CTA count 8–14. One-pager (page_purpose_summary and where applicable page_flow_explanation, cta_direction_summary) and preview_metadata (synthetic only) are present and validated by tests.

---

## 2. Batch-level counts and compliance

| Batch ID | Scope | Actual count | template_category_class | CTA rule | One-pager | Preview | Export |
|----------|-------|--------------|--------------------------|----------|-----------|---------|--------|
| PT-01 | top_level (general) | 12 | top_level | ≥3, last, no adjacent, 8–14 non-CTA | ☑ | synthetic | ☑ |
| PT-02 | top_level (legal/utility) | 12 | top_level | ≥3, last, no adjacent, 8–14 non-CTA | ☑ | synthetic | ☑ |
| PT-03 | hub (general) | 13 | hub | ≥4, last, no adjacent, 8–14 non-CTA | ☑ | — | ☑ |
| PT-04 | hub (geographic) | 14 | hub | ≥4, last, no adjacent, 8–14 non-CTA | ☑ | — | ☑ |
| PT-06 | nested_hub | 18 | nested_hub | ≥4, last, no adjacent, 8–14 non-CTA | ☑ | — | ☑ |
| PT-07 | child_detail (services, offerings, locations, informational) | 19 | child_detail | ≥5, last, no adjacent, 8–14 non-CTA | ☑ | synthetic | ☑ |
| PT-08 | child_detail (products) | 13 | child_detail | ≥5, last, no adjacent, 8–14 non-CTA | ☑ | synthetic | ☑ |
| PT-09 | child_detail (profiles, directories, events, informational) | 14 | child_detail | ≥5, last, no adjacent, 8–14 non-CTA | ☑ | synthetic | ☑ |
| PT-10 | top_level (educational, resource, authority, comparison, faq, buyer_guide, informational) | 12 | top_level | ≥3, last, no adjacent, 8–14 non-CTA | ☑ | — | ☑ |
| PT-11 | top_level variant expansion | 41 | top_level | ≥3, last, no adjacent, 8–14 non-CTA | ☑ | — | ☑ |
| PT-12 | hub + nested_hub variant expansion | 27 | hub (16), nested_hub (11) | ≥4, last, no adjacent, 8–14 non-CTA | ☑ | synthetic | ☑ |
| PT-13 | child_detail variant expansion | 30 | child_detail | ≥5, last, no adjacent, 8–14 non-CTA | ☑ | synthetic | ☑ |
| **Total** | | **225** | | All pass | ☑ | | ☑ |

**Evidence:** Unit test runs for Top_Level_Marketing_Page_Template_Test, Top_Level_Legal_Utility_Page_Template_Test, Top_Level_Educational_Resource_Authority_Page_Template_Test, Top_Level_Variant_Expansion_Page_Template_Test, Hub_Page_Template_Test, Geographic_Hub_Page_Template_Test, Nested_Hub_Page_Template_Test, Hub_Nested_Hub_Variant_Expansion_Page_Template_Test, Child_Detail_Page_Template_Test, Child_Detail_Product_Page_Template_Test, Child_Detail_Profile_Entity_Page_Template_Test, Child_Detail_Variant_Expansion_Page_Template_Test, Page_Template_Schema_Test, Page_Template_Registry_Test — 194 tests, 18,793 assertions, all passing.

---

## 3. Category and family distribution (actual)

### 3.1 By template_category_class

| template_category_class | Minimum (coverage matrix §3.2) | Actual | Meets | Share of 225 |
|-------------------------|---------------------------------|--------|-------|--------------|
| top_level | 80 | 77 | ☐ | 34.2% |
| hub | 120 | 43 | ☐ | 19.1% |
| nested_hub | 100 | 29 | ☐ | 12.9% |
| child_detail | 200 | 76 | ☐ | 33.8% |
| **Total pages** | **≥ 500** | **225** | ☐ | — |

No template_category_class exceeds 45% of current total. Remaining gap to 500: 275 templates; class minimums short by top_level 3, hub 77, nested_hub 71, child_detail 124.

### 3.2 By template_family (summary)

Family counts are derived from batch scope and definition metadata; exact per-family totals would require full registry iteration. Current batches contribute to: home, about, faq, contact, services, offerings, privacy, terms, accessibility, support, disclosure, trust, utility, resource, authority, comparison, buyer_guide, informational, products, directories, profiles, events, and geographic families (service_area, regional, city_directory, location_overview, coverage_listing, neighborhood, campus, etc.). Template-library-coverage-matrix §8.2 is updated with class totals; family minimums remain to be filled as batches expand.

---

## 4. Validation findings

### 4.1 Schema and taxonomy

- **Required fields:** All definitions in all batches include required page-template schema fields (internal_key, name, purpose_summary, archetype, ordered_sections, section_requirements, compatibility, one_pager, version, status, default_structural_assumptions, endpoint_or_usage_notes). Verified by unit tests per batch.
- **template_category_class:** All definitions use one of top_level, hub, nested_hub, child_detail. Allowed template_family values are batch-scoped and asserted in tests.
- **hierarchy_hints:** hub/nested_hub batches set hierarchy_role (hub, nested_hub); child_detail batches set hierarchy_role = leaf; parent_family_compatibility present for nested_hub and child_detail where applicable.

### 4.2 CTA sequencing (cta-sequencing-and-placement-contract)

- **CTA_COUNT:** All top_level templates have ≥3 CTA-classified sections; all hub and nested_hub have ≥4; all child_detail have ≥5. Verified by batch unit tests.
- **CTA_BOTTOM:** Last section in ordered_sections is CTA-classified for every template. Verified.
- **CTA_ADJACENT:** No template has two adjacent CTA-classified sections. Verified.
- **CTA_RANGE:** Non-CTA section count is between 8 and 14 (inclusive) for all templates. Verified.

### 4.3 One-pager and preview

- **One-pager:** Every template has one_pager with non-empty page_purpose_summary. Where applicable, page_flow_explanation and cta_direction_summary are present. Verified by tests.
- **Preview:** Batches that declare preview_metadata use synthetic-only preview data. No production data or secrets in preview inputs (contract PREVIEW-2).
- **Differentiation/variation notes:** Variant expansion batches (PT-11, PT-12, PT-13) and others where applicable include non-empty differentiation_notes or variation_family; validated by tests.

### 4.4 Export and registry integrity

- **Export fragment:** For every definition, Registry_Export_Fragment_Builder::for_page_template() produces a fragment with object_type, object_key, and payload. Verified by unit tests.
- **Section requirements:** section_requirements keys match ordered_sections section_key values; required flag present. Verified.

### 4.5 Corrective changes

- **None required.** All 225 templates pass schema, CTA, one-pager, preview, and export checks. No narrow corrective diffs were applied to page-template, one-pager, or preview definition files.

---

## 5. Gaps and next steps

- **Count gap:** 225 vs 500 target. Need 275 more page templates, with emphasis on hub (+77), nested_hub (+71), and child_detail (+124) to meet class minimums, plus top_level (+3) and family minimums per coverage matrix §3.3.
- **PT-05:** Not implemented; manifest PT-05 (hub products, offerings, directories) is partially covered by PT-03 and PT-12. Future prompts may add a dedicated PT-05 batch or continue to grow PT-12/PT-13-style variant batches.
- **Family minimums:** Coverage matrix §8.2 template_family rows remain to be filled with exact counts once a full registry aggregation is available or batches are expanded to meet each family minimum explicitly.
- **Library-browsing UI:** This validation pass gates readiness for admin directory/preview screens; counts and compliance are documented so that when UI is implemented, the library is structurally compliant and traceable.

---

## 6. Revision history

| Version | Date | Change |
|---------|------|--------|
| 1 | Prompt 167 | Initial page-template batch validation report; 12 batches, 225 templates; all tests pass; manifests and matrices updated. |
