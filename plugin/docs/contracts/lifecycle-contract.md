# Lifecycle Contract

**Document type:** Authoritative contract for activation, deactivation, and uninstall orchestration.  
**Governs:** Hook order, blocking-failure behavior, non-destructive deactivation, uninstall safety, and future extension points.  
**Reference:** Master Specification §4.18, §5.10, §6.4, §6.5, §9.12, §52.11, §53.1, §53.2.

---

## 1. Lifecycle result statuses

Every phase and overall run returns a **lifecycle result** with:

| Status              | Meaning |
|---------------------|--------|
| `success`           | Phase or run completed; no blocking issue. |
| `warning`           | Non-blocking issue; activation may continue. |
| `blocking_failure`  | Activation must abort; user is informed. |

Result shape: `status`, `message`, `phase` (optional), `details` (optional). Implemented as `Lifecycle_Result` in code; `to_array()` for array shape.

---

## 2. Activation phase ordering

Phases run in this order. First **blocking** result stops activation and aborts (plugin deactivated, message shown).

| Phase key | Description | Blocking / non-blocking | Current status | Future owning prompt |
|-----------|-------------|--------------------------|----------------|----------------------|
| `validate_environment` | WP/PHP version and environment checks | Blocking if failed | Placeholder no-op | Environment validation |
| `check_dependencies` | Required plugins (e.g. ACF) | Blocking if failed | Placeholder no-op | Dependency checks |
| `init_options` | Plugin option initialization | Blocking if failed | Placeholder no-op | Settings / options |
| `check_tables_schema` | Custom tables / schema creation check | Blocking if failed | Placeholder no-op | Storage / migrations |
| `register_capabilities` | Capability registration | Blocking if failed | Placeholder no-op | Security / capabilities |
| `register_schedules` | Recurring WP-Cron / schedule registration | Non-blocking | Placeholder no-op | Reporting / cron |
| `install_notification_eligibility` | Install notification eligibility check | Non-blocking | Placeholder no-op | Reporting |
| `first_run_redirect_readiness` | First-time setup / redirect to Dashboard or Onboarding | Non-blocking | Placeholder no-op | Admin / onboarding |

**Extension point:** Later prompts plug into these phases via `Lifecycle_Manager` without rewriting hook order. Add logic inside the existing phase methods or via dedicated services called from them.

---

## 3. Deactivation phase ordering

Phases run in order. **Non-destructive:** no deletion of options, content, or built pages.

| Phase key | Description | Current status | Future owning prompt |
|-----------|-------------|----------------|----------------------|
| `unschedule` | Unschedule cron jobs, stop queue workers | Placeholder no-op | Reporting / cron |
| `teardown_runtime` | Flush caches, stop runtime services | Placeholder no-op | Runtime teardown |

Deactivation does **not** delete plugin-owned data; uninstall (with export-before-cleanup pathway) owns cleanup.

---

## 4. Uninstall safety rules

- **Guarded:** Uninstall logic runs only when WordPress invokes uninstall (e.g. `WP_UNINSTALL_PLUGIN` defined). No request-driven uninstall.
- **Non-destructive until export pathway exists:** Current uninstall performs no deletion. Built pages and user content are never removed by uninstall.
- **Export-before-cleanup (future):** Per §52.11, before cleanup the plugin shall present an export prompt (full backup, settings only, skip, cancel). Uninstall screen shall state that built pages will remain. This is a **privileged, server-side** flow; not frontend-only.
- **Phase placeholders:** `export_reminder_integration`, `cleanup_plugin_data`. No deletion in current implementation.

| Phase key | Description | Current status | Future owning prompt |
|-----------|-------------|----------------|----------------------|
| `export_reminder_integration` | Export prompt and choices; state built pages remain | Placeholder no-op | Export / restore |
| `cleanup_plugin_data` | Remove plugin-owned operational data only; never built pages | Placeholder no-op | Export / uninstall |

---

## 5. Hook wiring

- **Root file** (`aio-page-builder.php`): Registers `register_activation_hook( __FILE__, [ Plugin::class, 'activate' ] )`, `register_deactivation_hook( __FILE__, [ Plugin::class, 'deactivate' ] )`. No other lifecycle logic in root.
- **Plugin::activate():** Instantiates `Lifecycle_Manager`, runs `activate()`. On `blocking_failure`, deactivates plugin and `wp_die()` with message.
- **Plugin::deactivate():** Instantiates `Lifecycle_Manager`, runs `deactivate()`.
- **uninstall.php:** Exits unless `WP_UNINSTALL_PLUGIN` defined. May call `Lifecycle_Manager::uninstall()` for orchestration; no deletion until export/restore contract exists.

---

## 6. Security

- No lifecycle phase may trust request input. All orchestration is server-side.
- Future export-before-uninstall is privileged and non-frontend.
- Uninstall remains protected by WordPress uninstall protections (constant check, no direct request-driven deletion).

---

## 7. Manual verification checklist (Prompt 005)

- [ ] Activation runs and calls `Lifecycle_Manager::activate()`; no fatal.
- [ ] Deactivation runs and calls `Lifecycle_Manager::deactivate()`; no fatal.
- [ ] A phase that returns `blocking_failure` causes activation to abort (plugin deactivated, message shown) without fatal.
- [ ] Uninstall file exits when accessed directly (no `WP_UNINSTALL_PLUGIN`); no deletion.
- [ ] Uninstall does not delete options, capabilities, or content in current implementation.
- [ ] Contract preserves future insertion points: install notification, scheduling, export-before-uninstall, first-run setup.
