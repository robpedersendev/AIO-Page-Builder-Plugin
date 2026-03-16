# Industry Pack Migration Contract (Prompt 412)

**Spec**: industry-pack-deprecation-contract.md; industry-pack-activation-contract.md; export/restore and lifecycle hardening docs.

**Status**: Contract. Defines the bounded migration executor for deprecated-to-replacement pack transitions. Migrations are explicit, auditable, and do not rewrite Build Plan or approval snapshots.

---

## 1. Purpose

- Support **controlled migration** of stored profile refs (primary_industry_key, secondary_industry_keys, selected_starter_bundle_key) from a deprecated industry pack to an approved replacement.
- Ensure migrations are **explicit** (operator-approved) and **auditable** (result objects, migration logs).
- **Preserve** historical Build Plan approval snapshots; migration never rewrites plan definitions or industry_approval_snapshot.

---

## 2. Scope

- **In scope**: Industry Profile (option-backed), starter bundle selection in profile. Migration updates only these when the replacement relationship is explicit and valid.
- **Out of scope**: Auto-run without approval; rewriting overlays/rules; mutating Build Plan snapshots; changing disabled-packs list (activation toggle is separate).

---

## 3. Executor

- **Industry_Pack_Migration_Executor** (plugin/src/Domain/Industry/Profile/Industry_Pack_Migration_Executor.php):
  - **get_replacement_pack_ref( pack_key )**: Returns replacement industry_key from pack definition (replacement_ref) when present and valid; null otherwise.
  - **get_replacement_bundle_ref( bundle_key )**: Returns replacement bundle_key from bundle definition (replacement_ref) when present and valid; null otherwise.
  - **run_migration( from_pack_key, to_pack_key )**: Validates from/to (to must exist and be active). Updates profile: primary and secondary industry keys where they equal from → to; selected_starter_bundle_key when it belongs to from industry and has a replacement bundle in to industry (else cleared with warning). Persists via Industry_Profile_Repository::merge_profile(). Returns **Industry_Pack_Migration_Result**.
  - **run_migration_to_replacement( deprecated_pack_key )**: Resolves replacement via get_replacement_pack_ref, then calls run_migration( deprecated_pack_key, replacement_key ). Fails with clear error when no valid replacement.
- **Industry_Pack_Migration_Result** (Industry_Pack_Migration_Result.php): success, migrated_refs (list of { object_type, old_ref, new_ref }), warnings, errors, audit_note. Object types: primary_industry_key, secondary_industry_keys, selected_starter_bundle_key.

---

## 4. Behavior

- **Validation**: from_pack must exist in registry; to_pack must exist and have status active. If validation fails, result is success=false with errors; no profile change.
- **Starter bundle**: If selected bundle’s industry_key matches from_pack, executor looks up bundle’s replacement_ref. If replacement bundle exists and its industry_key matches to_pack, profile is updated to that bundle; otherwise selected_starter_bundle_key is cleared and a warning is added.
- **Audit**: Result includes audit_note and migrated_refs for support/diagnostics. Callers may log or display these; no built-in persistence of migration history in this contract (additive logging is optional).

---

## 5. Security and permissions

- **Admin-only**: Migration must be triggered only by authorized admin actions. Capability: e.g. `aio_manage_settings` (or equivalent per admin-screen-contract). Nonce and capability checks are required for any manual execution surface (e.g. admin-post handler).
- **Safe failure**: Invalid or incomplete replacement refs result in failure with clear errors; no partial or silent overwrite of profile.

---

## 6. Build Plan and historical artifacts

- **No rewrite**: The executor does not read or write Build Plan repository, plan meta, or industry_approval_snapshot. Historical approval snapshots remain unchanged. Migration affects only Industry Profile (and related settings as defined above).

---

## 7. Integration

- **Surfacing**: Migration action may be surfaced on Industry Profile screen or diagnostics when primary industry (or selected bundle) references a deprecated pack with a valid replacement_ref (e.g. “Migrate to &lt;replacement&gt;” button). Such UI must use nonce and capability and call the executor; then redirect with result message.
- **Diagnostics/reporting**: Migration result (success, migrated_refs, warnings, audit_note) may be included in diagnostics or support payloads when migration was run; schema and redaction per reporting contract.

---

## 8. Cross-references

- **Deprecation**: industry-pack-deprecation-contract.md (replacement_ref, lifecycle states).
- **Activation**: industry-pack-activation-contract.md (inactive packs; migration does not change disabled list).
- **Profile**: industry-profile-schema.md; Industry_Profile_Repository.
- **Export/restore**: Migrated profile is stored normally; export/restore includes updated profile.
