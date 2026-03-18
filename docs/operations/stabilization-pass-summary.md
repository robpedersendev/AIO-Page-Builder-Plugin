# Stabilization Pass — Secondary Implementation Gaps

**Objective:** Stabilize secondary gaps that do not require new product semantics where behavior is already implied by current architecture/spec.

---

## 1. Stabilized (implemented)

### 1.1 Industry_Packs_Module registry/overlay follow-through

- **Gap:** Class docblock said "Registers placeholder services; full registries and overlays are added in later prompts" while the module actually registers full built-in registries and overlays.
- **Change:** Updated docblock to state that the module registers industry subsystem services including built-in pack, overlay, and registry implementations (per industry-pack-extension-contract), and that `CONTAINER_KEY_INDUSTRY_LOADED` indicates subsystem availability.
- **File:** `plugin/src/Bootstrap/Industry_Packs_Module.php`

### 1.2 Conversion_Goal_Comparison_Screen availability hardening

- **Gap:** When comparison service was missing, messaging was generic and state shape could be incomplete for the error path.
- **Change:** (1) Resolve `CONTAINER_KEY_INDUSTRY_LOADED` and include `industry_loaded` and `alternate_goal_param` in the error-state return so state shape is consistent. (2) Clearer, truthful message: "Conversion goal comparison is not available. The industry subsystem or comparison service is not loaded." (3) Link to Industry Profile in the same notice. (4) Guard render with `isset( $state['error'] )` and add `role="alert"` for the notice.
- **File:** `plugin/src/Admin/Screens/Industry/Conversion_Goal_Comparison_Screen.php`

### 1.3 Restore pipeline styling-path warning handling and truthfulness

- **Gap:** When styling restore was skipped (normalizer or sanitizer not available), the pipeline only logged a warning; the restore result and UI did not explain why styling was not restored.
- **Change:** (1) Pipeline collects `skipped_reasons` (category + user-facing reason) when a category is skipped. (2) Styling case: when normalizer/sanitizer is null, push `array( 'category' => 'styling', 'reason' => __( '...' ) )` and merge `skipped_reasons` into `template_library_restore_summary` before building `Restore_Result::success()`. (3) Import/Export screen: when displaying last restore result, if `template_library_restore_summary['skipped_reasons']` is present, show a "Skipped (not restored):" list with category and reason.
- **Files:** `plugin/src/Domain/ExportRestore/Import/Restore_Pipeline.php`, `plugin/src/Admin/Screens/ImportExport/Import_Export_Screen.php`
- **Test:** `plugin/tests/Unit/Import_Validator_And_Restore_Test.php` — `test_restore_result_success_includes_skipped_reasons_in_payload()`

### 1.4 ACF page-level visibility follow-through

- **Finding:** No implementation gap. `Page_Field_Group_Assignment_Service::get_visible_groups_for_page()` already returns empty for `page_id <= 0`; assignment is derived from template/composition per acf-page-visibility-contract; callers use resolved page IDs from admin context. No code or doc change.

---

## 2. Deferred (no new semantics)

- **ACF conditional registration:** Full scoped registration (admin page-edit only, front-end none) is defined in acf-conditional-registration-contract; implementation is tracked in separate prompts (282–284+). Not in scope for this pass.
- **Industry bundle apply:** Persistent store and apply handler design remain deferred per approved-backlog-implementation-summary; no new semantics invented here.
- **Other comparison screens:** Only Conversion_Goal_Comparison_Screen was in scope; other industry screens already use similar "not available" patterns where applicable.

---

## 3. Summary

| Area                         | Action        | Notes                                              |
|-----------------------------|---------------|----------------------------------------------------|
| Industry_Packs_Module       | Docblock fix  | Truthful description of built-in registries       |
| Conversion_Goal_Comparison  | Hardening     | Consistent state, clearer message, Industry link   |
| Restore styling-path        | Result + UI   | Skipped reasons in result and Import/Export UI    |
| ACF page-level visibility   | Verified      | No gap; contract and code aligned                 |
