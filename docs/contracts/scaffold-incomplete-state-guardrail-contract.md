# Scaffold Incomplete-State Guardrail Contract (Prompt 518)

**Spec:** industry-scaffold-generator-contract.md; future-industry-scaffold-pack-template.md; future-subtype-scaffold-pack-template.md; industry-definition-linting-guide.md; industry-pack-release-gate.md.  
**Status:** Contract. Defines validation and guardrails so scaffolded (incomplete) assets cannot be mistaken for releasable definitions. No public status system; no auto-promotion from scaffold to live.

---

## 1. Purpose

- **Distinguish incomplete from release-ready:** Scaffolded packs, subtypes, bundles, and overlays are explicitly incomplete until authored, validated, and status set to active. The system must not treat them as production-ready.
- **Safe failure:** Prefer excluding scaffold/incomplete assets from release and activation flows over accidentally including them.
- **Explicit guardrails:** Incomplete-state checks are bounded and documented; authors know how to clear incomplete state through authoring.

---

## 2. Incomplete-state definition

An asset is **incomplete** (scaffold) when any of the following hold:

| Condition | Meaning |
|-----------|---------|
| **status = draft** | Pack, subtype, or starter bundle has `status` = `draft` (Industry_Pack_Schema::STATUS_DRAFT or equivalent). Only `active` entities are used for recommendations and resolution. |
| **Overlay inactive** | Section-helper or page one-pager overlay has `status` inactive or content_body is placeholder; not referenced by pack/subtype until authored. |
| **Scaffold metadata present** | Optional metadata such as `scaffold_generated_at`, `scaffold_version` indicates the asset was generated from a scaffold template and may not yet be authored. (Metadata may be stripped or ignored in production validation.) |
| **Placeholder content** | Name, summary, or content_body is explicitly placeholder (e.g. "TODO Industry Name", "Scaffold – incomplete") per future-industry-scaffold-pack-template or future-subtype-scaffold-pack-template. |

Runtime registries remain **authoritative only for valid, active assets**. Draft or inactive scaffold output is discoverable by registries but must be excluded from release-ready and activation flows.

---

## 3. Incomplete-state validation behavior

- **Structural validation:** Scaffolded artifacts must pass **structural** validation (required fields present, types correct, version_marker supported). They may fail **ref resolution** (e.g. token_preset_ref, overlay refs pointing to keys that do not yet exist) until dependencies are added.
- **Linting:** Industry_Definition_Linter reports errors for broken refs, missing dependencies, and schema violations. Scaffolded packs with placeholder refs or draft status are expected to produce lint errors or warnings until refs are filled and status is set to active. Linter does not treat "scaffold" as a special bypass; draft packs with broken refs fail like any other invalid definition.
- **Health check:** Industry_Health_Check_Service reports errors for pack/bundle/subtype refs that do not resolve. Scaffolded packs with placeholder refs are expected to produce health errors until refs are fixed. No special "scaffold pass"; incomplete state is reflected in normal validation failure.
- **Optional incomplete-state check:** Tooling may implement an explicit "incomplete-state" check (e.g. list packs/subtypes with status = draft or with scaffold metadata) for author/release workflows. Such a check is **advisory** and used to exclude those assets from release-ready candidate lists; it does not replace schema or ref validation.

---

## 4. Release-ready candidate flows must exclude scaffold assets

- **Release gate:** Only **active** packs, subtypes, and bundles that pass definition lint and health check are eligible for release scope. Draft or scaffold-marked assets must **not** be included in the set of "release-ready" candidates. Pre-release validation pipeline (industry-pre-release-validation-pipeline.md) runs against the set of assets intended for release; scaffold-only or draft-only assets are out of scope until promoted per authoring flow.
- **Sandbox promotion:** Industry_Sandbox_Promotion_Service (or equivalent) must not promote draft or incomplete scaffold assets to "release-ready" without authoring and status change. check_prerequisites() and get_release_ready_summary() should consider only active, validated assets (or explicitly include draft only when workflow documents "promote scaffold to authored" step).
- **No silent promotion:** No code path may automatically set status from `draft` to `active` or add scaffold pack keys to builtin/default activation lists without explicit author or admin action and validation.

---

## 5. Linting integration

- **Existing lint rules:** Industry_Definition_Linter already checks schema, duplicate keys, ref resolution, profile, and subtype parent. Scaffolded assets with draft status and broken refs naturally fail these checks. No change to lint semantics is required for "incomplete" to be reflected: draft + broken refs = errors.
- **Optional scaffold-aware reporting:** Lint output may optionally tag or summarize assets that are draft or carry scaffold metadata (e.g. "N draft packs (scaffold/incomplete); not eligible for release"). This supports author/release workflows by making exclusion explicit. Implementation is additive; lint pass/fail remains based on schema and ref rules, not on a separate "scaffold" flag.
- **Documentation:** industry-definition-linting-guide and pre-release pipeline docs state that draft and scaffold-incomplete assets are expected to produce errors or warnings until authoring is complete, and that release candidate flows exclude them.

---

## 6. How incomplete state is cleared (authoring path)

Incomplete state is cleared only through **authoring** per industry-scaffold-generator-contract §6 and future-industry-scaffold-pack-template / future-subtype-scaffold-pack-template:

1. **Author content:** Replace placeholder name/summary/content_body with real content; add or fix refs (CTA, SEO, preset, overlays, bundle).
2. **Validate:** Run schema validation, Industry_Definition_Linter, and health check; fix errors.
3. **Resolve refs:** Ensure all refs resolve; health check passes for the pack/subtype and profile.
4. **Set status:** Change status from `draft` to `active` only when content and refs are complete and reviewed. Remove or ignore scaffold metadata for production.
5. **Include in release flow:** Only after status is active and validation passes, the asset is eligible for release-ready candidate flows and release gate.

No automatic transition from incomplete to complete; no "approve scaffold" without authoring and validation.

---

## 7. Safety and constraints

- **Internal-only:** Incomplete-state guardrails and validator behavior are for internal author/release tooling. No public "scaffold status" API or end-user-facing state.
- **No weakening of runtime validation:** Runtime validation (schema, ref resolution, health check) remains strict. Scaffold assets do not get a bypass; they fail validation until fixed.
- **Safe failure:** If in doubt, exclude an asset from release-ready flows. Accidental inclusion of incomplete scaffold in a release is the failure mode to prevent; accidental exclusion of an authored asset is mitigated by authoring checklist and status change.

---

## 8. Cross-references

- [industry-scaffold-completeness-report-contract.md](industry-scaffold-completeness-report-contract.md) — Advisory scaffold progress report (missing vs scaffolded vs authored); no release implication (Prompt 538).
- [industry-scaffold-generator-contract.md](industry-scaffold-generator-contract.md) — Scaffold scope, placeholder markers, promotion path.
- [future-industry-scaffold-pack-template.md](../operations/future-industry-scaffold-pack-template.md) — Industry scaffold template; placeholder and incomplete-state markers.
- [future-subtype-scaffold-pack-template.md](../operations/future-subtype-scaffold-pack-template.md) — Subtype scaffold template; placeholder and incomplete-state markers.
- [industry-definition-linting-guide.md](../operations/industry-definition-linting-guide.md) — Linting scope; draft and broken refs produce errors.
- [industry-pack-release-gate.md](../release/industry-pack-release-gate.md) — Release criteria; only active, validated assets in scope.
- [industry-pre-release-validation-pipeline.md](../release/industry-pre-release-validation-pipeline.md) — Pre-release steps; scaffold/incomplete assets excluded from release set.
