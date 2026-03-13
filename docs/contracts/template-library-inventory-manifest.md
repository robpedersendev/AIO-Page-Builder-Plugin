# Template Library Inventory Manifest and Batch Generation Strategy

**Spec**: §12 Section Template Registry; §13 Page Template Registry; §15.11 Reusability Rules; §58.2 Template Registry Versioning; §60.2 Deliverable Checklist Per Milestone; §60.4 Exit Criteria

**Upstream**: template-library-scale-extension-contract.md, template-library-coverage-matrix.md, section-template-category-taxonomy-contract.md, page-template-category-taxonomy-contract.md, cta-sequencing-and-placement-contract.md

**Status**: Governance artifact only. No actual section or page template creation; no UI implementation; no rendering code changes. This manifest **sequences** future section/page-template production into **controlled, measurable batches** and links each batch to coverage targets. Mass template growth remains **governed, version-aware, and traceable**. Each future batch must map back to category coverage, preview obligations, helper docs, and ACF/rendering rules.

---

## 1. Purpose and scope

This manifest:

- Breaks the expanded **250-section / 500-page** library into **concrete production batches** for later prompts.
- Defines **batch IDs**, **family scopes**, **count targets per batch**, **dependency order**, and **naming conventions**.
- Defines **completion checkpoints** and **milestone completion rules** so progress is measurable.
- Ensures **cross-dependencies** between section batches and page-template batches are explicit (e.g. page batches depend on sufficient sections in required families).
- Ensures **future prompts enhance** (do not replace) earlier prompts and this manifest; each batch adds to the library and fills the coverage matrix.

**Out of scope**: Actual template creation; preview or UI implementation. The goal is to **prevent architectural drift** and make later template-production tranches **clean and measurable**.

---

## 2. Naming systems

### 2.1 Batch ID format

| Asset type | Batch ID pattern | Example |
|------------|------------------|---------|
| Section templates | `SEC-{batch_index}` or `SEC-{purpose_family}-{batch_index}` | `SEC-01`, `SEC-hero-1`, `SEC-cta-1` |
| Page templates | `PT-{batch_index}` or `PT-{category_or_family}-{batch_index}` | `PT-01`, `PT-top_level-1`, `PT-services-1` |

**Rule**: Batch IDs are **stable**. Once a batch is defined in this manifest, its ID is not reused for a different scope. New batches get new IDs. Batch index may be numeric (01, 02, …) or family-prefixed for clarity.

### 2.2 Section internal_key and variation naming

| Rule | Requirement |
|------|-------------|
| **internal_key** | Per section-registry-schema; unique; stable. Pattern may follow `st_{purpose}_{variant}_{n}` or project convention (e.g. `st01_hero`, `st02_hero_compact`). |
| **variation_family_key** | Per section-template-category-taxonomy-contract §8; groups variants (e.g. `hero_primary`, `proof_cards`). |
| **Variant key** | Within a section, variant keys (e.g. `default`, `compact`, `media_left`) are from the section’s `variants` map. |

Naming must remain **deterministic** and **documented** per batch so that later batches do not collide.

### 2.3 Page template internal_key naming

| Rule | Requirement |
|------|-------------|
| **internal_key** | Per page-template-registry-schema; unique; stable. Pattern may follow `pt_{category}_{family}_{variant}_{n}` or project convention (e.g. `pt_top_home_01`, `pt_hub_services_full`). |
| **template_family** | Per page-template-category-taxonomy-contract §3; stable slug (home, services, locations, etc.). |
| **template_category_class** | One of top_level, hub, nested_hub, child_detail. |

Batch outputs must align with taxonomy so coverage matrix counts can be attributed to the correct class and family.

---

## 3. Section-library production batches

Batches are grouped so that **coverage matrix minimums** (template-library-coverage-matrix §2) can be met in a **dependency-sensible** order: opener and high-use families first, then CTA and supporting families, then remainder.

### 3.1 Section batch table

| Batch ID | Family scope (section_purpose_family) | Count target (min) | Notes / dependency |
|----------|----------------------------------------|--------------------|--------------------|
| SEC-01 | hero | 12 | Openers; style/layout variants. Foundation for page templates. |
| SEC-02 | proof | 18 | Trust, testimonials; density/format variants. |
| SEC-03 | offer, explainer | 14 + 14 | Value prop, process, steps. |
| SEC-04 | cta | 20 | CTA-classified; primary_cta ≥10, contact_cta ≥5, navigation_cta ≥5. **Required before** page batches that need CTA sections. |
| SEC-05 | faq, profile, stats | 10 + 8 + 8 | FAQ, profile, stats. |
| SEC-06 | listing, comparison | 14 + 8 | Directory, list, gallery; comparison. |
| SEC-07 | contact, legal, utility | 8 + 6 + 8 | Contact, legal, utility. |
| SEC-08 | timeline, related | 6 + 8 | Timeline, related content. |
| SEC-09 | (balance) | Remainder to 250 | Fill remaining minimums and spread; respect max share (25% per purpose family, 28% per category). Variation-family spread (§2.5). |

**Sum of minimums** in table aligns with coverage matrix §2.2 (154 minimum; remainder 96+ in SEC-09). **Dependency**: SEC-04 (CTA) must be complete (or sufficiently advanced) before page-template batches that require 3–5+ CTA sections per page (cta-sequencing-and-placement-contract).

### 3.2 Section batch completion checkpoint

A section batch is **complete** when:

- All templates in the batch scope are **created**, **valid** (section-registry-schema), and **registered**.
- **Taxonomy** (section_purpose_family, placement_tendency, cta_classification, variation_family_key where applicable) is set and valid.
- **Helper refs**, **field blueprint refs**, and **CSS contract refs** are populated per contract.
- **Preview dummy data** (or blueprint defaults) are defined per template-preview-and-dummy-data-contract so directory previews work.
- Counts for that batch’s purpose families are **recorded** in the batch-progress worksheet (§7).

---

## 4. Page-template production batches

Page batches are grouped by **template_category_class** and **template_family** so that coverage matrix minimums (§3) are met. Page templates **depend on** section templates: each page template’s `ordered_sections` must reference only **registered** section templates. So section batches (at least SEC-01 through SEC-04 and enough of SEC-05–SEC-08) must be **sufficient** before a page batch is produced.

### 4.1 Page batch table

| Batch ID | Category / family scope | Count target (min) | Notes / dependency |
|----------|--------------------------|--------------------|--------------------|
| PT-01 | top_level (general) | 20 | Entry, home, standalone. Uses hero, CTA, legal, contact sections. Depends: SEC-01, SEC-04, SEC-07. |
| PT-02 | top_level + home, about | 8 + 12 | Home templates (≥8), about (partial). Depends: SEC-01, SEC-02, SEC-04. |
| PT-03 | hub (general) | 30 | Hub pages; section mix. Depends: SEC-01, SEC-02, SEC-03, SEC-04, SEC-06. |
| PT-04 | hub + services, locations | 25 + 20 | Services hub, locations hub. Depends: SEC-01–SEC-06. |
| PT-05 | hub + products, offerings, directories | 20 + 15 + 15 | Products, offerings, directories. Depends: SEC-01–SEC-06. |
| PT-06 | nested_hub | 100 | Sub-hub pages across families. Depends: SEC-01–SEC-08. |
| PT-07 | child_detail + services, locations, products | 50 + 35 + 35 | Service/location/product detail pages. Depends: SEC-01–SEC-08. |
| PT-08 | child_detail + offerings, faq, events, profiles | 30 + 25 + 20 + 20 | Offer, FAQ, events, profiles. Depends: SEC-01–SEC-08. |
| PT-09 | child_detail + comparison, contact, informational | 12 + 12 + 25 | Comparison, contact, informational. Depends: SEC-01–SEC-08. |
| PT-10 | child_detail + privacy, terms, accessibility; balance | 6 + remainder | Legal/info pages; fill to 500. Respect max share (45% class, 22% family). |

**Sum** of count targets across PT-01–PT-10 meets or exceeds 500 and aligns with coverage matrix §3.2 and §3.3. **Dependency**: Every page batch depends on section library having enough sections in the required purpose families and **at least 20 CTA-classified sections** (SEC-04) so CTA sequencing rules can be satisfied.

### 4.2 Page batch completion checkpoint

A page batch is **complete** when:

- All page templates in the batch scope are **created**, **valid** (page-template-registry-schema), and **registered**.
- **ordered_sections** resolve to **registered** section templates only.
- **CTA and composition** rules (cta-sequencing-and-placement-contract) are satisfied: min CTA count by page class, bottom CTA, non-adjacency, non-CTA range.
- **Taxonomy** (template_category_class, template_family, hierarchy_role) is set and valid.
- **One-pager** (or one-pager ref) and **purpose_summary** are populated per spec §16 and coverage matrix.
- Counts for that batch’s class/family are **recorded** in the batch-progress worksheet (§7).

---

## 5. Cross-dependencies between section and page batches

| Dependency | Rule |
|------------|------|
| **Page on section** | Every page template’s `ordered_sections` must reference only **registered** section templates. So section batches that supply hero, proof, CTA, legal, contact, etc. must be **sufficient** before the page batch that uses them is produced. |
| **CTA sections first** | Page templates require 3–5+ CTA sections per page (by class) and a CTA section at the end (cta-sequencing-and-placement-contract). Therefore **SEC-04** (cta purpose family, 20 sections) must be **complete** (or nearly so) before **any** page batch that builds full pages with CTA sequencing. |
| **Opener sections** | PT-01, PT-02, PT-03 (top_level, hub) need hero/openers; **SEC-01** (hero) should be complete before those. |
| **No circular** | Section batches do **not** depend on page templates. Page batches depend on section batches. |

**Recommended order**: Complete section batches SEC-01 through SEC-04 (and optionally SEC-05–SEC-07) before starting page batches PT-01. Then proceed with page batches in order PT-01 → PT-10, with section batch SEC-09 (balance) run in parallel or after SEC-08 as needed to reach 250 sections and variation spread.

---

## 6. Milestone completion rules and count checkpoints

### 6.1 Milestone completion rules

| Milestone | Definition | Evidence |
|-----------|------------|----------|
| **Section library (250)** | Total section templates ≥ 250; all section_purpose_family minimums met; CTA minimums met; max share and variation spread met (template-library-coverage-matrix §6.1). | Coverage worksheet §8.1 filled; distribution report; validation report. |
| **Page library (500)** | Total page templates ≥ 500; all template_category_class and template_family minimums met; max share met; every page passes CTA/composition rules (template-library-coverage-matrix §6.1). | Coverage worksheet §8.2 filled; distribution report; CTA validation. |
| **Batch N complete** | All templates in batch N created, valid, registered; taxonomy and docs populated; batch row in batch-progress worksheet (§7) marked complete. | Batch-progress worksheet; checklist per §3.2 or §4.2. |

### 6.2 Count checkpoints

- **After each section batch**: Update section counts in coverage worksheet (§8.1) by section_purpose_family and CTA classification. Verify no single family exceeds 25% of **current** total.
- **After each page batch**: Update page counts in coverage worksheet (§8.2) by template_category_class and template_family. Verify no single class/family exceeds 45% / 22% of **current** total.
- **Final checkpoint**: All 12 completion thresholds in template-library-coverage-matrix §6.1 are met; acceptance evidence (§6.2) is produced.

---

## 7. Batch-progress worksheet (mock)

Later prompts use this worksheet to **mark completion by batch** and by family/count. Copy or reference this table in batch-generation prompts; fill “Actual” and “Done” as batches complete.

### 7.1 Section batch progress

| Batch ID | Family scope | Count target | Actual | Done |
|----------|--------------|--------------|--------|------|
| SEC-01 | hero | 12 | _____ | ☐ |
| SEC-02 | proof | 18 | _____ | ☐ |
| SEC-03 | offer, explainer | 28 | _____ | ☐ |
| SEC-04 | cta | 20 | _____ | ☐ |
| SEC-05 | faq, profile, stats | 26 | _____ | ☐ |
| SEC-06 | listing, comparison | 22 | _____ | ☐ |
| SEC-07 | contact, legal, utility | 22 | _____ | ☐ |
| SEC-08 | timeline, related | 14 | _____ | ☐ |
| SEC-09 | balance | to 250 total | _____ | ☐ |
| **Total sections** | | **≥ 250** | _____ | ☐ |

### 7.2 Page batch progress

| Batch ID | Scope | Count target | Actual | Done |
|----------|-------|--------------|--------|------|
| PT-01 | top_level (general) | 20 | _____ | ☐ |
| PT-02 | top_level + home, about | 20 | _____ | ☐ |
| PT-03 | hub (general) | 30 | _____ | ☐ |
| PT-04 | hub + services, locations | 45 | _____ | ☐ |
| PT-05 | hub + products, offerings, directories | 50 | _____ | ☐ |
| PT-06 | nested_hub | 100 | _____ | ☐ |
| PT-07 | child_detail + services, locations, products | 120 | _____ | ☐ |
| PT-08 | child_detail + offerings, faq, events, profiles | 95 | _____ | ☐ |
| PT-09 | child_detail + comparison, contact, informational | 49 | _____ | ☐ |
| PT-10 | child_detail + legal/info; balance | to 500 total | _____ | ☐ |
| **Total pages** | | **≥ 500** | _____ | ☐ |

### 7.3 Dependency and quality checklist

| Item | Done |
|------|------|
| SEC-01 through SEC-04 complete before PT-01 starts | ☐ |
| Section taxonomy (purpose_family, cta_classification, variation_family_key) set per batch | ☐ |
| Page taxonomy (template_category_class, template_family) set per batch | ☐ |
| All page templates pass CTA sequencing (cta-sequencing-and-placement-contract) | ☐ |
| Coverage matrix worksheet (§8) updated after each batch | ☐ |
| No duplicate or thin-clone templates counted | ☐ |

---

## 8. Enhancement-not-replacement discipline

Future template-production prompts **enhance** (do not replace) earlier prompts and this manifest.

| Rule | Requirement |
|------|-------------|
| **Reference this manifest** | Batch-generation prompts must **reference** this manifest (and template-library-coverage-matrix) and state which **batch ID(s)** they are fulfilling. |
| **Add, do not replace** | New templates are **added** to the registry. Existing templates are not removed or overwritten unless deprecation/replacement is explicit and documented. |
| **Traceability** | Each produced template (or batch output) should be traceable to a **batch ID** and **coverage matrix** cell (purpose family, category class, template_family) so completion can be measured. |
| **Versioning** | Registry versioning (§58.2) applies; batch-produced templates follow the same version and compatibility rules as hand-authored ones. |
| **Contracts unchanged** | Batch production does **not** relax semantic-seo-accessibility-extension-contract, smart-omission-rendering-contract, animation-support-and-fallback-contract, large-scale-acf-lpagery-binding-contract, or template-preview-and-dummy-data-contract. All produced templates must satisfy those contracts. |

---

## 9. Variation axes per batch (reminder)

When producing templates within a batch, **variation axes** (template-library-coverage-matrix §4) should be respected so the library has spread:

- **Style**: compact, full-width, card, list, etc.; no single style > 30% within a family.
- **Flow**: short vs long section count; linear vs branched.
- **Presentation**: text-heavy, media-heavy, balanced.
- **Emphasis**: hero-led, proof-led, offer-led, CTA-led; no single emphasis > 35% within a template_family.
- **Animation profile**: none, subtle, moderate (per animation-support-and-fallback-contract).
- **Proof density / CTA intensity**: spread across templates so CTA rules are satisfied without cloning.

Batch prompts should **plan** for this spread (e.g. “SEC-01: 12 hero sections across at least 3 variation_family_key values and 2–3 styles”) rather than produce 12 near-identical heroes.

---

## 10. Cross-references

- **template-library-coverage-matrix.md**: Minimum counts by section_purpose_family and template_category_class/template_family; completion thresholds (§6); worksheet (§8). This manifest **implements** the sequencing of how those counts are filled (batches).
- **template-library-scale-extension-contract.md**: 250/500 minimums; variation philosophy; category-coverage expectations.
- **section-template-category-taxonomy-contract.md**: section_purpose_family, cta_classification, variation_family_key.
- **page-template-category-taxonomy-contract.md**: template_category_class, template_family, hierarchy_role.
- **cta-sequencing-and-placement-contract.md**: Page-level CTA rules; section library must include enough CTA-classified sections (SEC-04).
- **template-preview-and-dummy-data-contract.md**: Preview and dummy data for produced templates; each batch must supply preview-safe data per contract.

---

## 11. Revision history

| Version | Date | Change |
|---------|------|--------|
| 1 | Prompt 144 | Initial template library inventory manifest and batch generation strategy. |
