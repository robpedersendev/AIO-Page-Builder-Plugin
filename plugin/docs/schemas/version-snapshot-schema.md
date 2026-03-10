# Version Snapshot Object Schema

**Document type:** Implementation-grade schema contract for version snapshot objects (spec §10.8, §14.8, §58.4–58.5).  
**Governs:** Snapshot scope types, root fields, preserved object references, version metadata, provenance, retention, and lifecycle before snapshot capture services are implemented.  
**Related:** object-model-schema.md (§3.8 Version Snapshot), composition-validation-state-machine.md (composition registry_snapshot_ref_at_creation).

---

## 1. Purpose and scope

The **Version Snapshot** object represents a **preserved record of a relevant system state at a moment in time**. It exists to support **traceability, reproducibility, and migration-aware reasoning**. Snapshots are **immutable records**, not mutable current state; their meaning must remain understandable even when current registries or schemas evolve.

**Snapshot use cases (spec §10.8):**

- **Template registry snapshots** — Section/page template registry state at creation or validation time (e.g. composition-linked).
- **Schema snapshots** — Structured schema versions (section registry schema, page template schema, profile schema) for migration and compatibility checks.
- **Compatibility snapshots** — Preserved compatibility state or rules for reasoning.
- **Build-context snapshots** — Context used for build plans or execution (profile, registry refs, etc.).
- **Prompt-pack snapshots** — (Future) Prompt pack state at a version; referenced by AI runs.

No secrets may be embedded in snapshot bodies. Payload references must avoid raw secret-bearing content. Snapshots may include operationally sensitive history and remain **admin-governed** later.

---

## 2. Snapshot scope types (scope_type)

Every snapshot has a **scope_type** that defines what kind of state it preserves. Implementation may key by scope_type + scope_id.

| scope_type | Description | Typical scope_id | Use case |
|------------|-------------|------------------|----------|
| `registry` | Template registry state (section + page templates, optionally compositions index) | Composition id, or `global`, or run id | Composition-linked registry snapshot; export/restore. |
| `schema` | Schema definition snapshot (section registry schema, page template schema, profile schema, table manifest) | Schema name + version, e.g. `section_registry_v1` | Migration versioning (§58.4); compatibility checks. |
| `compatibility` | Compatibility rules or state at a point in time | Scope identifier, e.g. `rules_20250101` | Compatibility reasoning; drift detection. |
| `build_context` | Build plan or execution context (profile ref, registry ref, crawl ref, etc.) | Build plan id or execution run id | Build-context snapshots; AI run input refs. |
| `prompt_pack` | Prompt pack state at a version (future) | Prompt pack id + version | AI run prompt-pack reference. |

**Required:** scope_type must be one of the above. New scope types may be added by schema revision; validation shall use an allowlist.

---

## 3. Required root fields

| Field | Type | Required | Validation | Export | Notes |
|-------|------|----------|------------|--------|--------|
| `snapshot_id` | string | Yes | Non-empty; unique; immutable; e.g. UUID; max 64 chars | Yes | Stable snapshot identifier (object-model: internal key). |
| `scope_type` | string | Yes | One of allowed scope_type enum (§2) | Yes | What kind of state this snapshot preserves. |
| `scope_id` | string | Yes | Non-empty; max 128 chars; meaning depends on scope_type | Yes | Scope identifier (e.g. composition id, schema version key, build plan id). |
| `created_at` | string | Yes | ISO 8601 datetime or equivalent | Yes | When the snapshot was captured. |
| `schema_version` | string | Yes | Non-empty; max 32 chars (e.g. plugin schema version or snapshot payload format version) | Yes | Schema version for migration and compatibility (§58.4, §58.5). |
| `status` | string | Yes | One of: `active`, `superseded` | Yes | Lifecycle status (§10.10, object-model §3.8). |

---

## 4. Optional root fields and blocks

| Field | Type | Required | Validation | Export | Notes |
|-------|------|----------|------------|--------|--------|
| `payload_ref` | string | No | Max 512 chars; reference to serialized payload or table row id | Yes | Where the actual snapshot payload is stored (no raw secrets). |
| `object_refs` | object | No | Shape per §5 | Yes | Preserved object references (what this snapshot preserves). |
| `version_metadata` | object | No | Shape per §6 | Yes | Version markers, plugin/schema/table version. |
| `provenance` | object | No | Shape per §7 | Yes | Creation context, actor, trigger. |
| `compatibility_notes` | object | No | Shape per §8 | Yes | Compatibility or migration notes. |
| `diff_eligibility` | boolean | No | — | Yes | Whether this snapshot is eligible for diff/comparison (e.g. structured payload). |
| `exportability` | string | No | One of: `full`, `metadata_only`, `excluded`; default `full` | Yes | Export posture. |
| `retention_notes` | string | No | Max 512 chars | Yes | Retention or archival policy notes. |

---

## 5. Preserved object references (object_refs)

Describes **what objects this snapshot preserves** (spec: “snapshots may reference the objects they preserve”). Shape is scope_type-dependent; common structure:

| Field | Type | Notes |
|-------|------|--------|
| `section_template_keys` | array of strings | Section template internal_key list (for registry snapshot). |
| `page_template_keys` | array of strings | Page template internal_key list (for registry snapshot). |
| `composition_ids` | array of strings | Composition ids if scope is composition-linked. |
| `schema_names` | array of strings | Schema names/identifiers (for schema snapshot). |
| `prompt_pack_ref` | string | Prompt pack id + version (for prompt_pack snapshot). |
| `build_plan_ref` | string | Build plan id (for build_context). |
| `profile_snapshot_ref` | string | Profile snapshot ref if applicable. |
| `crawl_snapshot_ref` | string | Crawl snapshot ref if applicable. |

Only fields relevant to scope_type need be present. All refs are **references only**; no embedded secrets or raw sensitive payloads.

---

## 6. Version metadata block

Supports **migration versioning** (§58.5) and schema versioning (§58.4).

| Field | Type | Notes |
|-------|------|--------|
| `plugin_version` | string | Plugin version at capture time; max 32 chars. |
| `schema_version` | string | Redundant with root schema_version; optional here for block consistency. |
| `table_version` | string | Table/manifest version if applicable; max 32 chars. |
| `registry_version` | string | Registry version where relevant; max 32 chars. |

---

## 7. Provenance block

Creation context for traceability.

| Field | Type | Notes |
|-------|------|--------|
| `created_by` | string | Actor ref (user id or system); max 64 chars. |
| `trigger` | string | What triggered the snapshot (e.g. `composition_validation`, `export`, `pre_mutation`); max 64 chars. |
| `source_ref` | string | Optional source object ref (e.g. composition id that requested registry snapshot); max 128 chars. |

---

## 8. Compatibility notes block

Optional block for migration-aware reasoning and compatibility checks.

| Field | Type | Notes |
|-------|------|--------|
| `compatible_with_schema_versions` | array of strings | Schema versions this snapshot is compatible with. |
| `migration_hint` | string | Optional migration or compatibility hint; max 256 chars. |

---

## 9. Lifecycle and retention

- **Status:** `active` — current snapshot for the scope (e.g. latest registry snapshot for a composition). `superseded` — a newer snapshot for the same scope exists; this one is retained for history.
- **Supersession:** When a new snapshot for the same scope_type + scope_id is created, the previous snapshot may be marked **superseded**. The new snapshot becomes **active**.
- **Retention:** Snapshots are retained for traceability; deletion per retention policy only. Retention notes may document policy (e.g. “keep until composition archived”).
- **Export:** Exportability may be `full` (include in exports), `metadata_only` (export metadata but not payload), or `excluded` (do not export). Default `full` for registry/schema snapshots used in restore.

---

## 10. Ineligibility / invalid snapshot

A snapshot is **invalid or incomplete** if:

1. Any required field (§3) is missing or empty.
2. `snapshot_id` is not unique within the snapshot store.
3. `scope_type` is not in the allowed scope type list.
4. `status` is not `active` or `superseded`.
5. `created_at` is not a valid datetime representation.

---

## 11. Completeness checklist (spec §10.8 use cases)

Support for all intended snapshot use cases:

- [ ] **Template registry snapshots** — scope_type `registry`; object_refs with section_template_keys, page_template_keys; composition-linked via scope_id or provenance.source_ref.
- [ ] **Schema snapshots** — scope_type `schema`; scope_id as schema name + version; version_metadata with schema_version.
- [ ] **Compatibility snapshots** — scope_type `compatibility`; compatibility_notes block.
- [ ] **Build-context snapshots** — scope_type `build_context`; object_refs with build_plan_ref, profile_snapshot_ref, registry refs as needed.
- [ ] **Prompt-pack snapshots** — scope_type `prompt_pack`; object_refs.prompt_pack_ref; (future implementation).
- [ ] **Composition-linked registry snapshot** — scope_type `registry`, scope_id = composition id (or ref in provenance); referenced by composition’s registry_snapshot_ref_at_creation.

---

## 12. Example: composition-linked registry snapshot

```json
{
  "snapshot_id": "snap-a1b2c3d4-registry",
  "scope_type": "registry",
  "scope_id": "comp-uuid-12345",
  "created_at": "2025-07-15T10:30:00Z",
  "schema_version": "1",
  "status": "active",
  "payload_ref": "wp_aio_snapshots/reg_comp_uuid_12345_v1.json",
  "object_refs": {
    "section_template_keys": ["st01_hero", "st02_faq", "st05_cta"],
    "page_template_keys": [],
    "composition_ids": ["comp-uuid-12345"]
  },
  "version_metadata": {
    "plugin_version": "1.0.0",
    "registry_version": "1"
  },
  "provenance": {
    "trigger": "composition_validation",
    "source_ref": "comp-uuid-12345"
  },
  "diff_eligibility": true,
  "exportability": "full"
}
```

---

## 13. Example: schema snapshot

```json
{
  "snapshot_id": "snap-schema-section-v1",
  "scope_type": "schema",
  "scope_id": "section_registry_v1",
  "created_at": "2025-07-01T00:00:00Z",
  "schema_version": "1",
  "status": "active",
  "payload_ref": "schemas/section-registry-schema.md",
  "object_refs": {
    "schema_names": ["section_registry"]
  },
  "version_metadata": {
    "plugin_version": "1.0.0",
    "schema_version": "1"
  },
  "compatibility_notes": {
    "compatible_with_schema_versions": ["1"]
  },
  "diff_eligibility": true,
  "exportability": "full"
}
```

---

## 14. Example: invalid snapshot (missing required field)

```json
{
  "snapshot_id": "snap-bad",
  "scope_type": "registry",
  "scope_id": "",
  "created_at": "2025-07-15T10:30:00Z",
  "schema_version": "1",
  "status": "active"
}
```
→ **Invalid:** `scope_id` is required and must be non-empty.

---

## 15. Example: invalid snapshot (unknown scope_type)

```json
{
  "snapshot_id": "snap-bad2",
  "scope_type": "custom_unknown",
  "scope_id": "x",
  "created_at": "2025-07-15T10:30:00Z",
  "schema_version": "1",
  "status": "active"
}
```
→ **Invalid:** `scope_type` must be one of the allowed enum values.
