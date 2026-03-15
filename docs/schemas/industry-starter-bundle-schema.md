# Industry Starter Bundle Schema

**Spec**: industry-pack-extension-contract.md; aio-page-builder-master-spec.md (Build Plan, onboarding/profile).

**Status**: Canonical schema for Industry Starter Bundle objects. Bundles are curated overlays that recommend page families, template refs, section emphasis, and optional CTA/style/LPagery refs for a specific industry. They do not replace core registries.

---

## 1. Purpose

- Define the **canonical shape** of a starter bundle object.
- Support **versioned**, **exportable** bundle definitions that can be referenced from Industry Packs or onboarding flows.
- Bundles are **overlays**: they recommend starting sets; section and page template registries remain authoritative.

---

## 2. Required fields

| Field | Type | Description |
|-------|------|-------------|
| **bundle_key** | string | Stable, unique key for the bundle (e.g. `realtor_starter`, `plumber_essentials`). |
| **industry_key** | string | Industry pack key this bundle belongs to (e.g. `realtor`, `plumber`). |
| **label** | string | Human-readable bundle name. |
| **summary** | string | Short description of what the bundle offers. |
| **status** | string | Lifecycle status: `active`, `draft`, or `deprecated`. Only `active` bundles are offered. |
| **version_marker** | string | Schema version (e.g. `1`). Used to reject unsupported versions at load. |

---

## 3. Optional fields

| Field | Type | Description |
|-------|------|-------------|
| **recommended_page_families** | list&lt;string&gt; | Page template families to emphasize as starting points. |
| **recommended_page_template_refs** | list&lt;string&gt; | Page template internal_keys recommended in this bundle. |
| **recommended_section_refs** | list&lt;string&gt; | Section template internal_keys to emphasize (section emphasis). |
| **token_preset_ref** | string | Optional reference to industry style preset key (Industry_Style_Preset_Registry). |
| **cta_guidance_ref** | string | Optional reference to CTA/posture guidance (e.g. CTA pattern or guidance key). |
| **lpagery_guidance_ref** | string | Optional reference to LPagery rule or guidance key. |
| **metadata** | map | Optional arbitrary metadata (e.g. sort order). Must not contain secrets or executable content. |

---

## 4. Validation rules

- **bundle_key**: Non-empty; pattern `^[a-z0-9_-]+$`; max length 64. Must be unique within the registry.
- **industry_key**: Non-empty; pattern `^[a-z0-9_-]+$`; max length 64. Should resolve to an industry pack when used.
- **label**, **summary**: Non-empty when present; reasonable max length (e.g. 256 for label, 1024 for summary).
- **status**: Must be one of `active`, `draft`, `deprecated`.
- **version_marker**: Must match a supported schema version (e.g. `1`). Unsupported versions cause safe rejection at load.
- **recommended_*** refs: Arrays of non-empty strings; keys are advisory and should exist in section/page registries when applied.
- **token_preset_ref**, **cta_guidance_ref**, **lpagery_guidance_ref**: No secrets; safe for export. Resolution defined by respective subsystems.

Invalid bundle definitions must be **skipped** at load (no throw); registry remains consistent.

---

## 5. Registry behavior

- **Industry_Starter_Bundle_Registry**: Read-only. `load( array $definitions )`, `get( string $bundle_key ): ?array`, `get_for_industry( string $industry_key ): array`, `list_all(): array`.
- Invalid definitions are skipped during load; duplicate bundle_key (first wins) or invalid shape do not break the registry.
- No arbitrary execution embedded in bundle data; refs are resolved by other subsystems when bundles are **applied** (out of scope for this schema).

---

## 6. Relationship to Industry Pack

- An industry pack may reference a starter bundle via an optional **starter_bundle_ref** (see industry-pack-schema.md).
- The bundle's **industry_key** should match the pack's **industry_key** when referenced from that pack.
- Bundles are not stored inside the pack object; they are separate registry entries.

---

## 7. Implementation reference

- **Industry_Starter_Bundle_Registry** (plugin/src/Domain/Industry/Registry/Industry_Starter_Bundle_Registry.php): Field constants, load, get, get_for_industry, list_all; validate_bundle returns list of error codes; invalid entries skipped at load.
