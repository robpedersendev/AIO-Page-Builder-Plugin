# Form Provider Integration Regression Report

**Governs:** Prompt 238, spec §56.3, §56.8, §59.14, §60.5.  
**Purpose:** Scenario matrix and evidence for provider-backed form regression harness; distinguishes automated (fixture-driven) vs manual/E2E.

---

## 1. Harness and fixtures

| Item | Location |
|------|----------|
| **Harness** | `plugin/tests/Regression/FormProviderIntegrationRegressionHarness.php` |
| **Fixtures** | `plugin/tests/fixtures/form-provider-integration/*.json` |
| **Unit test** | `plugin/tests/Unit/Form_Provider_Integration_Regression_Harness_Test.php` |

Harness uses real `Form_Provider_Registry`: shortcode build, provider registration, form_id validity. Rendering, submission, migration, and permission-denied paths are documented below; run via E2E or manual where required.

---

## 2. Scenario matrix

| Scenario | Automated (harness) | Manual / E2E | Notes |
|----------|--------------------|--------------|--------|
| **Shortcode build (valid)** | Yes | — | Fixture: `section-form-embed-valid.json`, `request-form-page-valid.json`. Asserts registry + build_shortcode. |
| **Missing provider** | Yes | — | Fixture: `section-missing-provider.json`. Asserts shortcode null, provider not registered. |
| **Invalid form_id** | Yes | — | Fixture: `section-invalid-form-id.json`. Asserts shortcode null when form_id empty/invalid. |
| **Rendering** | No | E2E | Section/page renders with shortcode; requires full render pipeline. See [form-provider-end-to-end-acceptance-report.md](form-provider-end-to-end-acceptance-report.md). |
| **Save/load** | No | E2E | Form provider/form_id persist and reload; ACF + detail screen. |
| **Stale binding** | No | Unit + manual | Availability service + state builder; unit tests in Form_Provider_Availability_Service_Test. UI check: form-provider-ui-checklist §2.4, §2.7. |
| **Migration/restore** | No | E2E / manual | Export/restore and migration must preserve form references; see export/restore checklists. |
| **Permission denied** | No | Integration / manual | Admin save and diagnostics capability-gated; negative path in security checklist. |

---

## 3. Evidence and run

- **Automated:** Run `php vendor/bin/phpunit tests/Unit/Form_Provider_Integration_Regression_Harness_Test.php` (and full suite). All fixture scenarios must pass.
- **Summary artifact:** `FormProviderIntegrationRegressionHarness::summary($results)` returns `ran_at`, `total`, `passed`, `failed`, `results` for machine-readable evidence.
- **Manual/E2E:** Execute per [form-provider-end-to-end-acceptance-report.md](form-provider-end-to-end-acceptance-report.md), [form-provider-ui-checklist.md](form-provider-ui-checklist.md), and release checklist; record pass/fail/waiver in release-candidate-closure.

---

## 4. Risk notes

- Harness does not replace E2E: rendering, submission, migration, and permission-denied are covered by E2E or manual QA.
- Fixtures are synthetic and versioned (`fixture_version`); schema changes may require fixture updates.
- Environment-dependent submission (e.g. live provider API) remains out of scope; use controlled fixtures and stubs.
