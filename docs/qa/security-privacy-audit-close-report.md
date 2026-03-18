# Security, Privacy & Completeness — Audit Close Report

**Date:** 2025-03-16  
**Source:** Post-remediation regression and re-audit.  
**Ledger:** [security-privacy-remediation-ledger.md](../operations/security-privacy-remediation-ledger.md).

---

## 1. Regression re-check (evidence)

### 1.1 Nonce coverage on state-changing actions

| Area | Evidence |
|------|----------|
| Admin_Menu admin_post handlers | All handlers verified: nonce checked before capability and business logic (industry profile, toggle pack, style preset, guided repair migrate/apply/activate, bundle preview, all seed actions). |
| Industry override actions | Save_Industry_Section_Override_Action, Save_Industry_Page_Template_Override_Action, Save_Industry_Build_Plan_Override_Action, Remove_Industry_Override_Action: each verifies own nonce + capability. |
| Create plan from bundle | Create_Plan_From_Starter_Bundle_Action: nonce from REQUEST + capability before service call. |
| Prompt experiments | Save: `aio_experiment_nonce` + `aio_save_experiment`. Delete: `_wpnonce` + action `aio_delete_experiment_{id}`. |
| Page template entity style | Page_Template_Detail_Screen: Entity_Style_UI_State_Builder nonce_action; verify on save. |
| Import/Export | Create export, validate, confirm restore, download: each uses dedicated nonce and redirects on failure. |
| Queue logs | Export logs, download log file, queue recovery: nonce verified in handler. |

**Verdict:** No state-changing admin_post or form action found without nonce verification.

### 1.2 Capability checks on plugin-owned screens/actions

| Area | Evidence |
|------|----------|
| Menu registration | Admin_Menu uses `get_capability()` from each screen instance for add_submenu_page (Dashboard, Settings, Diagnostics, Onboarding, Crawler, AI, Build Plans, Templates, etc.). |
| Admin_post handlers | Each handler checks `current_user_can( Capabilities::* )` after nonce (e.g. MANAGE_SETTINGS, MANAGE_SECTION_TEMPLATES, MANAGE_PAGE_TEMPLATES, APPROVE_BUILD_PLANS, IMPORT_DATA, EXPORT_DATA). |
| Screen render | Screens that render content check capability at entry (e.g. Build_Plan_Workspace_Screen, Import_Export_Screen, Industry_Override_Management_Screen). |
| Widget | Industry_Status_Summary_Widget uses VIEW_LOGS via get_required_capability(). |

**Verdict:** No plugin screen or state-changing action found without capability check. All use plugin capabilities (no `manage_options` in remediated set).

### 1.3 Upload / import validation

| Area | Evidence |
|------|----------|
| Industry bundle preview (SPR-001) | Industry_Bundle_Upload_Validator::validate_upload() before read: size limit 10 MB, extension .json, MIME allowlist (finfo). read_parse_validate_bundle() caps read at MAX_BYTES; validates JSON and bundle structure. |
| Import/Export validate | handle_validate(): nonce + IMPORT_DATA; is_uploaded_file(); .zip extension only; move to exports dir then Import_Validator::validate(). No explicit max file size before move (optional hardening). |

**Verdict:** Bundle preview fully hardened. Import/Export: nonce, capability, .zip and is_uploaded_file; optional gap: no pre-move size limit for zip.

### 1.4 Output escaping hotspots

| Area | Evidence |
|------|----------|
| Prompt_Experiments_Screen | Experiment name: esc_html at echo; delete link: esc_url, esc_attr for confirm; nonce output: phpcs:ignore with comment. |
| Other screens | Ledger changelog: AI_Run_Detail_Screen, Industry badges, Page/Section_Template_Detail_Screen, Template_Compare_Screen, Build_Plan_Workspace_Screen — explicit escape at echo where applicable. |

**Verdict:** Remediated hotspots use explicit escape; no new unescaped output found in re-check.

### 1.5 Privacy exporter/eraser coverage

| Area | Evidence |
|------|----------|
| Registration | Plugin::run() adds wp_privacy_personal_data_exporters and wp_privacy_personal_data_erasers. |
| Exporter | Personal_Data_Exporter: AI runs, job queue, template compare user meta, bundle preview transient. |
| Eraser | Personal_Data_Eraser: removes user meta/transient; redacts actor on AI runs and job queue; keeps records for audit. |
| Tests | Personal_Data_Privacy_Test: contract (data/done, items_removed/items_retained/messages/done) and scenarios. |

**Verdict:** Implemented and tested; decision recorded (actor-linked in scope, site-level/audit out of scope).

### 1.6 Placeholder/stub reachability

| Area | Evidence |
|------|----------|
| Execution stub | Single_Action_Executor checks has_handler() before dispatch; returns refused with ERROR_ACTION_NOT_AVAILABLE. Stub_Execution_Handler only reachable if dispatch called without that check. |
| Tokens step | Bulk actions disabled (enabled => false); copy: "Token application is not available in this version. Recommendations are for review only." |
| Build Plan workspace | Copy: "Execution is started from the plan run/queue flow, not from this step." No button implying confirm/execute from step. |
| Bundle apply | SPR-007: deferred; UI preview only, link to Import/Export. |

**Verdict:** No production UI suggests an executable workflow that only returns a stub message.

### 1.7 Legacy bootstrap / REST leftovers

| Area | Evidence |
|------|----------|
| Active entry | aio-page-builder.php loads only Bootstrap/Constants.php and Bootstrap/Plugin.php. |
| Legacy | PrivatePluginBase code under plugin/legacy/; LEGACY headers; legacy/README.md. Not required by plugin. |
| REST | Only register_rest_route in codebase is in plugin/legacy/PrivatePluginBase/Rest/NamespaceController.php (not loaded). No inactive REST route in production. |

**Verdict:** No legacy bootstrap or REST in active path.

---

## 2. Automated tests (remediated areas)

| Area | Test / change |
|------|----------------|
| Stub execution | Stub_Execution_Handler_Test: execute() returns success=false, expected message, artifacts=[]. Single_Action_Executor_Test: test_unregistered_action_type_returns_refused. |
| Bundle upload | Industry_Bundle_Upload_Validator_Test: validate_upload rejects empty/error; read_parse_validate rejects oversized, invalid JSON, invalid bundle structure. |
| Privacy | Personal_Data_Privacy_Test: exporter/eraser contract and scenarios. |
| Capabilities | Capabilities_Test, Crawler_Admin_Screen_Test, Industry_Status_Summary_Widget_Test, Dashboard_Screen_Test: plugin capability values. |
| Legacy | BootstrapTest: loads from plugin/legacy/ only; documents quarantined code. |

---

## 3. Remaining gaps (product/spec approval)

| Gap | Reason | Action |
|-----|--------|--------|
| Industry bundle “apply” not implemented | Spec/export-restore contract: apply deferred; UI honest (SPR-007). | None unless spec defines apply. |
| Import/Export zip: no pre-move size limit | Current: .zip + is_uploaded_file; validation runs after move. Large zip could be moved then fail. | Optional: add size check (e.g. PHP upload_max / or explicit cap) before move if product wants. |
| Personal data scope | Exporter/eraser cover actor-linked data; site-level options/audit not keyed by user. | Documented in ledger §6; no change unless spec expands scope. |

---

## 4. Summary

- **Nonce:** All state-changing actions re-checked; nonce present and verified before capability and logic.
- **Capability:** All plugin screens and admin_post handlers use plugin capabilities; no `manage_options` in remediated set.
- **Upload:** Bundle preview fully validated (size, MIME, structure). Import/Export: nonce, capability, .zip; optional size limit before move.
- **Escaping:** Remediated hotspots use explicit escape; no new issues.
- **Privacy:** Exporter/eraser registered and tested; scope documented.
- **Stubs/placeholders:** Executor gates unregistered types; UI copy and disabled actions prevent misleading workflows.
- **Legacy:** Quarantined; not loaded; single entry documented.

All SPR items in the ledger are **Fixed** except **SPR-007** (intentionally deferred). No finding remains open due to coding alone; remaining gaps are optional hardening or spec/product decisions.
