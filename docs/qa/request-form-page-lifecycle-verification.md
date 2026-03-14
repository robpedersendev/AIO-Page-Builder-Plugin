# Request-Form Page Lifecycle Verification

**Document type:** QA verification for request-form page template and provider-backed form sections in Build Plan, execution, replacement, and finalization (Prompt 230).  
**Purpose:** Confirm template metadata in Build Plan UI, dependency validation before build/replace, provider-backed section survival, execution/finalization traceability, and replacement recoverability.  
**Spec refs:** §33.3, §33.5, §33.8, §33.9, §40.4, §40.9, §40.10, §59.9, §59.10, §60.5.

---

## 1. Build Plan UI – request-form template metadata

- [ ] Request-form page template (`pt_request_form`) appears in new-page recommendations with correct name, family, section count, and template_category_class when recommended by the planner.
- [ ] Build Plan step 2 (new pages) rows show proposed_template_summary, hierarchy_context_summary, and template_selection_reason for request-form items.
- [ ] When the form provider required by the template (e.g. `ndr_forms`) is not registered, dependency_warnings for that row include a clear message (e.g. “Form provider X is not registered…”); build remains blockable by execution validation.

---

## 2. Dependency validation – build and replacement

- [ ] **New-page build:** Before running Create_Page_Job_Service, Template_Page_Build_Service runs Form_Provider_Dependency_Validator for the item’s template_key. If validation fails (missing provider), run returns Create_Page_Result::failure with the validator errors; no page is created.
- [ ] **Replace page:** Before running Replace_Page_Job_Service, Template_Page_Replacement_Service runs the same validator. If validation fails, run returns Replace_Page_Result with success false and errors; no replacement is performed; template_replacement_execution_result and template_key are still present in artifacts for traceability.
- [ ] Validator considers only sections with category `form_embed`; default form_provider is taken from section field_blueprint or falls back to `ndr_forms`. Unregistered provider yields valid=false and an error; no exception is thrown.

---

## 3. Provider-backed sections – survival on create/replace

- [ ] When the form provider is registered, building a new page from the request-form template (or any template with form_embed sections) produces page content that includes the provider shortcode and section structure; form_provider and form_id are stored in section content as per existing rendering/ACF model.
- [ ] Replacing an existing page with the request-form template (or a template with form_embed sections) overwrites/rebuilds content with the same semantics; provider references persist in the rebuilt page when the provider is registered.
- [ ] No hidden execution side effects: provider-backed sections are part of the normal template assembly and page instantiation; they are not applied in a separate, opaque step.

---

## 4. Execution and finalization summaries

- [ ] Execution traces and handler results for create/replace include template_key, template_build_execution_result or template_replacement_execution_result, and (when applicable) replacement_trace_record and snapshot_ref.
- [ ] Template_Finalization_Service builds template_execution_closure_record with one entry per completed create/replace item; each entry includes template_key, post_id, action_taken, and when applicable form_dependency.
- [ ] form_dependency is true for items whose template_key is the request-form template (`pt_request_form`) or whose template uses at least one form_embed section; otherwise it is omitted or false. Used for support/traceability only.

---

## 5. Replacement recoverability and post-build status

- [ ] Replacement flows preserve snapshot_ref and replacement_trace_record; rollback/diff tooling can use them. Provider dependency failure does not leave the target page in a half-updated state.
- [ ] Post-build status and failure reporting remain specific: when provider validation fails, the returned message and errors list the missing provider and template so the operator can fix the environment or choose another template.
- [ ] No capability or nonce behavior is changed; build and replacement remain capability-gated and server-authoritative.

---

## 6. Tests / QA checklist

- [ ] Unit: Form_Provider_Dependency_Validator::validate_for_template – unknown template returns valid true; template with form_embed and registered provider returns valid true; template with form_embed and unregistered provider returns valid false and non-empty errors.
- [ ] Unit: Form_Provider_Dependency_Validator::template_uses_form_sections – returns true for pt_request_form when it has form_embed section; returns false for template with no form_embed sections.
- [ ] Unit: Template_Page_Build_Service run – when validator is injected and returns invalid, run returns failure without calling job_service->run().
- [ ] Unit: Template_Page_Replacement_Service run – when validator is injected and returns invalid, run returns Replace_Page_Result with success false and errors.
- [ ] Build Plan UI: New-page step row for an item with template_key pt_request_form shows dependency_warnings when form provider is not registered (if validator is wired).
- [ ] Finalization: After a run that created or replaced a page with request-form template, template_execution_closure_record includes an entry with form_dependency true for that item.

---

## 7. Risk notes

- **Validator optional:** Form_Provider_Dependency_Validator is optional in Template_Page_Build_Service, Template_Page_Replacement_Service, New_Page_Template_Recommendation_Builder, and Template_Finalization_Service; if not registered in the container, behavior falls back to previous (no form-provider validation, no form_dependency in closure).
- **Canonical storage:** form_provider and form_id remain in page/section content model; no move of provider data into execution-only records.
- **Planner/executor separation:** Validation is part of execution preflight; planner output is unchanged. Approval-first behavior and typed Build Plan items are preserved.
