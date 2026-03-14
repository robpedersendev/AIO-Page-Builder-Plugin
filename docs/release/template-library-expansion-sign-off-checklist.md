# Template Library Expansion Sign-Off Checklist

**Scope:** Expanded template library initiative only (250+ sections, 500+ page templates). Not the full product release.  
**Governs:** Spec §59.14, §59.15; §60.4 Exit Criteria; §60.5–60.8.  
**Evidence packet:** [template-library-expansion-review-packet.md](template-library-expansion-review-packet.md).  
**Reference:** [hardening-release-gate-matrix.md](../contracts/hardening-release-gate-matrix.md) (performance / §55.7, §55.8); [sign-off-checklist.md](sign-off-checklist.md) (full release).

---

## 1. Expansion criteria (Met / Not met / Waived)

Each criterion must be **Met**, **Not met**, or **Waived** (with waiver_id). No silent waivers.

| # | Criterion | Status | Evidence / notes |
|---|-----------|--------|------------------|
| 1 | **Counts:** Section templates ≥ 250; page templates ≥ 500. | ☐ Met ☐ Not met ☐ Waived | [template-library-expansion-review-packet.md](template-library-expansion-review-packet.md) §1. Achieved: 254 sections, 580 pages. |
| 2 | **Category coverage:** Section purpose-family minimums and max share ≤ 25%; page template_category_class and template_family minimums per coverage matrix. | ☐ Met ☐ Not met ☐ Waived | [template-library-coverage-matrix.md](../contracts/template-library-coverage-matrix.md); [template-library-inventory-manifest.md](../contracts/template-library-inventory-manifest.md) §7. |
| 3 | **CTA-law compliance:** CTA count, bottom CTA, non-adjacent CTAs, non-CTA range (8–14) evidenced. | ☐ Met ☐ Not met ☐ Waived | [template-library-compliance-matrix.md](../qa/template-library-compliance-matrix.md); [template-library-accessibility-audit-report.md](../qa/template-library-accessibility-audit-report.md); CTA sequencing tests. |
| 4 | **Preview readiness:** Synthetic preview and cache; directory/detail preview available. | ☐ Met ☐ Not met ☐ Waived | [template-preview-and-dummy-data-contract.md](../contracts/template-preview-and-dummy-data-contract.md); [template-admin-performance-hardening-report.md](../qa/template-admin-performance-hardening-report.md) §2. |
| 5 | **One-pager / appendix / export:** One-pager metadata; section and page inventory appendices generated; export/restore validators. | ☐ Met ☐ Not met ☐ Waived | [template-library-expansion-review-packet.md](template-library-expansion-review-packet.md) §4; [section-template-inventory.md](../appendices/section-template-inventory.md), [page-template-inventory.md](../appendices/page-template-inventory.md). |
| 6 | **Semantic / accessibility audit:** Audit run; no unwaived hard failures. | ☐ Met ☐ Not met ☐ Waived | [template-library-accessibility-audit-report.md](../qa/template-library-accessibility-audit-report.md). |
| 7 | **Animation fallback QA:** Animation tier/fallback/reduced-motion QA run; manual checklist addressed. | ☐ Met ☐ Not met ☐ Waived | [template-library-animation-fallback-report.md](../qa/template-library-animation-fallback-report.md). |
| 8 | **Admin performance hardening:** MAX_PER_PAGE 50; preview cache cap; compare/compositions limits; no regression. | ☐ Met ☐ Not met ☐ Waived | [template-admin-performance-hardening-report.md](../qa/template-admin-performance-hardening-report.md). |
| 9 | **Versioning / deprecation / decision log:** Template_Versioning_Service, Template_Deprecation_Service; decision log and changelog aligned. | ☐ Met ☐ Not met ☐ Waived | [template-library-decision-log.md](template-library-decision-log.md); [template-library-expansion-review-packet.md](template-library-expansion-review-packet.md) §7. |
| 10 | **Planner / Build Plan integration:** Template_Recommendation_Context_Builder; Build_Plan_Template_Explanation_Builder; template rationale in Build Plan. | ☐ Met ☐ Not met ☐ Waived | [template-library-expansion-review-packet.md](template-library-expansion-review-packet.md) §7. |
| 11 | **Documentation:** Coverage matrix, compliance matrix, inventory manifest, hardening/audit/animation reports; release packet and known-risk register updated. | ☐ Met ☐ Not met ☐ Waived | [template-library-expansion-review-packet.md](template-library-expansion-review-packet.md) §8; [release-review-packet.md](release-review-packet.md) §2.7; [known-risk-register.md](known-risk-register.md). |

**Rule:** If any criterion is **Not met**, the expansion is **blocked** until fixed or formally waived. **Waived** requires waiver_id and entry in hardening matrix or this checklist §2.

---

## 2. Waivers (template-library expansion only)

| waiver_id | Criterion # | Short title | Scope | Signatory |
|-----------|-------------|-------------|-------|-----------|
| *(none if no waivers)* | — | — | — | — |

---

## 3. Role approval (template-library expansion)

**Optional:** Use when sign-off is scoped to the template-library expansion only (e.g. staged approval).

| Role | Approval | Name | Date |
|------|----------|------|------|
| Technical Lead | ☐ Approved ☐ Rejected | _______________________ | _______________________ |
| QA | ☐ Approved ☐ Rejected | _______________________ | _______________________ |
| Product Owner | ☐ Approved ☐ Rejected | _______________________ | _______________________ |

**Go:** All criteria §1 Met or Waived; no criterion Not met without waiver; roles above approved (if used).  
**No-go:** Any criterion Not met and not waived; or any role Rejected.

---

*This checklist is subordinate to the full [sign-off-checklist.md](sign-off-checklist.md). The expanded template library is production-ready for release only when both this expansion checklist and the full release sign-off are satisfied.*
