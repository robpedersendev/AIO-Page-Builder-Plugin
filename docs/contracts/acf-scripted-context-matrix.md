# ACF Registration — Scripted Context Matrix

**Prompt**: 304  
**Upstream**: acf-conditional-registration-contract.md, acf-registration-exception-matrix.md

---

## 1. Purpose

Defines ACF conditional-registration behavior for WP-CLI, cron, repair scripts, maintenance, and other scripted execution so these flows do not trigger full or ambiguous registration. Undefined automation contexts default to **skip** (no registration).

---

## 2. Rule

- **Generic bootstrap** (e.g. `acf/init` during a request): Calls `run_registration()`. For scripted contexts, `Registration_Request_Context::should_skip_registration()` returns true, so **no** ACF groups are registered.
- **Explicit tooling** that needs full or broad registration must invoke it from its own entry point (e.g. a WP-CLI command that calls `run_full_registration()` after loading context). See acf-registration-exception-matrix.md. Such tooling is **not** triggered by generic `acf/init`.

---

## 3. Contexts and intended behavior

| Context | Detection | Registration behavior |
|---------|-----------|------------------------|
| **WP-CLI** | `defined('WP_CLI') && WP_CLI` | Skip by default. No scoped registration from acf/init. Commands that need full registration must call it explicitly (exception matrix). |
| **Cron** | `defined('DOING_CRON') && DOING_CRON` | Skip. No registration from acf/init during cron. |
| **Front-end** | `! is_admin()` | Skip (unchanged). |
| **Admin (browser)** | `is_admin()` and not CLI/cron | Scoped or zero registration per Admin_Post_Edit_Context_Resolver (existing-page, new-page, non-page). |
| **REST (non-admin)** | REST request outside admin | Treated as non-admin; skip. |
| **Other scripted** | Unrecognized automation | Safe default: skip. Do not fall back to full registration. |

---

## 4. Implementation

- `Registration_Request_Context::is_cli()`, `is_cron()`, `is_scripted_context()` added (Prompt 304).
- `should_skip_registration()` returns true when `is_front_end()` **or** `is_scripted_context()`.
- `ACF_Registration_Bootstrap_Controller::run_registration()` consults `request_context->should_skip_registration()` first; when true it records `MODE_FRONT_END_SKIP` or `MODE_SCRIPTED_SKIP` and returns 0.

---

## 5. Allowed scripted exceptions

Full registration in scripted contexts is allowed **only** when a documented tool (e.g. ACF Regeneration Service, Debug Exporter) is **explicitly** invoked—e.g. from a WP-CLI command or a scheduled task that calls that tool. That tool then calls `run_full_registration()` or equivalent from its own code path, not from `acf/init`. No request parameter or environment variable may switch generic bootstrap into full registration.

---

## 6. Cross-references

- acf-conditional-registration-contract.md §4.5
- acf-registration-exception-matrix.md
- Registration_Request_Context.php
