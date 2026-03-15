# ACF Uninstall Retention and Handoff Contract

**Spec**: §17.10 Rendered Content Independence from Plugin; PORTABILITY_AND_UNINSTALL

**Upstream**: large-scale-acf-lpagery-binding-contract.md, acf-page-visibility-contract.md, acf-conditional-registration-contract.md

**Status**: Contract definition. Defines uninstall-preservation behavior for ACF integration: value retention vs group preservation, default non-destructive uninstall, and supported handoff strategy. Implementation of uninstall routine, handoff exporter, or native ACF group materialization is out of scope for this contract.

---

## 1. Purpose and scope

This contract defines:

- The distinction between **runtime plugin-registered** ACF groups and **persistent/native** ACF groups.
- The **default uninstall policy** for ACF values, field groups, caches, and plugin-owned artifacts (non-destructive by default).
- The **supported preservation strategy** for retaining field groups after uninstall (explicit handoff or export).
- What **metadata must be retained** so saved values keep mapping to preserved fields.
- What is **never deleted automatically** by default.

**Out of scope**: Implementing the uninstall routine or deletion of field values, field groups, or assignments in this contract. Native ACF field-group materialization is defined and implemented in **acf-native-handoff-contract.md** and ACF_Native_Handoff_Generator (Prompt 315).

---

## 2. Runtime vs persistent/native ACF groups

| Type | Definition | Survival after uninstall |
|------|------------|--------------------------|
| **Runtime plugin-registered groups** | Field groups registered by the plugin via `acf_add_local_field_group()` at runtime. Built from section blueprints; group key `group_aio_{section_key}` per acf-key-naming-contract. Not stored in ACF’s database or local JSON by the plugin for loading. | **Do not survive** uninstall unless explicit preservation (handoff/export) is performed. After uninstall, ACF no longer has the group definition; only saved **values** (post meta) remain. |
| **Persistent/native ACF groups** | Groups stored in ACF’s own storage (DB or local JSON) and loaded by ACF independently of the plugin. The plugin may create such groups in a future handoff step; today the plugin does **not** materialize plugin-owned groups as native ACF groups. | Survive uninstall because they are owned by ACF, not the plugin. If the plugin later adds handoff that creates native groups, those groups persist after uninstall. |

**Implication**: Preserving **field groups** (so the editor can continue to edit section fields after uninstall) requires **explicit** preservation logic (e.g. pre-uninstall handoff into native ACF groups or an export bundle). Preserving **saved field values** is the default: post meta is not deleted by the plugin on uninstall.

---

## 3. Default uninstall policy (non-destructive)

- **Default uninstall mode is non-destructive.** When in doubt, preserve.
- **Retained by default (never deleted automatically)**:
  - **Saved ACF field values** in post meta (all meta keys that store values for plugin-owned or plugin-related fields).
  - **Assignment map** data that records which groups were assigned to which page (unless a future, explicit “full cleanup” option is approved and documented).
  - **Built content**: pages, posts, blocks; see PORTABILITY_AND_UNINSTALL.
- **Not retained by default (may be removed by uninstall)**:
  - **Plugin-owned operational data**: options, transients, cron jobs, and other data used solely for plugin operation (e.g. section-key caches, diagnostics snapshots, reporting state). Removal must be documented and explicit.
  - **Runtime group definitions** exist only in plugin code/registry; they are not “deleted” but simply no longer registered. No uninstall step should delete ACF-stored group definitions unless they were explicitly created by the plugin as native groups and a documented cleanup option applies.
- **Group preservation** (keeping field groups editable after uninstall) is **not** default. It requires an explicit preservation step (handoff or export) as defined in §6.

---

## 4. Retained ACF values and metadata

- **Post meta** that stores ACF field values for plugin-related fields must **not** be deleted by the plugin’s uninstall routine. This includes meta keys that correspond to:
  - Field keys or field names produced by the plugin’s blueprint/registration (e.g. `field_{section_key}_{field_name}` or the ACF `name` used in post meta).
  - Any `_field_name` / value pairs or equivalent that ACF uses for those fields.
- **Metadata required for value-to-field mapping** (e.g. field key references in meta) must be retained so that if handoff creates native ACF groups with the same field keys/names, existing values continue to map correctly. LPagery-compatible field names and assignment-map semantics (group-key determinism) are preserved per existing contracts.

---

## 5. Retained/preserved group definitions and handoff artifacts

- **Runtime group definitions** (from section blueprints) are not stored in ACF by the plugin under normal operation; they are registered in memory. Therefore there is nothing to “retain” in ACF’s storage for them unless the plugin performs a **handoff** (e.g. writing native ACF group definitions to DB or local JSON) before uninstall.
- **Handoff artifacts** (if implemented later): any export bundle, manifest, or native ACF group definitions produced by a pre-uninstall handoff must be documented in the uninstall preservation policy and in the inventory contract. Such artifacts are **outputs** of an explicit preservation flow, not retained by default from the live system.
- **Uninstall cleanup exclusions**: The uninstall routine must **exclude** from deletion: (1) all post meta that holds ACF field values for plugin-related fields, (2) assignment map entries, (3) any handoff-generated files or native ACF groups if the operator has chosen preservation. Options/transients that are purely plugin-operational (e.g. cache keys, reporting state) may be removed if documented.

---

## 6. Supported preservation strategy for field groups

- **Preservation strategy** for keeping field groups editable after uninstall is one or both of:
  - **Pre-uninstall handoff into native ACF groups**: Plugin (or an admin tool) creates native ACF field groups (DB or local JSON) from the current section blueprints so ACF owns them; then uninstall removes only plugin code and operational data. No automatic handoff by default.
  - **Export bundle**: Plugin (or admin tool) exports group definitions and, optionally, value references to a file/bundle for later re-import or manual recreation. No automatic export by default.
- **Handoff/export** may be **optional**, **required**, or **admin-confirmed** depending on product design; the default is **optional** and **admin-initiated**. The contract does not require the plugin to perform handoff automatically on uninstall.
- **Determinism**: Any handoff must preserve field names, field keys (where required by contract), and LPagery token-compatible naming so that retained post meta values still map to the preserved group definitions.

---

## 7. What is never deleted by default

- Saved ACF values in post meta.
- Assignment map data (which groups are assigned to which page).
- Built pages and content (per PORTABILITY_AND_UNINSTALL).
- Any native ACF groups not created by the plugin (third-party or existing site groups).
- Handoff artifacts or export bundles once created (they are outside plugin storage).

---

## 8. Security and permissions

- Uninstall preservation behavior must **not** silently expose admin-only metadata publicly (e.g. no public URLs for handoff artifacts without access control).
- Preservation/handoff must not create unsafe public exports (e.g. no export to web-accessible directory without operator control).
- **Safe failure**: If preservation or handoff fails, behavior must favor **retaining data** (e.g. skip deletion of post meta or assignment map) rather than deleting it.

---

## 9. Inventory dependency

Classification and enumeration of plugin-owned groups, fields, and saved values for handoff and uninstall are defined in **acf-uninstall-inventory-contract.md**. The inventory is read-only and feeds the uninstall and handoff logic; it does not mutate groups or values.

---

## 10. Cross-references

- **PORTABILITY_AND_UNINSTALL**: Preserve built content by default; remove only plugin-owned operational data; when in doubt preserve.
- **acf-conditional-registration-contract.md**: §5 group key mapping; §6 invariants (field values, LPagery, assignment map, group key determinism).
- **large-scale-acf-lpagery-binding-contract.md**: Group key `group_aio_{section_key}`; field naming and scaling.
- **acf-page-visibility-contract.md**: Assignment and visibility.
- **docs/operations/acf-uninstall-preservation-policy.md**: Operator-facing preservation policy.
- **docs/contracts/acf-uninstall-inventory-contract.md**: Inventory result and classification for handoff/uninstall.
- **docs/contracts/acf-native-handoff-contract.md**: Native ACF handoff generator; marker; overwrite protection (Prompt 315).

---

## 11. Revision history

| Version | Date      | Change |
|---------|-----------|--------|
| 1       | Prompt 313 | Initial uninstall retention and handoff contract. |
| 2       | Prompt 314 | §9 Inventory dependency; cross-ref acf-uninstall-inventory-contract. |
| 3       | Prompt 315 | Cross-ref acf-native-handoff-contract; handoff materialization in scope. |
