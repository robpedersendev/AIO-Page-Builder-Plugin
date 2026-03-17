# Industry Pack Deprecation Policy and Migration Contract (Prompt 411)

**Spec**: industry-subsystem-roadmap-contract.md; industry-pack-activation-contract.md; industry-pack-extension-contract; export/restore and lifecycle hardening docs.

**Status**: Contract. Defines the deprecation lifecycle for industry packs, overlays, and starter bundles so outdated assets can be retired without silently breaking profile selections, docs, or recommendation flows.

---

## 1. Purpose

- Define **deprecated**, **inactive**, **superseded**, and **removed** states for industry packs and related assets.
- Define **replacement refs** and **warning behavior** so operators and support have explicit guidance.
- Ensure **profile selections** that point to deprecated or removed packs are handled safely and auditably.
- Keep deprecation behavior **deterministic** and **non-destructive**; no silent data removal or automatic destructive migrations.

---

## 2. Lifecycle states

| State | Meaning | Pack/asset load | Recommendation use | Profile reference |
|-------|---------|-----------------|---------------------|-------------------|
| **active** | Current, supported. | Loaded and exposed. | Used for scoring, overlays, guidance. | No warning. |
| **draft** | Not yet released for use. | May be loaded but not offered in UI or defaults. | Not used. | N/A. |
| **deprecated** | Supported but scheduled for retirement; replacement available or documented. | Loaded; may be excluded from “add new” flows. | Implementation may skip or treat as legacy; behavior documented. | Warning shown; replacement_ref suggested. |
| **inactive** | Disabled by admin (activation toggle). | Loaded; excluded from recommendation/overlay application. | Treated as “no pack” for that key. | Warning on profile screen; profile data preserved. |
| **superseded** | Replaced by another pack/bundle (replacement_ref set). | Loaded for backward compatibility. | Prefer replacement when available; legacy key still resolved. | Warning; link to replacement. |
| **removed** | No longer in registry definitions; key may still appear in stored profile. | Not loaded. | Not used. | Fallback: treat as missing; warn; do not clear profile automatically. |

- **deprecated_at** (optional): ISO 8601 timestamp or version marker when the asset was deprecated; for audit and support.
- **replacement_ref** (optional): Reference to the replacing pack key, bundle key, or overlay key. When set, admin/support can suggest migration.
- **deprecation_note** (optional): Short, stable note for operators (e.g. “Use realtor_v2 pack instead.”). No secrets; safe for export.

---

## 3. Replacement refs and warnings

- **Replacement ref**: When a pack or starter bundle has `replacement_ref` set, admin UI and diagnostics may show “Replaced by: &lt;replacement_ref&gt;” and link to the replacement where applicable.
- **Warning behavior**: When the profile’s primary_industry_key or selected_starter_bundle_key references a deprecated, superseded, or removed asset:
  - **Do not** silently clear or overwrite the profile.
  - **Do** show a clear warning (e.g. on Industry Profile screen, diagnostics, or health report) that the referenced asset is deprecated/superseded/removed and, if available, show replacement_ref and deprecation_note.
- **Recommendation/overlay behavior**: Deprecated/superseded packs may be excluded from “create new” or “recommended” flows per implementation; existing content and stored profile data remain valid. Removed keys are not in the registry; consumers must treat missing key as “no pack” or “unknown” and fail safe.

---

## 4. Profile selections pointing to deprecated or removed packs

- **Stored profile**: primary_industry_key, secondary_industry_keys, selected_starter_bundle_key may reference deprecated, superseded, or removed keys. This is **allowed**; export/restore and admin must not corrupt or silently delete these values.
- **At read time**: When resolving a pack or bundle by key, if the key is deprecated or superseded, resolution succeeds (asset still loaded); if removed, resolution fails (key not in registry). Consumers must handle “not found” without crashing and may surface a warning.
- **Migration**: Migration to a replacement is **manual** or guided (e.g. admin chooses new primary industry or new starter bundle). No automatic destructive migration of profile data in this contract.

---

## 5. Export/import expectations

- **Export**: Deprecated and superseded assets may be included in export payloads if they are still in registry definitions; removed assets are not in the registry so are not exported as definitions. Stored profile (primary_industry_key, selected_starter_bundle_key, etc.) is always exported as stored; no stripping of deprecated/removed keys.
- **Import/restore**: Restored profile may contain keys that are deprecated, superseded, or no longer present in the current registry. Restore must not fail fatally; invalid or unresolved keys may be reported and the rest of the restore may proceed. Re-validate refs after restore per industry-export-restore-contract; deprecated/removed refs may produce warnings in diagnostics or health report.

---

## 6. Support and operator guidance

- **Deprecation notice**: When deprecating a pack or bundle, set status (and optional deprecated_at, replacement_ref, deprecation_note) in the definition; update industry-pack-catalog, industry-starter-bundle-schema appendix, and maintenance checklist. Document sunset or “remove from builtin” only when safe for all consumers (changelog and roadmap).
- **Operator guidance**: Support docs and admin-facing help should state that deprecated/superseded assets remain usable but may show warnings; recommend switching to replacement when available. Removed assets are no longer loadable; profile may still show the key with a “not found” or “removed” warning.
- **Determinism**: Same pack/bundle definition and same profile state must produce the same warning and fallback behavior across versions; no silent data mutation.

---

## 7. Schema and implementation (additive)

- **Pack schema** (industry-pack-schema.md): Add optional fields **deprecated_at** (string, ISO 8601 or version), **replacement_ref** (string, pack or bundle key), **deprecation_note** (string, bounded length). status continues to include `deprecated`; implementations may treat **superseded** as an alias or extension of status (e.g. status=deprecated and replacement_ref set).
- **Starter bundle schema**: Same optional fields (deprecated_at, replacement_ref, deprecation_note) and status values (active, draft, deprecated) where applicable; see industry-starter-bundle-schema.md.
- **Activation vs deprecation**: “Inactive” is the admin-disabled state (Industry_Pack_Toggle_Controller); “deprecated” is a lifecycle state on the definition. A pack can be both deprecated and inactive; behavior is the same as inactive for recommendation use, with additional deprecation warning when the profile references it.

---

## 8. Out of scope for this contract

- **No removal of existing packs** in this prompt; no automatic destructive migrations.
- **No redesign of the registry model**; additive metadata and behavior only.
- **No change to planner/executor or Build Plan execution**; deprecation affects recommendation and profile display only.

---

## 9. Cross-references

- **Roadmap deprecation**: industry-subsystem-roadmap-contract.md §4.
- **Activation (inactive)**: industry-pack-activation-contract.md.
- **Pack schema**: industry-pack-schema.md; industry-starter-bundle-schema.md.
- **Export/restore**: industry-export-restore-contract; industry-lifecycle-hardening-contract.md.
- **Maintenance**: industry-pack-maintenance-checklist.md §8; known-risk-register.md (industry deprecation risk).
- **Guided repair workflow**: [industry-guided-repair-workflow-contract.md](industry-guided-repair-workflow-contract.md) — operator workflow for deprecated refs, replacement, and migration.
