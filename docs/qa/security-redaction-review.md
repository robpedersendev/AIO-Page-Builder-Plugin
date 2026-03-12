# Security Tightening and Redaction Review

**Governs:** Spec §43 (Security and Permissions), §43.3–43.6, §43.13–43.14, §45.9, §46.8–46.9, §52.6, §56.5; Hardening matrix §59.14 (security, redaction).  
**Purpose:** Evidence for capability, nonce, request-safety, and redaction coverage; closure or waiver of high-severity issues.  
**Scope:** Admin routes, execution/rollback triggers, import/export, reporting, logs, provider integrations, artifact/detail surfaces.

---

## 1. Capability Enforcement (§43.3)

| Surface | Capability | Evidence |
|---------|------------|----------|
| Admin menu / screens | Per-screen: VIEW_BUILD_PLANS, VIEW_AI_RUNS, VIEW_LOGS, get_capability() for Dashboard, Settings, Diagnostics, Onboarding, Crawler, Queue & Logs, Build Plans. Menu gated by WordPress admin callback. | current_user_can() at screen render or before handler. |
| Build Plan workspace (approve/deny, bulk, rollback) | APPROVE_BUILD_PLANS for step 1/2/navigation; EXECUTE_ROLLBACKS for rollback. Export/View artifacts: EXPORT_DATA or DOWNLOAD_ARTIFACTS; VIEW_SENSITIVE_DIAGNOSTICS for raw. | Build_Plan_Workspace_Screen: capability check before each maybe_handle_*; render uses can_approve, can_execute, can_view_artifacts, can_rollback. |
| Execution (single action) | Actor envelope ACTOR_CAPABILITY_CHECKED; default_capability_check uses current_user_can($cap). | Single_Action_Executor::default_capability_check(). |
| Rollback eligibility | EXECUTE_ROLLBACKS required; skip_permission only for internal. | Rollback_Eligibility_Service checks current_user_can($required_cap). |
| Queue & Logs | VIEW_LOGS. | Queue_Logs_Screen::render() returns early if !current_user_can($this->get_capability()). |
| AI Run Detail | VIEW_AI_RUNS for screen; VIEW_SENSITIVE_DIAGNOSTICS for raw artifact content. | AI_Run_Detail_Screen: get_capability(); $can_view_raw gates include_raw in get_artifact_summary_for_review. |
| Onboarding | Screen capability; POST actions. | Onboarding_Screen: current_user_can($this->get_capability()); nonce on POST. |
| Export / Import | Export_Generator and Restore_Pipeline do not check capability; **callers must enforce** EXPORT_DATA / IMPORT_DATA. Uninstall export runs in uninstall flow (WordPress uninstall UI is capability-gated). | Documented; any future admin export/import UI must check EXPORT_DATA/IMPORT_DATA before calling generate/validate/restore. |

**REST:** No REST routes are registered in the AIO Page Builder bootstrap (Plugin.php). Any future REST endpoint must register an explicit permission_callback per §43.9.

---

## 2. Nonce Coverage (§43.4)

| Request type | Nonce action | Evidence |
|--------------|--------------|----------|
| Build Plan Step 1 (bulk / single approve/deny) | NONCE_ACTION_STEP1_REVIEW | wp_verify_nonce on POST and GET actions. |
| Build Plan Step 2 (bulk / single) | NONCE_ACTION_STEP2_REVIEW | wp_verify_nonce on POST and GET. |
| Build Plan Navigation (bulk / single) | NONCE_ACTION_NAVIGATION_REVIEW | wp_verify_nonce on POST and GET. |
| Build Plan Rollback | NONCE_ACTION_ROLLBACK | wp_verify_nonce before processing rollback request. |
| Onboarding POST (save draft, advance, go back) | NONCE_ACTION (field name); same action for verify | wp_verify_nonce on POST in handle_post(). |

Nonces support request integrity; capability checks are separate and applied as above.

---

## 3. Import / Upload / Restore Safety (§43.11, §43.12, §52.7)

| Check | Implementation |
|-------|-----------------|
| Permission | Caller of Import_Validator::validate() and Restore_Pipeline must enforce IMPORT_DATA. |
| ZIP integrity | ZipArchive::open( RDONLY ); validation fails if not openable. |
| Manifest | manifest.json required; required keys (Export_Bundle_Schema::manifest_has_required_keys). |
| Schema version | check_schema_version: migration_floor, same major, incoming ≤ current; blocks newer or below floor. |
| Prohibited paths | ALLOWED_PREFIXES allowlist; path traversal (..) rejected; any other path → prohibited. |
| No arbitrary code execution | No extract of PHP/executables; ZIP used for JSON/data only; no include/require from package. |
| Checksum | Optional verify_checksums; mismatch adds warning, does not block (per current contract). |

Import_Validator returns failures and does not write until caller runs Restore_Pipeline after validation passes.

---

## 4. Export Exclusions (§52.6)

| Rule | Implementation |
|------|----------------|
| EXCLUDED_CATEGORIES | Export_Bundle_Schema::EXCLUDED_CATEGORIES (api_keys, passwords, auth_session_tokens, runtime_lock_rows, temporary_cache, corrupted_remnants). These are never in INCLUDED_CATEGORIES or optional_included; Export_Generator uses get_categories_for_mode() and does not add excluded categories to the bundle. |
| Secrets in export | Provider secrets and credentials are not part of export categories; profile/settings export uses redaction where applicable. Token sets in export are design tokens, not auth tokens. |

---

## 5. Provider Secret Handling and Redaction (§43.13, §43.14, §45.9, §46.8–46.9)

| Surface | Mechanism |
|---------|------------|
| Artifact summary for review | AI_Run_Artifact_Service::get_artifact_summary_for_review(): REDACT_BEFORE_DISPLAY categories (raw_prompt, raw_response, normalized_prompt_package, input_snapshot) summarized as [redacted] unless $include_raw true. $include_raw only when caller has VIEW_SENSITIVE_DIAGNOSTICS. |
| Run metadata display | AI_Run_Artifact_Service::redact_sensitive_values() for run_metadata (api_key, secret, token, password, authorization, etc.). |
| Secret_Redactor | Domain\AI\Secrets\Secret_Redactor: redact_array/redact_string for logs, export, reports; SECRET_KEYS and SECRET_KEY_SUFFIXES. |
| Reporting (developer error) | Reporting_Redaction_Service: redact_message (patterns for password, api_key, bearer, token, nonce, etc.), redact_context (prohibited keys), build_sanitized_summary. Developer_Error_Reporting_Service uses redaction before send; payloads conform to §46.8–46.9. |
| Logs / admin display | Queue_Logs_Screen and log state builders use redacted/sanitized data; no raw secrets in UI. |

---

## 6. Execution and Rollback Entrypoints

| Entrypoint | Authorization |
|------------|---------------|
| Build Plan approve/deny/bulk | Capability APPROVE_BUILD_PLANS; nonce per action. |
| Build Plan rollback request | Capability EXECUTE_ROLLBACKS; nonce NONCE_ACTION_ROLLBACK. |
| Execution (queue/job) | Execution envelope carries capability; Single_Action_Executor::default_capability_check uses current_user_can. Queue execution runs in cron/admin context; job dispatch should be gated by same capability. |
| Rollback execution | Rollback_Eligibility_Service::check_eligibility enforces EXECUTE_ROLLBACKS unless skip_permission (internal). |

---

## 7. Issue Register and Fix Status

| id | Category | Severity | Title | Affected surface | Status | Fix / evidence / waiver_id |
|----|----------|----------|-------|------------------|--------|----------------------------|
| *(none open)* | — | — | — | — | — | — |

No high-severity security or redaction issues were verified in this pass. If any are found, add a row and either fix (document in Fix column) or create a waiver record per hardening matrix §5.2.

---

## 8. Residual Caveats and Waivers

- **Export/Import capability at callers:** Export_Generator and Restore_Pipeline do not perform capability checks. Uninstall_Export_Prompt_Service is invoked from uninstall flow (WordPress uninstall UI is restricted). Any future admin UI that triggers export or import/restore must enforce EXPORT_DATA and IMPORT_DATA before calling these services.
- **REST:** No REST routes in current AIO bootstrap. When adding REST, every route must have permission_callback and follow §43.9.
- **AJAX:** No AJAX handlers were found in AIO code. Any future AJAX must use capability checks, nonce where applicable, and sanitization per §43.10.

---

## 9. QA Evidence Summary

- **Capability:** Audited admin screens, Build Plan handlers, rollback, execution default_capability_check, Queue_Logs, Onboarding, AI Run Detail. All enforce capability or document caller responsibility.
- **Nonce:** Build Plan step 1/2/navigation/rollback and Onboarding POST verified with wp_verify_nonce.
- **Import safety:** Path allowlist, .. rejection, manifest and schema version checks; no code execution from package.
- **Redaction:** Secret_Redactor, AI_Run_Artifact_Service (redact_sensitive_values, REDACT_BEFORE_DISPLAY), Reporting_Redaction_Service, Developer_Error_Reporting_Service; Export_Bundle_Schema EXCLUDED_CATEGORIES.
- **Negative tests:** Recommended: unauthorized mutation attempts (e.g. missing nonce, wrong capability) and prohibited path import; document results in this section when run.
- **Evidence artifacts:** Keep evidence redacted; no raw secrets in checklists or logs.

---

## 10. Hardening Matrix Alignment

- **Security gate (gate 1):** REST/AJAX permission, no secrets in logs/exports, explicit permission callbacks. Addressed above; no REST in scope; export/import caller responsibility documented.
- **Redaction gate (gate 6):** Logs, exports, reports, diagnostics free of secrets; redaction rules applied. Addressed in §5 and §9.
- **Issue register (§5.1):** Use table in §7 for any security/redaction issue; link waiver_id to waiver record if waived.
