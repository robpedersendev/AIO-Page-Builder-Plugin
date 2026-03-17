# Industry Scaffold Generator Contract (Prompt 460)

**Spec:** authoring guide; future industry candidate evaluation framework; roadmap and maintenance contracts.  
**Status:** Contract. Defines the internal scaffold-generator contract for creating new industry or subtype asset skeletons. No GUI or auto-authoring of substantive content; scaffold output is clearly incomplete until authored.

---

## 1. Purpose

- Provide a **consistent starting structure** for new industry packs, bundles, overlays, rules, presets, and subtype assets so authors do not copy-paste ad hoc.
- **Scope:** File paths, naming, required artifact classes, placeholder state markers, and validation expectations. Optional scaffold metadata conventions.
- **Out of scope:** Full GUI scaffold tool; auto-generation of substantive content; any mechanism that could ship placeholder assets as production-ready.

---

## 2. Scaffold scope (artifact types)

| Artifact type | Location (relative to plugin Industry domain) | Required shape / class | Placeholder marker |
|---------------|------------------------------------------------|------------------------|---------------------|
| **Pack** | Registry/Packs/ (or equivalent load path used by Industry_Pack_Registry) | industry_key, name, summary, status, version_marker per industry-pack-schema | status = `draft`; name/summary may be placeholder text (e.g. "TODO Industry Name"). |
| **Starter bundle** | Registry/StarterBundles/*.php (and optionally StarterBundles/Subtypes/*.php) | bundle_key, industry_key, label, status, version_marker; optional subtype_key per industry-starter-bundle-schema | status = `draft`; recommended_* refs empty or minimal. |
| **Section helper overlay** | Docs/SectionHelperOverlays/*.php | industry_key, section_key, content_body, status per industry-section-helper-overlay-schema | status inactive or content_body placeholder; not registered in pack helper_overlay_refs until authored. |
| **Page one-pager overlay** | Docs/PageOnePagerOverlays/*.php | industry_key, page_template_key, content_body, status per industry-page-onepager-overlay-schema | status inactive or content_body placeholder; not in pack one_pager_overlay_refs until authored. |
| **Subtype section overlay** | Docs/SubtypeSectionHelperOverlays/*.php (via Builtin_Subtype_Section_Helper_Overlays) | subtype_key, section_key, content_body, status per subtype-section-helper-overlay-schema | status inactive or placeholder. |
| **Subtype page one-pager** | Docs/SubtypePageOnePagerOverlays/*.php (via Builtin_Subtype_Page_OnePager_Overlays) | subtype_key, page_template_key, content_body, status per subtype-page-onepager-overlay-schema | status inactive or placeholder. |
| **Subtype definition** | Registry/Subtypes/*.php | subtype_key, parent_industry_key, label, summary, status, version_marker per industry-subtype-schema | status = `draft`. |
| **CTA pattern** | Registry/CTAPatterns/*.php | pattern_key, name, description per contract | name/description placeholder until authored. |
| **Style preset** | Registry/StylePresets/ or equivalent | preset key, label, tokens per industry-style-preset-schema | Label placeholder; tokens minimal or default. |
| **LPagery rule** | LPagery/Rules/*.php | rule key, definition per industry-lpagery-rule-schema | Definition minimal; not referenced by pack until authored. |
| **SEO guidance** | Registry/SEOGuidance/*.php | guidance key, definition per schema | Definition minimal; not in pack seo_guidance_ref until authored. |

---

## 3. File naming and placement

- **Naming:** Lowercase alphanumeric and underscore; pattern `[a-z0-9_-]+`. Match [industry-contract-consistency-audit.md](industry-contract-consistency-audit.md) §5.
- **Placement:** Scaffolded files must sit in the same directories that the existing registries and loaders use (see §2). No new top-level directories without a contract update.
- **Load order:** Scaffolded assets are loaded by the same registry/loader as authored assets; they must be **discoverable** but **not activated** until status is set to active and content is authored. Registries that filter by status will ignore draft/inactive scaffold output.

---

## 4. Incomplete / placeholder status markers

- **Pack, bundle, subtype:** Set `status` = `draft` (Industry_Pack_Schema::STATUS_DRAFT and equivalent). Only `active` entities are used for recommendations and resolution; draft scaffold will not affect live behavior.
- **Overlays:** Set overlay `status` to inactive or equivalent so they are not composed into helper/one-pager output until authored.
- **Refs:** Do not add scaffold pack keys to profile or to builtin pack list used for default activation. Do not add scaffold overlay refs to pack `helper_overlay_refs` / `one_pager_overlay_refs` until content is ready.
- **Metadata:** Optional scaffold metadata (e.g. `scaffold_generated_at`, `scaffold_version`) may be added for tooling; it must not affect runtime resolution or export semantics. Strip or ignore in production validation.

---

## 5. Validation expectations for scaffolded-but-incomplete assets

- **Schema:** Scaffolded artifacts must pass **structural** validation (required fields present, types correct, version_marker supported). They may fail **ref resolution** (e.g. token_preset_ref pointing to a key that does not yet exist) until dependencies are added.
- **Linting:** Industry_Definition_Linter may report errors for broken refs or missing dependencies; authors resolve these before promoting to active.
- **Health check:** Industry_Health_Check_Service will report errors for pack refs that do not resolve; scaffolded packs with placeholder refs are expected to produce health errors until refs are filled. Do not activate scaffolded packs in production.
- **Incomplete-state guardrails:** Scaffolded (incomplete) assets are excluded from release-ready candidate flows and must not be mistaken for releasable definitions. See [scaffold-incomplete-state-guardrail-contract.md](scaffold-incomplete-state-guardrail-contract.md) for validation behavior, release-gate exclusion, and how incomplete state is cleared through authoring.

---

## 6. Promotion path (scaffold → authored)

1. **Author content:** Replace placeholder name/summary/content_body with real content; add or fix refs (CTA, SEO, preset, overlays, bundle).
2. **Validate:** Run Industry_Pack_Schema::validate_pack() (or equivalent per artifact); run Industry_Definition_Linter; fix errors.
3. **Resolve refs:** Ensure all refs resolve (health check passes for the pack and profile).
4. **Set status:** Change status from `draft` to `active` only when content and refs are complete and reviewed.
5. **Register:** Ensure pack is in builtin definitions (or load path) so registry discovers it; overlays registered in pack refs only when ready.
6. **Release:** Follow industry-pack-authoring-guide and industry-pack-release-gate; scaffold contract does not replace release or pre-release validation.

---

## 7. Safety and constraints

- **No hidden activation:** Scaffold output must not be auto-activated (e.g. no code that sets primary_industry_key to a scaffold pack key on first run). Activation is always explicit (admin or author).
- **Registry-first:** Scaffolded files must be in registry load paths; no "scaffold-only" shadow registry that could be confused with production.
- **Internal only:** Scaffold tooling and contract are for internal authoring; no end-user-facing scaffold generation.

---

## 8. References

- [future-industry-scaffold-pack-template.md](../operations/future-industry-scaffold-pack-template.md) — Concrete file and artifact skeleton for a future industry pack (Prompt 516); required artifact classes, placement, placeholder markers, minimum docs/QA.
- [future-industry-starter-bundle-scaffold-template.md](../operations/future-industry-starter-bundle-scaffold-template.md) — Starter bundle scaffold structure, parent/subtype/goal hook points, and promotion path (Prompt 536).
- [future-industry-overlay-scaffold-template-set.md](../operations/future-industry-overlay-scaffold-template-set.md) — Overlay and rule scaffold templates: helper, page one-pager, caution, SEO, CTA (Prompt 537).
- [future-subtype-scaffold-pack-template.md](../operations/future-subtype-scaffold-pack-template.md) — Concrete file and artifact skeleton for a future subtype pack (Prompt 517); subtype definitions, overlay/bundle/caution placeholders, docs/QA minimums.
- [industry-pack-authoring-guide.md](../operations/industry-pack-authoring-guide.md) — Required pieces and implementation order.
- [industry-pack-maintenance-checklist.md](../operations/industry-pack-maintenance-checklist.md) — Maintenance baseline.
- [future-industry-candidate-evaluation-framework.md](../operations/future-industry-candidate-evaluation-framework.md) — Candidate evaluation before authoring.
- [industry-contract-consistency-audit.md](industry-contract-consistency-audit.md) — Naming and lifecycle.
- [scaffold-incomplete-state-guardrail-contract.md](scaffold-incomplete-state-guardrail-contract.md) — Incomplete-state validation, release-gate exclusion, linting integration (Prompt 518).
- [industry-pack-completeness-scoring-contract.md](industry-pack-completeness-scoring-contract.md) — Advisory completeness dimensions for packs, subtypes, bundles, overlays, docs, QA (Prompt 519); use to assess scaffold progress toward release-grade.
