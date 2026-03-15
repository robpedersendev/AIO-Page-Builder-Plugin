# ACF Registration — Hook Timing Report

**Prompt**: 294 (ACF hook timing validation and registration sequence hardening)  
**Contracts**: acf-conditional-registration-contract.md, large-scale-acf-lpagery-binding-contract.md §6.2–6.3

---

## 1. Current hook and priority

| Item | Value |
|------|--------|
| **Hook** | `acf/init` |
| **Priority** | 5 |
| **Registered in** | `ACF_Registration_Provider::register()` |
| **Action** | Resolve section keys from context → call `ACF_Group_Registrar::register_sections( $section_keys )` (or skip). |

Scoped registration runs inside this single `acf/init` callback. No duplicate registration or later `register_all()` is triggered from generic request bootstrap.

---

## 2. Sequencing guarantees

| Requirement | Guarantee |
|-------------|-----------|
| **Context and assignment available** | By `acf/init`, WordPress has loaded; `is_admin()`, `$pagenow`, `$_GET['post']` / `$_GET['post_type']`, and `get_post_type()` are available. Assignment map is in the database and readable via `Page_Field_Group_Assignment_Service`. Template/composition derivation uses registries that are loaded by the time admin screens run. |
| **Before ACF field-group list construction** | ACF builds its list of local field groups from groups registered before or during `acf/init`. Registering at priority 5 ensures our groups are added before ACF uses the list for the edit screen. |
| **No late heavy path** | The bootstrap closure calls only `run_registration()` on the controller, which never calls `register_all()` for generic requests. Full registration is only via explicit `run_full_registration()` (tooling). |

---

## 3. Safe fallback when data is missing

If at registration time the context cannot be resolved (e.g. ambiguous request) or assignment/derivation returns no keys, the controller registers **zero groups** and returns 0. It does not fall back to `register_all()`. Unsupported or non-page admin contexts also yield zero groups. This satisfies “safe fallback if required data is not available at the expected timing point.”

---

## 4. Order of operations (bootstrap path)

1. **acf/init** fires (priority 5).
2. Cache listener: `Page_Section_Key_Cache_Service::listen_for_assignment_changes()` (one-time hook registration).
3. **ACF_Registration_Bootstrap_Controller::run_registration()**:
   - `Registration_Request_Context::should_skip_registration()` → if true (front-end), return 0.
   - `Admin_Post_Edit_Context_Resolver::resolve()` → typed context.
   - If existing-page edit: resolve section keys (assignment map + cache optional) → `register_sections( $section_keys )`.
   - If new-page edit: resolve section keys (template/composition filters + cache optional) → `register_sections( $section_keys )`.
   - Otherwise: return 0 (no registration).
4. ACF continues with remaining `acf/init` callbacks and later uses the registered groups for the edit screen.

---

## 5. Validation and manual QA

- **Unit/integration**: Controller and context resolver tests confirm branching and that `register_all()` is not called from `run_registration()`.
- **Manual QA**: Load existing-page edit and new-page edit screens; expected ACF groups appear. Load front-end and non-page admin; no registration path runs. No duplicate or late full registration.

---

*Timing logic remains centralized in ACF_Registration_Provider and ACF_Registration_Bootstrap_Controller. See acf-conditional-registration-contract.md §11 for revision history.*
