# Per-Entity Style Payload Contract

**Spec**: [styling-subsystem-contract.md](styling-subsystem-contract.md), [pb-style-render-surfaces-spec.json](../specs/pb-style-render-surfaces-spec.json)  
**Status**: Schema and repository implemented (Prompt 251). Sanitization and emission are out of scope for this contract.

---

## 1. Purpose

This contract defines the **per-entity style payload**: a versioned, structured store for style overrides attached to approved entity types (e.g. section template, page template). Data is keyed by stable identifiers (section_key, template_key), is separate from global styling settings and from saved post_content, and contains only token overrides and component overrides—no raw CSS or selectors.

---

## 2. Storage and keying

| Aspect | Description |
|--------|-------------|
| **Option key** | `aio_entity_style_payloads` (single option). |
| **Top-level shape** | `version` (string), `payloads` (object). |
| **payloads** | `{ entity_type: { entity_key: payload } }`. Example: `payloads.section_template["hero_intro"]`, `payloads.page_template["pt_landing"]`. |
| **Entity types** | `section_template`, `page_template` (aligned with render surfaces). |
| **Entity keys** | Stable identifiers: section_key (section template internal_key), template_key (page template internal_key or post_name). Sanitized via sanitize_key; max length 128. |

Per-entity data is **distinct** from:
- **Global styling settings** (`aio_global_style_settings`): site-wide tokens and component overrides.
- **Saved content**: no injection into post_content or block markup.

---

## 3. Payload schema (per entity)

Each payload is an object:

| Key | Type | Description |
|-----|------|-------------|
| **version** | string | Payload version for migration (e.g. `"1"`). |
| **token_overrides** | object | `[ group => [ name => value ] ]` — same shape as global token values; only allowed group/name and string values (validated by sanitization layer elsewhere). |
| **component_overrides** | object | `[ component_id => [ token_var_name => value ] ]` — same shape as global component overrides; only allowed component/token pairs (validated elsewhere). |

- **No raw CSS**: Values are token values (colors, lengths, font stacks, etc.), not CSS rules or selectors.
- **No secrets**: Style payloads must not contain API keys or sensitive data.
- **Corrupt or missing**: Repository returns default payload (empty branches); rendering must not break.

---

## 4. Repository

- **Entity_Style_Payload_Repository**: `get_payload(entity_type, entity_key)`, `set_payload(entity_type, entity_key, payload)`, `delete_payload(entity_type, entity_key)`, `get_all_payloads_for_type(entity_type)`.
- **Entity_Style_Payload_Schema**: Option and payload defaults, `ENTITY_TYPES`, `is_allowed_entity_type()`.
- **Normalization**: On read/write, payloads are normalized to schema shape; non-array or non-string values in override branches are stripped. Full sanitization (registry-whitelist) is implemented in a later prompt.

---

## 5. Versioning

- **Option version** (`version`): Schema version for the whole option; migration may use this.
- **Payload version** (`payload.version`): Per-payload version for future payload-level migrations.
- Current values: `Entity_Style_Payload_Schema::SCHEMA_VERSION`, `Entity_Style_Payload_Schema::PAYLOAD_VERSION`.

---

## 6. Security and failure

- Unsupported entity types or invalid entity_key: repository returns default payload or false (no write).
- No arbitrary CSS text or selector storage.
- Corrupt or missing option: `get_full()` and getters return defaults or empty structures.

---

## 7. Cross-references

- [styling-subsystem-contract.md](styling-subsystem-contract.md)
- [global-styling-settings-contract.md](global-styling-settings-contract.md)
- Entity_Style_Payload_Schema, Entity_Style_Payload_Repository
