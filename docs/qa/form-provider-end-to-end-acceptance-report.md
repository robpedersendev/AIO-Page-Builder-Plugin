# Form Provider — End-to-End Acceptance Report

**Document type:** Integrated QA and acceptance evidence for provider-backed form sections and request-form page template (Prompt 234).  
**Spec refs:** §56.3, §56.4, §59.14, §60.4, §60.5, §60.6, §60.7.  
**Scenario manifest:** [tests/e2e/form-provider-integration/SCENARIO_MANIFEST.md](../../tests/e2e/form-provider-integration/SCENARIO_MANIFEST.md).

---

## 1. Acceptance test plan

| Area | Scope | Evidence type |
|------|--------|----------------|
| Registry visibility | Form section and request-form template in directories and detail screens. | Manual or e2e run; scenario IDs FPE2E-REG-* |
| Admin editing | Provider/form selection, validation, save/load. | Manual or e2e; FPE2E-EDT-* |
| Rendering | Assembled content, shortcode emission, frontend form. | Manual or e2e; FPE2E-RND-* |
| Build Plan / execution | Recommendations, new-page build, replacement, finalization form_dependency. | Manual or e2e; FPE2E-BP-* |
| Diagnostics / reporting | Provider dependency classification, survivability messaging. | Per Prompt 231; FPE2E-DIA-* |
| Export / restore | References in package, restore validation. | Per Prompt 232; FPE2E-EXP-* |
| Security | Malicious/malformed input, capability/nonce denial. | Unit (Form_Provider_Registry_Security_Test) + manual; FPE2E-SEC-* |

---

## 2. Scenario matrix (summary)

| Scenario ID range | Description | Pass / Fail / Waiver / N/A |
|-------------------|-------------|----------------------------|
| FPE2E-REG-01 – 04 | Registry and admin visibility; seed capability/nonce | *Execute and record.* |
| FPE2E-EDT-01 – 03 | Edit/save and validation | *Execute and record.* |
| FPE2E-RND-01 – 03 | Rendering and frontend | *Execute and record.* |
| FPE2E-BP-01 – 05 | Build Plan and execution | *Execute and record.* |
| FPE2E-DIA-01 – 02 | Diagnostics and survivability | *Execute when Prompt 231 complete.* |
| FPE2E-EXP-01 – 02 | Export and restore | *Execute when Prompt 232 complete.* |
| FPE2E-SEC-01 – 04 | Security and permission | *Unit tests for registry; manual for capability.* |

---

## 3. Unit test evidence (Prompt 233)

| Test suite | Coverage | Status |
|------------|----------|--------|
| Form_Provider_Registry_Security_Test | build_shortcode null for unregistered/malformed; is_valid_provider_id; is_valid_form_id_format; validate_provider_and_form; has_provider sanitization | *Run and record.* |
| Form_Provider_Dependency_Validator_Test | validate_for_template; template_uses_form_sections | Pass (Prompt 230). |
| Template_Page_Build_Service_Test | run returns failure when validator fails | Pass (Prompt 230). |

---

## 4. Blocker and fix notes

- **Blockers:** None identified at report creation. Any scenario failure that blocks milestone exit must be fixed or waived per hardening matrix.
- **Dependencies:** FPE2E-DIA-* and FPE2E-EXP-* depend on Prompts 231 and 232; run when those are complete.
- **Environment:** Current-provider (ndr_forms) acceptance only; future providers are registry-extensibility checks, not in scope for this suite.

---

## 5. Sign-off readiness

- [ ] All in-scope scenarios executed and recorded (pass/fail/waiver).
- [ ] Unit tests for form-provider security and dependency validator passing.
- [ ] No critical/high open for provider-backed form in hardening issue register.
- [ ] Acceptance evidence sufficient for milestone-level QA review per §60.4, §60.5.

*Update this report when scenarios are run and when Prompts 231–232 deliver diagnostics and export/restore validation.*
