# Industry Scaffold Completeness Report Contract (Prompt 538)

**Spec:** Scaffold templates and guardrail docs; completeness scoring docs; authoring and release docs.

**Status:** Contract. Defines an **advisory** report that evaluates scaffold packs and subtype scaffolds for completeness relative to required artifact classes and authored-content expectations. Report is internal-only and bounded; it does not treat scaffold completeness as release readiness or auto-promote scaffolds.

---

## 1. Purpose

- **Assess scaffold progress:** Authors and maintainers can run a report that shows, per scaffold (future-industry or future-subtype), which required artifact classes are present as **scaffolded** (placeholder/draft) vs **authored** (active, non-placeholder) vs **missing** (not yet scaffolded).
- **Distinguish missing scaffolding from missing authored content:** "Missing scaffolding" = required artifact class has no file or definition (e.g. no starter bundle file for the industry). "Scaffolded" = artifact exists but is draft or placeholder. "Authored" = artifact exists and is active with real content.
- **Advisory only:** Report supports authoring workflows and author dashboard; it does not replace release review, release gate, or pre-release validation. No scaffold activation or auto-promotion.

---

## 2. Artifact classes evaluated

| Artifact class | Required in scaffold (per template) | States reported |
|----------------|-------------------------------------|------------------|
| **Pack definition** | Yes (future-industry or subtype) | missing \| scaffolded \| authored |
| **Starter bundle(s)** | Yes; at least one per industry (or subtype) | missing \| scaffolded \| authored |
| **Section helper overlay** | Placeholder optional | missing \| scaffolded \| authored |
| **Page one-pager overlay** | Placeholder optional | missing \| scaffolded \| authored |
| **Rule placeholders** | CTA, SEO, compliance per pack refs | missing \| scaffolded \| authored |
| **Docs placeholder** | Yes (README or doc) | missing \| scaffolded \| authored |
| **QA placeholder** | Yes (reference or stub) | missing \| scaffolded \| authored |

Subtype scaffold adds: subtype definition, subtype overlays, subtype bundle(s). Same states apply.

---

## 3. State definitions

- **missing:** No definition or file for this artifact class in the registry/load path for this scaffold (e.g. no bundle definition for the industry_key).
- **scaffolded:** Definition or file exists but status is draft or content is placeholder (e.g. bundle with status = draft, or overlay with content_body = "Scaffold – incomplete").
- **authored:** Definition or file exists, status is active (or equivalent), and content is non-placeholder. Ready for release consideration (subject to release gate).

---

## 4. Report structure

- **Input:** Optional list of scaffold identifiers (industry_key for future-industry scaffolds; subtype_key for future-subtype scaffolds). If omitted, report may discover draft packs and draft subtypes from registries.
- **Output:** Bounded structure:
  - `generated_at`: ISO 8601 timestamp.
  - `scaffold_results`: List of per-scaffold results:
    - `scaffold_type`: `industry` | `subtype`.
    - `scaffold_key`: industry_key or subtype_key.
    - `artifact_classes`: Map of artifact class key → `missing` | `scaffolded` | `authored`.
    - `summary`: Short human-readable summary (e.g. "3 scaffolded, 2 missing, 0 authored").
  - `readable_summary`: List of one-line summaries for dashboard or log.
  - `warnings`: List of non-fatal warnings (e.g. "Registry not available for overlay count").

---

## 5. Integration

- **Author dashboard:** Industry_Author_Dashboard_Screen (or equivalent) may call Industry_Scaffold_Completeness_Report_Service::generate_report() and display scaffold progress (e.g. "Scaffold X: 2 missing, 5 scaffolded"). Does not replace pack completeness report for active packs.
- **Maintenance reports:** Maintenance or authoring reports may include a "scaffold completeness" section when scaffold sets are in use.
- **No activation:** Report is read-only. It must not activate scaffold assets, set status to active, or add scaffold keys to release-ready lists.

---

## 6. Safety and constraints

- **Internal-only:** Report and service are for internal author/maintainer use. No public API or end-user-facing report.
- **Bounded:** Only artifact classes defined in scaffold templates and this contract are evaluated. No unbounded expansion.
- **Advisory:** Scaffold completeness does not imply release readiness. Release gate and pre-release validation remain the authority for release eligibility.
- **No auto-promotion:** No code path may use this report to automatically promote a scaffold to authored or release-ready.

---

## 7. Cross-references

- [future-industry-scaffold-pack-template.md](../operations/future-industry-scaffold-pack-template.md) — Required artifact classes for industry scaffold.
- [future-subtype-scaffold-pack-template.md](../operations/future-subtype-scaffold-pack-template.md) — Required artifact classes for subtype scaffold.
- [future-industry-starter-bundle-scaffold-template.md](../operations/future-industry-starter-bundle-scaffold-template.md) — Bundle scaffold structure.
- [future-industry-overlay-scaffold-template-set.md](../operations/future-industry-overlay-scaffold-template-set.md) — Overlay/rule scaffold structure.
- [scaffold-incomplete-state-guardrail-contract.md](scaffold-incomplete-state-guardrail-contract.md) — Incomplete state; release exclusion.
- [industry-pack-completeness-scoring-contract.md](industry-pack-completeness-scoring-contract.md) — Pack completeness (authored packs); distinct from scaffold completeness.
