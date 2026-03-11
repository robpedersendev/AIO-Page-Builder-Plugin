# Registry Admin Screen Contract

**Document type:** Authoritative contract for registry management screens (spec §62.10, §59.4, §49.6–49.7).  
**Governs:** Screen slugs, menu placement, list/detail/create/edit flows, validation presentation, deprecation/duplication, documentation and snapshot visibility before any registry UI implementation.  
**Related:** admin-screen-inventory.md, section-registry-schema.md, page-template-registry-schema.md, composition-validation-state-machine.md, documentation-object-schema.md, version-snapshot-schema.md, Capabilities.php.

---

## 1. Purpose and scope

Registry admin screens provide the UI for **section templates**, **page templates**, **custom compositions**, **documentation** (helper paragraphs and one-pagers), and **version snapshots**. This contract locks screen IA, list/detail/create/edit flows, validation and error states, deprecation/version views, documentation visibility, and snapshot visibility so that later UI prompts implement file-by-file without redesigning the UX.

**In scope:** Screen slugs, titles, object types, key list columns, key actions, detail panels, validation/error state handling, required capabilities, empty states, and workflow state maps.  
**Out of scope:** No screen classes, save handlers, REST/AJAX, modals, or registry CRUD implementation. All registry mutation screens must be **capability-gated**; validation and save results are **server-authoritative**. Error messaging must be **understandable and actionable** (§45.3); no raw stack traces or secret exposure.

---

## 2. Menu placement

Registry screens live under the **AIO Page Builder** parent menu (`aio-page-builder`). Submenu order and slugs are stable.

| Order | Screen slug | Menu title |
|-------|-------------|------------|
| 1 | `aio-page-builder` | Dashboard |
| 2 | `aio-page-builder-section-templates` | Section Templates |
| 3 | `aio-page-builder-page-templates` | Page Templates |
| 4 | `aio-page-builder-compositions` | Compositions |
| 5 | `aio-page-builder-documentation` | Documentation |
| 6 | `aio-page-builder-snapshots` | Version Snapshots |
| … | (other existing: settings, diagnostics, etc.) | … |

Detail/edit screens are reached via list-screen actions (e.g. Add new, Edit row); they use a dedicated slug with an identifier for edit (e.g. `aio-page-builder-section-template-edit` with `id` or `key` query). Create uses the same slug without id.

---

## 3. Section Templates screens

### 3.1 List screen: Section Templates

| Property | Value |
|----------|--------|
| **Screen slug** | `aio-page-builder-section-templates` |
| **Title** | Section Templates |
| **Object type** | Section template (section-registry-schema) |
| **Required capability** | `aio_manage_section_templates` |
| **Key list columns** | Internal key, Name, Category, Status, Version, Helper status, Deprecation marker |
| **Key actions** | Add new section template, Edit (per row), Filter by category, Filter by status, Search by key/name |
| **Detail panels** | N/A (list only; detail on edit screen) |
| **Validation/error states** | List load failure: show message per §45.3 (what failed, next step). Empty state: "No section templates yet. Add one to get started." |
| **Empty state** | Message above; primary action "Add section template" |
| **Implementation notes** | Searchable template table (§49.6). Category and status filters. Version column. Helper-doc access (link to documentation object or inline summary). Compatibility summary optional. Deprecation marker when status = deprecated. |

### 3.2 Detail/Edit screen: Section Template (create/edit)

| Property | Value |
|----------|--------|
| **Screen slug** | `aio-page-builder-section-template-edit` |
| **Title** | Add Section Template / Edit: {name} |
| **Object type** | Section template |
| **Required capability** | `aio_manage_section_templates` |
| **Key panels** | Identity (internal_key, name, purpose_summary, category); Blueprint refs (structural, field, helper, CSS contract); Variants & default; Compatibility; Version & status; Asset declaration; Optional (short label, preview, notes). |
| **Key actions** | Save draft, Activate, Deprecate (with reason and replacement), Cancel / Back to list |
| **Validation/error states** | On save: show validation errors (blocking) and warnings (non-blocking) per section-registry-schema incompleteness rules. Each error: explain what failed, next step (§45.3). Inline field-level errors where applicable. |
| **Helper visibility** | Helper paragraph reference (helper_ref) is a first-class field; link or embed to documentation object (section_helper type). Panel label: "Helper documentation". |
| **Deprecation** | Deprecate action: require reason and optional replacement section key. Show deprecation block (reason, replacement_section_key). Do not imply deletion; preserve old references. |
| **Empty state (create)** | Form empty; title "Add Section Template". |
| **Implementation notes** | Form and accessibility rules apply (§51). No generic "manage template" undefined structure. Version marker and status editable; lifecycle draft → active → inactive → deprecated. |

**List → Edit workflow state map:** List → [Add new] → Edit (create mode, no id). List → [Edit row] → Edit (edit mode, id set). Edit → [Save] → revalidate; on success stay on Edit or redirect to list. Edit → [Cancel] → List.

---

## 4. Page Templates screens

### 4.1 List screen: Page Templates

| Property | Value |
|----------|--------|
| **Screen slug** | `aio-page-builder-page-templates` |
| **Title** | Page Templates |
| **Object type** | Page template (page-template-registry-schema) |
| **Required capability** | `aio_manage_page_templates` |
| **Key list columns** | Internal key, Name, Archetype, Section count, Status, Version, One-pager status, Deprecation marker |
| **Key actions** | Add new page template, Edit (per row), Filter by archetype, Filter by status, Search |
| **Validation/error states** | List load failure: actionable message. Empty state: "No page templates yet." |
| **Empty state** | Primary action "Add page template" |
| **Implementation notes** | Page template list (§49.7). Section-order preview (e.g. first few section keys). One-pager access (link to documentation object page_template_one_pager). Compatibility notes optional. |

### 4.2 Detail/Edit screen: Page Template (create/edit)

| Property | Value |
|----------|--------|
| **Screen slug** | `aio-page-builder-page-template-edit` |
| **Title** | Add Page Template / Edit: {name} |
| **Object type** | Page template |
| **Required capability** | `aio_manage_page_templates` |
| **Key panels** | Identity (internal_key, name, purpose_summary, archetype); Ordered sections (list of section refs + required/optional); Section requirements; Compatibility; One-pager metadata; Version & status; Default structural assumptions; Endpoint/usage notes; Optional (SEO defaults, hierarchy hints, deprecation block). |
| **Key actions** | Save draft, Activate, Deprecate (reason + replacement_template_key), Cancel |
| **Validation/error states** | Save: validate per page-template-registry-schema (missing required field, empty ordered_sections, section_requirements mismatch). Show blocking errors and warnings; explain what failed. |
| **One-pager visibility** | One-pager generation metadata is first-class; link or embed to documentation object (page_template_one_pager) for this template. Panel: "One-pager documentation". |
| **Implementation notes** | Ordered section list is explicit; no ad hoc freeform array. Deprecation preserves traceability. |

**Workflow:** Same pattern as section templates (list → add/edit → save/cancel).

---

## 5. Compositions screens

### 5.1 List screen: Compositions

| Property | Value |
|----------|--------|
| **Screen slug** | `aio-page-builder-compositions` |
| **Title** | Compositions |
| **Object type** | Custom template composition |
| **Required capability** | `aio_manage_compositions` |
| **Key list columns** | Composition id (or name), Name, Section count, Status, Validation result, Source template (if any), Snapshot ref, One-pager status |
| **Key actions** | Add new composition, Edit (per row), Duplicate (per row), Filter by status, Filter by validation result, Search |
| **Validation/error states** | List shows validation result (valid, warning, validation_failed, deprecated_context). Empty state: "No compositions yet." |
| **Empty state** | "Create a composition from section templates, or duplicate an existing one." |
| **Implementation notes** | Composition list with create/edit composition controls for authorized users (§49.7). Duplication triggers revalidation; new id, provenance preserved (duplicated_from_composition_id). |

### 5.2 Detail/Edit screen: Composition (create/edit)

| Property | Value |
|----------|--------|
| **Screen slug** | `aio-page-builder-composition-edit` |
| **Title** | Add Composition / Edit: {name} |
| **Object type** | Composition |
| **Required capability** | `aio_manage_compositions` |
| **Key panels** | Identity (name, optional source_template_ref); Ordered section list (section keys + required/optional); Validation result & codes; Snapshot reference (registry_snapshot_ref_at_creation); One-pager reference; Provenance (duplicated_from_composition_id if clone); Status. |
| **Key actions** | Save, Validate (re-run validation), Duplicate (from list or here), Archive, Cancel |
| **Validation/error states** | Present validation result and **validation codes** (blocking vs warning) per composition-validation-state-machine. Do not collapse to single "invalid"; show explainable reasons (section_missing, section_deprecated_*, compatibility_adjacency, etc.). Warnings allow activation; blocking failures do not. |
| **Helper/one-pager visibility** | Composition one-pager is first-class; link or embed to documentation object (composition_one_pager). Panel: "Composition one-pager". |
| **Snapshot visibility** | Show registry_snapshot_ref_at_creation; link to snapshot detail if screen exists. Explain: "Registry state at last validation." |
| **Duplication** | Duplicate creates new composition with new id; set duplicated_from_composition_id; revalidate. UI: "Duplicate composition" with optional rename. |
| **Implementation notes** | Compositions are governed and validated; not arbitrary freeform. Validation state is server-authoritative. |

**Workflow:** List → Add/Edit → Save → revalidate; show validation result. List → Duplicate → new composition edit screen (revalidate). Deprecation/archive per lifecycle.

---

## 6. Documentation screen

### 6.1 List screen: Documentation

| Property | Value |
|----------|--------|
| **Screen slug** | `aio-page-builder-documentation` |
| **Title** | Documentation |
| **Object type** | Documentation object (section_helper, page_template_one_pager, composition_one_pager) |
| **Required capability** | `aio_manage_documentation` |
| **Key list columns** | Documentation id, Type (helper / page one-pager / composition one-pager), Source (section key, page template key, or composition id), Status, Generated vs human-edited, Version marker |
| **Key actions** | Filter by type, Filter by source, Edit (per row), Search by id or source |
| **Validation/error states** | Empty state: "No documentation records yet. Helper and one-pager docs are created when you add section templates, page templates, or compositions." |
| **Implementation notes** | Documentation is not optional side content; this list gives visibility into all helper and one-pager docs. Edit may open inline or detail screen (slug TBD: e.g. `aio-page-builder-documentation-edit`). |

### 6.2 Detail/Edit screen: Documentation (optional)

| Property | Value |
|----------|--------|
| **Screen slug** | `aio-page-builder-documentation-edit` |
| **Title** | Edit documentation: {id or source summary} |
| **Object type** | Documentation object |
| **Required capability** | `aio_manage_documentation` |
| **Key panels** | Type (read-only), Source reference (read-only), Content body (editable), Generated or human-edited, Version marker, Export metadata, Provenance (generated_at, last_edited_at) |
| **Key actions** | Save, Cancel |
| **Implementation notes** | Generated content may be refined (human edit); set generated_or_human_edited to mixed and update provenance. |

---

## 7. Version Snapshots screen

### 7.1 List screen: Version Snapshots

| Property | Value |
|----------|--------|
| **Screen slug** | `aio-page-builder-snapshots` |
| **Title** | Version Snapshots |
| **Object type** | Version snapshot (version-snapshot-schema) |
| **Required capability** | `aio_view_version_snapshots` |
| **Key list columns** | Snapshot id, Scope type (registry, schema, compatibility, build_context, prompt_pack), Scope id, Created at, Status (active/superseded), Schema version |
| **Key actions** | Filter by scope_type, Filter by status, View detail (read-only), Search by scope id |
| **Validation/error states** | Empty state: "No snapshots yet. Snapshots are created when you validate compositions, export, or run builds." |
| **Snapshot reference presentation** | In composition and other screens, show snapshot ref as link to this list (filtered by scope_id) or to a read-only detail view. Do not expose raw payload if sensitive. |
| **Implementation notes** | Read-only list (and detail if implemented). No delete in contract; retention per policy. |

---

## 8. Validation and error presentation rules

- **Server-authoritative:** All validation outcomes come from the server. UI must not show a "valid" state that was not confirmed by the server after save/revalidate.
- **Explainable:** Show validation **codes** and **severity** (blocking vs warning). Use composition-validation-state-machine codes; for section/page template use schema incompleteness rules. Do not hide validation reasons.
- **User-facing errors (§45.3):** Explain what failed, avoid technical noise, indicate retry possibility, point to next step. No raw stack traces for ordinary failures.
- **Admin-facing detail (§45.4):** Privileged users may see internal code/category, target object, failure context, retry recommendation, log reference—without exposing secrets.
- **Forms and accessibility (§51):** Labels, focus order, error association with fields, and confirmation for destructive actions (deprecate, archive). Confirmation interactions must be keyboard-accessible and clearly stated.

---

## 9. UI verification checklist

Use this checklist when implementing or testing registry screens:

- [ ] **List screens:** Slug, title, capability check, list columns, filters, search, empty state, primary action (Add).
- [ ] **Detail/Edit screens:** Slug, title, capability check, panels per object schema, Save/Cancel, validation errors shown on save.
- [ ] **Section template:** Helper documentation visibility; deprecation with reason and replacement; version and status.
- [ ] **Page template:** Ordered sections and section requirements; one-pager visibility; deprecation.
- [ ] **Composition:** Validation result and codes (blocking vs warning); duplicate with provenance; snapshot ref and one-pager visibility.
- [ ] **Documentation:** List by type and source; generated vs human-edited visible.
- [ ] **Snapshots:** List by scope type and status; snapshot ref from composition (and others) links or shows scope.
- [ ] **Deprecation:** Reason and replacement; no implied deletion; old references preserved.
- [ ] **Duplication (compositions):** New id, provenance field set, revalidation.
- [ ] **Accessibility:** Form labels, error association, focus order, confirmation dialogs keyboard-accessible.
- [ ] **Empty states:** Clear message and primary action for each list.
- [ ] **Error states:** Actionable message per §45.3; no raw secrets or internals.

---

## 10. State maps (summary)

- **List → Create:** List screen → [Add new] → Edit screen (create mode, no id). Edit → [Save] → validate; success → stay or redirect to list; failure → show errors on form.
- **List → Edit:** List → [Edit row] → Edit screen (edit mode, id). Same save/validation flow.
- **List → Duplicate (compositions):** List → [Duplicate] → create new composition with duplicated_from_composition_id; open Edit screen for new id; revalidate.
- **Deprecate:** Edit screen → [Deprecate] → confirmation → require reason (and replacement if applicable) → save status + deprecation block; do not delete.
- **Archive:** Edit or list → [Archive] → confirmation → set status archived.

All transitions assume capability check before showing mutation actions. Validation and save results are always server-authoritative.
