# Industry Pack Import Conflict Contract (Prompt 395)

**Spec**: industry-pack-bundle-format-contract; industry-export-restore-contract; existing import conflict and restore validation policies.

This contract defines how import conflicts for industry pack bundles are detected, classified, and resolved. Imports must remain validated, explicit, and auditable; local registry state must not be silently corrupted.

---

## 1. Purpose

- **Detect** key collisions, version mismatches, missing dependencies, and local-modification conflicts when importing a pack bundle.
- **Define** replace, skip, or merge policies per object type where justified.
- **Preserve** auditability: every conflict has an object key, conflict type, proposed resolution, and final outcome.
- **Fail safely** on unresolved conflicts when policy requires operator choice.

---

## 2. Conflict types

| Type | Description | Default policy |
|------|-------------|----------------|
| **duplicate_key** | Incoming object key already exists in local registry. | Configurable per category: replace, skip, or merge (when supported). |
| **newer_version** | Incoming object has higher schema/version than local. | replace (incoming wins). |
| **older_version** | Incoming object has lower schema/version than local. | skip (keep current) or replace with operator override. |
| **missing_dependency** | Incoming object references a key (e.g. token_preset_ref, starter_bundle_ref) that is neither in the bundle nor present locally. | warn; skip object or fail import depending on severity. |
| **invalid_payload** | Incoming object fails schema validation. | skip; do not apply. |
| **inactive_or_deprecated** | Incoming object is status draft/deprecated; or local object is customized. | skip or replace per policy; do not overwrite local custom without explicit choice. |

---

## 3. Conflict result shape

Each conflict item in the analysis result:

| Field | Type | Description |
|-------|------|-------------|
| **object_key** | string | Key identifying the object (e.g. industry_key for packs, bundle_key for starter_bundles). |
| **category** | string | Payload category (packs, starter_bundles, style_presets, etc.). |
| **conflict_type** | string | One of the conflict types above. |
| **proposed_resolution** | string | replace \| skip \| merge \| fail. |
| **final_outcome** | string \| null | Set after resolution: applied \| skipped \| merged \| failed. |
| **warning_severity** | string | info \| warning \| error. error = must resolve or fail import. |
| **message** | string | Human-readable explanation for operators. |

---

## 4. Policies by object type

| Category | Duplicate key | Newer version | Older version | Missing dependency |
|----------|---------------|---------------|---------------|---------------------|
| **packs** | replace (incoming overwrites) or skip (keep current). Operator choice. | replace | skip (keep current) | warn; skip pack or fail per config. |
| **starter_bundles** | replace or skip. Operator choice. | replace | skip | warn; skip bundle. |
| **style_presets** | replace or skip. | replace | skip | warn; skip preset. |
| **cta_patterns** | replace or skip. | replace | skip | N/A (refs in packs). |
| **seo_guidance** | replace or skip. | replace | skip | N/A. |
| **lpagery_rules** | replace or skip. | replace | skip | N/A. |
| **section_helper_overlays** | replace or skip (composite key industry_key|section_key). | replace | skip | N/A. |
| **page_one_pager_overlays** | replace or skip (composite key industry_key|page_template_key). | replace | skip | N/A. |
| **question_packs** | replace or skip (by industry_key). | replace | skip | N/A. |
| **site_profile** | replace (incoming overwrites profile/preset) or skip. Operator choice. | replace | skip | N/A. |

**Auditability:** The importer records which conflicts were presented, which resolution was chosen (e.g. "replace all", "skip duplicates"), and the final_outcome per object. No silent overwrite without a logged resolution.

---

## 5. Safe failure

- **Unresolved error-level conflict:** If any conflict has warning_severity = error and no resolution is provided (or resolution = fail), the import MUST NOT apply any change for that category or MUST abort the entire bundle import with a clear message.
- **Missing dependency (critical):** If a pack references token_preset_ref or starter_bundle_ref and that ref is missing from bundle and local, the service MAY mark the conflict as error so the operator must choose to skip the pack or supply the dependency.
- **Validation before apply:** All objects that will be applied MUST pass schema validation again at apply time. Invalid payloads are skipped and recorded in the result.

---

## 6. Service and integration

- **Contract**: docs/contracts/industry-pack-import-conflict-contract.md (this file).
- **Service**: plugin/src/Domain/Industry/Export/Industry_Pack_Import_Conflict_Service.php — analyzes bundle against local state; returns conflict list; applies resolution to produce outcome list.
- **Consumer**: Bundle import flow (admin-only) calls the service to analyze, presents conflicts to operator (or uses default policy), then applies only objects with final_outcome = applied. Full site restore pipeline remains separate; pack bundle import does not bypass restore validation.

---

## 7. Operator guidance

- **Duplicate key:** "An object with key X already exists. Choose Replace (incoming overwrites) or Skip (keep current)."
- **Older version:** "Incoming object X has an older version than current. Default: Skip to keep your current version."
- **Missing dependency:** "Pack X references token_preset_ref Y which is not in the bundle or on this site. Skip this pack or add the dependency first."
- **Document** operator-facing conflict outcomes in support/runbook so operators understand replace vs skip vs merge.
