# Industry Coverage Gap Analysis Guide (Prompt 439)

**Spec**: authoring and maintenance docs; overlay coverage docs; recommendation and bundle contracts; health-report docs.  
**Purpose**: Internal analyzer that identifies where an industry or subtype lacks sufficient metadata, overlays, bundle coverage, or caution rules so authors can prioritize high-impact gaps systematically.

---

## 1. Scope

- **Tool**: `Industry_Coverage_Gap_Analyzer` (plugin/src/Domain/Industry/Reporting/Industry_Coverage_Gap_Analyzer.php). Internal-only; advisory; no auto-generation; no public reports.
- **Input**: Loaded registries (pack, section overlay, page overlay, starter bundle, style preset, compliance rules, SEO guidance, question pack, subtype). Analyzes active packs and optionally each subtype scope.
- **Output**: List of gaps (scope, missing_artifact_class, priority, explanation) and by_scope grouping for actionable reports.

---

## 2. Gap artifact classes

| Class | Description | Typical priority |
|-------|-------------|------------------|
| section_helper_overlays | No section helper overlays for this industry. | medium |
| page_onepager_overlays | No page one-pager overlays for this industry. | medium |
| starter_bundle | No starter bundle for this industry (or industry+subtype). | high |
| style_preset | Pack has no token_preset_ref or ref does not resolve. | low / medium |
| compliance_rules | No compliance/caution rules for this industry. | low |
| seo_guidance | Pack has no seo_guidance_ref or ref does not resolve. | low / medium |
| question_pack | No question pack for this industry. | low |

---

## 3. Priorities

- **high**: Gaps that materially affect onboarding or recommendation (e.g. no starter bundle).
- **medium**: Gaps that affect content quality or UX (overlays, unresolved preset/SEO refs).
- **low**: Advisory gaps (no compliance rules, no question pack, missing optional refs).

Priorities are bounded and explainable; author judgment still determines what to fix first.

---

## 4. Scope format

- **Industry-only**: `scope` = industry_key (e.g. `realtor`).
- **Subtype**: `scope` = `industry_key|subtype_key` (e.g. `realtor|buyer_agent`). Subtype coverage uses the same industry-level overlay and rule counts; starter bundle is checked for get_for_industry(industry_key, subtype_key).

---

## 5. How to run

- **Runtime**: Instantiate the analyzer with the same registries used for health/linting (pack, section overlay, page overlay, starter bundle, preset, compliance, SEO, question pack, subtype). Call `analyze(true)` to include subtype scopes. Use `gaps` for a flat list or `by_scope` for grouped output.
- **Use case**: Run after registries are loaded. Use output to prioritize backlog (e.g. add starter bundle for industry X, add section overlays for industry Y). Does not replace author judgment.

---

## 6. Integration

- **Release gate**: Coverage-gap analysis can be part of pre-release validation; see [industry-pre-release-validation-pipeline.md](../release/industry-pre-release-validation-pipeline.md).
- **Maintenance**: Use with [industry-pack-maintenance-checklist.md](industry-pack-maintenance-checklist.md) and coverage matrix appendices (helper-overlay-coverage-matrix, page-overlay-coverage-matrix) to plan expansion.

---

## 7. Do not

- Auto-generate missing artifacts from this tool.
- Expose coverage reports on a public surface.
- Use as the only source of truth; registries and runtime behavior remain authoritative.
