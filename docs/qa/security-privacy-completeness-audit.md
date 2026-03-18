# Security, Privacy, Role Scope, and Completeness Audit

**Scope:** Full project audit for security vulnerabilities, privacy concerns, role/capability escape, WordPress plugin attack vectors, placeholders, and unimplemented areas.  
**Date:** 2025-03-16.

---

## 1. Executive summary

- **Security:** State-changing admin paths are protected by nonce and capability checks. SQL uses `$wpdb->prepare`. Input is generally sanitized; a few output-escaping spots should be tightened. No direct `eval`/`unserialize` on user input. File include paths are built from plugin dir + known keys (no user-controlled path traversal).
- **Privacy:** Reporting and diagnostics are documented; no WordPress privacy exporter/eraser hooks found. Secrets are not logged; redaction is used in reporting.
- **Role scope:** Plugin uses custom capabilities (Capability_Registrar); some screens still use `manage_options` instead of plugin caps, creating inconsistency. One widget uses `manage_options`.
- **Completeness:** Several placeholders and “not yet implemented” areas exist (settings screen, dashboard screen, bundle import apply, stub execution handler). Dead/legacy code exists under `PrivatePluginBase` namespace.

---

## 2. Security

### 2.1 Nonces and CSRF

| Area | Status | Notes |
|------|--------|--------|
| Industry profile save | OK | `wp_verify_nonce(..., 'aio_save_industry_profile')` in Admin_Menu. |
| Pack toggle, style preset | OK | Named nonces verified. |
| Override save/remove | OK | Save_Industry_*_Override_Action, Remove_Industry_Override_Action use NONCE_NAME/NONCE_ACTION. |
| Guided repair (migrate/apply/activate) | OK | Nonces verified in Admin_Menu. |
| Bundle preview (preview only; apply not implemented) | OK | POST nonce on upload; no confirm-import action. |
| Export/restore/download | OK | Import_Export_Screen: NONCE_CREATE_EXPORT, VALIDATE, RESTORE, DOWNLOAD. |
| Build Plan approve/deny/rollback | OK | Build_Plan_Workspace_Screen: step-specific nonces. |
| Prompt experiments save/delete | OK | Nonce on save and delete. |
| Section/Page template entity style save | OK | Nonce key in POST. |
| AI credential test/update | OK | Nonce with provider_id in action. |

No state-changing admin action was found without a nonce check.

### 2.2 Capabilities and authorization

| Area | Status | Notes |
|------|--------|--------|
| Industry screens | OK | `current_user_can( $this->get_capability() )` (VIEW_LOGS or equivalent). |
| Override actions | OK | MANAGE_SECTION_TEMPLATES, MANAGE_PAGE_TEMPLATES, APPROVE_BUILD_PLANS per target type. |
| Menu registration | OK | All submenus use screen `get_capability()`. |
| **Inconsistency** | **Review** | Some screens use **manage_options** instead of plugin capabilities: Crawler_Comparison_Screen, Crawler_Sessions_Screen, Crawler_Session_Detail_Screen, Onboarding_Screen, Settings_Screen, Diagnostics_Screen, Dashboard_Screen (placeholder), Dashboard_Screen (Dashboard provider). Industry_Status_Summary_Widget uses **manage_options**. Prefer plugin caps (e.g. VIEW_LOGS, MANAGE_SETTINGS) for consistency and least-privilege. |

### 2.3 SQL and database

- **Prepared statements:** All audited DB access uses `$wpdb->prepare()` (Crawl_Snapshot_Repository, Assignment_Map_Service, Job_Queue_Repository, Abstract_Table_Repository, Export_Token_Set_Reader, Table_Installer, Uninstall_Cleanup_Service, Restore_Pipeline). Table names are from constants or schema. No raw concatenation of user input into SQL.

### 2.4 Input sanitization and output escaping

- **GET/POST:** Common pattern: `isset( $_GET['x'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['x'] ) ) : ''` (or `sanitize_key`, `sanitize_textarea_field`, `sanitize_file_name` where appropriate). Compositions_Screen, Import_Export_Screen, Override_Management_Screen, Prompt_Experiments_Screen, etc. follow this.
- **Output:** Most output uses `esc_html`, `esc_attr`, `esc_url`. Exceptions:
  - **Prompt_Experiments_Screen.php line 73:** `echo $name` — `$name` is set from `esc_html( (string) $def['name'] )` on line 69, so output is already escaped. Using `echo esc_html( $name )` would be redundant but clearer and defensive.
  - **Prompt_Experiments_Screen.php line 138:** `echo $nonce` — `$nonce` is from `wp_nonce_field()` (HTML). Typically allowed via phpcs:ignore for nonce output; consider adding a comment.
  - **Industry_Override_Management_Screen line 132:** `echo $this->render_artifact_link( $row )` — Method returns HTML built with `esc_url` and `esc_html__`; no user input in link text. Safe.
  - **Post_Release_Health_Screen line 160:** `echo $json` with phpcs:ignore — Response is `Content-Type: application/json`; payload is `wp_json_encode()`. Not HTML; acceptable.
  - **Build_Plan_Workspace_Screen:** Multiple `echo $nonce_html` with phpcs:ignore — Nonce HTML; acceptable.
  - **industry-section-badges.php line 43:** `$class` and `$title` are built with `esc_attr`/`sanitize_html_class` before echo; safe.
  - **Global_Style_Token_Settings_Screen line 123:** Placeholder is literal `' placeholder="#000000"'`; safe.

### 2.5 File operations and uploads

- **Bundle file upload (Admin_Menu::handle_industry_bundle_preview):** Nonce and `current_user_can( Capabilities::MANAGE_SETTINGS )` checked. `is_uploaded_file()` used. Content read and `json_decode`; invalid JSON rejected. No extension allowlist; validation is on decoded structure (Industry_Pack_Bundle_Service::validate_bundle). Consider adding a size limit and/or MIME/extension check to reduce DoS or misuse.
- **require/include $path:** All found instances build `$path` from plugin directory and known keys (e.g. builtin definition files). No user input in path; path traversal risk low.

### 2.6 Dangerous functions

- No `eval`, `unserialize` on user input, or dynamic `include`/`require` with user-controlled paths found in active code paths.

### 2.7 REST API

- **Active plugin:** No REST routes registered by the AIOPageBuilder bootstrap (Plugin.php and providers). No public REST surface.
- **Dead code:** `plugin/src/Rest/NamespaceController.php` (PrivatePluginBase) registers `GET /private-plugin-base/v1/status` with `permission_callback` using `PrivatePluginBase\Security\Capabilities::MANAGE`. This file is not loaded by the main plugin entry (aio-page-builder.php loads AIOPageBuilder\Bootstrap\Plugin only). So this REST route is not active.

---

## 3. Privacy

- **Reporting/disclosure:** Privacy_Settings_State_Builder builds disclosure and retention state; no secrets in payload. Reporting_Redaction_Service redacts secret-like patterns.
- **Secrets:** AI provider credentials are stored via Option_Based_Provider_Secret_Store; not echoed in UI (credential status only). Prompt pack schema defines redact_before_export.
- **WordPress privacy tools:** No `wp_privacy_*` exporter or eraser registration found. If the plugin stores personal data (e.g. in logs or profiles), consider adding exporter/eraser per WordPress privacy API.
- **Logging:** No logging of raw credentials, API keys, or passwords observed in audited code.

---

## 4. Role scope and capability escape

- **Custom capabilities:** Capability_Registrar assigns plugin capabilities to Administrator and a subset to Editor. Activation uses Lifecycle_Manager (AIOPageBuilder); PrivatePluginBase\Activation is not used by main plugin.
- **Escape risk:** All industry and most admin screens gate by capability. Risk of a lower role gaining elevated access is low provided:
  - No screen mistakenly uses a weaker cap (e.g. `edit_posts`) for privileged actions.
  - Screens using `manage_options` are intentionally admin-only; if so, consider aligning with plugin caps for consistency and future role mapping (e.g. custom “AIO Manager” role).
- **Widget:** Industry_Status_Summary_Widget uses `manage_options`; if the dashboard is restricted to plugin caps elsewhere, this widget should use a plugin capability (e.g. VIEW_LOGS) for consistency.

---

## 5. Known attack vectors (WordPress plugin)

| Vector | Status |
|--------|--------|
| CSRF on state-changing actions | Mitigated (nonces). |
| SQL injection | Mitigated (prepare). |
| XSS (reflected/stored) | Mostly mitigated; see §2.4 for minor hardening. |
| Unauthorized access (capability bypass) | Mitigated (capability checks); minor inconsistency with manage_options. |
| Path traversal (file include) | Low risk (paths from plugin dir + known keys). |
| Arbitrary file upload | Upload is JSON-only in practice; no executable upload. |
| Sensitive data in logs/export | Mitigated (redaction, no secrets in export). |
| REST without permission_callback | N/A (no active REST routes). |
| AJAX without nonce/capability | No wp_ajax_ handlers found in grep; admin actions are POST/GET with nonce. |

---

## 6. Placeholders and not-yet-implemented

| Location | Description |
|----------|-------------|
| **Settings_Screen** | “Not yet implemented. This screen will show plugin settings and reporting disclosure.” Placeholder screen. |
| **Dashboard_Screen** (root) | “Not yet implemented. This screen will show the plugin dashboard and first-run guidance.” Placeholder screen. |
| **Admin_Menu::handle_industry_bundle_confirm_import** | “Actual import application is not yet implemented; use full export/restore for site moves.” Clears preview transient only. |
| **Stub_Execution_Handler** | Returns “Action type X is not yet implemented” for action types without a real handler. |
| **Tokens_Step_UI_Service** | Shell-only UI; “No token application.” |
| **Profile_Snapshot_Data** | “Placeholder for profile snapshot payload shape … No persistence.” |
| **Additional_AI_Provider_Driver** | `cost_placeholder` => null in config. |

---

## 7. Dead / legacy code

- **PrivatePluginBase namespace (remediated):** Legacy code has been quarantined to `plugin/legacy/` (SPR-011). The following are **not** loaded by the active plugin; they exist only under `plugin/legacy/PrivatePluginBase/` with LEGACY headers and `legacy/README.md`:
  - Bootstrap.php, Activation.php, Deactivation.php, Options.php
  - Security/Capabilities.php, Rest/NamespaceController.php
  - Admin/Menu.php, Admin/Settings/Page.php, Settings/Registrar.php
  - Reporting/Service.php, Diagnostics/Logger.php

  **Single entry point:** `aio-page-builder.php` → `AIOPageBuilder\Bootstrap\Plugin`; activation/deactivation via `Lifecycle_Manager`. No production code loads anything from `legacy/`. See [security-privacy-remediation-ledger.md](../operations/security-privacy-remediation-ledger.md) SPR-011 and `plugin/legacy/README.md`.

---

## 8. Recommendations

1. **Escaping:** In Prompt_Experiments_Screen, use `echo esc_html( $name )` for the experiment name (or add a short comment that `$name` is pre-escaped) for clarity and defensiveness.
2. **Capabilities:** Replace `manage_options` with the appropriate plugin capability on screens that should align with plugin role model (Crawler, Onboarding, Settings, Diagnostics, Dashboard, Industry_Status_Summary_Widget), or document why admin-only is required.
3. **Bundle upload:** Add a file size limit and optionally MIME/extension check for the industry bundle upload.
4. **Privacy:** If any plugin data is “personal data” under applicable law, register `wp_privacy_*` exporter and eraser.
5. **Legacy code:** Remove or isolate PrivatePluginBase (Bootstrap, Activation, Rest, Security) and document that the single entry point is AIOPageBuilder\Bootstrap\Plugin.

---

## 9. References

- [security-privacy-remediation-ledger.md](../operations/security-privacy-remediation-ledger.md) — Remediation execution plan derived from this audit.
- [industry-security-mutation-audit-report.md](industry-security-mutation-audit-report.md) (Prompt 608)
- [industry-audit-remediation-ledger.md](../operations/industry-audit-remediation-ledger.md)
- [.cursor/rules/Security-and-Privacy.mdc](../.cursor/rules/Security-and-Privacy.mdc)
