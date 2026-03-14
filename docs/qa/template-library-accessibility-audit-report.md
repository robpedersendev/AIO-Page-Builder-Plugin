# Template Library Accessibility Audit Report

**Document type:** QA report for automated semantic, accessibility, and CTA audit (Prompt 186).  
**Governs:** Machine-checkable rules over section and page-template registries; evidence for template-library-compliance-matrix SEMANTIC/CTA families and §56.6 Accessibility Test Scope.  
**Spec refs:** §12.12 Section Accessibility Contract; §15.9 SEO-Relevant Guidance; §15.10 Accessibility Guidance Rules; §51.3 Front-End Output Accessibility; §51.6 Semantic Heading Rules; §51.7 Landmark and ARIA Rules; §56.6 Accessibility Test Scope.

**Authority:** semantic-seo-accessibility-extension-contract.md; cta-sequencing-and-placement-contract.md; template-library-compliance-matrix.md.

---

## 1. Purpose

The **Template Accessibility Audit** runs over the full template library (section and page-template registries) and flags **machine-checkable** semantic, accessibility, and CTA rule violations. It does **not** replace manual accessibility review or claim full legal compliance. It strengthens quality guarantees by enforcing contract-level obligations at scale.

---

## 2. Rule codes (semantic_rule_violations)

| Rule code | Scope | Contract / spec | Description |
|-----------|--------|------------------|-------------|
| `accessibility_expectations_missing` | section | §12.12 | Section has no accessibility_warnings_or_enhancements. |
| `cta_clarity_marker_missing` | section | §15.10, semantic contract §5 | CTA-classified section has no CTA/a11y expectations declared. |
| `heading_role_undeclared` | section | §51.6, semantic contract §3 | Section with section_purpose_family has no hierarchy_role_hints. |
| `cta_count_below_minimum` | page | cta-sequencing §3 | Page has fewer CTA-classified sections than minimum for template_category_class. |
| `non_cta_count_below_minimum` | page | cta-sequencing §4 | Page has fewer than 8 non-CTA sections. |
| `non_cta_count_above_max` | page | cta-sequencing §4 | Page has more than 14 non-CTA sections (warning). |
| `bottom_cta_missing` | page | cta-sequencing §5 | Last section in ordered_sections is not CTA-classified. |
| `adjacent_cta_violation` | page | cta-sequencing §6 | Two CTA-classified sections are adjacent. |

---

## 3. Human review still required

The audit does **not** verify:

- Heading hierarchy in **rendered** output (single h1, no skip).
- Landmark presence (main, nav) in **rendered** output.
- CTA visible text and image-alt in **rendered** output.
- List, table, accordion, form semantics in **rendered** output.
- Color contrast and focus styling.

These remain in **human_review_required** in the result payload and must be covered by markup review / §56.6 accessibility test scope.

---

## 4. How to run

- **Service:** `Template_Accessibility_Audit_Service` (container key: `template_accessibility_audit_service`).
- **Method:** `run()` returns `Template_Accessibility_Audit_Result`.
- **Payload:** `$result->to_array()` yields `template_accessibility_audit_result`; `$result->to_summary_lines()` yields human-readable summary lines.

---

## 5. Example accessibility-audit result payload

```json
{
  "passed": false,
  "semantic_rule_violations": [
    {
      "scope": "section",
      "template_key": "st_hero_01",
      "rule_code": "cta_clarity_marker_missing",
      "message": "CTA-classified section must declare CTA clarity or accessibility expectations."
    },
    {
      "scope": "page",
      "template_key": "pt_landing_01",
      "rule_code": "bottom_cta_missing",
      "message": "Last section is not CTA-classified."
    }
  ],
  "section_audit_summary": {
    "audited": 120,
    "violations": 1,
    "by_rule_code": { "cta_clarity_marker_missing": 1 }
  },
  "page_audit_summary": {
    "audited": 45,
    "violations": 1,
    "by_rule_code": { "bottom_cta_missing": 1 }
  },
  "human_review_required": [
    "Heading hierarchy in rendered output (single h1, no skip).",
    "Landmark presence (main, nav) in rendered output.",
    "CTA visible text and image-alt in rendered output.",
    "List, table, accordion, and form semantics in rendered output.",
    "Color contrast and focus styling (token/admin)."
  ]
}
```

---

## 6. Human-readable summary excerpt

```
Sections audited: 120 (1 violations). Pages audited: 45 (1 violations).
Rule violations: cta_clarity_marker_missing=1, bottom_cta_missing=1
Accessibility audit: FAILED (resolve violations).
Human review still required: Heading hierarchy in rendered output (single h1, no skip). Landmark presence (main, nav) in rendered output. …
```

---

## 7. Cross-references

- **Contracts:** semantic-seo-accessibility-extension-contract.md; cta-sequencing-and-placement-contract.md; template-library-compliance-matrix.md.
- **Release gate:** hardening-release-gate-matrix.md (Gate 2 Accessibility).
- **Implementation:** `plugin/src/Domain/Registries/QA/Template_Accessibility_Audit_Service.php`; `Template_Accessibility_Audit_Result.php`.
