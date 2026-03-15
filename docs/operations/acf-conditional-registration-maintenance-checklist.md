# ACF Conditional Registration — Long-Term Maintenance Checklist

**Prompt**: 312  
**Contract**: acf-conditional-registration-contract.md  
**Release gate**: acf-registration-performance-release-gate.md

---

## 1. Purpose

Checklist for future developers and maintainers when changing ACF registration, assignment, template/composition, cache, or admin context logic. Protects the performance retrofit as section/template counts grow and prevents accidental reintroduction of heavy-load paths.

---

## 2. When to use this checklist

- Any change that touches: ACF_Registration_Provider, ACF_Registration_Bootstrap_Controller, Admin_Post_Edit_Context_Resolver, Existing_Page_ACF_Registration_Context_Resolver, New_Page_ACF_Registration_Context_Resolver, Page_Section_Key_Cache_Service, assignment map or Page_Field_Group_Assignment_Service in registration paths, Section_Field_Blueprint_Service (get_all_blueprints vs get_blueprint_for_section), ACF_Group_Registrar (register_all vs register_sections).
- Adding or changing hooks that run on acf/init or that call run_full_registration() / register_all().
- Changes to template or composition definition storage that affect derived section keys or cache invalidation.
- New admin screens or request contexts that might be mistaken for page edit.

---

## 3. Before making changes

- [ ] Read [acf-conditional-registration-contract.md](../contracts/acf-conditional-registration-contract.md) and [acf-registration-exception-matrix.md](../contracts/acf-registration-exception-matrix.md).
- [ ] Confirm the change does not call run_full_registration() or register_all() from generic request bootstrap (acf/init closure). Only documented tooling may do so.
- [ ] If adding a new “full registration” path, add it to the exception matrix with justification and get approval.

---

## 4. Section and template growth

- [ ] As section or page template counts grow, ensure assignment resolution and derivation remain bounded (single-page, single-template/composition lookups; no list_all_definitions( 9999 ) on request path).
- [ ] Cache TTL and invalidation remain appropriate; see [acf-registration-cache-invalidation-matrix.md](../contracts/acf-registration-cache-invalidation-matrix.md).
- [ ] Run regression guard tests and (if available) benchmark/profile to confirm no regression.

---

## 5. Assignment integrity

- [ ] Assignment map remains the source of truth for existing-page visible groups. Do not bypass it for normal registration.
- [ ] Legacy/incomplete assignment: safe fallback is zero groups; no silent full registration. See [acf-legacy-assignment-verification.md](../qa/acf-legacy-assignment-verification.md) and [acf-legacy-page-repair-guide.md](acf-legacy-page-repair-guide.md).
- [ ] Any new assignment storage or API must trigger invalidation (e.g. aio_acf_assignment_changed) where appropriate.

---

## 6. Cache invalidation

- [ ] Template or composition definition saves must fire aio_page_template_definition_saved / aio_composition_definition_saved so section-key cache is invalidated; see [acf-registration-cache-invalidation-matrix.md](../contracts/acf-registration-cache-invalidation-matrix.md).
- [ ] New code that persists assignment changes must fire aio_acf_assignment_changed for the affected page.

---

## 7. Diagnostics and benchmarking

- [ ] Diagnostics and benchmark services remain internal-only; no sensitive data in outputs.
- [ ] If registration paths change, update diagnostics modes or benchmark protocol if new contexts are added.
- [ ] Memory/query profile evidence: use [acf-memory-and-query-profile-report-template.md](../qa/acf-memory-and-query-profile-report-template.md) for release review when relevant.

---

## 8. Compatibility and conflict

- [ ] Resolver guards (e.g. $pagenow invalid) stay in place; see [acf-third-party-admin-compatibility-matrix.md](../qa/acf-third-party-admin-compatibility-matrix.md).
- [ ] New plugin/theme conflict scenarios: document in [acf-plugin-theme-conflict-verification.md](../qa/acf-plugin-theme-conflict-verification.md) and known-risk-register if needed.
- [ ] Fail-safe: ambiguous context → zero groups; never fall back to full registration.

---

## 9. Regression testing

- [ ] Run ACF_Registration_Regression_Guard_Test and ACF_Registration_Bootstrap_Controller_Test after changes. They must pass (no register_all() on generic paths).
- [ ] If adding a new registration path, add a regression test that verifies it does not call register_all() unless it is an approved exception.

---

## 10. Blockers vs maintenance warnings

| Severity | Meaning | Action |
|----------|---------|--------|
| **Blocker** | Change would call register_all() from acf/init or generic admin request; or would remove invalidation for assignment/template/composition. | Do not merge until fixed. |
| **Maintenance warning** | Change touches registration-adjacent code but does not violate contract; or new context not yet covered by regression test. | Proceed with review; add test or doc update as needed. |

---

## 11. Cross-references

- [acf-conditional-registration-contract.md](../contracts/acf-conditional-registration-contract.md)
- [acf-registration-exception-matrix.md](../contracts/acf-registration-exception-matrix.md)
- [acf-registration-cache-invalidation-matrix.md](../contracts/acf-registration-cache-invalidation-matrix.md)
- [acf-registration-regression-guard.md](../qa/acf-registration-regression-guard.md)
- [acf-conditional-registration-support-runbook.md](acf-conditional-registration-support-runbook.md)
- [acf-conditional-registration-final-signoff.md](../release/acf-conditional-registration-final-signoff.md)
