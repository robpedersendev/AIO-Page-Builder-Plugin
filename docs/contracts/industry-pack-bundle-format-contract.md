# Industry Pack Bundle Format Contract (Prompt 394)

**Spec**: export/restore sections of master spec; industry-export-restore-contract; industry-pack-extension-contract; industry-pack-schema and related industry schemas.

This contract defines the portable industry pack bundle format for exporting/importing Industry Pack definitions, overlays, rules, presets, and starter bundles as a shareable package independent of full site export. Site-specific profile data remains separate unless explicitly included.

---

## 1. Purpose and scope

- **Portable bundle**: A single structured artifact (e.g. JSON or in-memory array) containing industry pack–related definitions that can be moved or versioned independently of a full site export.
- **Internal use**: For team workflows (pack authoring, version control, staging). No public marketplace; no executable payloads; no secrets.
- **Full site export remains authoritative** for full-instance moves; pack bundles complement it for industry-only portability.

---

## 2. Bundle manifest

Every bundle MUST include a top-level manifest with the following fields:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **bundle_version** | string | Yes | Bundle format version (e.g. `1`). Used for import compatibility. |
| **schema_version** | string | Yes | Industry pack schema version (e.g. `1`); aligns with Industry_Pack_Schema version_marker. |
| **created_at** | string | Yes | ISO 8601 timestamp when the bundle was built. |
| **included_categories** | list&lt;string&gt; | Yes | Categories present in this bundle (see §3). |
| **dependency_refs** | object or null | No | Optional dependency or compatibility hints (e.g. plugin_min_version). No executable content. |

**Supported bundle_version values:** `1`. Import MUST reject unsupported bundle_version with a clear error; no silent skip of entire bundle.

---

## 3. Included categories and payload keys

Bundle payload is keyed by category. Only categories listed in `included_categories` need be present; others omitted or empty.

| Category key | Payload key | Description |
|--------------|-------------|-------------|
| **packs** | `packs` | List of industry pack definition objects (Industry_Pack_Schema shape). |
| **starter_bundles** | `starter_bundles` | List of starter bundle definition objects (industry-starter-bundle-schema). |
| **style_presets** | `style_presets` | List of industry style preset definition objects (industry-style-preset-schema). |
| **cta_patterns** | `cta_patterns` | List of CTA pattern definition objects (industry-cta-pattern-contract). |
| **seo_guidance** | `seo_guidance` | List of SEO guidance rule objects (industry-seo-guidance-schema). |
| **lpagery_rules** | `lpagery_rules` | List of LPagery rule objects (industry-lpagery-rule-schema). |
| **section_helper_overlays** | `section_helper_overlays` | List of section-helper overlay definition objects. |
| **page_one_pager_overlays** | `page_one_pager_overlays` | List of page one-pager overlay definition objects. |
| **question_packs** | `question_packs` | List of onboarding question-pack definition objects. |
| **site_profile** | `site_profile` | Optional. When policy allows: industry profile + applied preset snapshot. Excluded by default for shareable pack-only bundles. |

---

## 4. Validation rules

- **Manifest**: `bundle_version`, `schema_version`, `created_at`, `included_categories` must be present and non-empty. `bundle_version` must be in the supported list.
- **Per-category**: Each payload array must be a list of arrays (definition objects). No executable logic; no secrets. Schema validation for each object type is applied at import (see industry-pack-import-conflict-contract for conflict handling).
- **No executable payloads**: Bundles contain only data (arrays, strings, numbers). No PHP/code, no script URLs, no unsafe markup.

---

## 5. Versioning and compatibility

- **Bundle format version** (`bundle_version`): Bump when the bundle structure or required manifest fields change. Import must support all non-deprecated versions or fail with a clear message.
- **Schema version** (`schema_version`): Aligns with pack/object schema versions; used for per-object validation and migration hints.
- **Compatibility**: Document intended plugin/minimum versions in dependency_refs if needed; import may warn on version mismatch but should not execute remote code.

---

## 6. Relationship to full export/restore

- **Full site export** (profiles category): Exports `profiles/industry.json` with industry profile and applied preset only (industry-export-restore-contract). Pack definitions are not part of full export payload; they are code/registry-backed.
- **Pack bundle**: Exports pack definitions, overlays, rules, presets, starter bundles. Optional inclusion of site_profile for operator-controlled portability. Import applies to industry registries/overlays; conflict resolution is defined in industry-pack-import-conflict-contract.
- **No bypass**: Pack bundle import MUST NOT bypass full export/restore validation when used in a restore pipeline; it is an additional path for industry-only data.

---

## 7. Security and permissions

- **Admin-only**: Export and import of pack bundles are admin-only operations (capability check at the surface that invokes the service).
- **Strict validation on import**: Invalid manifest or unsupported version → fail with error; no silent partial apply.
- **No secrets**: Bundle contents must not include API keys, credentials, or personal data. Redaction not required for pack/overlay definitions (they are non-secret by design).

---

## 8. Files and service

- **Contract**: docs/contracts/industry-pack-bundle-format-contract.md (this file).
- **Service**: plugin/src/Domain/Industry/Export/Industry_Pack_Bundle_Service.php — builds bundle from built-in (or provided) definitions; validates bundle structure.
- **Import conflict**: docs/contracts/industry-pack-import-conflict-contract.md; Industry_Pack_Import_Conflict_Service (Prompt 395).
