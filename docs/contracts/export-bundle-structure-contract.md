# Export Bundle Structure Contract

**Document type:** Authoritative contract for export package structure, manifest, and category rules (spec §52, §59.13).  
**Governs:** ZIP root layout, manifest.json schema, export modes, included/optional/excluded categories, checksum list semantics, compatibility flags, and package naming.  
**Out of scope:** ZIP creation, import validation implementation, restore pipeline, uninstall UI, file generation.

---

## 1. Purpose

This contract defines the **exact structure and semantics** of plugin export bundles. All export generation, restore validation, and uninstall export prompts must conform. Bundles are version-aware, explicit, and portable. Secrets and excluded categories must never appear. Export mode behavior is deterministic and documented.

---

## 2. Export Modes (spec §52.1)

Each export mode defines which data categories are included or excluded. Behavior is fixed per mode; no informal toggles.

| Mode key | Description | Use case |
|----------|-------------|----------|
| `full_operational_backup` | All included categories plus optional categories as configured. | Full backup before migration or disaster recovery. |
| `pre_uninstall_backup` | Same as full backup; used when user chooses "Export full backup" at uninstall. | Preserve state before plugin removal; built pages remain. |
| `support_bundle` | Settings (redacted), profile (redacted), registries, plans, token sets; optional: logs, reporting_history (redacted). No raw AI artifacts. | Support diagnostics; no secrets. |
| `template_only_export` | Registries (sections, page templates, compositions), docs; no plans, no artifacts, no crawl. | Share templates across sites. |
| `plan_artifact_export` | Build plans, related artifact references, token sets; optional: normalized AI outputs. No raw prompts/responses. | Move plans and outcomes between environments. |

### 2.1 Export mode matrix (category behavior)

| Category | Full backup | Pre-uninstall | Support bundle | Template-only | Plan/artifact |
|----------|-------------|---------------|----------------|---------------|---------------|
| settings (no secrets) | ✓ | ✓ | ✓ (redacted) | ✗ | ✗ |
| profiles | ✓ | ✓ | ✓ (redacted) | ✗ | ✗ |
| registries | ✓ | ✓ | ✓ | ✓ | ✗ |
| compositions | ✓ | ✓ | ✓ | ✓ | ✗ |
| plans | ✓ | ✓ | ✓ | ✗ | ✓ |
| token_sets | ✓ | ✓ | ✓ | ✗ | ✓ |
| uninstall_restore_metadata | ✓ | ✓ | ✓ | ✗ | ✗ |
| raw_ai_artifacts | optional | optional | ✗ | ✗ | ✗ |
| normalized_ai_outputs | optional | optional | ✗ | ✗ | optional |
| crawl_snapshots | optional | optional | ✗ | ✗ | ✗ |
| logs | optional | optional | optional | ✗ | ✗ |
| reporting_history | optional | optional | optional (redacted) | ✗ | ✗ |
| rollback_snapshots | optional | optional | ✗ | ✗ | ✗ |

---

## 3. ZIP Root Structure (spec §52.2)

All exports use a ZIP archive. Root layout:

```
/
├── manifest.json          # Required. Single file; encoding UTF-8.
├── settings/              # Plugin settings (export-safe only).
├── profiles/              # Brand/business profile data.
├── registries/            # Section templates, page templates, compositions, docs.
├── compositions/          # Composition definitions (may mirror or reference registries).
├── plans/                 # Build plans and plan metadata.
├── tokens/                # Token set definitions.
├── artifacts/             # Optional. AI run artifacts, normalized outputs (per mode).
├── logs/                  # Optional. Log exports (redacted).
└── docs/                  # Optional. Documentation or export notes.
```

- **manifest.json** MUST be at root. No other files at root except manifest.
- Directory purposes are fixed. Implementations MUST NOT place included-category data in ad hoc paths.
- Empty directories MAY be omitted from the ZIP unless manifest explicitly lists them in `included_categories`.
- Paths use forward slashes; no leading slashes inside ZIP.

---

## 4. Manifest Schema (spec §52.3)

**File:** `manifest.json`  
**Encoding:** UTF-8  
**Format:** JSON object

### 4.1 Required fields

| Field | Type | Description |
|-------|------|-------------|
| `export_type` | string | One of the export mode keys (§2). |
| `export_timestamp` | string | ISO 8601 UTC when the export was created. |
| `plugin_version` | string | Plugin version at export time (e.g. from Versions::plugin()). |
| `schema_version` | string | Export bundle schema version (e.g. from Versions::export_schema()). Used for cross-version import rules. |
| `source_site_url` | string | Site URL or host at export time (no credentials). For display and conflict hints. |
| `included_categories` | array of strings | List of category keys that are present in this bundle. |
| `excluded_categories` | array of strings | List of category keys explicitly excluded (for audit). |
| `package_checksum_list` | object or array | See §5. Semantics: path → checksum for integrity. |
| `restore_notes` | string | Optional human-readable notes for restore (e.g. "Pre-migration backup"). |
| `compatibility_flags` | object | See §6. Cross-version and import hints. |

### 4.2 Optional manifest fields

| Field | Type | Description |
|-------|------|-------------|
| `optional_included` | array of strings | Optional categories that were included in this bundle. |
| `package_filename` | string | Suggested or actual package filename (no path). |
| `min_import_plugin_version` | string | Minimum plugin version that can import this bundle (if defined). |

### 4.3 Prohibited in manifest

- No secret values, API keys, passwords, tokens, or session data.
- No raw credentials or internal server paths that could leak.

---

## 5. Package Checksum List Semantics

- **Purpose:** Allow import to verify integrity of selected paths before restore.
- **Shape:** Object: `{ "path/within/zip": "algo:hexdigest" }` or array of `{ "path": "...", "checksum": "algo:hexdigest" }`.
- **Algo:** At least one of `sha256` or `sha384`. Format: `sha256:abc123...`.
- **Paths:** Relative to ZIP root (e.g. `manifest.json`, `settings/settings.json`). Directories are not checksummed; only files.
- **Scope:** Implementations SHOULD include checksums for every file that is part of included categories. manifest.json MAY be self-describing without its own checksum in the list.
- **Import:** Validators MAY verify entries in this list before proceeding with restore. Mismatch or missing checksum for a required path can be blocking per policy.

---

## 6. Compatibility Flags (spec §52.10)

| Flag | Type | Description |
|------|------|-------------|
| `schema_version` | string | Same as manifest root `schema_version`; duplicated here for quick access. |
| `same_major_required` | boolean | If true, import must match major schema version. |
| `migration_floor` | string | Minimum schema version below which import is blocked (deprecated). |
| `max_supported_export_schema` | string | Maximum export_schema that this plugin version can import. |

**Cross-version rules:**

- Same major schema version: **allowed**.
- Older supported schema version: **allowed with migration** (if migration path exists).
- Newer unsupported schema version: **blocked**.
- Schema below migration floor: **blocked**.

---

## 7. Included Data Categories (spec §52.4)

Default for **full export** (all of these unless excluded by mode):

| Category key | Description |
|--------------|-------------|
| `settings` | Plugin settings excluding secrets (per global-options/settings schema). |
| `profiles` | Brand/business profile (secret-free). When included, also writes `profiles/industry.json` (industry profile and applied preset; see industry-export-restore-contract.md). |
| `registries` | Section template, page template, composition, documentation registry snapshots. |
| `compositions` | Composition definitions. |
| `plans` | Build plans and plan metadata. |
| `token_sets` | Token set definitions. |
| `uninstall_restore_metadata` | Uninstall/restore preferences and export metadata. |

---

## 8. Optional Data Categories (spec §52.5)

Included only when explicitly requested or when export mode allows and user opts in:

| Category key | Description |
|--------------|-------------|
| `raw_ai_artifacts` | Raw AI run artifacts (sensitive; include only when needed). |
| `normalized_ai_outputs` | Normalized AI outputs (e.g. run summaries, not full prompts). |
| `crawl_snapshots` | Crawl snapshot data. |
| `logs` | Log exports (redacted). |
| `reporting_history` | Reporting log entries (redacted; no secrets). |
| `rollback_snapshots` | Rollback/snapshot data for restore. |

Optional-category inclusion MUST be explicit in manifest (`optional_included` or listed in `included_categories`).

---

## 9. Excluded Data Categories (spec §52.6)

**Always excluded by default.** Must never appear in export payloads or manifest metadata:

| Category / rule | Description |
|-----------------|-------------|
| API keys | Provider keys, third-party keys. |
| Passwords | Any password field. |
| Auth/session tokens | Bearer tokens, session IDs, nonces used for auth. |
| Runtime lock rows | Execution lock state, queue lock tokens. |
| Temporary cache entries | Transients, short-lived caches. |
| Corrupted partial remnants | Incomplete or corrupted package data. |

Implementations MUST strip or omit these before writing any file into the bundle. Manifest metadata MUST NOT leak secret values.

---

## 10. Naming and Versioning

- **Package filename:** Deterministic, readable. Pattern: `aio-export-{mode}-{YYYYMMDD}-{HHmmss}-{site_slug}.zip`. Site slug MUST be sanitized (e.g. hostname, no credentials).
- **Schema version:** From `Versions::export_schema()`. Bump when manifest or ZIP layout changes in a backward-incompatible way.
- **Restore notes:** May include export mode and date for operator reference.

---

## 11. Example Manifests

### 11.1 Full operational backup

```json
{
  "export_type": "full_operational_backup",
  "export_timestamp": "2025-03-15T14:30:00Z",
  "plugin_version": "0.1.0",
  "schema_version": "1",
  "source_site_url": "https://example.com",
  "included_categories": [
    "settings",
    "profiles",
    "registries",
    "compositions",
    "plans",
    "token_sets",
    "uninstall_restore_metadata",
    "logs",
    "reporting_history"
  ],
  "excluded_categories": [
    "raw_ai_artifacts",
    "crawl_snapshots",
    "rollback_snapshots"
  ],
  "optional_included": ["logs", "reporting_history"],
  "package_checksum_list": {
    "settings/settings.json": "sha256:a1b2c3...",
    "profiles/profile.json": "sha256:d4e5f6...",
    "registries/sections.json": "sha256:789abc..."
  },
  "restore_notes": "Full backup before migration.",
  "compatibility_flags": {
    "schema_version": "1",
    "same_major_required": true,
    "migration_floor": "1",
    "max_supported_export_schema": "1"
  }
}
```

### 11.2 Support bundle

```json
{
  "export_type": "support_bundle",
  "export_timestamp": "2025-03-15T16:00:00Z",
  "plugin_version": "0.1.0",
  "schema_version": "1",
  "source_site_url": "https://support-site.example.com",
  "included_categories": [
    "settings",
    "profiles",
    "registries",
    "compositions",
    "plans",
    "token_sets",
    "uninstall_restore_metadata",
    "logs",
    "reporting_history"
  ],
  "excluded_categories": [
    "raw_ai_artifacts",
    "normalized_ai_outputs",
    "crawl_snapshots",
    "rollback_snapshots"
  ],
  "optional_included": ["logs", "reporting_history"],
  "package_checksum_list": {},
  "restore_notes": "Support bundle; redacted; not for full restore.",
  "compatibility_flags": {
    "schema_version": "1",
    "same_major_required": true
  }
}
```

---

## 12. Uninstall Export Rules (spec §52.11)

Before uninstall cleanup, the plugin presents an export prompt with choices:

- **Export full backup** → mode `pre_uninstall_backup` (same structure as full operational backup).
- **Export settings/profile only** → reduced bundle: only `settings`, `profiles`, `uninstall_restore_metadata`.
- **Skip export and continue** → no bundle; proceed with uninstall.
- **Cancel uninstall** → abort.

The uninstall screen MUST state clearly that **built pages will remain** (export does not remove content).

---

## 13. Relationship to Other Contracts

- **Registry Export Basics:** Registry-owned objects use the fragment shape and export_schema_version defined there; they are placed under `registries/` in the ZIP.
- **Versions:** `Versions::export_schema()` and `Versions::plugin()` supply manifest values.
- **Global options / settings:** Settings export MUST follow the same exclusion rules (no secrets).

---

## 14. Versioning

- **Schema version:** Stored in manifest as `schema_version`. Implementations must set it when building bundles. Breaking changes to manifest or ZIP layout require a new schema version and contract revision.
- **Stability:** No export pathway may add optional "debug" or "temporary" fields that bypass inclusion/exclusion. All manifest fields must be documented in this contract or a formal revision.
