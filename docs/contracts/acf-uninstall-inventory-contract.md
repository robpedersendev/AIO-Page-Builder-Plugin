# ACF Uninstall Inventory Contract

**Spec**: acf-uninstall-retention-contract.md (Prompt 313)

**Status**: Contract definition. Defines the read-only inventory of plugin-related ACF groups, field definitions, and value storage for handoff and uninstall. Implementation: ACF_Uninstall_Inventory_Service and ACF_Uninstall_Inventory_Result (Prompt 314).

---

## 1. Purpose and scope

The inventory provides a **deterministic, read-only** classification of:

- Plugin-owned **runtime** ACF group keys (`group_aio_{section_key}`).
- **Field definitions** (field key, field name, group key) derived from section blueprints.
- **Value storage**: meta keys under which saved ACF values for those fields are stored (post meta).
- **Persistent/native** ACF group keys that are plugin-origin (if any; currently none).
- **Safe-to-remove artifacts**: transient prefixes and option keys that are plugin-operational only and may be deleted on uninstall.

The inventory **does not** mutate field groups, fields, or values. It feeds later handoff and uninstall steps.

---

## 2. Inventory result structure

| Property | Type | Description |
|----------|------|-------------|
| **plugin_runtime_group_keys** | `list<string>` | Group keys for runtime-registered plugin-owned groups (`group_aio_*`). One per section with a valid blueprint. |
| **field_definitions** | `list<array{group_key: string, field_key: string, field_name: string}>` | Per-field entries: group key, ACF field key, and field name (ACF stores values in post meta by field name). Includes top-level fields only; repeater/group subfield naming follows ACF conventions. |
| **value_meta_keys** | `list<string>` | Unique meta keys that may hold saved values for plugin fields (typically field names). Used to exclude these from uninstall cleanup. Repeater/group subfield meta keys follow ACF patterns (e.g. `field_name_0_subfield`). |
| **persistent_group_keys** | `list<string>` | Plugin-origin ACF groups stored in ACF’s DB or local JSON (if any). Currently empty; populated if handoff creates native groups. |
| **cleanup_transient_prefixes** | `list<string>` | Transient key prefixes that are plugin-operational only (e.g. `aio_acf_sk_p_`, `aio_acf_sk_t_`, `aio_acf_sk_c_`). Uninstall may delete transients matching these. |
| **cleanup_option_keys** | `list<string>` | Option keys that are plugin-operational only and may be removed on uninstall. Empty until explicitly documented. |

---

## 3. Classification rules

- **Runtime-only groups**: Every group key derived from section templates that have a valid field blueprint. Source: section registry → section_key → `Field_Key_Generator::group_key( section_key )`.
- **Persistent groups**: Groups the plugin has written to ACF’s storage (DB or local JSON). Today the plugin does not create such groups; list remains empty unless handoff is implemented.
- **Unrelated/native ACF groups**: Any group key not in `plugin_runtime_group_keys` and not in `persistent_group_keys` is **not** plugin-owned. The inventory must not classify third-party or existing site groups as plugin-owned.
- **Value meta keys**: Derived from blueprint field `name` values. Retained by default on uninstall; never deleted by default.

---

## 4. Data flow

1. **Inventory service** (read-only): Uses `Section_Template_Repository` (or equivalent) to enumerate section definitions; for each section with a blueprint, uses `Section_Field_Blueprint_Service::get_blueprint_for_section()` and `Field_Key_Generator` to build group keys and field key/name list. Collects transient/option prefixes from plugin constants.
2. **Result**: Populates `ACF_Uninstall_Inventory_Result` with the structure above.
3. **Consumers**: Future uninstall routine and handoff exporter use the result to know what to preserve (values, assignment map) and what may be removed (transients), and which group/field definitions to export or materialize as native ACF groups.

---

## 5. Security and safety

- Inventory is **internal/admin-only**. No public API to enumerate ACF values or field metadata.
- **Safe failure**: On error (e.g. missing blueprint), skip that section and continue; do not delete or misclassify data. Favor omitting from inventory over guessing.
- **Read-only**: No writes to ACF, post meta, or options during inventory.

---

## 6. Cross-references

- **acf-uninstall-retention-contract.md**: Value retention vs group preservation; default non-destructive uninstall.
- **acf-conditional-registration-contract.md**: Group key format `group_aio_{section_key}`.
- **large-scale-acf-lpagery-binding-contract.md**: Field naming; one blueprint per section.
- **data-schema-appendix.md**: Inventory result and manifest schema (additive).
