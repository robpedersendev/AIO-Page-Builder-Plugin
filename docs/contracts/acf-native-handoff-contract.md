# ACF Native Handoff Contract

**Spec**: acf-uninstall-retention-contract.md; acf-uninstall-inventory-contract.md; large-scale-acf-lpagery-binding-contract.md

**Status**: Contract definition. Defines how plugin-owned runtime ACF groups are materialized into persistent/native ACF field groups for post-uninstall editor continuity. Implementation: ACF_Native_Handoff_Generator, ACF_Handoff_Group_Marker (Prompt 315).

---

## 1. Purpose and scope

The handoff:

- **Reads** the inventory of plugin-owned runtime groups and fields (ACF_Uninstall_Inventory_Result).
- **Builds** equivalent ACF field group arrays from section blueprints (same field keys, field names, types) so existing saved values remain addressable.
- **Persists** them via ACF’s storage (e.g. `acf_import_field_group`) with **page** location so they appear on page edit screens after uninstall.
- **Marks** handed-off groups with a documented marker so they can be recognized and not overwritten by unrelated logic.

**Out of scope**: Executing uninstall; deleting runtime registrations; altering saved values or field names.

---

## 2. Deterministic rules

| Rule | Requirement |
|------|-------------|
| **Group key** | Unchanged: `group_aio_{section_key}`. Same as runtime registration. |
| **Field keys and names** | Preserved from blueprint so post meta values continue to map. LPagery token-compatible naming intact. |
| **Location** | Handoff groups use location `post_type == page` so they remain visible on page edit screens without the plugin. |
| **Marker** | Each handed-off group array includes `_aio_handoff_origin` = `aio_page_builder` (ACF_Handoff_Group_Marker). Additive; documented. |

---

## 3. Overwrite protection

- Before importing a group with key `K`, the generator **checks** whether ACF already has a field group with key `K`.
- If a group with key `K` exists and is **not** marked as our handoff (`_aio_handoff_origin` ≠ `aio_page_builder`), the generator **skips** that group (does not overwrite unrelated native groups).
- If the group does not exist or is marked as our handoff, the generator proceeds with import/update.

---

## 4. Value compatibility

- Handoff **does not mutate** post meta or saved ACF values.
- Field names and field keys in the handed-off group definitions match the runtime registration so that existing values (stored by field name in post meta) continue to load and save correctly in the editor after uninstall.

---

## 5. Security and permissions

- Handoff generation is **admin-only** and **intentional**. No public route or unauthenticated execution.
- **Safe failure**: On ACF unavailability or import error, the generator returns structured result (imported count, skipped counts, errors) and does not corrupt or duplicate field groups arbitrarily.

---

## 6. Marker schema

| Key | Value | Purpose |
|-----|-------|---------|
| `_aio_handoff_origin` | `aio_page_builder` | Identifies the group as handed off from AIO Page Builder; used to avoid overwriting and to recognize preserved groups. |

Additive only; no removal of ACF-standard keys.

---

## 7. Cross-references

- **acf-uninstall-retention-contract.md**: Value retention vs group preservation; handoff strategy.
- **acf-uninstall-inventory-contract.md**: Inventory result feeds handoff; plugin_runtime_group_keys, field_definitions.
- **large-scale-acf-lpagery-binding-contract.md**: Group key `group_aio_{section_key}`; field naming.
- **data-schema-appendix.md**: Handoff result shape and marker (additive).
