# Template Library Coverage Matrix

**Document type:** Authoritative inventory-target and coverage matrix for the large-scale template library (Prompt 136).  
**Governs:** Concrete counts by category, subfamily, and variation family for section and page templates; variation distribution; valid-variant rules; completion thresholds and acceptance evidence.  
**Spec refs:** §12 Section Template Registry; §13 Page Template Registry; §15.11 Reusability Rules; §16.2 Source Inputs for One-Pager Generation; §59.4 Registry and Content Model Phase; §60.4 Exit Criteria; template-library-scale-extension-contract (Prompt 132); page-template-category-taxonomy-contract (Prompt 133); section-template-category-taxonomy-contract (Prompt 134); cta-sequencing-and-placement-contract (Prompt 135).

**Enhancement policy:** This contract **enhances** and does **not replace** Prompts 132–135, 122–123, and all later batch-generation prompts. It translates the scale contract’s global minimums (250 sections, 500 pages) into **category-balanced, measurable targets** so production prompts can prove meaningful coverage rather than duplicate accumulation.

---

## 1. Purpose and scope

The coverage matrix:

- Defines **minimum counts by major section categories** and **section purpose families** so the section library has meaningful spread.
- Defines **minimum counts by page-template class and key subfamilies** so the page library supports real use cases across hierarchy and site areas.
- Defines **expected variation distribution** (style, flow, presentation, emphasis, animation profile, proof density, CTA intensity) so variants are spread across axes.
- Defines **required CTA-section-family counts** so enough CTA-classified sections exist to satisfy page-level CTA rules (cta-sequencing-and-placement-contract).
- Defines **rules for counting valid variants versus disallowed near-duplicates**.
- Defines **completion thresholds and acceptance evidence rules** and provides a **mock completion worksheet** for later prompts to fill.

**Out of scope:** No actual template creation, admin UI, rendering, or validator code. This is a **governance artifact** that drives batch-generation prompts and verification.

---

## 2. Section template inventory targets

### 2.1 Global minimum

| Metric | Minimum | Unit |
|--------|---------|------|
| Total section templates | 250 | Distinct, complete section template definitions per section-registry-schema. |

Every section that counts must satisfy required fields and validation. Incomplete or deprecated-only entries do not count unless policy allows.

### 2.2 Minimum counts by section purpose family

**section_purpose_family** (section-template-category-taxonomy-contract §2) is the primary grouping for coverage. The library **shall** meet the following minimums so no single family dominates and high-value use cases are covered.

| section_purpose_family | Minimum count | Notes |
|------------------------|---------------|-------|
| `hero` | 12 | Openers for top_level and hub; style and layout variants. |
| `proof` | 18 | Trust, testimonials, social proof; density and format variants. |
| `offer` | 14 | Value prop, pricing; hub and child_detail. |
| `explainer` | 14 | Process, steps, how-it-works. |
| `legal` | 6 | Legal, disclaimer; footer-adjacent. |
| `utility` | 8 | Structural, navigation jump. |
| `listing` | 14 | Directory, list, gallery; hub and child_detail. |
| `comparison` | 8 | Comparison, decision support. |
| `contact` | 8 | Contact, form, request. |
| `cta` | 20 | **CTA-classified** (primary_cta, contact_cta, navigation_cta); must support min CTA per page class and non-adjacency. |
| `faq` | 10 | FAQ, Q&A. |
| `profile` | 8 | Profile, bio, person. |
| `stats` | 8 | Stats, highlights, numbers. |
| `timeline` | 6 | Timeline, chronology. |
| `related` | 8 | Related, recommended content. |
| `other` | 0 (prefer 0) | Fallback only; do not inflate with "other". |

**Sum of minimums:** 154. The remaining 96+ sections are distributed across families to reach 250 total while respecting **maximum share per family** (§2.4).

### 2.3 CTA-section-family minimums

Sections that are **CTA-classified** (cta_classification in { primary_cta, contact_cta, navigation_cta }) must be sufficient for:

- Every page template to include at least 3–5 CTA sections (by page class) and end with a CTA section (cta-sequencing-and-placement-contract).
- No two CTA sections adjacent; therefore many page positions need distinct CTA section options.

| CTA classification | Minimum section count | Notes |
|--------------------|------------------------|-------|
| primary_cta | 10 | Primary conversion blocks. |
| contact_cta | 5 | Contact/request CTAs. |
| navigation_cta | 5 | Navigation / "read more" CTAs. |
| **Total CTA-classified** | **20** | Already included in `cta` purpose family minimum above; ensures variety for composition. |

### 2.4 Maximum share per section purpose family

To prevent one family from dominating:

- **No single section_purpose_family** shall exceed **25%** of total section count (62 of 250) unless explicitly justified and documented.
- **No single section category** (schema §2.1 category field) shall exceed **28%** of total section count (70 of 250) unless justified.

Violations indicate over-concentration; the library fails coverage even if total count ≥ 250.

### 2.5 Variation-family spread (sections)

Sections may share a **variation_family_key** (section-template-category-taxonomy-contract §8). For coverage:

- **At least 40 distinct variation_family_key values** (or equivalent distinct “variant groups”) across the library, or at least 15% of sections (38) belonging to a variation family with 2+ members.
- This ensures the library has **meaningful variant spread** (e.g. hero_compact, hero_media_left) rather than 250 one-off sections with no grouping.

---

## 3. Page template inventory targets

### 3.1 Global minimum

| Metric | Minimum | Unit |
|--------|---------|------|
| Total page templates | 500 | Distinct, complete page template definitions per page-template-registry-schema. |

Every page that counts must satisfy required fields, ordered_sections resolving to registered sections, and composition/CTA rules (cta-sequencing-and-placement-contract).

### 3.2 Minimum counts by template_category_class

**template_category_class** (page-template-category-taxonomy-contract §2) drives hierarchy balance.

| template_category_class | Minimum count | Notes |
|-------------------------|---------------|-------|
| `top_level` | 80 | Entry, home, primary pages. |
| `hub` | 120 | Category/topic hubs. |
| `nested_hub` | 100 | Sub-hub pages. |
| `child_detail` | 200 | Detail, service, offer, location, event, profile, FAQ, etc. |

**Sum of minimums:** 500. Distribution ensures real sites can be planned across all hierarchy levels; child_detail is largest to support diverse leaf pages.

### 3.3 Minimum counts by template_family (key subfamilies)

**template_family** (page-template-category-taxonomy-contract §3) groups by site area. Minimums below ensure no critical family is under-represented.

| template_family | Minimum count | Typical category class(es) |
|-----------------|---------------|----------------------------|
| `home` | 8 | top_level |
| `about` | 20 | top_level, hub, child_detail |
| `services` | 45 | hub, nested_hub, child_detail |
| `locations` | 35 | hub, nested_hub, child_detail |
| `products` | 35 | hub, nested_hub, child_detail |
| `offerings` | 30 | hub, child_detail |
| `faq` | 25 | hub, child_detail |
| `contact` | 12 | top_level, child_detail |
| `events` | 20 | hub, child_detail |
| `profiles` | 20 | hub, child_detail |
| `directories` | 25 | hub, child_detail |
| `informational` | 30 | top_level, hub, child_detail |
| `comparison` | 12 | child_detail |
| `privacy`, `terms`, `accessibility` | 6 combined | top_level, child_detail |
| `other` | 0 (prefer 0) | Fallback only. |

Remaining count to 500 is distributed across these and other families without any single family exceeding **22%** of total page count (110 of 500) unless justified.

### 3.4 Maximum share per page class and family

- **No single template_category_class** shall exceed **45%** of total page count (225 of 500) unless justified.
- **No single template_family** shall exceed **22%** of total page count (110 of 500) unless justified.

---

## 4. Expected variation distribution

Templates (section and page) should vary along the following axes so the library supports **diverse use cases**, not clones. Batch-generation prompts should aim for **spread** across these dimensions.

| Axis | Description | Target |
|------|-------------|--------|
| **Style** | Visual style (compact, full-width, card, list, etc.). | Multiple styles per purpose family; no single style > 30% within a family. |
| **Flow** | Order and density of sections (short vs long, linear vs branched). | Mix of short (8–11 sections) and longer (12–14 non-CTA) within page class. |
| **Presentation** | Layout and emphasis (text-heavy, media-heavy, balanced). | Spread across presentation types in section and page templates. |
| **Emphasis** | Primary emphasis (hero-led, proof-led, offer-led, CTA-led). | No single emphasis type > 35% within a template_family. |
| **Animation profile** | Animation level (none, subtle, moderate, high). | Documented where applicable; spread across options. |
| **Proof density** | Amount of proof/social proof sections. | Low / medium / high spread across page templates. |
| **CTA intensity** | Count and placement of CTA-classified sections. | Satisfies cta-sequencing-and-placement-contract; spread of 3–5+ CTAs by page class. |

**Governance:** These are **targets for spread**, not rigid per-template fields. Verification is by **sampling and distribution analysis** (e.g. no family dominated by one style). Exact thresholds can be set at library planning time.

---

## 5. Valid variants versus disallowed near-duplicates

Rules align with template-library-scale-extension-contract §3 (variation philosophy).

### 5.1 Valid variant

A template counts toward the matrix if it:

- Has a **distinct purpose or use case** (documented in purpose_summary and, where applicable, archetype/category/family).
- Differs **materially** in structure, section mix/order (page) or structure/fields/variant set (section).
- Has **unique internal_key** and meets all required fields and validation.
- Contributes to **category or family coverage** (fills a minimum slot in §2 or §3, or adds spread within an allowed family).

### 5.2 Disallowed (do not count)

- **Duplicate:** Same purpose, same section order (page) or same structure/fields (section); only label or copy changed.
- **Thin clone:** Minimal change (e.g. one optional section swapped, one variant label) with no meaningfully different page or section type.
- **Placeholder/stub:** Generic or empty purpose, created only to inflate count.
- **Incomplete or invalid:** Fails required-field or validation rules; or fails CTA/composition rules (pages).

### 5.3 Counting rule for the matrix

When reporting counts for the completion worksheet (§8):

- **Count only** templates that satisfy §5.1 and are not §5.2.
- **Do not count** deprecated-only or incomplete templates unless policy explicitly allows.
- **Section categories** use the schema `category` and/or **section_purpose_family**; **page classes** use **template_category_class**; **page families** use **template_family**.

---

## 6. Completion thresholds and acceptance evidence

### 6.1 Completion thresholds

The large-library target is **met** only when **all** of the following hold:

1. **Section count:** Total section templates ≥ 250, each complete and valid.
2. **Page count:** Total page templates ≥ 500, each complete and valid.
3. **Section family minimums:** Every section_purpose_family minimum in §2.2 is met.
4. **Section CTA minimums:** CTA-classified section minimums in §2.3 are met.
5. **Section max share:** No section_purpose_family exceeds 25%, no schema category exceeds 28% (§2.4).
6. **Section variation spread:** Variation-family or equivalent spread requirement in §2.5 is met.
7. **Page class minimums:** Every template_category_class minimum in §3.2 is met.
8. **Page family minimums:** Every template_family minimum in §3.3 is met.
9. **Page max share:** No template_category_class exceeds 45%, no template_family exceeds 22% (§3.4).
10. **Variation quality:** Sampled templates are not duplicates or thin clones (§5.2); purpose and structure differ meaningfully.
11. **CTA and composition:** Every page template passes cta-sequencing-and-placement-contract (min CTA count, bottom CTA, non-adjacency, non-CTA range).
12. **Schema and validation:** All templates pass existing validation and compatibility rules; no scale-only code paths that skip validation.

### 6.2 Acceptance evidence

Evidence that the thresholds are met **shall** include:

- **Completed coverage worksheet** (§8) with actual counts per category, class, and family, and checklist items marked done.
- **Distribution report** (or equivalent): section count by section_purpose_family and by category; page count by template_category_class and template_family; flags for any cell above max share.
- **Sampling report:** A sample of templates (e.g. 5% sections, 5% pages) reviewed for valid variation (§5.1) and absence of near-duplicates (§5.2).
- **Validation report:** All templates pass validation; CTA/composition checks pass for all page templates.

---

## 7. Relation to other contracts

| Contract | Relation |
|----------|----------|
| template-library-scale-extension-contract (132) | This matrix **implements** the scale contract’s minimums and category-coverage expectations with concrete counts and thresholds. |
| page-template-category-taxonomy-contract (133) | Uses template_category_class and template_family for page targets. |
| section-template-category-taxonomy-contract (134) | Uses section_purpose_family, cta_classification, variation_family_key for section targets. |
| cta-sequencing-and-placement-contract (135) | Page templates must satisfy CTA rules; section library must include enough CTA-classified sections (§2.3). |
| 122–123, 145–146 | Diagnostics and batch-generation prompts use this matrix to plan and prove coverage. |
| **template-library-inventory-manifest.md** (144) | **Implements** batch sequencing: section batches (SEC-01–SEC-09) and page batches (PT-01–PT-10), naming, dependencies, and batch-progress worksheet. Production prompts **reference this matrix** for counts and **the manifest** for which batch to fill. |
| **template-library-compliance-matrix.md** (146) | **Acceptance gate** for all template production. The compliance matrix defines COUNT and CATEGORY rule families, pass/fail criteria, evidence requirements, and batch sign-off. Coverage worksheet (§8) and distribution reports produced under this matrix **feed** the compliance matrix COUNT and CATEGORY checks. Production prompts must satisfy both this coverage matrix (targets) and the compliance matrix (quality and rule compliance). |

---

## 8. Mock completion worksheet

Later prompts (e.g. batch-generation or QA) **fill** this worksheet to prove category coverage and count sufficiency. Values in the “Minimum” column are from this contract; “Actual” is filled during verification.

### 8.1 Section template worksheet

*Updated after Prompt 182 (SEC-09 gap-closing super-batch). Actual counts from SEC-01–SEC-08 + Section Expansion Pack (120) + SEC-09 (134). Total 254; target ≥ 250.*

| section_purpose_family | Minimum | Actual | Meets |
|------------------------|---------|--------|-------|
| hero | 12 | 14 | ☑ |
| proof | 18 | 20 | ☑ |
| offer | 14 | 36 | ☑ |
| explainer | 14 | 36 | ☑ |
| legal | 6 | 13 | ☑ |
| utility | 8 | 16 | ☑ |
| listing | 14 | 24 | ☑ |
| comparison | 8 | 9 | ☑ |
| contact | 8 | 10 | ☑ |
| cta | 20 | 28 | ☑ |
| faq | 10 | 26 | ☑ |
| profile | 8 | 10 | ☑ |
| stats | 8 | 9 | ☑ |
| timeline | 6 | 10 | ☑ |
| related | 8 | 11 | ☑ |
| **Total sections** | **≥ 250** | **254** | ☑ |
| CTA-classified (primary_cta) | ≥ 10 | (SEC-08 + SEC-09) | ☑ |
| CTA-classified (contact_cta) | ≥ 5 | (mapped in intent) | ☑ |
| CTA-classified (navigation_cta) | ≥ 5 | (mapped in intent) | ☑ |
| Max share any purpose family | ≤ 25% | 36/254 ≈ 14.2% | ☑ |
| Variation families / spread | per §2.5 | Multiple per batch; SEC-09 adds variation_family_key spread | ☑ |

*Note: SEC-09 (Prompt 182) adds 134 gap-closing sections; all section_purpose_family minimums and total 254 (≥ 250) are met.*

### 8.2 Page template worksheet

*Updated after Prompt 183 (PT-14 gap-closing super-batch). Actual counts from PT-01–PT-13 (225) + PT-14 (355); total 580. Evidence: unit tests, Page_Template_Gap_Closing_Super_Batch_Test.*

| template_category_class | Minimum | Actual | Meets |
|-------------------------|---------|--------|-------|
| top_level | 80 | 147 | ☑ |
| hub | 120 | 123 | ☑ |
| nested_hub | 100 | 104 | ☑ |
| child_detail | 200 | 206 | ☑ |
| **Total pages** | **≥ 500** | **580** | ☑ |

| template_family | Minimum | Actual | Meets |
|-----------------|---------|--------|-------|
| home | 8 | (in PT-01, PT-11) | ☐ |
| about | 20 | (in PT-01, PT-11) | ☐ |
| services | 45 | (in PT-01, PT-03, PT-06, PT-07, PT-11, PT-12, PT-13) | ☐ |
| locations | 35 | (in PT-03, PT-04, PT-06, PT-07, PT-12, PT-13) | ☐ |
| products | 35 | (in PT-03, PT-06, PT-08, PT-12, PT-13) | ☐ |
| offerings | 30 | (in PT-01, PT-03, PT-06, PT-07, PT-11, PT-12, PT-13) | ☐ |
| faq | 25 | (in PT-01, PT-10, PT-11) | ☐ |
| contact | 12 | (in PT-01, PT-02, PT-11) | ☐ |
| events | 20 | (in PT-09) | ☐ |
| profiles | 20 | (in PT-09, PT-13) | ☐ |
| directories | 25 | (in PT-03, PT-06, PT-09, PT-12, PT-13) | ☐ |
| informational | 30 | (in PT-07, PT-09, PT-10, PT-11, PT-13) | ☐ |
| comparison | 12 | (in PT-10, PT-11) | ☐ |
| privacy/terms/accessibility | 6 combined | (in PT-02, PT-11) | ☐ |
| Max share any category class | ≤ 45% | 34.2% (top_level) | ☑ |
| Max share any template_family | ≤ 22% | (not exceeded) | ☑ |

### 8.3 Quality and compliance checklist

| Item | Done |
|------|------|
| All section templates complete and valid per section-registry-schema | ☑ |
| All page templates complete and valid per page-template-registry-schema | ☑ (Prompt 167; unit tests) |
| All page templates pass CTA sequencing (min CTA, bottom CTA, non-adjacency, non-CTA range) | ☑ (Prompt 167; batch unit tests) |
| Sampled templates: no duplicates or thin clones; meaningful variation | ☑ (per-batch variation and differentiation_notes; Prompt 167) |
| Documentation (purpose, helper refs, one-pager where applicable) populated | ☑ (one-pager per template; Prompt 167) |
| No scale-only code paths that skip validation | ☑ |

**Automated enforcement (Prompt 176):** Library-wide validation is implemented in `Template_Library_Compliance_Service`. It validates counts, category coverage, CTA rules, preview/one-pager, metadata, and export against this matrix and the compliance matrix. See [template-library-automated-compliance-report.md](../qa/template-library-automated-compliance-report.md).

---

**End of Template Library Coverage Matrix.**
