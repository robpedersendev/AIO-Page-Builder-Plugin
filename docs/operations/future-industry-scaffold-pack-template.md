# Future Industry Scaffold Pack Template (Prompt 516)

**Spec:** industry-scaffold-generator-contract.md; industry-pack-authoring-guide.md; future-industry-candidate-evaluation-framework.md.  
**Purpose:** Concrete file and artifact skeleton for a **future** industry definition pack. New industries start from this consistent, clearly incomplete structure. No GUI generator; no auto-fill of substantive content; scaffold assets are not production-ready until authored and reviewed.

---

## 1. Required artifact classes in an industry scaffold

| Artifact class | Purpose | Required in scaffold |
|----------------|---------|----------------------|
| **Pack definition** | Industry pack registry entry (industry_key, name, summary, status, version_marker; refs). | Yes; one pack file per future industry. |
| **Starter bundle(s)** | At least one starter bundle placeholder for the industry (bundle_key, industry_key, label, status). | Yes; one or more bundle placeholders. See [future-industry-starter-bundle-scaffold-template.md](future-industry-starter-bundle-scaffold-template.md) for bundle-specific scaffold structure, parent/subtype/goal hook points, and promotion path. |
| **Section helper overlay placeholders** | Optional; placeholder files for section-helper overlays (industry_key, section_key, content_body, status). | Placeholder only; empty or minimal. See [future-industry-overlay-scaffold-template-set.md](future-industry-overlay-scaffold-template-set.md) for overlay and rule scaffold structure. |
| **Page one-pager overlay placeholders** | Optional; placeholder files for page one-pager overlays (industry_key, page_template_key, content_body, status). | Placeholder only; empty or minimal. |
| **CTA pattern placeholders** | Optional; CTA pattern keys referenced by pack must exist; placeholders allowed. | If pack references CTAs; minimal definition. |
| **Rule placeholders** | LPagery rules, SEO guidance, compliance rules per schema; optional. | Placeholder only when pack refs them. |
| **Docs placeholders** | Minimum: README or doc file stating scaffold status and authoring steps. | Yes; see §5. |
| **QA/release placeholders** | Minimum: note or checklist placeholder for pre-release validation and release gate. | Yes; see §5. |

Subtype definitions, subtype overlays, style presets: include only if the future industry is planned to have subtypes or a dedicated preset from day one; otherwise omit or add in a second scaffold pass.

---

## 2. File naming and placement rules

- **Naming:** Lowercase alphanumeric and underscore; pattern `[a-z0-9_-]+`. Align with [industry-contract-consistency-audit.md](../contracts/industry-contract-consistency-audit.md) §5 and industry-scaffold-generator-contract §3.
- **Placement:** All scaffolded files live in the **same directories** used by production registries and loaders. No separate "scaffold" directory that could be mistaken for a different namespace.
  - Pack: `Industry/Registry/Packs/` (or equivalent load path used by Industry_Pack_Registry).
  - Starter bundles: `Industry/Registry/StarterBundles/*.php` (and optionally `StarterBundles/Subtypes/*.php`).
  - Section helper overlays: `Industry/Docs/SectionHelperOverlays/*.php`.
  - Page one-pager overlays: `Industry/Docs/PageOnePagerOverlays/*.php`.
  - CTA patterns: `Industry/Registry/CTAPatterns/*.php`.
  - LPagery rules: `Industry/LPagery/Rules/*.php`.
  - SEO guidance: `Industry/Registry/SEOGuidance/*.php`.
  - Style presets: `Industry/Registry/StylePresets/` or equivalent.
- **Load order:** Scaffolded assets are discovered by the same registries as authored assets. They remain **inactive** until status is set to active and content is authored (registries filter by status).

---

## 3. Placeholder and incomplete-state markers

- **Pack:** `status` = `draft` (Industry_Pack_Schema::STATUS_DRAFT). Name/summary may be placeholder (e.g. "TODO Industry Name", "Future industry – not yet authored"). Do not add the pack to the builtin pack list used for default activation.
- **Starter bundle:** `status` = `draft`. `recommended_*` refs empty or minimal. Label may be "TODO Bundle Label".
- **Overlays (section helper, page one-pager):** `status` inactive or content_body placeholder. Do **not** add scaffold overlay refs to pack `helper_overlay_refs` / `one_pager_overlay_refs` until content is ready.
- **Subtype definition (if present):** `status` = `draft`.
- **Metadata:** Optional `scaffold_generated_at`, `scaffold_version` for tooling; must not affect runtime resolution or export. Strip or ignore in production validation.
- **Docs/QA:** Every scaffold pack MUST include a clear marker (e.g. a doc file or top-level comment) stating: "Scaffold – incomplete. Do not treat as release-ready. Author and validate per industry-pack-authoring-guide and scaffold-generator contract before activating."

---

## 4. Minimum docs and QA artifacts in the scaffold

| Artifact | Content |
|----------|---------|
| **Scaffold README or doc** | Short statement: this is a scaffold for future industry `{industry_key}`; not production-ready; author per industry-pack-authoring-guide; run definition linter and release gate before activation. |
| **QA placeholder** | Reference or stub for: industry-definition-linting-guide; industry-pre-release-validation-pipeline; industry-pack-release-gate. No need to duplicate full checklists; link or list them. |
| **Release placeholder** | Note that the pack must not be included in release-ready candidate flows until status is active and release gate criteria are met. |

No substantive authoring content (overlay copy, CTA copy, SEO text) is required in the scaffold; placeholders only.

---

## 5. Alignment with actual subsystem architecture

- **Registry-first:** Scaffolded files are in registry load paths; the same Industry_Pack_Registry, Industry_Starter_Bundle_Registry, overlay registries, and rule registries load them. There is no "scaffold-only" shadow registry.
- **One-plugin overlay architecture:** Overlays (section helper, page one-pager) follow the same schema and loading as production; scaffold places files in the same Docs/ overlay paths.
- **Validation:** Scaffolded artifacts must pass **structural** validation (required fields, types, version_marker). They may fail **ref resolution** until dependencies are added; linting and health check will report these until authors fix them.
- **No hidden activation:** Scaffold output must not be auto-activated (e.g. no code that sets primary_industry_key to a scaffold pack key on first run). Activation is always explicit (admin or author).

---

## 6. How a scaffold becomes authored and reviewable

1. **Start from this template:** Create the file/artifact skeleton per §1–§4 (pack, bundle placeholders, overlay placeholders, rule placeholders, docs/QA placeholders).
2. **Follow the first-pack runbook:** For a disciplined end-to-end workflow from scaffold through validation and release readiness, use [future-industry-first-pack-authoring-runbook.md](future-industry-first-pack-authoring-runbook.md) (Prompt 539).
3. **Author content:** Replace placeholder name/summary/content_body with real content; add or fix refs (CTA, SEO, preset, overlays, bundle). Follow industry-pack-authoring-guide implementation order.
4. **Validate:** Run Industry_Pack_Schema::validate_pack() (or equivalent per artifact); run Industry_Definition_Linter; fix errors.
5. **Resolve refs:** Ensure all refs resolve; health check passes for the pack and profile.
6. **Set status:** Change status from `draft` to `active` only when content and refs are complete and reviewed.
7. **Register:** Ensure pack is in builtin definitions (or load path); add overlay refs to pack only when overlays are ready.
8. **Release:** Follow industry-pack-release-gate and pre-release validation pipeline; scaffold contract does not replace release or pre-release validation. Use [future-industry-first-pack-authoring-runbook.md](future-industry-first-pack-authoring-runbook.md) for the full sequence and signoff checkpoints.

Reviewability: any reviewer can confirm that (a) the scaffold matches this template’s artifact classes and placement, (b) incomplete-state markers are present and clear, and (c) the promotion path is documented.

---

## 7. Cross-references

- [future-industry-first-pack-authoring-runbook.md](future-industry-first-pack-authoring-runbook.md) — End-to-end first-pack authoring runbook (Prompt 539).
- [future-industry-starter-bundle-scaffold-template.md](future-industry-starter-bundle-scaffold-template.md) — Starter bundle scaffold structure and hook points (Prompt 536).
- [future-industry-overlay-scaffold-template-set.md](future-industry-overlay-scaffold-template-set.md) — Helper/page overlay and caution/SEO/CTA rule scaffold templates (Prompt 537).
- [industry-scaffold-generator-contract.md](../contracts/industry-scaffold-generator-contract.md) — Scaffold scope, validation expectations, promotion path, safety.
- [scaffold-incomplete-state-guardrail-contract.md](../contracts/scaffold-incomplete-state-guardrail-contract.md) — Incomplete-state validation, release-gate exclusion, how to clear incomplete state through authoring (Prompt 518).
- [industry-pack-authoring-guide.md](industry-pack-authoring-guide.md) — Required pieces and implementation order once authoring starts.
- [future-industry-candidate-evaluation-framework.md](future-industry-candidate-evaluation-framework.md) — Evaluate candidate before creating scaffold; use scorecard and dossier.
- [industry-pack-release-gate.md](../release/industry-pack-release-gate.md) — Release criteria; scaffold assets are excluded until authored and gate-passed.
- [industry-pack-completeness-scoring-contract.md](../contracts/industry-pack-completeness-scoring-contract.md) — Advisory completeness scoring; use to assess progress from scaffold (low score) to release-grade (Prompt 519).
- [industry-scaffold-completeness-report-contract.md](../contracts/industry-scaffold-completeness-report-contract.md) — Scaffold-specific report: missing vs scaffolded vs authored per artifact class (Prompt 538).
