# Template Ecosystem Release SOP

**Spec:** §0.14 Change Approval Process; §60.6 Documentation Completion Requirements; §60.8 Sign-Off Requirements; §58.6 Release Notes Standards.  
**Purpose:** Standard operating procedure for releasing the template ecosystem (or a release that includes template changes). Aligned to implemented checklists, reports, and sign-off. Complements [template-ecosystem-maintenance-runbook.md](template-ecosystem-maintenance-runbook.md).

**Audience:** Internal release owners and QA. No new features or automation; procedure only.

---

## 1. Pre-Release: Appendices and Compliance

1. **Regenerate inventory appendices** after any template batch or version/deprecation change. Use `Section_Inventory_Appendix_Generator` and `Page_Template_Inventory_Appendix_Generator` (runbook §2). Write output to `docs/appendices/section-template-inventory.md` and `docs/appendices/page-template-inventory.md` so committed docs match the registry.
2. **Run compliance pass:** Resolve `template_library_compliance_service` and call `run()`. Address all **hard-fail** findings per [template-library-compliance-matrix.md](../qa/template-library-compliance-matrix.md). Record warnings and any waivers in [hardening-release-gate-matrix.md](../contracts/hardening-release-gate-matrix.md) or expansion sign-off checklist.
3. **Run accessibility audit:** `Template_Accessibility_Audit_Service::run()`. Resolve or waive per compliance matrix and [template-library-accessibility-audit-report.md](../qa/template-library-accessibility-audit-report.md).
4. **Run animation QA:** `Animation_QA_Service::run()`. Address per [template-library-animation-fallback-report.md](../qa/template-library-animation-fallback-report.md).

---

## 2. Decision Log and Changelog

1. **Decision log:** Every governed template-family or policy change must have an entry in [template-library-decision-log.md](../release/template-library-decision-log.md). Use `Template_Deprecation_Service::build_decision_log_entry()` and paste/sync (runbook §5.1). Status must be `approved` for implemented decisions.
2. **Changelog:** For each deprecation in this release, add a line from `Template_Deprecation_Service::build_changelog_snippet_for_deprecation()` to the release changelog Deprecations section (runbook §5.2). Per §58.6: what changed, added, fixed; migrations; deprecations; limitations.

---

## 3. Release Notes

1. **Template-specific content:** Use [template-library-release-notes-addendum.md](../release/template-library-release-notes-addendum.md) as the source for counts, screens, preview/compare, compositions, CTA enforcement, compatibility, migration, and limitations. Keep it truthful and operationally useful; update if counts or behavior have changed.
2. **Main release notes:** Ensure the main release notes (e.g. release-notes-rc1.md or current standard) include or reference: tested WP/PHP range, required plugins, preferred environment, extension pack (if any), known limitations (multisite, theme detection), and any template-library addendum. Reference [compatibility-matrix.md](../qa/compatibility-matrix.md) for limitations.

---

## 4. Sign-Off

1. **Template-library expansion (if applicable):** Complete [template-library-expansion-sign-off-checklist.md](../release/template-library-expansion-sign-off-checklist.md). Every criterion must be **Met** or **Waived** (with waiver_id). No criterion **Not met** without waiver.
2. **Full release:** Template expansion sign-off is subordinate to the full [sign-off-checklist.md](../release/sign-off-checklist.md). Both must be satisfied for a release that includes the expanded template library.
3. **Roles (spec §60.8):** M1–M4: Product Owner + Technical Lead. M5–M10: + QA. M11–M12: + Security review where applicable. No critical unresolved issue may be carried into release (§61.10).

---

## 5. Post-Release

1. **Evidence:** Record test date and results in [compatibility-matrix.md](../qa/compatibility-matrix.md) and any extension-pack evidence as required. Template library compatibility checklist: [template-library-compatibility-report.md](../qa/template-library-compatibility-report.md).
2. **Known risks:** Update [known-risk-register.md](../release/known-risk-register.md) if new template-related risks or mitigations are identified.
3. **Support:** Ensure [template-library-support-guide.md](../guides/template-library-support-guide.md) and [template-library-operator-guide.md](../guides/template-library-operator-guide.md) are still accurate for the released behavior; update if needed and document in revision log.

---

## 6. Reference Matrix

| Activity | Doc / artifact |
|----------|----------------|
| Appendix regeneration | Runbook §2; section-template-inventory.md, page-template-inventory.md |
| Compliance gate | Template_Library_Compliance_Service::run(); template-library-compliance-matrix.md; template-library-automated-compliance-report.md |
| Accessibility gate | Template_Accessibility_Audit_Service::run(); template-library-accessibility-audit-report.md |
| Animation gate | Animation_QA_Service::run(); template-library-animation-fallback-report.md |
| Decision log | template-library-decision-log.md; build_decision_log_entry() |
| Changelog (deprecations) | build_changelog_snippet_for_deprecation(); §58.6 |
| Release notes | template-library-release-notes-addendum.md; §58.6 |
| Expansion sign-off | template-library-expansion-sign-off-checklist.md |
| Full release sign-off | sign-off-checklist.md; §60.8 |
| Escalation | Spec §61.10; runbook §4 |
