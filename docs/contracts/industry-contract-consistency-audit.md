# Industry Subsystem Cross-Contract Consistency Audit (Prompt 457)

**Purpose:** Formal audit of industry subsystem contracts, schemas, guides, and release docs for terminology, lifecycle, schema-field, and workflow consistency. Resolved conventions are documented here; conflicting docs are updated to align.

---

## 1. Resolved terminology

| Concept | Canonical term | Used in profile | Used in pack/bundle/subtype | Notes |
|---------|----------------|-----------------|-----------------------------|-------|
| Primary industry | **primary_industry_key** | Industry_Profile_Schema | N/A (profile only) | Profile field; must match pack industry_key. |
| Pack identifier | **industry_key** | Referenced by primary_industry_key | Industry_Pack_Schema, overlays, registries | Stable key for a pack (e.g. realtor, plumber). |
| Secondary industries | **secondary_industry_keys** | Industry_Profile_Schema | N/A | List of industry_key. |
| Structured subtype | **industry_subtype_key** | Industry_Profile_Schema | Subtype_Registry, subtype overlays | Must reference subtype whose parent_industry_key matches primary. |
| Legacy subtype hint | **subtype** | Industry_Profile_Schema (optional) | N/A | Freeform; industry_subtype_key is structured. |
| Starter bundle identifier | **bundle_key** | selected_starter_bundle_key in profile | Industry_Starter_Bundle_Registry | Unique per bundle. |
| Subtype in bundle | **subtype_key** | N/A | Bundle definition FIELD_SUBTYPE_KEY | Optional; when set bundle is subtype-scoped. |
| Parent industry (subtype) | **parent_industry_key** | N/A | Industry_Subtype_Registry | Subtype definition; must match primary when subtype selected. |

**Consistency rule:** In profile docs use primary_industry_key / industry_subtype_key / selected_starter_bundle_key. In pack/bundle/subtype object schemas use industry_key, bundle_key, subtype_key, parent_industry_key per schema. Do not mix "industry" and "primary_industry" for the same concept in the same layer.

---

## 2. Lifecycle state consistency

| Object | States | Canonical values | Where defined |
|--------|--------|------------------|---------------|
| Industry pack | active, draft, deprecated | STATUS_ACTIVE, STATUS_DRAFT, STATUS_DEPRECATED | Industry_Pack_Schema; Industry_Pack_Registry |
| Starter bundle | active, draft, deprecated | Same | industry-starter-bundle-schema; Industry_Starter_Bundle_Registry |
| Subtype | active, draft, deprecated | Same | industry-subtype-schema; Industry_Subtype_Registry |
| Overlay (helper, one-pager) | status active/inactive per schema | STATUS_ACTIVE | Respective overlay schemas |

**Rule:** Only **active** entities are used for recommendations, overlays, and resolution. Draft and deprecated are ignored for resolution; deprecated may surface warnings or replacement_ref. All lifecycle docs (deprecation, migration, release) use these three states consistently.

---

## 3. Schema-field consistency

- **Profile:** Industry_Profile_Schema and industry-profile-schema.md: schema_version, primary_industry_key, secondary_industry_keys, subtype, industry_subtype_key, service_model, geo_model, derived_flags, question_pack_answers. selected_starter_bundle_key in schema (profile store). Data-schema-appendix §11.1 matches.
- **Pack:** industry_key, name, summary, status, version_marker required; refs and key arrays optional. industry-pack-schema.md and Industry_Pack_Schema aligned.
- **Starter bundle:** bundle_key, industry_key, subtype_key (optional), label, status, version_marker, recommended_* refs. industry-starter-bundle-schema and Industry_Starter_Bundle_Registry constants aligned.
- **Subtype:** subtype_key, parent_industry_key, label, summary, status, version_marker. industry-subtype-schema and Industry_Subtype_Registry aligned.

**Audit result:** No conflicting field names found across sampled contracts. Optional fields consistently documented as optional.

---

## 4. Workflow consistency

- **Release:** Pre-release validation pipeline → release gate → sign-off. Sandbox promotion → release-ready summary (no auto-activate). Release candidate manifest defines evidence bundle. All referenced in industry-pack-release-gate, industry-pre-release-validation-pipeline, industry-sandbox-promotion-workflow, industry-release-candidate-manifest-contract.
- **Fallback:** No industry / invalid subtype / deprecated → parent-only or neutral. industry-subtype-extension-contract §6 and industry-subtype-fallback-audit align.
- **Diagnostics:** Snapshot shape (primary_industry, secondary_industries, profile_readiness, active_pack_refs, etc.) consistent in industry-subsystem-diagnostics-checklist and Industry_Diagnostics_Service.

---

## 5. Naming conventions (resolved)

- **Keys:** Lowercase alphanumeric and underscore (e.g. realtor, plumber_residential_starter). Pattern #^[a-z0-9_-]+$ in registries.
- **Refs:** References to other artifacts use _ref suffix when single (e.g. token_preset_ref, starter_bundle_ref) or _refs when list (helper_overlay_refs).
- **Version:** version_marker in definitions; schema_version in profile and export envelopes.

---

## 6. Updates applied

- **data-schema-appendix:** §11 already references industry-pack-schema, industry-profile-schema, overlays, override, compliance. No field name changes; this audit is additive. Add cross-reference to this audit in data-schema-appendix §11 (optional) for future maintainers.
- **Conflicting docs:** Spot-check of contracts found no contradictory lifecycle or field names; resolved terminology table above is the single source for future edits.

---

## 7. Maintenance

- When adding new industry contracts or schemas, use the terminology table (§1) and lifecycle states (§2). Add new artifact types to the schema-field section (§3) and workflow section (§4) as needed.
- Re-run a lightweight consistency check when adding new docs (e.g. new contract referencing industry_key vs primary_industry_key in profile context).

---

## 8. References

- [industry-profile-schema.md](../schemas/industry-profile-schema.md)
- [industry-pack-schema.md](../schemas/industry-pack-schema.md)
- [industry-starter-bundle-schema.md](../schemas/industry-starter-bundle-schema.md)
- [industry-subtype-schema.md](../schemas/industry-subtype-schema.md)
- [data-schema-appendix.md](../appendices/data-schema-appendix.md) §11
- [industry-subtype-extension-contract.md](industry-subtype-extension-contract.md)
- [industry-pack-deprecation-contract.md](industry-pack-deprecation-contract.md)
