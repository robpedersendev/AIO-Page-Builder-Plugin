# ACF Blueprint Bulk-Load Elimination Report

**Prompt**: 289. **Contracts**: acf-conditional-registration-contract.md, large-scale-acf-lpagery-binding-contract §6.2–6.3.

---

## 1. Registration-time paths (no bulk load)

The following paths run during **acf/init** or in response to the bootstrap controller and use **single-section lookup only**:

| Path | Method | Blueprint load |
|------|--------|----------------|
| Front-end | run_registration() → should_skip_registration() | None (registration skipped). |
| Admin existing-page edit | run_registration() → Existing_Page_ACF_Registration_Context_Resolver → get_visible_groups_for_page() → group_keys_to_section_keys() → register_sections($section_keys) | get_blueprint_for_section($key) per key only. |
| Admin new-page edit | run_registration() → New_Page_ACF_Registration_Context_Resolver → derive_from_template/composition → group_keys_to_section_keys() → register_sections($section_keys) | get_blueprint_for_section($key) per key only. |
| Admin non-page | run_registration() → returns 0 | None. |

**Conclusion**: No call to `get_all_blueprints()` or `list_all_definitions( 9999, 0 )` is made from the acf/init registration bootstrap path.

---

## 2. Remaining bulk-load callers (explicit tooling only)

The following still use `get_all_blueprints()` or `list_all_definitions( 9999, 0 )` and are **not** invoked from normal request registration:

| Caller | Use | Justification |
|--------|-----|----------------|
| ACF_Group_Registrar::register_all() | get_all_blueprints() | Invoked only via run_full_registration() (explicit tooling). |
| ACF_Group_Registrar::register_by_family() | list_all_definitions( 9999, 0 ) | Family-scoped registration; not used from bootstrap. |
| ACF_Field_Group_Debug_Exporter | get_all_blueprints() | Debug export tool. |
| ACF_Local_JSON_Mirror_Service | get_all_blueprints() | JSON mirror / export. |
| ACF_Diagnostics_Service | get_all_blueprints() | Diagnostics screen. |
| ACF_Migration_Verification_Service | get_all_blueprints(), list_all_definitions (page/composition) | Migration verification. |
| ACF_Regeneration_Service | list_all_definitions, get_all_blueprints | Repair/regeneration. |
| Section_Template_Repository (internal) | list_all_definitions( 9999, 0 ) in count methods | Other callers (export, validation, etc.). |
| Import_Validator | list_all_definitions( 9999, 0 ) | Import validation. |

These remain legitimate for one-off or admin tooling and are not triggered by generic acf/init.

---

## 3. Single-section lookup in scoped registration

- **ACF_Group_Registrar::register_sections()** → **register_sections_with_result()** uses **Section_Field_Blueprint_Service::get_blueprint_for_section( $section_key )** for each key. No get_all_blueprints() or list_all_definitions() in that path.
- **Section_Field_Blueprint_Service::get_blueprint_for_section()** uses **Section_Template_Repository::get_definition_by_key( $section_key )** (single definition fetch), not list_all_definitions.

---

## 4. Verification

- Unit tests: Section_Scoped_Registration_Test asserts that when using register_sections_with_result() with a mock blueprint service, get_all_blueprints() is never called.
- Bootstrap: run_registration() never calls register_all() for front-end, existing-page, new-page, or non-page admin; only register_sections() with a bounded list or 0.

---

## 5. Revision history

| Version | Date | Change |
|---------|------|--------|
| 1 | Prompt 289 | Initial bulk-load elimination report. |
