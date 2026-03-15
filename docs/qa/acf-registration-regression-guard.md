# ACF Registration — Regression Guard

**Prompt**: 307  
**Contracts**: acf-conditional-registration-contract.md, acf-registration-exception-matrix.md

---

## 1. Purpose

Automated regression guards so future development cannot silently reintroduce unconditional full ACF registration (`register_all()`) or bulk section-definition loading on generic front-end or admin requests. Allowed exception paths remain supported and documented.

---

## 2. What constitutes a regression

| Regression | Meaning |
|------------|---------|
| **Front-end full registration** | Any code path that causes `register_all()` or `run_full_registration()` to run on a public/front-end request (when `should_skip_registration()` is true). |
| **Generic admin full registration** | `run_registration()` (from acf/init) calling `run_full_registration()` or the registrar’s `register_all()` for any generic admin request (non-page admin, or page edit when section keys resolve to null). |
| **Bulk load on request bootstrap** | `get_all_blueprints()` or `list_all_definitions( 9999, 0 )` invoked from the acf/init bootstrap closure or from `run_registration()`. |

---

## 3. What is allowed (not a regression)

| Allowed | Reference |
|---------|-----------|
| **Explicit tooling** | Debug exporter, local JSON mirror, migration verification, regeneration, diagnostics screen: may call `run_full_registration()` or `get_all_blueprints()` from their **own** entry point only. Not from acf/init or generic request bootstrap. | acf-registration-exception-matrix.md |
| **Scoped registration** | `register_sections( $section_keys )` from `run_registration()` when context is existing-page or new-page and section keys are resolved. | acf-conditional-registration-contract.md §4.2, §4.3 |
| **Zero groups** | `run_registration()` returns 0 when skip, non-page admin, or unresolved section keys. No `register_all()` in these paths. | Contract §4.4, §7 |

---

## 4. Automated guards

| Guard | Location | What it asserts |
|-------|----------|-----------------|
| **Front-end no register_all** | ACF_Registration_Bootstrap_Controller_Test::test_run_registration_returns_zero_on_front_end_without_calling_registrar | When `should_skip_registration()` is true, `run_registration()` returns 0 and the registrar’s `register_all()` is never called. |
| **Non-page admin no register_all** | ACF_Registration_Bootstrap_Controller_Test::test_run_registration_returns_zero_for_non_page_admin_without_calling_register_all | When admin context is NON_PAGE_ADMIN, `run_registration()` returns 0 and `register_all()` is never called. |
| **Scoped path uses register_sections only** | ACF_Registration_Bootstrap_Controller_Test::test_run_registration_uses_register_sections_when_existing_page_returns_section_keys, test_run_registration_uses_register_sections_when_new_page_returns_section_keys | When existing/new page returns section keys, only `register_sections()` is called; `register_all()` is never called. |
| **Regression suite** | ACF_Registration_Regression_Guard_Test | Explicitly named regression tests that fail if generic paths ever call `register_all()`. |

---

## 5. How to run

- Run the ACF registration unit tests (ACF_Registration_Bootstrap_Controller_Test, ACF_Registration_Regression_Guard_Test) as part of CI or pre-commit.
- Command (from project root): `composer run phpunit -- --filter ACF_Registration` (or equivalent per project test setup).
- If any of the “never call register_all” tests fail, treat it as a regression and fix the bootstrap/controller so generic requests do not trigger full registration.

---

## 6. CI / QA

- Include these tests in the same CI job that runs other plugin unit tests.
- Release gate (acf-registration-performance-release-gate.md) references this document; before release, confirm regression guard tests pass.

---

## 7. Cross-references

- acf-conditional-registration-contract.md
- acf-registration-exception-matrix.md
- plugin/tests/Unit/ACF_Registration_Bootstrap_Controller_Test.php
- plugin/tests/Unit/ACF_Registration_Regression_Guard_Test.php (Prompt 307)
