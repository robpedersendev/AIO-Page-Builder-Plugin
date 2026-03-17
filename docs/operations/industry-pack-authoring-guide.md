# Industry Pack Authoring Guide (Internal)

**Spec**: industry-pack-extension-contract.md; industry-pack-schema.md; industry-pack-release-gate.md; industry-subsystem-roadmap-contract.md; all industry subsystem contracts and schemas (Prompts 318–378).

**Purpose**: Internal workflow for adding new industry packs so expansion stays disciplined, registry-first, and consistent with subsystem contracts. Use with [industry-pack-author-checklist.md](industry-pack-author-checklist.md). Long-term extension boundaries and roadmap: [industry-subsystem-roadmap-contract.md](../contracts/industry-subsystem-roadmap-contract.md). **v2 guardrails** (Prompt 469): [industry-subsystem-v2-guardrails.md](../contracts/industry-subsystem-v2-guardrails.md) define unacceptable drift and acceptable seams; authoring must align. **Before** adding a new industry, evaluate the candidate with [future-industry-candidate-evaluation-framework.md](future-industry-candidate-evaluation-framework.md) and [future-industry-scorecard-template.md](future-industry-scorecard-template.md). To start from consistent file structures and schema-valid placeholders instead of ad hoc copying, use [industry-scaffold-generator-contract.md](../contracts/industry-scaffold-generator-contract.md) and the concrete [future-industry-scaffold-pack-template.md](future-industry-scaffold-pack-template.md) (required artifact classes, file placement, placeholder markers, minimum docs/QA, promotion path). **End-to-end workflow:** [future-industry-first-pack-authoring-runbook.md](future-industry-first-pack-authoring-runbook.md) (Prompt 539) and [future-subtype-first-pack-authoring-runbook.md](future-subtype-first-pack-authoring-runbook.md) (Prompt 540) document the first-pack authoring sequence from scaffold through validation and release readiness.

---

## 1. Scope and principles

- **Registry-first**: All new industry objects (pack definition, CTA patterns, overlays, SEO rules, style presets, LPagery rules, question packs) must conform to published schemas and be loaded via existing registries. No ad hoc files or bypasses.
- **Additive only**: New packs extend the subsystem; they do not replace core registries or change core plugin behavior when no industry is set.
- **No secrets**: Industry pack data (definitions, overlays, presets) must never contain API keys, credentials, or other secrets. Safe for export and support diagnostics.
- **Validation and QA**: Every new pack and related artifact must pass schema validation and the release gate expectations (or documented waiver).

---

## 2. Required subsystem pieces for a new industry

| Piece | Description | Schema / contract |
|-------|-------------|-------------------|
| **Pack definition** | Industry pack object with industry_key, name, summary, status, version_marker; optional supported_page_families, preferred/discouraged section keys, CTA refs, seo_guidance_ref, token_preset_ref, lpagery_rule_ref, overlay refs. | industry-pack-schema.md; Industry_Pack_Schema |
| **Registry registration** | Pack loaded via Industry_Pack_Registry (e.g. builtin definitions under `Industry/Registry/Packs/`). | industry-pack-extension-contract; industry-pack-service-map |
| **CTA patterns** | Any CTA pattern keys referenced by the pack (preferred_cta_patterns, required_cta_patterns, discouraged_cta_patterns) must exist in Industry_CTA_Pattern_Registry. | industry-cta-pattern-contract.md; industry-cta-pattern-catalog |
| **Section/page affinity** | Section and page templates may carry industry_affinity / industry_discouraged (or pack uses preferred_section_keys / discouraged_section_keys). No requirement to tag every template; pack lists drive recommendation. | industry-section-recommendation-contract; industry-page-template-recommendation-contract |
| **Style preset** (optional) | If token_preset_ref is set, the preset must exist in Industry_Style_Preset_Registry. | industry-style-preset-schema; industry-style-preset-catalog |
| **SEO guidance** (optional) | If seo_guidance_ref is set, the rule must exist in Industry_SEO_Guidance_Registry. | industry-seo-guidance-schema; industry-seo-guidance-catalog |
| **LPagery rules** (optional) | If lpagery_rule_ref is set, the rule must exist in Industry_LPagery_Rule_Registry. | industry-lpagery-rule-schema; industry-lpagery-rule-catalog |
| **Helper overlays** (optional) | Section-helper overlays keyed by industry + section_key; schema-valid. Expansion: [industry-helper-overlay-expansion-plan.md](industry-helper-overlay-expansion-plan.md) and [industry-helper-overlay-coverage-matrix.md](../appendices/industry-helper-overlay-coverage-matrix.md). | industry-section-helper-overlay-schema; industry-overlay-catalog |
| **One-pager overlays** (optional) | Page one-pager overlays keyed by industry + page_template_key; schema-valid. Expansion: [industry-page-onepager-overlay-expansion-plan.md](industry-page-onepager-overlay-expansion-plan.md) and [industry-page-overlay-coverage-matrix.md](../appendices/industry-page-overlay-coverage-matrix.md). | industry-page-onepager-overlay-schema; industry-overlay-catalog |
| **Question packs** (optional) | Onboarding question-pack definitions for the industry; referenced by profile. | industry-question-pack-contract; industry-question-pack-catalog |
| **Export/restore** | Industry profile and pack refs included in export; restore validates and migrates; no new secrets. | industry-export-restore-contract |
| **Diagnostics** | Industry snapshot in Support Triage remains bounded; new pack refs appear in diagnostics only as refs, not raw content. | industry-pack-release-gate; industry-subsystem-diagnostics-checklist |
| **Subtypes** (optional) | Structured sub-variants of a pack (e.g. realtor_buyer_agent, plumber_residential) per industry-subtype-schema.md. Profile may store industry_subtype_key; resolver falls back to parent when invalid. | industry-subtype-extension-contract.md; industry-subtype-schema.md |

---

## 3. Recommended implementation order

1. **Pack definition** – Create the pack PHP definition (industry_key, name, summary, status, version_marker, supported_page_families, preferred/discouraged section keys, CTA refs). Validate with Industry_Pack_Schema / validator.
2. **CTA patterns** – Add any new CTA pattern definitions referenced by the pack; register in Industry_CTA_Pattern_Registry (or extend builtin CTAPatterns).
3. **Style preset** (if used) – Add preset definition; register in Industry_Style_Preset_Registry. Link pack token_preset_ref to preset key.
4. **SEO guidance** (if used) – Add SEO guidance rule; register in Industry_SEO_Guidance_Registry. Link pack seo_guidance_ref.
5. **LPagery rules** (if used) – Add LPagery rule definition; register in Industry_LPagery_Rule_Registry. Link pack lpagery_rule_ref.
6. **Helper overlays** (if used) – Add section-helper overlay definitions per industry-section-helper-overlay-schema; load via Industry_Section_Helper_Overlay_Registry. For systematic expansion across section families, use [industry-helper-overlay-expansion-plan.md](industry-helper-overlay-expansion-plan.md) (tiers, waves, consistency rules) and [industry-helper-overlay-coverage-matrix.md](../appendices/industry-helper-overlay-coverage-matrix.md).
7. **One-pager overlays** (if used) – Add page one-pager overlay definitions; load via Industry_Page_OnePager_Overlay_Registry. For systematic expansion across page families, use [industry-page-onepager-overlay-expansion-plan.md](industry-page-onepager-overlay-expansion-plan.md) and [industry-page-overlay-coverage-matrix.md](../appendices/industry-page-overlay-coverage-matrix.md).
8. **Question packs** (if used) – Add question-pack definition; register in Industry_Question_Pack_Registry.
9. **Catalog and docs** – Update industry-pack-catalog.md, industry-overlay-catalog.md, industry-cta-pattern-catalog.md, industry-style-preset-catalog.md, industry-seo-guidance-catalog.md, industry-lpagery-rule-catalog.md, industry-question-pack-catalog.md as applicable.
10. **Validation and QA** – Run Industry_Profile_Validator and pack validation for the new industry_key; run industry-subsystem-acceptance-report checks; update release gate evidence if in scope.
11. **Subtypes** (optional) – If adding subtypes for the industry, define subtype objects per industry-subtype-schema.md; register in subtype registry; ensure profile industry_subtype_key validation and resolver fallback (industry-subtype-extension-contract.md). To start from a consistent skeleton, use [future-subtype-scaffold-pack-template.md](future-subtype-scaffold-pack-template.md).

---

## 4. Validation and QA steps

- **Schema validation**: Pack definition passes Industry_Pack_Schema validation (required fields, status enum, version_marker supported, refs non-empty and format-valid). Invalid packs must be rejected at load.
- **Ref resolution**: All refs (seo_guidance_ref, token_preset_ref, lpagery_rule_ref, CTA pattern keys, overlay refs) resolve to existing registry entries at runtime. Missing refs fail safely (no crash; recommendation/overlay may skip).
- **Definition linting**: Before release or import, run **Industry_Definition_Linter** (see [industry-definition-linting-guide.md](industry-definition-linting-guide.md)) to catch schema conformance, duplicate keys, and broken refs. Resolve errors; treat warnings as advisory.
- **Pre-release pipeline**: For pack version or subtype releases, follow [industry-pre-release-validation-pipeline.md](../release/industry-pre-release-validation-pipeline.md) and [industry-pre-release-checklist.md](../release/industry-pre-release-checklist.md).
- **Dry-run sandbox**: To validate candidate pack/bundle definitions without activating them, use **Industry_Author_Sandbox_Service::run_dry_run()** (see [industry-author-sandbox-guide.md](industry-author-sandbox-guide.md)). No live state is read or written.
- **No-industry fallback**: With zero industry packs or empty profile, core plugin behavior unchanged. Acceptance report documents no-industry path.
- **Export/restore**: Export includes industry profile and applied preset; pack definitions are part of registry export when applicable. Restore validates industry schema version; unsupported version skips industry restore with log.
- **Diagnostics**: Industry_Diagnostics_Service snapshot includes primary/secondary industries, active pack refs, applied preset ref; no secrets or unbounded payloads.
- **Completeness (advisory):** Use [industry-pack-completeness-scoring-contract.md](../contracts/industry-pack-completeness-scoring-contract.md) and `Industry_Pack_Completeness_Report_Service::generate_report()` to assess whether a pack or subtype set is minimally complete, strong, or release-grade. Score does not replace validation or release gate.

---

## 5. Export/restore, diagnostics, and release expectations

- **Export**: Industry data (profile, applied preset) in profiles category per industry-export-restore-contract. Pack definitions follow registry export strategy (e.g. builtin packs are code; no separate export of builtin pack JSON unless product decides otherwise). **Portable pack bundle**: For internal workflows (versioning, moving industry-only config), use the industry pack bundle format and Industry_Pack_Bundle_Service; see [industry-pack-bundle-format-contract.md](../contracts/industry-pack-bundle-format-contract.md).
- **Restore**: Restore pipeline supports industry profile and preset; schema_version checked; unsupported version skips with log. No silent overwrite of core data. Pack bundle import uses conflict resolution per [industry-pack-import-conflict-contract.md](../contracts/industry-pack-import-conflict-contract.md) when implemented.
- **Diagnostics**: Bounded snapshot only; admin/support use. Document any new fields in industry-subsystem-diagnostics-checklist.
- **Release**: For first release of a new pack, satisfy industry-pack-release-gate criteria (additive behavior, export/restore, diagnostics, known risks). New industries beyond the first four require acceptance report update and gate sign-off if in scope.

---

## 6. Common pitfalls and unsupported shortcuts

- **Do not** add pack definitions outside the registry load path (e.g. random PHP files not loaded by Industry_Pack_Registry). Packs must be discoverable and validatable.
- **Do not** hardcode industry_key in core flows; use profile and registry. Core must run with no industry.
- **Do not** put API keys, credentials, or secrets in pack definitions, overlays, or export payloads.
- **Do not** skip schema validation or version checks. Unsupported version_marker must fail validation.
- **Do not** create duplicate industry_key; registry behavior (first wins or reject) must be consistent.
- **Do not** assume section_key or page template key exists without checking registry; refs that don’t resolve fail safely.
- **Do not** add new public admin routes or mutation surfaces without going through capability and nonce checks and contract review.

---

## 7. Cross-references

- [future-industry-first-pack-authoring-runbook.md](future-industry-first-pack-authoring-runbook.md) — End-to-end runbook: candidate approval → scaffold → authoring → validation → release (Prompt 539).
- [future-subtype-first-pack-authoring-runbook.md](future-subtype-first-pack-authoring-runbook.md) — End-to-end runbook for first subtype pack: planning → scaffold → authoring → validation → release (Prompt 540).
- [industry-pack-author-checklist.md](industry-pack-author-checklist.md) – Concise checklist for each new pack.
- [industry-pack-maintenance-checklist.md](industry-pack-maintenance-checklist.md) – Ongoing maintenance (overlays, CTA, resolvers, export, presets).
- [industry-subsystem-roadmap-contract.md](../contracts/industry-subsystem-roadmap-contract.md) – Long-term extension seams, per-industry vs core, deprecation policy, roadmap categories.
- [industry-pack-release-gate.md](../release/industry-pack-release-gate.md) – Release gate and sign-off.
- [industry-pack-catalog.md](../appendices/industry-pack-catalog.md) – Built-in pack list; add new pack entry here.
- [industry-pack-extension-contract.md](../contracts/industry-pack-extension-contract.md) – Subsystem boundary and terminology.
- [industry-author-dashboard-contract.md](../contracts/industry-author-dashboard-contract.md) – Internal author dashboard (Prompt 521); single place for health, completeness, release readiness.
