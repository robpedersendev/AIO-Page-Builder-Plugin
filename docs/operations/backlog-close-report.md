# Final Backlog Close Report

**Purpose:** Audit of remaining backlog after decision and stabilization prompts. Every non-implemented area categorized; evidence with file references.

**References:** [approved-backlog-implementation-summary.md](approved-backlog-implementation-summary.md), [shell-placeholder-backlog.md](shell-placeholder-backlog.md), [stabilization-pass-summary.md](stabilization-pass-summary.md), decision docs in `docs/operations/`.

---

## 1. Categorization key

| Category | Meaning |
|----------|--------|
| **Approved and implemented** | Decision approved; code/tests/docs done; evidence in repo. |
| **Intentionally deferred** | Product/spec decision: out of scope or no implementation without future spec. |
| **Blocked on spec/product decision** | Approved in principle but implementation blocked on missing spec or product choice. |
| **Removed / de-scoped** | Feature removed from “available” surfaces; type/contract may remain for stability. |

---

## 2. Item-by-item status and evidence

### 2.1 Industry bundle apply

| Field | Value |
|-------|--------|
| **Category** | Blocked on spec/product decision |
| **Decision** | In scope — [industry-bundle-apply-decision.md](industry-bundle-apply-decision.md) Outcome A. |
| **Evidence** | Acceptance criteria: [industry-bundle-apply-acceptance-criteria.md](industry-bundle-apply-acceptance-criteria.md) (all checkboxes open). Apply handler and persistence to industry registries/overlays not implemented. Registries load from built-in PHP only (e.g. `Industry_Packs_Module.php` registers `get_builtin_*`); no writable store for applied bundle. |
| **Why open** | No design for where to persist applied bundle or how registries merge applied + built-in. Contracts (industry-pack-bundle-format-contract, industry-pack-import-conflict-contract) define apply semantics; storage/registry merge not specified. |
| **Next required** | **Spec/product:** Define persistence store for applied bundle (or registry merge contract). Then **code:** apply handler, validation at apply time, conflict resolution → write only `final_outcome = applied`. |

---

### 2.2 ZIP upload cap (50 MB pre-move)

| Field | Value |
|-------|--------|
| **Category** | Approved and implemented |
| **Decision** | [import-export-zip-size-limit-decision.md](import-export-zip-size-limit-decision.md). |
| **Evidence** | `plugin/src/Admin/Screens/ImportExport/Import_Export_Screen.php`: `MAX_ZIP_UPLOAD_BYTES = 52_428_800` (L33), `ERROR_CODE_FILE_TOO_LARGE` (L36), size check before `move_uploaded_file()` (L410–411), `is_zip_upload_size_allowed()` (L554–555). `plugin/tests/Unit/Import_Export_Zip_Size_Limit_Test.php`: constant, oversized rejected, at/under/zero/negative, error code. |
| **Why open** | N/A — closed. |
| **Next required** | None. |

---

### 2.3 Privacy scope expansion

| Field | Value |
|-------|--------|
| **Category** | Intentionally deferred |
| **Decision** | Out of scope — [privacy-exporter-eraser-scope-decision.md](privacy-exporter-eraser-scope-decision.md). |
| **Evidence** | Decision: scope does not expand to site-level, onboarding draft, reporting log, industry profile, diagnostics, execution log table. Export/erase remain actor-linked per WordPress privacy API. |
| **Why open** | N/A — explicitly out of scope. |
| **Next required** | None unless a new product decision expands scope. |

---

### 2.4 Token application truthfulness

| Field | Value |
|-------|--------|
| **Category** | Intentionally deferred (truthfulness in place) |
| **Decision** | Out of scope — [token-application-scope-decision.md](token-application-scope-decision.md). |
| **Evidence** | `plugin/src/Domain/BuildPlan/Steps/Tokens/Tokens_Step_UI_Service.php`: bulk apply/deny disabled in `placeholder_bulk_states()`; detail panel “Token application is not available in this version. Recommendations are for review only.” (L188). `docs/contracts/execution-action-contract.md`: implementation status states `apply_token_set` infrastructure exists, token application not user-facing, step recommendation-only. APPLY_TOKEN_SET remains in `Logs_Monitoring_State_Builder::RETRYABLE_JOB_TYPES` (L284) per token-application-descope-criteria (handler retained). |
| **Why open** | N/A — deferred; UI and contract do not present apply as available. |
| **Next required** | None unless product brings token application in scope. |

---

### 2.5 UPDATE_PAGE_METADATA truthfulness

| Field | Value |
|-------|--------|
| **Category** | Removed / de-scoped |
| **Decision** | Out of scope — [update-page-metadata-scope-decision.md](update-page-metadata-scope-decision.md). |
| **Evidence** | `plugin/src/Domain/Execution/Queue/Bulk_Executor.php` L36: comment “ITEM_TYPE_SEO not mapped: UPDATE_PAGE_METADATA out of scope”. No `update_page_metadata` in `Queue_Health_Summary_Builder`, `Queue_Recovery_Service`, or `Logs_Monitoring_State_Builder::RETRYABLE_JOB_TYPES` (L280–286: create_page, replace_page, update_menu, apply_token_set, finalize_plan, rollback_action only). `docs/contracts/execution-action-contract.md`: implementation status “update_page_metadata not implemented; SEO step is recommendation-only.” Type constant remains in `Execution_Action_Types.php` per descope criteria (contract stability). |
| **Why open** | N/A — de-scoped from mapping and health/recovery/logs. |
| **Next required** | None. |

---

### 2.6 Build Plan Step 2 Deny / workspace detail–table improvements

| Field | Value |
|-------|--------|
| **Category** | Blocked on spec/product decision (or not in backlog) |
| **Decision** | No decision record found. |
| **Evidence** | [approved-backlog-implementation-summary.md](approved-backlog-implementation-summary.md) §3: “Build Plan Step 2 Deny / workspace detail–table improvements — No decision records found; not implemented.” Master spec §33 (Step 2: New Page Creation) and guides describe approve, Build All/Build selected; no separate “Step 2 Deny” decision. |
| **Why open** | Unclear whether “Step 2 Deny” is a distinct product request (e.g. Deny all for new-page step) or general workspace improvements; no approved scope. |
| **Next required** | **Product/spec:** If desired, define “Step 2 Deny” and/or workspace detail–table improvements and add decision/acceptance criteria. Then **code** if approved. |

---

### 2.7 ACF / industry registry / comparison / restore stabilization

| Field | Value |
|-------|--------|
| **Category** | Approved and implemented (stabilization pass) |
| **Decision** | N/A — stabilization only. |
| **Evidence** | [stabilization-pass-summary.md](stabilization-pass-summary.md): Industry_Packs_Module docblock (`plugin/src/Bootstrap/Industry_Packs_Module.php`); Conversion_Goal_Comparison_Screen hardening (`plugin/src/Admin/Screens/Industry/Conversion_Goal_Comparison_Screen.php`); restore pipeline styling skipped_reasons (`Restore_Pipeline.php`, `Import_Export_Screen.php`); ACF page-level visibility verified, no gap. |
| **Why open** | N/A — closed. |
| **Next required** | None. |

---

## 3. Summary table

| Item | Category | Evidence (file or doc) |
|------|----------|-------------------------|
| Industry bundle apply | Blocked on spec/product decision | industry-bundle-apply-decision.md (in scope); acceptance criteria unchecked; no persistence design |
| ZIP upload cap | Approved and implemented | Import_Export_Screen.php, Import_Export_Zip_Size_Limit_Test.php |
| Privacy scope expansion | Intentionally deferred | privacy-exporter-eraser-scope-decision.md (out of scope) |
| Token application truthfulness | Intentionally deferred | Tokens_Step_UI_Service.php, execution-action-contract.md |
| UPDATE_PAGE_METADATA | Removed / de-scoped | Bulk_Executor.php, Queue_Health_Summary_Builder, Queue_Recovery_Service, Logs_Monitoring_State_Builder, execution-action-contract.md |
| Build Plan Step 2 Deny / workspace | Blocked on spec/product decision | No decision record; approved-backlog-implementation-summary.md §3 |
| ACF/industry/restore stabilization | Approved and implemented | stabilization-pass-summary.md, Industry_Packs_Module, Conversion_Goal_Comparison_Screen, Restore_Pipeline, Import_Export_Screen |

---

## 4. Remaining action list (what is still open)

| # | Item | Why open | Next required |
|---|------|----------|----------------|
| 1 | **Industry bundle apply** | Persistence/store for applied bundle (or registry merge) not defined. | Spec/product: define storage or registry merge. Then code: apply handler, validation, conflict → write. |
| 2 | **Build Plan Step 2 Deny / workspace improvements** | No decision or scope. | Product/spec: define if in scope and acceptance criteria. Then code if approved. |

All other items from the audited list are either implemented, intentionally deferred with no implementation planned, or de-scoped. No further code changes required for deferred or de-scoped items unless product/spec later changes.
