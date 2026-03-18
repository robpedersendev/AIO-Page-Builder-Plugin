# Approved Backlog Implementation Summary

**Purpose:** Map implemented code, tests, and doc changes to the decision records and acceptance criteria that approved them. Only explicitly approved, in-scope items are listed.

**Reference:** Request to implement only approved backlog items (industry bundle apply, ZIP pre-move cap, privacy scope, token application, UPDATE_PAGE_METADATA de-scope, Build Plan Step 2 Deny, workspace detail/table). Privacy scope, token application, Build Plan Step 2 Deny, and workspace improvements had no in-scope approval or were out of scope; industry bundle apply was approved but requires storage/registry design not yet specified—not implemented in this pass.

---

## 1. UPDATE_PAGE_METADATA de-scope

**Decision:** [update-page-metadata-scope-decision.md](update-page-metadata-scope-decision.md)  
**Criteria:** [update-page-metadata-descope-criteria.md](update-page-metadata-descope-criteria.md)

| Change | File | Description |
|--------|------|-------------|
| Code | `plugin/src/Domain/Execution/Queue/Bulk_Executor.php` | Removed `ITEM_TYPE_SEO => UPDATE_PAGE_METADATA` from `ITEM_TYPE_TO_ACTION_TYPE`; added comment that SEO is not mapped to an action type. |
| Code | `plugin/src/Domain/Execution/Queue/Queue_Health_Summary_Builder.php` | Removed `Execution_Action_Types::UPDATE_PAGE_METADATA` from retryable job type list. |
| Code | `plugin/src/Domain/Execution/Queue/Queue_Recovery_Service.php` | Removed `Execution_Action_Types::UPDATE_PAGE_METADATA` from retryable job type list. |
| Code | `plugin/src/Domain/Reporting/UI/Logs_Monitoring_State_Builder.php` | Removed `'update_page_metadata'` from `RETRYABLE_JOB_TYPES`. |
| Docs | `docs/contracts/execution-action-contract.md` | Added implementation-status note: `update_page_metadata` not implemented; SEO step is recommendation-only. |

No new tests: de-scope is removal of mapping and type listing; existing queue/health tests cover behavior.

---

## 2. Import/Export ZIP pre-move size limit (50 MB)

**Decision:** [import-export-zip-size-limit-decision.md](import-export-zip-size-limit-decision.md)  
**Criteria:** [import-export-zip-size-limit-acceptance-criteria.md](import-export-zip-size-limit-acceptance-criteria.md)

| Change | File | Description |
|--------|------|-------------|
| Code | `plugin/src/Admin/Screens/ImportExport/Import_Export_Screen.php` | `MAX_ZIP_UPLOAD_BYTES = 52_428_800`; `ERROR_CODE_FILE_TOO_LARGE = 'file_too_large'`; in `handle_validate()`, after `.zip` and before `move_uploaded_file()`, size check and redirect on overflow; `error_message_for_code()` message for `file_too_large`; file input description “Maximum size 50 MB”; public static `is_zip_upload_size_allowed( int $size ): bool`. |
| Tests | `plugin/tests/Unit/Import_Export_Zip_Size_Limit_Test.php` | Constant value; oversized rejected; at/under limit allowed; zero allowed; negative rejected; error code value. |
| Docs | (this summary) | Acceptance criteria satisfied: single constant, pre-move check, dedicated error code and message, no change to nonce/capability, tests for over/at-or-under. |

---

## 3. Not implemented (this pass)

- **Industry bundle apply** — In scope per [industry-bundle-apply-decision.md](industry-bundle-apply-decision.md) and [industry-bundle-apply-acceptance-criteria.md](industry-bundle-apply-acceptance-criteria.md). Not implemented: requires persistent store for applied bundle (or registry merge) and apply handler design; registries currently load from built-in PHP only.
- **Privacy scope expansion** — Out of scope per [privacy-exporter-eraser-scope-decision.md](privacy-exporter-eraser-scope-decision.md).
- **Token application end-to-end** — Out of scope per [token-application-scope-decision.md](token-application-scope-decision.md).
- **Build Plan Step 2 Deny / workspace detail–table improvements** — No decision records found; not implemented.
