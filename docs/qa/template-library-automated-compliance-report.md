# Template Library Automated Compliance Report (Prompt 176)

**Document type:** How to run and interpret the automated template-library compliance pass.  
**Spec refs:** §14.3 Allowed Section Ordering; §14.4 Invalid Combination Handling; §48.1 General Logging; §56.2 Unit Test Scope; §56.3 Integration Test Scope; §60.4 Exit Criteria; §60.5 Acceptance Test Requirements.  
**Contracts:** template-library-coverage-matrix.md, cta-sequencing-and-placement-contract.md, template-library-compliance-matrix.md.

---

## 1. Purpose

The automated compliance pass validates the current section and page template inventory against **hard rules** from the compliance matrix and coverage matrix. It acts as the **library-wide enforcement gate** before additional batches continue. It does **not** create or modify templates; it only reports progress and violations.

---

## 2. What is validated

| Area | Authority | Checks |
|------|------------|--------|
| Counts | template-library-coverage-matrix | Section total ≥ 250, page total ≥ 500; progress vs targets. |
| Category coverage | template-library-coverage-matrix §2.2, §2.4, §3.2, §3.3 | Section purpose-family minimums; page class minimums; max share per family/category/class. |
| CTA rules | cta-sequencing-and-placement-contract | Min CTA by page class; mandatory bottom CTA; no adjacent CTA; non-CTA count 8–14 (min hard, max warning). |
| Preview / one-pager | template-preview-and-dummy-data-contract, §16 | Section preview readiness (preview_defaults or ref); page one_pager presence. |
| Semantic / animation | semantic-seo-accessibility-extension, animation-support-and-fallback-contract | Section accessibility_warnings_or_enhancements; animation_tier in allowed set. |
| Export | §55.8, registry export | JSON encode/decode viability for all definitions. |

Hard rules are **not** advisory: any hard-fail violation causes the overall result to **fail**. Warnings (e.g. non_cta_count_above_max) are recorded but do not by themselves fail the pass.

---

## 3. How to run

- **Programmatically:** Resolve `template_library_compliance_service` from the container and call `run()`. The service is registered in `Registries_Provider`.
- **Result:** `Template_Library_Compliance_Result` with `to_array()` for machine-readable payload and `to_summary_lines()` for human-readable summary.

Example (pseudo):

```php
$service = $container->get( 'template_library_compliance_service' );
$result = $service->run();
$payload = $result->to_array();
$lines   = $result->to_summary_lines();
```

No CLI or admin screen is required by this prompt; the service is the gate. Tests and any future admin/CLI can call the same service.

---

## 4. Result format (machine-readable)

`Template_Library_Compliance_Result::to_array()` returns:

- **count_summary:** `section_total`, `page_total`, `section_target` (250), `page_target` (500), `by_section_purpose_family`, `by_page_category_class`, `by_page_family`.
- **category_coverage_summary:** `section_family_minimums` (family => bool), `page_class_minimums` (class => bool), `max_share_violations` (list of strings e.g. `section_family:cta`, `page_class:hub`).
- **cta_rule_violations:** List of `{ template_key, code, message }`. Codes: `cta_count_below_minimum`, `non_cta_count_below_minimum`, `bottom_cta_missing`, `adjacent_cta_violation`, `non_cta_count_above_max` (warning).
- **preview_readiness:** `sections_missing_preview`, `pages_missing_one_pager` (lists of internal keys).
- **metadata_checks:** `sections_missing_accessibility`, `sections_invalid_animation` (lists of internal keys).
- **export_viability:** `viable` (bool), `errors` (list of strings).
- **passed:** `true` only when no hard-fail condition remains.

---

## 5. Human-readable summary

`Template_Library_Compliance_Result::to_summary_lines()` returns a short list of strings, e.g.:

- "Sections: 120 / 250. Pages: 225 / 500."
- "CTA rule violations (hard): 0" (if any hard CTA violations).
- "Max-share violations: section_family:cta" (if any).
- "Compliance: PASSED." or "Compliance: FAILED (resolve hard-fail items)."

---

## 6. Relation to other docs

- **template-library-compliance-matrix.md:** Rule families and severity; this automation implements the matrix as executable validation.
- **template-library-coverage-matrix.md:** Targets and minimums; the service uses the same numbers (250, 500, family/class minimums, max shares).
- **section-library-batch-validation-report.md**, **page-template-batch-validation-report.md:** Batch-level evidence; the automated pass is library-wide and can be run after any batch to see current status vs targets.
