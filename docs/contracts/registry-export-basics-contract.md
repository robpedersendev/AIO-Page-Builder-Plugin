# Registry Export Basics Contract

**Spec**: §52.2 Export Package Structure; §52.3 Export Manifest Rules; §52.4 Included Data Categories; §52.6 Excluded Data Categories; §58.2 Template Registry Versioning

**Status**: Implemented (Prompt 032)

## Export Fragment Shape

All registry-owned objects are serialized into a deterministic fragment with these stable keys:

| Key | Type | Description |
|-----|------|-------------|
| `export_schema_version` | string | From `Versions::export_schema()`. Used for import compatibility checks. |
| `object_type` | string | One of: `section_template`, `page_template`, `composition`, `documentation`, `version_snapshot` |
| `object_key` | string | Stable identifier (internal_key, composition_id, documentation_id, snapshot_id) |
| `object_status` | string | Lifecycle status (draft, active, deprecated, archived, superseded) |
| `object_version` | string | Schema/version marker for the payload |
| `payload` | object | Sanitized definition; excludes prohibited fields |
| `relationships` | object | Cross-references (section_keys, helper_refs, source_reference) |
| `deprecation` | object | Deprecation metadata when applicable |
| `source_metadata` | object | Schema name, type, validation state |

## Example: Section Template Fragment

```json
{
  "export_schema_version": "1",
  "object_type": "section_template",
  "object_key": "st01_hero",
  "object_status": "active",
  "object_version": "1",
  "payload": {
    "internal_key": "st01_hero",
    "name": "Hero",
    "purpose_summary": "Primary hero section.",
    "category": "hero_intro",
    "status": "active",
    "version": {"version": "1"},
    "variants": {"default": {"label": "Default"}}
  },
  "relationships": {
    "section_refs": [],
    "helper_ref": "helper_st01",
    "css_contract_ref": "css_st01"
  },
  "deprecation": {},
  "source_metadata": {"schema": "section_registry", "version": "1"}
}
```

## Example: Composition Fragment

```json
{
  "export_schema_version": "1",
  "object_type": "composition",
  "object_key": "comp_landing_001",
  "object_status": "active",
  "object_version": "1",
  "payload": {
    "composition_id": "comp_landing_001",
    "name": "Landing Page",
    "ordered_section_list": [
      {"section_key": "st01_hero", "position": 0, "variant": "default"}
    ],
    "status": "active",
    "validation_status": "valid",
    "source_template_ref": "pt_landing_main"
  },
  "relationships": {
    "section_keys": ["st01_hero"],
    "source_template_ref": "pt_landing_main",
    "helper_one_pager_ref": "",
    "registry_snapshot_ref": ""
  },
  "deprecation": {},
  "source_metadata": {"schema": "composition", "validation_status": "valid"}
}
```

## Included Data Categories (§52.4)

- Section templates: full definition, deprecation block, relationships
- Page templates: full definition, section refs, deprecation block
- Compositions: full definition, section refs, template ref, validation state
- Documentation: full definition, source reference
- Version snapshots: full definition, object_refs, scope metadata

## Excluded Data Categories (§52.6)

The following field names (or substrings) are stripped from payloads:

- `api_key`, `api_secret`, `password`, `auth_token`, `session_token`
- `secret`, `credential`, `transient`, `cache_key`
- `runtime_lock`, `lock_row`, `corrupted`

Transient, UI-only, and internal WP post fields are not included.

## Fixture Keys

`Registry_Fixture_Builder` uses stable keys for tests and QA:

- Section: `st_fixture_hero`
- Page template: `pt_fixture_landing`
- Composition: `comp_fixture_001`
- Documentation: `doc_fixture_helper_001`
- Snapshot: `snap_fixture_registry_001`

## Bundle Structure

`Registry_Export_Serializer::build_registry_bundle()` returns:

```json
{
  "registries": {
    "sections": [...],
    "page_templates": [...],
    "compositions": [...]
  }
}
```

Documentation and snapshots are serializable via `serialize_documentation()` and `serialize_snapshot()` but are not included in the default registry bundle (optional categories).

## Version Compatibility

Export fragments are version-aware. Future import logic must check `export_schema_version` against supported versions before restore.
