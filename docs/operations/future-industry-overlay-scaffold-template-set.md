# Future Industry Overlay Scaffold Template Set (Prompt 537)

**Spec:** scaffold-generator contract; future-industry scaffold pack template; overlay and rule contracts.

**Purpose:** Scaffold template set for **future-industry helper overlays, page one-pager overlays, caution rules, SEO guidance, and CTA patterns** so new verticals begin with a coherent documentation/rules skeleton. Scaffold assets are clearly incomplete until authored; no auto-authoring of substantive content; no production-ready marking.

---

## 1. Helper and page overlay scaffold file structure

| Artifact type | Location (per existing registries) | Required shape | Placeholder marker |
|---------------|-----------------------------------|----------------|--------------------|
| **Section helper overlay** | Industry/Docs/SectionHelperOverlays/ (or equivalent) | industry_key, section_key, content_body, status per industry-section-helper-overlay-schema | status inactive or content_body = placeholder (e.g. "Scaffold – incomplete"). Do not add to pack helper_overlay_refs until authored. |
| **Page one-pager overlay** | Industry/Docs/PageOnePagerOverlays/ (or equivalent) | industry_key, page_template_key, content_body, status per industry-page-onepager-overlay-schema | status inactive or content_body placeholder. Not in pack one_pager_overlay_refs until authored. |
| **Subtype section helper** | Docs/SubtypeSectionHelperOverlays/ (or Builtin_Subtype_Section_Helper_Overlays) | subtype_key, section_key, content_body, status per subtype-section-helper-overlay-schema | status inactive or placeholder. |
| **Subtype page one-pager** | Docs/SubtypePageOnePagerOverlays/ (or Builtin_Subtype_Page_OnePager_Overlays) | subtype_key, page_template_key, content_body, status per subtype-page-onepager-overlay-schema | status inactive or placeholder. |
| **Goal section helper** | Per goal section helper overlay registry (conversion-goal-helper-overlay-schema) | goal_key, section_key, content_body, status | status inactive or placeholder; only when conversion goal overlay authoring is planned. |
| **Goal page one-pager** | Per goal page one-pager overlay registry (conversion-goal-page-onepager-overlay-schema) | goal_key, page_template_key, content_body, status | status inactive or placeholder; only when goal overlay authoring is planned. |

**Naming:** Lowercase alphanumeric and underscore; pattern `[a-z0-9_-]+`. One file or definition per (industry_key|subtype_key|goal_key) + (section_key|page_template_key) as per schema.

---

## 2. Caution, SEO, and CTA rule scaffold file structure

| Artifact type | Location | Required shape | Placeholder marker |
|---------------|----------|----------------|--------------------|
| **Industry compliance / caution rules** | Industry/Registry/ComplianceRules/ or equivalent (Industry_Compliance_Rule_Registry) | rule key, industry_key, scope, severity, caution_summary, guidance_text, status per industry-compliance-rule-schema | status = draft or inactive; guidance_text placeholder. |
| **Subtype compliance rules** | Subtype_Compliance_Rule_Registry load path | parent_industry_key, subtype_key, rule key, scope, severity, status | status = draft; content placeholder. |
| **Goal caution rules** | Goal caution rule registry (conversion-goal-caution-rule-schema) | goal_rule_key, goal_key, scope, severity, caution_summary, guidance_text, status | status = draft or inactive; only when goal rules are planned. |
| **SEO guidance** | Industry/Registry/SEOGuidance/ or equivalent | guidance key, definition per industry-seo-guidance-schema | Definition minimal or placeholder; not in pack seo_guidance_ref until authored. |
| **CTA patterns** | Industry/Registry/CTAPatterns/ or equivalent | pattern_key, name, description per industry-cta-pattern contract | name/description placeholder until authored. Pack may reference pattern_key only after CTA definition is authored. |

---

## 3. Optional style and LPagery placeholders

| Artifact type | When to include | Placeholder marker |
|---------------|-----------------|---------------------|
| **Style preset** | When the future industry is planned to have a dedicated preset from day one. | Label placeholder; tokens minimal or default; not referenced by pack until authored. |
| **LPagery rule** | When pack or overlay will reference an LPagery rule. | Definition minimal; not in pack lpagery_rule_ref until authored. |

Omit if the future industry will use a shared or parent preset/rule initially.

---

## 4. Placeholder and incomplete-state markers

- **Overlays:** `status` = inactive or equivalent; `content_body` may be "Scaffold – incomplete. Author per industry-pack-authoring-guide." Do **not** add overlay refs to pack or subtype until content is ready.
- **Rules (caution, SEO, CTA):** `status` = `draft` or inactive; summary/guidance text placeholder. Do not reference from pack until authored.
- **Metadata:** Optional `scaffold_generated_at`, `scaffold_version` for tooling; strip or ignore in production.
- **Docs/QA:** Every overlay/rule scaffold set MUST include a clear marker (doc or comment): "Overlay/rule scaffold – incomplete. Do not treat as release-ready. Author and validate per industry-pack-authoring-guide and scaffold contract before activating."

---

## 5. Minimum docs and QA placeholders

| Artifact | Content |
|----------|---------|
| **Overlay/rule scaffold README or doc** | Short statement: overlay and rule scaffolds for industry `{industry_key}` (and subtype/goal if applicable); not production-ready; author per industry-pack-authoring-guide; run definition linter and release gate before activation. |
| **QA placeholder** | Reference to industry-definition-linting-guide, industry-pre-release-validation-pipeline, industry-pack-release-gate. Link or list. |
| **Release placeholder** | Note that overlay/rule assets must not be included in release-ready flows until status is active and refs resolve. |

---

## 6. Alignment with overlay and rule architecture

- **One-plugin overlay architecture:** Same schema and load paths as production; scaffold files sit in the same Docs/ and Registry/ paths. Registries load them but exclude inactive/draft from composition.
- **Composition order:** Base → industry → subtype → goal (when applicable). Scaffold placeholders do not change composition; they are simply skipped or not referenced until authored.
- **Rule contracts:** industry-compliance-rule-contract, subtype-compliance-rule-contract, conversion-goal-caution-rule-contract, industry-cta-pattern, SEO guidance schema. Scaffold satisfies required fields with placeholders; refs from pack/subtype added only after authoring.
- **No hidden activation:** Scaffold overlay/rule refs must not be added to pack or subtype definition until content is ready and status is active.

---

## 7. Promotion from scaffold to authored

1. **Start from this template set:** Create overlay and rule file skeletons per §1–§5 (helper, page one-pager, caution, SEO, CTA; optional style/LPagery).
2. **Author content:** Replace placeholder content_body, guidance_text, name, description with real content; ensure keys and refs match schema.
3. **Validate:** Run schema validation and Industry_Definition_Linter; fix ref and schema errors. Health check must pass for pack refs to overlays and rules.
4. **Set status:** Set overlay/rule status to active only when content is complete and reviewed.
5. **Wire pack/subtype/goal:** Add overlay refs to pack helper_overlay_refs, one_pager_overlay_refs; add rule refs (compliance_rule_refs, seo_guidance_ref, CTA refs) only when definitions are ready.
6. **Release:** Follow industry-pack-release-gate; scaffold exclusion applies until all above steps are done.

---

## 8. Cross-references

- [future-industry-scaffold-pack-template.md](future-industry-scaffold-pack-template.md) — Full industry scaffold; overlays and rules are artifact classes within it.
- [industry-scaffold-generator-contract.md](../contracts/industry-scaffold-generator-contract.md) — Scaffold scope, validation, promotion path.
- [scaffold-incomplete-state-guardrail-contract.md](../contracts/scaffold-incomplete-state-guardrail-contract.md) — Incomplete state, release exclusion.
- [industry-pack-authoring-guide.md](industry-pack-authoring-guide.md) — Authoring order.
- Overlay contracts: industry-section-helper-overlay-schema, industry-page-onepager-overlay-schema, subtype overlays, conversion-goal-helper-overlay, conversion-goal-page-onepager-overlay.
- Rule contracts: industry-compliance-rule-contract, conversion-goal-caution-rule-contract, industry-cta-pattern, SEO guidance schema.
