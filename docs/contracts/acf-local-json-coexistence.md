# ACF Local JSON and Sync — Coexistence with Conditional Registration

**Prompt**: 306  
**Upstream**: acf-conditional-registration-contract.md

---

## 1. Purpose

Clarifies how the conditional-registration retrofit coexists with ACF’s native local JSON, sync, and field-group export tooling. Plugin-owned groups remain under plugin control; no conflict with native ACF workflows when used as intended.

---

## 2. Plugin-owned groups (source of truth)

| Aspect | Behavior |
|--------|----------|
| **Registration** | Plugin registers section-owned ACF groups at runtime via `acf_add_local_field_group()`. Group keys follow `group_aio_{section_key}`. |
| **Source of truth** | Section registries and blueprints (Section_Field_Blueprint_Service, etc.). Not ACF’s database-stored or JSON-stored field groups for these keys. |
| **Conditional registration** | Only groups for the current context (existing page, new page template/composition, or explicit tooling) are registered. Full registration is reserved for documented tooling only. |

---

## 3. Plugin “local JSON” (mirror only)

- **ACF_Local_JSON_Mirror_Service** writes JSON files for plugin-owned groups to a **target directory** (e.g. export/staging). Used for debug, environment comparison, and support.
- This mirror is **not** ACF’s native “load from acf-json” path. The plugin does **not** load field groups from these files at runtime. Registry remains the source of truth.
- Scoped registration does not change mirror behavior: tooling that generates the mirror uses full blueprint load from its own entry point (exception matrix).

---

## 4. Native ACF local JSON and sync

- **ACF’s native local JSON**: ACF can save/load field groups to/from a directory (e.g. `wp-content/acf-json`). Groups loaded from there are registered by ACF.
- **Coexistence rule**: Plugin-owned group keys (`group_aio_*`) must **not** be stored in ACF’s native local JSON save path for the same site. The plugin registers these groups from the registry; duplicate registration from ACF-loaded JSON would be undefined or duplicate.
- **ACF Sync UI**: If the site uses ACF’s sync feature for other (non–plugin-owned) groups, that is independent. Plugin does not rely on sync for its own groups.
- **Operator expectation**: Do not use “Save to JSON” in ACF UI for plugin-owned groups; the plugin does not read them back from ACF JSON. Use the plugin’s export/mirror tooling if you need a JSON backup of plugin groups.

---

## 5. No-go zones

| Scenario | Support | Reason |
|----------|--------|--------|
| Saving plugin-owned groups via ACF “Local JSON” and expecting plugin to load from them | Not supported | Plugin registers from registry only; ACF and plugin would both register same keys. |
| Using ACF sync to “sync” plugin groups from another site | Not supported | Plugin groups are site-local and registry-driven. |
| Disabling conditional registration by moving groups to ACF JSON | Not supported | No such toggle; registration remains conditional from plugin. |

---

## 6. Safe coexistence

- Use ACF local JSON / sync for **non–plugin-owned** field groups only, or ensure group keys do not overlap with `group_aio_*`.
- Plugin mirror (ACF_Local_JSON_Mirror_Service) is for backup/diff only; do not point ACF’s “acf-json” path at the plugin’s mirror output directory for loading.
- Conditional-registration system remains the authoritative runtime model for plugin-owned groups.

---

## 7. Cross-references

- acf-conditional-registration-contract.md
- acf-registration-exception-matrix.md (tooling that may do full blueprint load / mirror)
- template-library-support-guide.md (§ ACF / registration)
