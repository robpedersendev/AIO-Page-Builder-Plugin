# Capability Matrix

**Document type:** Authoritative contract for custom capabilities and default role mappings (spec §44, §62.2).  
**Governs:** All privileged actions; screen and handler gating.  
**Reference:** Master Specification §4.17, §43.1, §43.3–43.6, §44, §62.2.

---

## 1. Design rules

- **Least privilege:** No major workflow relies only on generic WordPress edit permissions.
- **Stable names:** Capability names are constants in `Infrastructure\Config\Capabilities`; do not rename.
- **Nonces:** Nonces do not replace capabilities; all privileged actions require server-side capability checks.
- **Extension:** Custom/support roles may be added later; matrix leaves space for additional columns.

---

## 2. Default role mappings (spec §44.2)

| Role | Plugin capabilities by default |
|------|---------------------------------|
| **Administrator** | All plugin capabilities. |
| **Editor** | Limited subset only: view Build Plans, approve/deny recommendations, view non-sensitive logs. No provider management, reporting settings, raw artifact export, or replacement/rollback execution. |
| **Author** | None. |
| **Contributor** | None. |
| **Subscriber** | None. |

---

## 3. Capability matrix table

| Capability | Description | Admin | Editor | Author | Contributor | Subscriber | Notes |
|------------|-------------|:-----:|:------:|:------:|:-----------:|:----------:|-------|
| `aio_manage_settings` | Plugin settings | ✓ | — | — | — | — | |
| `aio_manage_section_templates` | Section template registry | ✓ | — | — | — | — | §44.5 |
| `aio_manage_page_templates` | Page template registry | ✓ | — | — | — | — | §44.5 |
| `aio_manage_compositions` | Custom compositions | ✓ | — | — | — | — | §44.5 |
| `aio_manage_brand_profile` | Brand/business profile | ✓ | — | — | — | — | |
| `aio_run_onboarding` | Run onboarding flow | ✓ | — | — | — | — | |
| `aio_manage_ai_providers` | Provider credentials, defaults | ✓ | — | — | — | — | §44.6; Editor denied |
| `aio_run_ai_plans` | Initiate AI planning runs | ✓ | — | — | — | — | §44.6 |
| `aio_view_build_plans` | Read plan details | ✓ | ✓ | — | — | — | §44.7 |
| `aio_approve_build_plans` | Approve/deny recommendations | ✓ | ✓ | — | — | — | §44.7 |
| `aio_execute_build_plans` | Execute non-destructive build actions | ✓ | — | — | — | — | §44.7 |
| `aio_execute_page_replacements` | Replace existing pages | ✓ | — | — | — | — | §44.8; high-impact |
| `aio_manage_navigation_changes` | Menu changes | ✓ | — | — | — | — | §44.8 |
| `aio_manage_token_changes` | Branding/token application | ✓ | — | — | — | — | §44.8 |
| `aio_finalize_plan_actions` | Publish/finalize plan actions | ✓ | — | — | — | — | §44.7 |
| `aio_view_logs` | View normal operational logs | ✓ | ✓ | — | — | — | §44.10; non-sensitive |
| `aio_view_sensitive_diagnostics` | Provider errors, reporting failures, queue payloads | ✓ | — | — | — | — | §44.10 |
| `aio_download_artifacts` | Raw prompts, responses, AI bundles | ✓ | — | — | — | — | §44.9; Editor denied |
| `aio_export_data` | Export data | ✓ | — | — | — | — | |
| `aio_import_data` | Import data | ✓ | — | — | — | — | §44.4 |
| `aio_execute_rollbacks` | Rollback jobs | ✓ | — | — | — | — | §44.8; Editor denied |
| `aio_manage_reporting_and_privacy` | Reporting settings, retention, privacy exports | ✓ | — | — | — | — | §44.11; Editor denied |

— = not granted by default.

---

## 4. Template workflows (expanded ecosystem, §49.6, §49.7, §62.2)

Template-related screens and actions use the following capability mapping. UI visibility and server-side checks both enforce these; no screen relies on hiding alone.

| Surface | Capability | Notes |
|--------|------------|-------|
| Section Templates directory & detail | `aio_manage_section_templates` | Browsing, metadata, helper-doc; §44.5. |
| Page Templates directory & detail | `aio_manage_page_templates` | Browsing, metadata, one-pager; §44.5. |
| Template Compare | `aio_manage_page_templates` | Compare list add/remove; nonce + capability in handler. |
| Compositions list & builder | `aio_manage_compositions` | List, build, create/edit; §44.5. |
| Template Analytics | `aio_view_logs` | Observational dashboard; support-oriented. |
| Build Plan Analytics | `aio_view_build_plans` | Plan-focused; unchanged. |

Versioning, deprecation, and export for templates/compositions are gated by the same MANAGE_* caps via CPT map_cap and screen capability. See `docs/qa/template-capability-audit-report.md` for the full audit table.

---

## 5. Implementation

- **Source of truth:** `src/Infrastructure/Config/Capabilities.php` defines constants and `getAll()`, `get_editor_defaults()`, `is_plugin_capability()`.
- **Registration:** `src/Bootstrap/Capability_Registrar.php` registers capabilities at activation via `Lifecycle_Manager::register_capabilities`. `register()` adds all caps to Administrator and editor subset to Editor. `remove_from_all_roles()` used at uninstall.
- **Runtime:** Use `current_user_can( Capabilities::MANAGE_SETTINGS )` (or equivalent) for gating; capability availability is inspectable via `Capability_Registrar::role_has_cap()` for diagnostics.

---

## 6. Future roles

The matrix may be extended with additional columns for custom or support roles. Internal capability names remain unchanged; only mappings change.

---

## 7. Verification

- **Stable capability strings:** Unit test `Capabilities_Test` asserts that `Capabilities::getAll()` returns 22 items, constants match expected strings, `get_editor_defaults()` returns exactly the three Editor caps, and `is_plugin_capability()` behaves correctly.
- **Role mappings (post-activation):** After activating the plugin in a WordPress environment, verify: (1) Administrator has all plugin capabilities; (2) Editor has only `aio_view_build_plans`, `aio_approve_build_plans`, `aio_view_logs`; (3) Author, Contributor, and Subscriber have no plugin capabilities. Use `Capability_Registrar::role_has_cap( $role_key, $cap )` or role inspection in admin to confirm.
