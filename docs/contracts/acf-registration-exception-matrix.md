# ACF Registration — Exception Matrix (Full Registration / Broad Load)

**Prompt**: 297  
**Upstream**: acf-conditional-registration-contract.md

---

## 1. Purpose

This matrix lists the **only** contexts where full ACF registration (`register_all()`) or broad blueprint/definition loading (`get_all_blueprints()`, `list_all_definitions( 9999, 0 )`) is permitted. All other requests (front-end, admin page edit, non-page admin, autosave, preview, etc.) must use no registration or scoped registration only.

---

## 2. Rule

- **Default**: Generic request bootstrap (e.g. `acf/init`) must **never** call `run_full_registration()` or `register_all()`.
- **Exception**: Only the explicitly documented tooling contexts below may invoke full registration or equivalent broad load. Callers must be admin-only, permissioned, and not triggerable by ordinary page views or edit screens.

---

## 3. Approved exception contexts

| Context | Entry point / trigger | Why allowed |
|--------|------------------------|-------------|
| **ACF Field Group Debug Exporter** | Admin screen or export action that explicitly runs debug export. | One-off export of all field groups for comparison or backup. |
| **ACF Local JSON Mirror** | Admin/tooling action that builds or syncs local JSON mirror. | Environment comparison, schema dump. |
| **ACF Migration Verification** | Admin/tooling that runs migration verification. | Post-upgrade verification of field group consistency. |
| **ACF Regeneration Service** | Admin/tooling that runs regeneration/repair. | Repair or rebuild of ACF group registration from registries. |
| **ACF Diagnostics Service** | Admin diagnostics screen that builds architecture health card. | Bounded admin-only diagnostics. |

Each of these uses `get_all_blueprints()` or equivalent broad load **only** when invoked from their own explicit entry point (e.g. specific admin screen, CLI, or tooling action). They must **not** be invoked from `acf/init` or from generic front-end or admin request bootstrap.

---

## 4. Enforcement

- **Bootstrap**: `ACF_Registration_Provider` hooks `acf/init` (priority 5) to `run_registration()` only. It does **not** call `run_full_registration()`.
- **Controller**: `run_full_registration()` exists for explicit tooling only. Any future caller must be one of the documented exception contexts (or added to this matrix with justification).
- **Guard**: Do not add code that calls `run_full_registration()` or `register_all()` from request bootstrap, REST endpoints used by front-end, or unpermissioned paths.

---

## 5. Cross-references

- acf-conditional-registration-contract.md §4.5 Tooling / explicit full registration
- docs/qa/acf-blueprint-bulk-load-elimination-report.md
- Section_Field_Blueprint_Service::get_all_blueprints() docblock (reserved for explicit tooling)
