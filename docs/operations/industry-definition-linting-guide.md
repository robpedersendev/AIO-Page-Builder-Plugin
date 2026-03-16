# Industry Definition Linting Guide (Prompt 438)

**Spec**: industry-pack-schema; pack, bundle, overlay, subtype, and rule contracts; health-report contracts.  
**Purpose**: Internal linting and registry-graph validation for industry authors so pack files, overlays, bundles, rules, and refs can be checked automatically before release or import.

---

## 1. Scope

- **Tool**: `Industry_Definition_Linter` (plugin/src/Domain/Industry/Reporting/Industry_Definition_Linter.php). Internal-only; no public validator; no auto-fix.
- **Checks**: Schema conformance (pack), duplicate keys (pack, starter bundle), missing or invalid refs (CTA, SEO, LPagery, preset, overlay, starter bundle), subtype parent_industry_key resolution, bundle industry_key resolution. Reuses `Industry_Health_Check_Service` for ref and profile/bundle consistency.
- **Output**: Human-readable lint results (errors, warnings, summary counts) for use in authoring and release workflows.

---

## 2. What the linter checks

| Check | Description | Severity |
|-------|-------------|----------|
| Pack schema | Required fields, status, version_marker, key pattern/length per Industry_Pack_Schema. | error |
| Duplicate pack key | Same industry_key in more than one pack. | error |
| Ref resolution | token_preset_ref, seo_guidance_ref, lpagery_rule_ref, CTA pattern refs, helper_overlay_refs, one_pager_overlay_refs, starter_bundle_ref (if set) resolve to existing registry entries. | error / warning |
| Profile | Primary/secondary industry keys and selected starter bundle resolve; pack disabled warning. | error / warning |
| Starter bundle graph | Bundle industry_key has matching pack. | warning |
| Subtype parent | Each subtype’s parent_industry_key has a matching pack. | error |
| Duplicate bundle key | Duplicate bundle_key in starter bundle definitions. | warning |

---

## 3. How to run

- **Runtime**: Instantiate `Industry_Definition_Linter` with the same registries used by `Industry_Health_Check_Service` (pack, CTA, SEO, LPagery, preset, section overlay, page overlay, question pack, starter bundle, profile repo, optional pack toggle). Pass `Industry_Health_Check_Service` (or equivalent) so ref and profile checks are included. Call `lint()` for errors/warnings/summary or `get_all_issues()` for a flat list.
- **Before release or import**: Run after registries are loaded (e.g. after bootstrap or after loading a bundle). Fix reported errors before release; treat warnings as advisory.

---

## 4. Result shape

- `errors`: list of `{ severity, code, message, object_type, key, field?, related_refs }`
- `warnings`: same shape
- `summary`: `{ error_count, warning_count }`

Use `message` and `related_refs` for human-readable output. `object_type` is one of pack, starter_bundle, subtype, profile.

---

## 5. Integration

- **Release gate**: Include definition lint in pre-release validation; see [industry-pre-release-validation-pipeline.md](../release/industry-pre-release-validation-pipeline.md) and [industry-pre-release-checklist.md](../release/industry-pre-release-checklist.md).
- **Authoring**: Run after adding or changing pack, overlay, bundle, or subtype definitions. Does not replace manual review or runtime validation.

---

## 6. Do not

- Expose the linter on a public endpoint.
- Auto-fix or mutate definition files from this tool.
- Rely on lint alone; runtime validation and health checks remain the runtime safety layer.
