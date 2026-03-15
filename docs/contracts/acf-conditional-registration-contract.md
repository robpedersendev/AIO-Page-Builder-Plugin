# ACF Conditional Registration Contract

**Spec**: §20 Field Governance; §59.5 Rendering and ACF Phase; large-scale-acf-lpagery-binding-contract §6.2–6.3

**Upstream**: acf-page-visibility-contract.md, large-scale-acf-lpagery-binding-contract.md, acf-key-naming-contract.md

**Status**: Contract definition. Defines the performance retrofit for conditional, page-scoped ACF field-group registration so the plugin does not load all section definitions on every request. Implementation is carried out in separate prompts (282–284+).

---

## 1. Purpose and scope

This contract formalizes:

- The **current** unconditional ACF registration hot path (to be retrofitted).
- The **target** registration model by request context (front-end, admin existing-page, admin new-page, non-page admin).
- **Invariants** that must be preserved: field values, LPagery contracts, assignment-map authority, group-key determinism, editor-visible group behavior.
- **Retrofit rules** for conditional registration: when to register, which groups, and how to resolve section keys without loading all section definitions.

**Out of scope for this contract**: Changing ACF field values, LPagery key behavior, assignment-map semantics, or editor-visible group behavior. Runtime registration behavior changes are implemented in later prompts per this contract.

---

## 2. Typed concepts (docs only)

| Concept | Definition |
|--------|------------|
| **Registration context** | The request environment that determines whether and which ACF groups are registered: `front_end`, `admin_existing_page`, `admin_new_page`, `admin_non_page`, or `tooling_full` (explicit full registration for tools). |
| **Resolved section keys** | The set of section `internal_key` values for which field groups must be registered in the current context. Derived from assignment map (existing page), template/composition (new page), or empty (front-end; non-page admin unless product requires a minimal set). |
| **Registration mode** | `none` (no groups registered), `page_scoped` (only groups for resolved section keys), or `full` (all section groups; reserved for explicit tooling only). |

---

## 3. Current unconditional registration hot path (pre-retrofit)

### 3.1 Call graph

1. **Trigger**: WordPress fires `acf/init` on every request (front-end and admin).
2. **Provider**: `ACF_Registration_Provider::register()` adds an action on `acf/init` (priority 5) that runs a closure.
3. **Closure**: The closure resolves `acf_group_registrar` from the container and calls `register_all()`.
4. **Registrar**: `ACF_Group_Registrar::register_all()`:
   - Calls `Section_Field_Blueprint_Service::get_all_blueprints()`.
5. **Blueprint service**: `Section_Field_Blueprint_Service::get_all_blueprints()`:
   - Calls `Section_Template_Repository::list_all_definitions( 9999, 0 )`.
6. **Repository**: Loads all section template definitions (CPT posts + meta) from the database.
7. **Back in registrar**: For each normalized blueprint, calls `register_blueprint()` → `ACF_Group_Builder::build_group()` → `acf_add_local_field_group()`.

**Result**: On every request, the plugin loads all section definitions and registers all section-owned ACF groups, regardless of whether the current page uses any of them.

### 3.2 Hook timing (post-retrofit; Prompt 294)

Scoped registration runs on **acf/init** at **priority 5**. At that point:
- Request context (`is_admin()`, `$pagenow`, `$_GET['post']` / `post_type`) and assignment map reads are available.
- Registration completes before ACF builds its field-group list for the edit screen.
- No duplicate or late full registration is triggered from generic bootstrap. See **docs/qa/acf-registration-hook-timing-report.md**.

### 3.3 Files and entrypoints

| Location | Role |
|----------|------|
| `Infrastructure/Container/Providers/ACF_Registration_Provider.php` | Hooks `acf/init` (priority 5) → controller `run_registration()` (scoped or skip). |
| `Domain/ACF/Registration/ACF_Group_Registrar.php` | `register_all()` calls `get_all_blueprints()` then registers each blueprint. |
| `Domain/ACF/Blueprints/Section_Field_Blueprint_Service.php` | `get_all_blueprints()` calls `list_all_definitions( 9999, 0 )`. |
| `Domain/Storage/Repositories/Section_Template_Repository.php` | `list_all_definitions()` performs the full section CPT query. |

### 3.4 Other callers of full blueprint or definition load (for context only)

The following use `get_all_blueprints()` or `list_all_definitions( 9999, 0 )` for **non–request-bootstrap** purposes (debug, export, diagnostics, repair, migration, reporting). They are **not** on the critical path for every page load but must remain valid after the retrofit:

- `ACF_Field_Group_Debug_Exporter`, `ACF_Local_JSON_Mirror_Service`, `ACF_Diagnostics_Service`, `ACF_Migration_Verification_Service`, `ACF_Regeneration_Service`: explicit tooling; may keep full load or use a dedicated path.
- `Import_Validator`, `Template_Library_*` summary builders, `Registry_Export_Serializer`, etc.: one-off or admin-only operations; out of scope for this contract’s “every request” path.

---

## 4. Target registration model (post-retrofit)

### 4.1 Front-end (public / non-admin) requests

| Rule | Requirement |
|------|-------------|
| **Registration** | **No** ACF field groups are registered. Registration mode = `none`. |
| **Blueprint loading** | **No** bulk section definition load for ACF purposes. No call to `get_all_blueprints()` or `list_all_definitions( 9999, 0 )` for registration. |
| **Field values** | Unchanged. ACF and WordPress continue to load and expose saved field values from post meta for the requested page. Rendering that reads ACF fields must behave exactly as today. |

### 4.2 Admin – existing page edit

| Rule | Requirement |
|------|-------------|
| **Registration** | Only groups for sections **on that page** are registered. Registration mode = `page_scoped`. |
| **Resolution** | Resolve visible group keys via `Page_Field_Group_Assignment_Service::get_visible_groups_for_page( $post_id )`. Map each group key to section key (see §5). Call `ACF_Group_Registrar::register_sections( $section_keys )`. |
| **Blueprint loading** | Use **single-section** blueprint retrieval only: `Section_Field_Blueprint_Service::get_blueprint_for_section( $section_key )` per key. No `get_all_blueprints()` or full `list_all_definitions()` for registration. |
| **Safe failure** | If the page has no assignment (e.g. not yet built from template), resolved section keys may be empty; register no groups. Do not leak internal implementation details. |

### 4.3 Admin – new page edit (template or composition already chosen)

| Rule | Requirement |
|------|-------------|
| **Registration** | Only groups for sections implied by the chosen **template** or **composition** are registered. Registration mode = `page_scoped`. |
| **Resolution** | Derive section keys from the template’s ordered sections or composition’s section list (same derivation used by assignment when the page is built). Register via `register_sections( $section_keys )`. |
| **Blueprint loading** | Single-section retrieval per key only. No full blueprint list for registration. |
| **Safe failure** | If no template/composition is chosen yet, resolved section keys may be empty; register no groups or a product-defined minimal set only. |

### 4.4 Admin – non-page context (e.g. template directory, settings, dashboard)

| Rule | Requirement |
|------|-------------|
| **Registration** | Do **not** register all groups. Either register **no** groups or a **minimal/product-defined** set only. No `register_all()` on generic admin requests. |
| **Blueprint loading** | No bulk section load for ACF registration on these contexts unless a specific feature (e.g. template directory preview) requires a bounded subset and documents it. |

**Matrix**: **acf-admin-context-registration-matrix.md** enumerates admin context categories and documents that non-page admin registers no groups; full registration is reserved for explicit tooling only.

### 4.5 Tooling / explicit full registration

| Rule | Requirement |
|------|-------------|
| **When** | Explicit code paths only (e.g. debug exporter, JSON mirror, regeneration, migration verification). Not tied to generic `acf/init` or request bootstrap. |
| **How** | May call `register_all()` or equivalent only when invoked deliberately (e.g. from an admin tool or CLI). Document any such path. |

---

## 5. Group key to section key mapping

- **Group key format**: `group_aio_{section_key}` per acf-key-naming-contract and `Field_Key_Generator::group_key( $section_key )` (prefix `group_aio_` + sanitized section key).
- **Reverse mapping**: Given a group key (e.g. from `get_visible_groups_for_page()`), section key = strip the prefix `group_aio_` from the group key. Example: `group_aio_st01_hero` → `st01_hero`. Implementation may use a shared helper (e.g. mirroring `Field_Assignment_Compatibility_Service::group_key_to_section_key` logic) so all resolvers use the same rule.
- **Determinism**: Group keys remain deterministic and unchanged; mapping is reversible and one-to-one for plugin-owned groups.

---

## 6. Required invariants (do not break)

The following must be preserved by the retrofit and all downstream implementation:

| Invariant | Requirement |
|-----------|-------------|
| **Field values** | ACF field values (post meta) load, save, and display exactly as today. No change to when or how ACF reads/writes field values. |
| **LPagery** | Token-compatible field names, token maps, injection behavior, and validation/fallback rules remain as defined in Field_Blueprint_Schema and large-scale-acf-lpagery-binding-contract. No change to token keys or naming. |
| **Assignment map** | The assignment map remains the **source of truth** for which groups are assigned to which page. Execution and build flows continue to call `assign_from_template` / `assign_from_composition` and persist via `Assignment_Map_Service`. |
| **Group key determinism** | Group keys remain `group_aio_{section_key}`. No change to `Field_Key_Generator::group_key()` or to how group keys are stored or referenced. |
| **Editor-visible groups** | On page edit screens, the same ACF groups (sections) must appear as today; only the **registration path** (when and for which sections `acf_add_local_field_group` is called) changes. |
| **No front-end registration** | No ACF field-group registration on public/front-end requests. |
| **Admin-only registration** | Selective registration runs only in admin (or explicit tooling) context. No public request parameter may force full or arbitrary registration. |

---

## 7. Safe-failure behavior

| Context | Required behavior |
|--------|-------------------|
| **Front-end** | No registration; page renders using existing post meta. No internal errors or implementation details exposed. |
| **Admin existing page, no assignment** | Resolved section keys = empty; register no groups. Edit screen may show no section groups until assignment exists; acceptable. No leak of internals. |
| **Admin new page, no template chosen** | Resolved section keys = empty (or minimal set per product); register accordingly. No full registration. |
| **Invalid or missing section key** | When resolving or registering by section key, missing/invalid key must not cause fatal or expose internals; skip that key and continue or return a bounded error. |

---

## 8. Implementation map for later prompts

| Prompt / phase | Scope |
|----------------|--------|
| **282** | Trace and isolate unconditional bootstrap paths. Centralize ACF registration behind an explicit entrypoint (e.g. ACF_Registration_Bootstrap_Controller). Remove direct “always register all” from generic `acf/init`. Leave extension points for context-aware registration. |
| **283** | Implement front-end guard: detect public request; skip all ACF group registration and bulk blueprint load on front-end. |
| **284** | Harden section-scoped API: `register_sections( array $section_keys )` (or equivalent) with single-section blueprint lookup, de-duplication, and safe handling of invalid/missing keys. No full blueprint list load. |
| **Later** | Admin existing-page: resolve section keys from `get_visible_groups_for_page()` → map group keys to section keys → `register_sections()`. |
| **Later** | Admin new-page: resolve section keys from chosen template/composition → `register_sections()`. |
| **Later** | Non-page admin: no or minimal registration; no `register_all()` on generic bootstrap. |

---

## 9. Security and permissions

- Selective registration must **not** expose groups in unauthorized contexts (e.g. no admin-only groups on front-end).
- No public request parameter (e.g. query arg, cookie) may be used to force full or arbitrary ACF registration.
- Safe failure on public requests must not reveal internal paths, section keys, or implementation details.

---

## 10. Cross-references

- **acf-page-visibility-contract.md**: Page-level assignment derivation; expanded by this contract for conditional registration.
- **large-scale-acf-lpagery-binding-contract.md**: §6.2–6.3 registration scaling, performance, derivation from section list.
- **acf-key-naming-contract.md**: Group key format `group_aio_{section_key}`.
- **acf-admin-context-registration-matrix.md**: Non-page admin registration behavior; no full registration on generic admin.
- **docs/qa/acf-registration-performance-impact-analysis.md**: Impact analysis and verification checklist.

---

## 11. Revision history

| Version | Date | Change |
|---------|------|--------|
| 1 | Prompt 281 | Initial conditional registration contract; hot path and target model. |
| 2 | Prompt 294 | §3.2 Hook timing: acf/init priority 5; sequencing and timing report reference. |
