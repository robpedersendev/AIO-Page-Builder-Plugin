# ACF Registration Performance — Impact Analysis and Verification

**Prompt**: 281 (ACF registration performance retrofit audit and contract hardening)

**Contracts**: acf-conditional-registration-contract.md, large-scale-acf-lpagery-binding-contract.md §6.2–6.3, acf-page-visibility-contract.md

---

## 1. Impact analysis

### 1.1 Current hot path (confirmed)

- **Trigger**: `acf/init` (every request).
- **Flow**: `ACF_Registration_Provider` → `ACF_Group_Registrar::register_all()` → `Section_Field_Blueprint_Service::get_all_blueprints()` → `Section_Template_Repository::list_all_definitions( 9999, 0 )`.
- **Impact**: Every front-end and admin request loads all section CPT definitions and registers all section-owned ACF groups. At 250+ sections this is a major performance cost (DB + object work).

### 1.2 Affected areas (post-retrofit)

| Area | Impact |
|------|--------|
| **Bootstrap** | ACF registration must be gated by request context; single central entrypoint (Prompt 282). |
| **Front-end** | No ACF registration; no section definition load for ACF (Prompt 283). |
| **Admin page edit** | Only page-scoped groups registered; resolution from assignment map or template/composition (Prompts 284+). |
| **Tooling** | Debug/export/repair/migration may retain explicit full-registration paths; must be documented and not tied to generic init. |
| **Tests** | New tests for front-end no-registration, section-scoped registration, duplicate/invalid keys; existing registration tests may need to run in “full” or admin context. |

### 1.3 What does not change

- ACF field values (post meta) and their read/write behavior.
- LPagery token naming, token maps, injection, validation, fallbacks.
- Assignment map authority, `assign_from_template` / `assign_from_composition`, persistence.
- Group key format `group_aio_{section_key}`, field names, blueprint structure.
- Rendering pipeline and how templates read ACF data on the front-end.
- Execution/build flows that assign groups to pages.

---

## 2. Dependency map

```
ACF_Registration_Provider (acf/init)
    └── ACF_Group_Registrar::register_all()  [to be gated / replaced by controller]
            └── Section_Field_Blueprint_Service::get_all_blueprints()
                    └── Section_Template_Repository::list_all_definitions( 9999, 0 )

Post-retrofit (target):

Registration_Request_Context (new) — determines context (front_end, admin_*, tooling_full)
ACF_Registration_Bootstrap_Controller (new) — central entrypoint; branches by context
    ├── Front-end → no registration
    ├── Admin existing page → Page_Field_Group_Assignment_Service::get_visible_groups_for_page()
    │       → group_key → section_key mapping → ACF_Group_Registrar::register_sections()
    ├── Admin new page → template/composition → section keys → register_sections()
    ├── Admin non-page → none or minimal
    └── Tooling → explicit register_all() when invoked

ACF_Group_Registrar
    ├── register_all() — retained for tooling only
    └── register_sections( array $section_keys ) — uses get_blueprint_for_section() per key

Section_Field_Blueprint_Service
    ├── get_all_blueprints() — used only by tooling / full registration
    └── get_blueprint_for_section( $section_key ) — used by register_sections()
```

### 2.1 Services and files

| Component | File(s) | Role |
|-----------|---------|------|
| ACF registration hook | `ACF_Registration_Provider.php` | Today: calls `register_all()` on acf/init. Later: delegate to controller. |
| Central entrypoint | `ACF_Registration_Bootstrap_Controller.php` (new) | Branches by Registration_Request_Context; calls register_sections or register_all only when appropriate. |
| Request context | `Registration_Request_Context.php` (new, Prompt 283) | Detects front_end vs admin; optional post_id/screen. |
| Registrar | `ACF_Group_Registrar.php` | register_all(), register_sections(); no change to group/field semantics. |
| Blueprint service | `Section_Field_Blueprint_Service.php` | get_blueprint_for_section() already exists; get_all_blueprints() remains for tooling. |
| Assignment | `Page_Field_Group_Assignment_Service.php` | get_visible_groups_for_page( $post_id ) returns group keys; used to resolve section keys. |
| Group key ↔ section key | Shared prefix strip `group_aio_` | Existing pattern in Field_Assignment_Compatibility_Service; reuse or centralize. |

---

## 3. Implementation plan (summary)

| Step | Prompt | Deliverable |
|------|--------|-------------|
| 1 | 281 | Contract + impact analysis (this doc); update acf-page-visibility and large-scale §6.2–6.3. |
| 2 | 282 | ACF_Registration_Bootstrap_Controller; Module_Registrar / ACF_Registration_Provider no longer call register_all() directly on acf/init; central entrypoint that can branch by context. |
| 3 | 283 | Registration_Request_Context; front-end guard in controller; no registration and no bulk blueprint load on public requests. |
| 4 | 284 | Harden register_sections(); single-section lookup; Section_Scoped_Group_Registration_Result; tests for valid set, duplicates, invalid keys. |
| 5 | Later | Admin existing-page: resolve section keys from get_visible_groups_for_page → register_sections. |
| 6 | Later | Admin new-page: resolve from template/composition → register_sections. |
| 7 | Later | Non-page admin: no or minimal registration. |

---

## 4. Manual verification checklist

Use this checklist to verify the documented hot path and contract compliance before and after implementation.

### 4.1 Front-end

- [ ] **Hot path**: Confirm that on a public page request, the call graph does **not** include `register_all()` or `get_all_blueprints()` or `list_all_definitions( 9999, 0 )` for ACF registration.
- [ ] **Rendering**: Existing front-end pages that use ACF fields still render correctly; saved field values are visible where they were before.
- [ ] **No registration**: No ACF field groups are registered on front-end (e.g. no acf_add_local_field_group calls for plugin groups on public requests).

### 4.2 Admin – existing page edit

- [ ] **Visible groups**: On editing a page that has an assignment (template or composition), the same ACF groups (sections) appear in the editor as before the retrofit.
- [ ] **No full load**: Loading the edit screen does **not** trigger `list_all_definitions( 9999, 0 )` or `get_all_blueprints()` for registration; only single-section lookups for the page’s section keys.
- [ ] **Field values**: Saved ACF values load and save correctly; no change to field names or group keys.

### 4.3 Admin – new page edit

- [ ] **Template chosen**: When creating a new page with a template already selected, only the section groups for that template appear (or product-defined behavior).
- [ ] **No template**: When no template/composition is chosen, either no groups or minimal set only; no full registration.

### 4.4 Admin – non-page context

- [ ] **Template directory / settings / dashboard**: No full ACF registration on these screens; no `register_all()` on generic admin bootstrap for these contexts.

### 4.5 Contract invariants

- [ ] **Field values**: Unchanged load/save/display behavior for ACF post meta.
- [ ] **LPagery**: Token field names and behavior unchanged; token maps and validation/fallback as before.
- [ ] **Assignment map**: Still source of truth; assign_from_template / assign_from_composition and persistence unchanged.
- [ ] **Group keys**: Remain `group_aio_{section_key}`; determinism preserved.
- [ ] **Safe failure**: No internal implementation details or stack traces exposed on public or admin when assignment is missing or keys invalid.

---

## 5. Revision history

| Version | Date | Change |
|---------|------|--------|
| 1 | Prompt 281 | Initial impact analysis and verification checklist. |
