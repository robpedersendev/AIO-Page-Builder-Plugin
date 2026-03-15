# Form Provider — Upgrade and Support Runbook

**Spec:** §0.10.7, §0.10.11, §57.9, §60.6.  
**Purpose:** Step-by-step procedures for provider upgrades, support triage, and regression verification. Use with [form-provider-maintenance-sop.md](form-provider-maintenance-sop.md).

---

## 1. Support incident: “Form not rendering” or “Provider not registered”

1. **Reproduce:** Confirm site has the form provider plugin active and the section/page uses the correct form_provider and form_id (Section/Page Template Detail or ACF field values).
2. **Form Provider Health screen:** AIO Page Builder → Form Provider Health. Check provider_availability for the provider in question; note status (available, unavailable, provider_error, etc.).
3. **Support bundle:** If available, request a support bundle. Open `template_library_support_summary.json` (or equivalent) and check `form_provider_availability` and `form_provider_health_summary`. Confirm no secrets; confirm section_templates_with_forms_count and page_templates_using_forms_count are plausible.
4. **Resolution:** If provider is inactive → activate provider plugin. If form_id is wrong or stale → correct in section/page and save. If provider plugin was updated and shortcode or API changed → treat as upgrade (§2). If our plugin is at fault → run regression (§3) and open fix.

---

## 2. Provider plugin upgrade (e.g. NDR Form Manager or future WPForms/CF7)

1. **Read provider changelog** for shortcode, form-list API, or attribute changes.
2. **Registry:** If shortcode_tag or id_attr changed, update Form_Provider_Registry registration (e.g. in provider plugin or main plugin bootstrap). No change to canonical storage (form_provider, form_id).
3. **Picker adapter:** If the provider’s form list or stale detection changed, update the adapter implementation (get_form_list(), is_item_stale()). Re-run unit tests for Form_Provider_Availability_Service and adapter.
4. **Regression:** Run `Form_Provider_Integration_Regression_Harness_Test` and full form-provider E2E/acceptance. Add fixtures if the new behavior needs a scenario.
5. **Diagnostics:** Open Form Provider Health screen; confirm availability and counts. Generate a support bundle and confirm `form_provider_health_summary` looks correct.
6. **Decision log / changelog:** If the upgrade implies a supported version or behavior change, add an entry to [template-library-decision-log.md](../release/template-library-decision-log.md) and a line in [changelog](../release/changelog.md). Update [known-risk-register.md](../release/known-risk-register.md) if there is a new limitation.

---

## 3. Regression and diagnostics verification (post-change)

1. **Unit:** `php vendor/bin/phpunit plugin/tests/Unit/Form_Provider_*.php plugin/tests/Unit/Form_Provider_Integration_Regression_Harness_Test.php` (and any other form-provider tests). All pass.
2. **Regression harness:** Ensure all fixtures in `plugin/tests/fixtures/form-provider-integration/*.json` are exercised and expectations met (see [form-provider-regression-report.md](../qa/form-provider-regression-report.md)).
3. **Form Provider Health:** Load the screen; confirm no PHP/JS errors; confirm provider availability table and counts render. Optional: export support bundle and inspect `form_provider_health_summary`.
4. **Security:** Run [form-provider-security-checklist.md](../qa/form-provider-security-checklist.md) if code paths for save, REST, or diagnostics changed.

---

## 4. Worked example: provider API/picker regression

**Scenario:** A support report says “form list is empty after we updated the form provider plugin.” Triage determines the provider’s form-list API response shape changed.

| Step | Action |
|------|--------|
| 1 | **Incident:** Support creates a ticket; evidence includes Form Provider Health showing provider status “provider_error” or “no_forms” and user confirms the provider plugin was just updated. |
| 2 | **Triage (SOP §1):** Classify as (g) other / provider API change. Gather support bundle; check known-risk register for that provider. |
| 3 | **Analysis:** Developer inspects adapter’s get_form_list() and the provider’s new API response. Finds that the provider now returns a different key for “form id” (e.g. `id` → `form_id`). Adapter was mapping the old key. |
| 4 | **Fix:** Update adapter to support both old and new response shape (or only new if support policy drops old). Add or adjust unit test that mocks the new response. |
| 5 | **Regression:** Run Form_Provider_Integration_Regression_Harness_Test and Form_Provider_Availability_Service_Test; run form-provider E2E if available. All pass. |
| 6 | **Decision log:** Add entry to [template-library-decision-log.md](../release/template-library-decision-log.md): e.g. “DL-FP-001: Adapter X updated to support provider API response shape as of provider plugin version Y. Impacted: form list picker and availability state.” Status approved; effective version noted. |
| 7 | **Changelog:** Add line under Fixes or Compatibility: “Form provider X: compatibility with provider plugin version Y form-list API.” |
| 8 | **Release:** Include in next patch or minor release; no silent drift. |

This example shows the full path from **support incident → triage → fix → regression → decision log → changelog → release**. The same flow applies to shortcode changes, registry registration fixes, or security/permission issues, with escalation per SOP §6 where needed.
