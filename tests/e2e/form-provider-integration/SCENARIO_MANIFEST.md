# Form Provider Integration — E2E Scenario Manifest

**Spec:** §56.3, §56.4, §59.14, §60.4, §60.5. **Prompt 234.**

Representative scenarios for provider-backed form sections and request-form page template across admin, persistence, rendering, Build Plan/execution, diagnostics, export/restore, and security. Evidence: [form-provider-end-to-end-acceptance-report.md](../../../docs/qa/form-provider-end-to-end-acceptance-report.md).

---

## 1. Registry and admin visibility

| ID | Scenario | Type | Steps | Expected outcome |
|----|----------|------|--------|------------------|
| FPE2E-REG-01 | Form section template visible in Section Templates directory | Happy | Seed form templates; open Section Templates; filter or search for form section. | form_section_ndr (or equivalent) listed; category form_embed; no raw secrets. |
| FPE2E-REG-02 | Request-form page template visible in Page Templates directory | Happy | Seed form templates; open Page Templates; locate request-form template. | pt_request_form listed; ordered sections include form section; metadata correct. |
| FPE2E-REG-03 | Section template detail shows form binding state (provider, form_id, validation) | Happy | Open form section template detail; verify form binding subsection. | Form_Section_Field_State_Builder state: registered_provider_ids, form_provider, form_id, provider_valid, form_id_valid, shortcode_preview when valid. |
| FPE2E-REG-04 | Seed form templates requires capability and nonce | Failure | POST admin-post aio_seed_form_templates without nonce or as user without MANAGE_SECTION_TEMPLATES + MANAGE_PAGE_TEMPLATES. | Redirect with error; no seed performed. |

---

## 2. Provider/form selection and save

| ID | Scenario | Type | Steps | Expected outcome |
|----|----------|------|--------|------------------|
| FPE2E-EDT-01 | Edit form section: set registered provider and valid form_id; save | Happy | Edit page or section instance with form section; set form_provider (e.g. ndr_forms), form_id (e.g. contact); save. | Values persist; on load, state shows provider_valid and form_id_valid; shortcode_preview present. |
| FPE2E-EDT-02 | Invalid form_id format rejected or not emitted as shortcode | Failure | Enter form_id with disallowed chars (e.g. space, quote, `id=1`). | Validation message or on render no shortcode emitted (registry build_shortcode returns null). |
| FPE2E-EDT-03 | Unregistered provider does not produce shortcode | Failure | Set form_provider to non-registered slug; save; render. | No arbitrary shortcode in output; build_shortcode returns null. |

---

## 3. Rendering and frontend

| ID | Scenario | Type | Steps | Expected outcome |
|----|----------|------|--------|------------------|
| FPE2E-RND-01 | Assembled post_content contains provider shortcode for valid provider+form_id | Happy | Build or replace page with request-form template (provider registered); view assembled content. | Block markup contains [ndr_forms id="…"] (or equivalent); no raw form_provider/form_id as visible text. |
| FPE2E-RND-02 | Frontend form rendering (provider shortcode expanded) | Happy | View front-end page with form section; provider plugin active. | Shortcode expanded by provider; form visible; no XSS from form_id in markup. |
| FPE2E-RND-03 | Missing provider: section renders without shortcode | Failure | Deactivate provider plugin; render page with form section. | No fatal; shortcode not emitted or shows placeholder; no arbitrary tag. |

---

## 4. Build Plan and execution

| ID | Scenario | Type | Steps | Expected outcome |
|----|----------|------|--------|------------------|
| FPE2E-BP-01 | New-page recommendation for request-form shows dependency_warnings when provider missing | Failure | Ensure form provider not registered; open Build Plan with new-page item using pt_request_form. | Row shows dependency_warnings (e.g. form provider not registered). |
| FPE2E-BP-02 | New-page build with request-form succeeds when provider registered | Happy | Register provider; run new-page build for pt_request_form. | Page created; template_build_execution_result; post_content includes form shortcode. |
| FPE2E-BP-03 | New-page build blocked when form provider not registered | Failure | Unregister provider; attempt new-page build for pt_request_form. | Create_Page_Result failure; message references missing provider; no page created. |
| FPE2E-BP-04 | Replace page with request-form: provider validation and persistence | Happy | Replace existing page with pt_request_form; provider registered. | Replace succeeds; template_replacement_execution_result; form shortcode in rebuilt content. |
| FPE2E-BP-05 | Finalization closure record includes form_dependency for request-form | Happy | Complete run that created or replaced a page with pt_request_form. | template_execution_closure_record has entry with form_dependency true for that item. |

---

## 5. Diagnostics and reporting

| ID | Scenario | Type | Steps | Expected outcome |
|----|----------|------|--------|------------------|
| FPE2E-DIA-01 | Diagnostics/support classify provider-backed dependency | Happy | Run diagnostics or build support bundle on site with form section pages. | Provider dependency surfaced as external dependency; no secrets; bounded. (Per Prompt 231.) |
| FPE2E-DIA-02 | Survivability messaging distinguishes page durability vs provider runtime | Happy | Check uninstall/export or survivability messaging. | Message clarifies built page survives; form functionality depends on provider. (Per Prompt 231.) |

---

## 6. Export / restore

| ID | Scenario | Type | Steps | Expected outcome |
|----|----------|------|--------|------------------|
| FPE2E-EXP-01 | Export includes provider-backed form references | Happy | Export package from site with pages using form section. | Manifest or content includes form_provider/form_id where applicable. (Per Prompt 232.) |
| FPE2E-EXP-02 | Restore validates provider-backed references; missing provider as conflict | Failure | Restore package to site without provider plugin. | Validation warns or conflicts; no silent drop; recoverable. (Per Prompt 232.) |

---

## 7. Security and permission

| ID | Scenario | Type | Steps | Expected outcome |
|----|----------|------|--------|------------------|
| FPE2E-SEC-01 | Malicious provider_id does not produce arbitrary shortcode | Failure | Attempt to persist or render with provider_id like `<script>` or `evil_shortcode`. | build_shortcode returns null; no script/shortcode in output. |
| FPE2E-SEC-02 | Malformed form_id does not break output or inject attribute | Failure | Persist form_id with quote or space; render. | Shortcode not built or attribute escaped; no XSS. |
| FPE2E-SEC-03 | Template seed denied without capability | Failure | Request seed as user without MANAGE_SECTION_TEMPLATES or MANAGE_PAGE_TEMPLATES. | 403 or redirect; no seed. |
| FPE2E-SEC-04 | Section/Page template detail access denied without capability | Failure | Open section or page template detail as user without manage capability. | Access denied or redirect. |

---

*Execute scenarios with synthetic/demo data. Record pass/fail/waiver and blocker notes in the acceptance report.*
