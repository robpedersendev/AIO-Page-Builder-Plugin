# ACF Migration Verification Report

**Document type:** QA report for ACF field architecture upgrade and migration verification (spec §53, §58.4, §58.5, §58.8, §59.14; Prompt 225).  
**Purpose:** Describe the ACF migration verification harness, payloads, how to run it, and how to interpret results.

---

## 1. Purpose

The harness proves that across supported version transitions:

- Field keys and group keys remain **stable** (deterministic from registry).
- Programmatic registration **survives** upgrades.
- Registry-to-group mappings and **page-assignment relevance** stay intact.
- **Regeneration/repair** behaves safely after version changes or partial failures.
- **Local JSON mirror** / debug exports remain **coherent** with the registry.

It does **not** replace normal registration or repair; it **verifies** that the architecture is stable and recoverable. Breaking changes or deprecation risks are surfaced explicitly.

---

## 2. Authorities

- Field blueprint schemas and deterministic naming (Field_Key_Generator, §20.4).
- Version-aware registration metadata (_aio_section_key, _aio_section_version).
- Migration trackers and Versions (plugin, registry_schema).
- Local JSON mirror manifests (acf_local_json_manifest) and debug exporter diff (acf_mirror_diff_summary).

---

## 3. Payloads

### 3.1 acf_migration_verification_result

Produced by `ACF_Migration_Verification_Service::run_verification()` and `ACF_Migration_Verification_Result::to_array()`.

| Key | Type | Description |
|-----|------|-------------|
| verification_run_at | string | ISO 8601 UTC. |
| plugin_version | string | At run time. |
| registry_schema | string | At run time. |
| field_key_stability_summary | object | See §3.2. |
| assignment_continuity_summary | object | Assignments checked, relevant, orphaned. |
| mirror_coherence | object | in_sync, version_mismatch, summary. |
| regeneration_safe | object | plan_buildable, repair_candidates_consistent, summary. |
| breaking_change_risks | list | Strings; non-empty implies fail. |
| deprecation_risks | list | Strings; non-empty can imply warning. |
| overall_status | string | pass \| warning \| fail. |
| human_summary | string | One-line human-readable. |

### 3.2 field_key_stability_summary

| Key | Type | Description |
|-----|------|-------------|
| stable_group_keys | list | Group keys derived from blueprints. |
| stable_field_keys | list | Field keys derived from blueprints. |
| unstable_or_missing | list | Invalid keys or live orphans (e.g. group_orphan:key). |
| summary | string | Human-readable. |

### 3.3 assignment_continuity_summary

| Key | Type | Description |
|-----|------|-------------|
| assignments_checked | int | PAGE_TEMPLATE + PAGE_COMPOSITION rows considered. |
| assignments_relevant | int | target_ref exists in registry. |
| orphaned_or_invalid | list | target_ref not in registry (e.g. page_template:pt_old). |
| summary | string | Human-readable. |

---

## 4. How to run

- **Programmatic:** Resolve `acf_migration_verification_service` from the container; call `run_verification( array() )`. Optional: `simulated_mirror_manifest` for upgrade-path diff; `acf_available` to force ACF on/off for tests.
- **Internal/admin only:** No public UI required by this prompt; any future UI must be capability-gated and nonce-protected. Reports must not expose secrets.

---

## 5. How to interpret

- **pass:** No breaking risks; field identity, assignments, mirror, and regeneration are stable.
- **warning:** Deprecation or drift (e.g. orphaned assignments, mirror version mismatch); no immediate break.
- **fail:** Breaking risks (e.g. unstable/missing keys, plan not buildable); must be resolved before treating upgrade as safe.

---

## 6. Example payloads

### 6.1 Example acf_migration_verification_result

```json
{
  "verification_run_at": "2025-03-14T15:00:00Z",
  "plugin_version": "1.0.0",
  "registry_schema": "1",
  "field_key_stability_summary": {
    "stable_group_keys": ["group_aio_st01_hero", "group_aio_st05_faq"],
    "stable_field_keys": ["field_st01_hero_headline", "field_st05_faq_question"],
    "unstable_or_missing": [],
    "summary": "All keys stable."
  },
  "assignment_continuity_summary": {
    "assignments_checked": 5,
    "assignments_relevant": 5,
    "orphaned_or_invalid": [],
    "summary": "All assignments relevant."
  },
  "mirror_coherence": {
    "in_sync": 2,
    "in_registry_not_mirror_count": 0,
    "in_mirror_not_registry_count": 0,
    "version_mismatch": 0,
    "summary": "2 in sync, 0 only in registry, 0 only in mirror, 0 version mismatch."
  },
  "regeneration_safe": {
    "plan_buildable": true,
    "repair_candidates_consistent": true,
    "summary": "Regeneration plan buildable; 0 mismatch(es), 5 repair candidate(s)."
  },
  "breaking_change_risks": [],
  "deprecation_risks": [],
  "overall_status": "pass",
  "human_summary": "ACF migration verification passed."
}
```

### 6.2 Example field_key_stability_summary (with issues)

```json
{
  "stable_group_keys": ["group_aio_st01_hero"],
  "stable_field_keys": ["field_st01_hero_headline"],
  "unstable_or_missing": ["group_orphan:group_aio_legacy_old"],
  "summary": "1 group(s) and 1 field key(s) from registry; 1 unstable or missing."
}
```

---

## 7. Test coverage

Tests cover: same-version verification, field-key stability, group-key stability, assignment continuity, mirror coherence, regeneration behavior after simulated partial registration failure. See `tests/Unit/ACF_Migration_Verification_Service_Test.php`.
