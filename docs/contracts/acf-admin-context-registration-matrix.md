# ACF Admin Context Registration Matrix

**Spec**: acf-conditional-registration-contract.md §4.4, §8

**Status**: Defines which admin contexts trigger ACF group registration and which do not.

---

## 1. Purpose

Generic admin screens (template directory, settings, dashboard, etc.) must **not** trigger full ACF section registration. This matrix documents the intended behavior per context and any justified exceptions.

---

## 2. Context categories and registration behavior

| Context | Registration behavior | Notes |
|---------|------------------------|--------|
| **Front-end (public)** | None | No ACF groups registered. |
| **Admin – existing page edit** (post.php, page) | Page-scoped only | Visible groups from assignment map → section keys → register_sections(). |
| **Admin – new page edit** (post-new.php, page) | Template/composition-scoped or none | If template/composition chosen (via filter), register those sections; else register no groups. |
| **Admin – template directory / section directory** | None | No full registration. Screens do not need ACF field groups for listing. |
| **Admin – settings / dashboard / logs / import-export** | None | No full registration. |
| **Admin – compositions list / page template list** | None | No full registration. |
| **Admin – other (unlisted)** | None | Default for unrecognized admin context is no registration. |
| **Explicit tooling** (e.g. debug export, regeneration, migration) | Full only when invoked | Code paths that deliberately call run_full_registration() or register_all(); not tied to acf/init. |

---

## 3. Implementation

- **ACF_Registration_Bootstrap_Controller::run_registration()** branches in order: front-end skip → existing-page scoped → new-page scoped → **non-page admin: register 0** (no fallback to register_all()).
- No generic admin request shall call register_all() from the bootstrap path.
- Exceptions: only where a feature explicitly requires full registration and documents it (e.g. one-off CLI or admin tool that calls run_full_registration() directly).

---

## 4. Cross-references

- **acf-conditional-registration-contract.md**: §4.4 non-page admin; §8 implementation map.
- **acf-page-visibility-contract.md**: Assignment and visibility vs registration.

---

## 5. Revision history

| Version | Date | Change |
|---------|------|--------|
| 1 | Prompt 288 | Initial admin context registration matrix. |
