# Future Industry Starter Bundle Scaffold Template (Prompt 536)

**Spec:** scaffold-generator contract; future-industry scaffold pack template; starter bundle contracts.

**Purpose:** Reusable scaffold structure for **future-industry starter bundle** authoring so new verticals start from a consistent bundle artifact set aligned with the rest of the subsystem. Scaffold bundles are clearly incomplete until authored; no auto-authoring of substantive content; no production-ready marking.

---

## 1. Starter bundle scaffold file structure

- **Placement:** Same paths used by Industry_Starter_Bundle_Registry (e.g. `Industry/Registry/StarterBundles/` or equivalent load path). No separate "scaffold" namespace.
- **Naming:** Lowercase alphanumeric and underscore; `[a-z0-9_-]+`. Bundle key pattern per industry-starter-bundle-schema.md.
- **Layering:**
  - **Parent bundle(s):** At least one industry-scoped bundle file (industry_key set; subtype_key empty) per future industry.
  - **Subtype overlay hook:** Optional directory or file convention for subtype-scoped bundles (e.g. `StarterBundles/Subtypes/` or bundle definitions with `subtype_key` set when subtype scaffold exists).
  - **Goal overlay hook:** Bundle definitions do not embed goal directly; goal overlays apply at Build Plan / conversion layer (conversion-goal-starter-bundle-contract). Scaffold may include a **docs placeholder** noting which conversion goals the bundle is intended to support once authored (e.g. "Target goals: bookings, lead_capture – to be reflected in goal overlay pack when authored").
- **Minimum files:** One parent bundle definition file per industry (e.g. `{industry_key}_starter.php` or single definitions array including `{industry_key}_starter`).

---

## 2. Placeholder and incomplete-state markers

| Element | Marker / rule |
|---------|----------------|
| **status** | `draft` (Industry_Starter_Bundle_Registry / schema). Only `active` bundles are offered; scaffold must not be active. |
| **label / summary** | Placeholder text allowed (e.g. "TODO Bundle Label", "Scaffold – incomplete. Author per starter bundle authoring guide."). |
| **recommended_*** | Empty or minimal; refs may be placeholders or empty arrays until authored. |
| **subtype_key** | Omit for parent bundle; set only when subtype scaffold exists and bundle is for that subtype. |
| **Metadata** | Optional `scaffold_generated_at`, `scaffold_version` for tooling; strip or ignore for production. |
| **Docs/QA** | Bundle scaffold set MUST include a doc or comment stating: "Starter bundle scaffold – incomplete. Do not treat as release-ready. Author and validate per industry-pack-authoring-guide and scaffold contract before activating." |

---

## 3. Parent, subtype, and goal overlay hook points

- **Parent:** One or more bundle definitions with `industry_key` = future industry key; `subtype_key` omitted or empty. Pack `starter_bundle_ref` (in pack scaffold) points to one of these bundle keys once authored.
- **Subtype:** When the future industry has a subtype scaffold, add subtype-scoped bundle definitions (same `industry_key`, `subtype_key` set) in the same or designated subtype-bundle location. Subtype bundle ref is referenced from subtype definition when authored (subtype-starter-bundle-contract).
- **Goal:** Goal overlays (conversion-goal-starter-bundle-contract) are applied at bundle-to–Build Plan conversion and recommendation layer, not inside the bundle file. Scaffold includes a **placeholder note** (in docs or bundle metadata) for "intended conversion goals" so authors know which goal overlays to add later. No goal keys inside the bundle schema; goal layering is additive in the subsystem.

---

## 4. Minimum docs and QA artifacts

| Artifact | Content |
|----------|---------|
| **Bundle scaffold README or doc** | Short statement: starter bundle scaffold for industry `{industry_key}`; not production-ready; author per industry-pack-authoring-guide and starter bundle schema; run definition linter and release gate before activation. |
| **QA placeholder** | Reference to industry-definition-linting-guide, industry-pre-release-validation-pipeline, industry-pack-release-gate. Link or list; no duplicate checklists. |
| **Release placeholder** | Note that the bundle must not be included in release-ready candidate flows until status is active and refs resolve. |
| **Goal intent (optional)** | If known, list intended conversion goals (calls, bookings, etc.) so goal overlay authoring can be planned; no schema change. |

---

## 5. Alignment with bundle architecture

- **Schema:** industry-starter-bundle-schema.md (bundle_key, industry_key, label, summary, status, version_marker; optional subtype_key, recommended_*, refs). Scaffold satisfies required fields with placeholders; optional refs empty or placeholder.
- **Registry:** Same Industry_Starter_Bundle_Registry load path; scaffold bundles are loaded but excluded from "offered" sets until status = active.
- **Pack ref:** Pack scaffold references bundle via `starter_bundle_ref` only after bundle is authored and active; scaffold pack keeps ref empty or placeholder.
- **Subtype:** subtype-starter-bundle-contract; get_for_industry(industry_key, subtype_key) returns subtype-scoped bundles when present; scaffold may add subtype bundle file when subtype scaffold exists.
- **Goal:** conversion-goal-starter-bundle-contract and Conversion_Goal_Starter_Bundle_To_Build_Plan_Service; goal context is applied when converting bundle to Build Plan, not stored in bundle file. Scaffold stays aligned; goal overlay authoring is separate step.

---

## 6. Promotion from scaffold to authored

1. **Start from this template:** Create bundle file(s) per §1–§4 (parent bundle; optional subtype bundle; docs/QA placeholders).
2. **Author content:** Replace placeholder label/summary; fill recommended_page_families, recommended_page_template_refs, recommended_section_refs, and refs (token_preset_ref, cta_guidance_ref, lpagery_guidance_ref) with resolvable keys. Follow industry-pack-authoring-guide and starter bundle schema.
3. **Validate:** Run schema validation and Industry_Definition_Linter; fix ref and schema errors. Health check must pass for bundle refs.
4. **Set status:** Change bundle status from `draft` to `active` only when content and refs are complete and reviewed.
5. **Wire pack:** Set pack `starter_bundle_ref` (and subtype `starter_bundle_ref` if applicable) to this bundle key. Ensure pack/subtype are also authored and active.
6. **Release:** Follow industry-pack-release-gate; scaffold exclusion applies until all above steps are done.

---

## 7. Cross-references

- [industry-starter-bundle-schema.md](../schemas/industry-starter-bundle-schema.md) — Bundle object shape and validation.
- [future-industry-scaffold-pack-template.md](future-industry-scaffold-pack-template.md) — Full industry scaffold; starter bundles are one artifact class within it.
- [scaffold-incomplete-state-guardrail-contract.md](../contracts/scaffold-incomplete-state-guardrail-contract.md) — Incomplete state; release exclusion; authoring path.
- [industry-pack-authoring-guide.md](industry-pack-authoring-guide.md) — Authoring order and checklist.
- [subtype-starter-bundle-contract.md](../contracts/subtype-starter-bundle-contract.md) — Subtype-scoped bundles.
- [conversion-goal-starter-bundle-contract.md](../contracts/conversion-goal-starter-bundle-contract.md) — Goal layer at bundle-to–Build Plan conversion.
