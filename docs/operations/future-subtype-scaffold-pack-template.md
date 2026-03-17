# Future Subtype Scaffold Pack Template (Prompt 517)

**Spec:** industry-scaffold-generator-contract.md; industry-subtype-extension-contract.md; launch-subtype-second-wave-planning-framework.md.  
**Purpose:** Concrete file and artifact skeleton for a **future** subtype definition pack. New subtype expansions start from this consistent, clearly incomplete structure. No seeding of new subtypes; no auto-authoring of substantive content; scaffold assets are not production-ready until authored and reviewed.

---

## 1. Required artifact classes in a subtype scaffold

| Artifact class | Purpose | Required in scaffold |
|----------------|---------|----------------------|
| **Subtype definition** | Subtype registry entry (subtype_key, parent_industry_key, label, summary, status, version_marker; optional refs). | Yes; one subtype definition per future subtype. |
| **Overlay placeholders** | Subtype section-helper and/or page one-pager overlays (subtype_key, section_key or page_template_key, content_body, status). | Placeholder only; empty or minimal; not in subtype refs until authored. |
| **Bundle placeholder(s)** | Starter bundle(s) for this subtype (bundle_key, industry_key, subtype_key, label, status). | Yes if subtype is expected to have a distinct starter bundle; status = draft. |
| **Caution placeholders** | Caution/compliance rule refs for subtype (when applicable). | Placeholder only when subtype will reference caution rules. |
| **Style/SEO/CTA/LPagery placeholders** | Optional refs to style preset, SEO guidance, CTA posture, LPagery rules when subtype overrides them. | Placeholder only when subtype definition references them; minimal definition. |
| **Docs placeholder** | Minimum: README or doc stating scaffold status and authoring steps. | Yes; see §5. |
| **QA/release placeholders** | Minimum: note or checklist placeholder for pre-release validation and release gate. | Yes; see §5. |

Parent industry pack remains the base layer; subtype scaffold does not create or modify the parent pack. All refs (parent_industry_key, starter_bundle_ref, overlay refs, caution_rule_refs) must resolve to existing registries or be left empty until authored.

---

## 2. File naming and placement rules

- **Naming:** Lowercase alphanumeric and underscore; pattern `[a-z0-9_-]+`. Align with industry-scaffold-generator-contract §3 and industry-contract-consistency-audit.
- **Placement:** All scaffolded files live in the **same directories** used by production registries and loaders.
  - Subtype definition: `Industry/Registry/Subtypes/*.php` (load path used by Industry_Subtype_Registry).
  - Subtype section helper overlays: `Industry/Docs/SubtypeSectionHelperOverlays/*.php` (via Builtin_Subtype_Section_Helper_Overlays).
  - Subtype page one-pager overlays: `Industry/Docs/SubtypePageOnePagerOverlays/*.php` (via Builtin_Subtype_Page_OnePager_Overlays).
  - Starter bundles (subtype-specific): `Industry/Registry/StarterBundles/Subtypes/*.php` or equivalent; bundle definition includes subtype_key.
  - CTA/SEO/LPagery/style: same as industry scaffold (Registry/CTAPatterns, Registry/SEOGuidance, LPagery/Rules, Registry/StylePresets) when subtype refs them.
- **Load order:** Scaffolded assets are discovered by the same registries as authored assets. They remain **inactive** or **draft** until status is set to active and content is authored.

---

## 3. Placeholder and incomplete-state markers

- **Subtype definition:** `status` = `draft` (Industry_Subtype_Registry / schema STATUS_DRAFT). Label/summary may be placeholder (e.g. "TODO Subtype Label"). Do not add the subtype to any default or auto-selection path. Optional refs (starter_bundle_ref, helper_overlay_refs, one_pager_overlay_refs, caution_rule_refs) empty or minimal until authored.
- **Subtype section helper overlay:** `status` inactive or content_body placeholder. Do **not** add to subtype `helper_overlay_refs` until content is ready.
- **Subtype page one-pager overlay:** `status` inactive or content_body placeholder. Do **not** add to subtype `one_pager_overlay_refs` until content is ready.
- **Starter bundle (subtype):** `status` = `draft`; recommended_* refs empty or minimal; subtype_key set to this scaffold’s subtype_key.
- **Caution/CTA/SEO/LPagery:** Placeholder definitions minimal; not referenced by subtype until authored.
- **Docs/QA:** Every subtype scaffold MUST include a clear marker stating: "Scaffold – incomplete. Do not treat as release-ready. Author and validate per industry-pack-authoring-guide and subtype extension contract before activating."

---

## 4. Minimum docs and QA artifacts in the scaffold

| Artifact | Content |
|----------|---------|
| **Scaffold README or doc** | Short statement: this is a scaffold for future subtype `{subtype_key}` under parent `{parent_industry_key}`; not production-ready; author per industry-pack-authoring-guide and subtype extension contract; run definition linter and release gate before activation. |
| **QA placeholder** | Reference or stub for: industry-definition-linting-guide; industry-pre-release-validation-pipeline; industry-pack-release-gate. Link or list; no need to duplicate full checklists. |
| **Release placeholder** | Note that the subtype must not be included in release-ready candidate flows until status is active and release gate criteria are met. |

No substantive authoring content (overlay copy, CTA copy, caution text) is required in the scaffold; placeholders only.

---

## 5. Alignment with actual subtype extension architecture

- **Parent industries remain the base layer:** Subtype scaffold does not create or modify the parent industry pack. parent_industry_key must reference an existing, active industry pack.
- **Registry-first:** Scaffolded files are in registry load paths; Industry_Subtype_Registry, overlay registries, and bundle registry load them. No "scaffold-only" shadow registry.
- **One-plugin overlay architecture:** Subtype overlays (section helper, page one-pager) follow the same schema and composition order as production (base → industry → subtype).
- **Validation:** Scaffolded subtype definition must pass **structural** validation (required fields, types, version_marker, parent_industry_key format). It may fail **ref resolution** until dependencies are added; linting and health check will report these until authors fix them.
- **No hidden activation:** Scaffold output must not be auto-activated (e.g. no code that sets industry_subtype_key to a scaffold subtype on first run). Activation is always explicit (admin or author).

---

## 6. How a subtype scaffold becomes authored and reviewable

1. **Start from this template:** Create the file/artifact skeleton per §1–§4 (subtype definition, overlay placeholders, bundle placeholders, caution/CTA/SEO/LPagery placeholders as needed, docs/QA placeholders).
2. **Confirm parent industry:** Ensure parent_industry_key exists and is active; subtype is justified per launch-subtype-second-wave-planning-framework (differentiation, no new core seams).
3. **Author content:** Replace placeholder label/summary/content_body with real content; add or fix refs (starter_bundle_ref, overlay refs, caution_rule_refs). Follow industry-pack-authoring-guide and subtype extension contract.
4. **Validate:** Run subtype schema validation and Industry_Definition_Linter; fix errors.
5. **Resolve refs:** Ensure all refs resolve; parent_industry_key matches an active pack; health check passes for the subtype and profile when selected.
6. **Set status:** Change status from `draft` to `active` only when content and refs are complete and reviewed.
7. **Release:** Follow industry-pack-release-gate and pre-release validation pipeline; scaffold contract does not replace release or pre-release validation.

Reviewability: any reviewer can confirm that (a) the scaffold matches this template’s artifact classes and placement, (b) incomplete-state markers are present and clear, and (c) the promotion path and parent-industry dependency are documented.

---

## 7. Cross-references

- [industry-scaffold-generator-contract.md](../contracts/industry-scaffold-generator-contract.md) — Scaffold scope (including subtype artifact types), validation expectations, promotion path, safety.
- [scaffold-incomplete-state-guardrail-contract.md](../contracts/scaffold-incomplete-state-guardrail-contract.md) — Incomplete-state validation, release-gate exclusion, how to clear incomplete state through authoring (Prompt 518).
- [industry-subtype-extension-contract.md](../contracts/industry-subtype-extension-contract.md) — Subtype object model, override scope, fallback, registry.
- [launch-subtype-second-wave-planning-framework.md](launch-subtype-second-wave-planning-framework.md) — Admission criteria and prioritization for new subtypes; use before creating a subtype scaffold.
- [industry-pack-authoring-guide.md](industry-pack-authoring-guide.md) — Required pieces and implementation order; subtypes optional step.
- [future-industry-scaffold-pack-template.md](future-industry-scaffold-pack-template.md) — Industry-level scaffold template; subtype scaffold extends the same discipline for subtype-specific artifacts.
- [industry-pack-completeness-scoring-contract.md](../contracts/industry-pack-completeness-scoring-contract.md) — Advisory completeness scoring for subtype artifact sets (Prompt 519).
