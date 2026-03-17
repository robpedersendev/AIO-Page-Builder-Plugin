# Brief: ACF Registration Performance — Reduce Heavy Load, Preserve Functionality

**Goal:** Eliminate heavy, long page loads caused by ACF-related work while keeping core behaviour: pages and sections work with their ACF field values and LPagery keys unchanged.

**Contracts:** large-scale-acf-lpagery-binding-contract.md §6.2–6.3 (registration must not load all section groups on every page; lazy registration allowed); acf-page-visibility-contract.md (stub).

---

## 1. Problem

- On **every request** (front and admin), `acf/init` runs and the plugin calls `ACF_Group_Registrar::register_all()`.
- That triggers `Section_Field_Blueprint_Service::get_all_blueprints()` → `Section_Template_Repository::list_all_definitions( 9999, 0 )`, loading **all** section template definitions (250+ CPT posts + meta).
- Result: heavy DB and object work on every page load even when the page uses only a small set of sections.

---

## 2. What Must Stay the Same

- **ACF field values:** Each page’s saved ACF data (post meta) continues to load and save as today. No change to how or when ACF reads/writes field values.
- **LPagery:** Token-compatible field keys, naming, and injection behaviour stay as defined in Field_Blueprint_Schema and large-scale-acf-lpagery-binding-contract. No change to token maps, fallbacks, or validation.
- **Assignment map:** Page ↔ field-group assignment (from template/composition) remains the source of truth. Execution and build flows still call `Page_Field_Group_Assignment_Service::assign_from_template` / `assign_from_composition` and persist via `Assignment_Map_Service`.
- **Editor experience:** When editing a page, the same ACF groups (sections) must appear as today; only the **registration path** changes (when and for which sections we call `acf_add_local_field_group`).
- **Determinism:** Group keys remain `group_aio_{section_key}`; field names and blueprint structure unchanged.

---

## 3. Approach: Conditional, Page-Scoped Registration

**3.1 Front-end (public / non-admin)**

- Do **not** register any ACF field groups.
- Do **not** load section template definitions for ACF.
- ACF field values for the requested page still load via normal WordPress/ACF post meta; no change.

**3.2 Admin**

- Register ACF groups **only when needed** and **only for the sections used on the current context** (e.g. the page being edited).
- **When:** On `acf/init` (or an earlier hook that runs when the post edit screen is loading), and only when in admin and when the context is a page edit (or new page with a chosen template).
- **What to register:**
  - If editing an **existing page:** Resolve visible group keys from `Page_Field_Group_Assignment_Service::get_visible_groups_for_page( $post_id )`. Map group keys → section keys. Call `ACF_Group_Registrar::register_sections( $section_keys )`. That uses `Section_Field_Blueprint_Service::get_blueprint_for_section( $key )` per key (one section definition per call), so only those sections are loaded.
  - If creating a **new page** and template is already chosen: Derive section keys from that template (or composition) and register only those sections.
  - If context is not a single page (e.g. template directory, settings): Either register no groups or a minimal set per product requirements; do **not** call `register_all()` or load all section definitions.

**3.3 No “register all” on every request**

- Remove or bypass the current `register_all()` call on `acf/init` that runs for every request.
- Ensure no other code path loads all section definitions (e.g. `list_all_definitions( 9999, 0 )`) on front-end or on admin except where explicitly required (e.g. admin template directory, export, or one-off tools).

---

## 4. Implementation Notes

- **Group key ↔ section key:** Visible groups from the assignment map are stored as group keys (e.g. `group_aio_st_hero_01`). Use the same rule as `Field_Key_Generator::group_key()` in reverse to get section keys for `register_sections()`.
- **New pages with no assignment yet:** Until the page has an assignment (e.g. built from template or composition), either register no groups or derive from a default/template if the UX exposes one at create time.
- **ACF timing:** Registration must happen before ACF builds the field group list for the edit screen. Keeping registration on `acf/init` with a guard (admin + post context) is sufficient; if using a later hook, confirm ACF still accepts `acf_add_local_field_group()` at that point.
- **Caching (optional):** Consider caching the list of section keys per page ID (or per template key) so repeated loads of the same edit screen avoid re-querying the assignment map; avoid caching full section definitions long-term if definitions can change.

---

## 5. Acceptance Criteria

- Front-end: No section template definitions loaded for ACF; no `register_all()` or equivalent.
- Admin page edit: Only the section definitions for that page’s assigned groups are loaded; only those groups are registered.
- Existing pages and new builds: ACF field values and LPagery keys behave as before; assignment map and execution/build flows unchanged.
- No new ACF or LPagery contracts; existing field naming, token compatibility, and visibility rules remain in force.
