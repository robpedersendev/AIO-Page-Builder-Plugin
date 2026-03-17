# Industry Style Preset Benchmark Protocol (Prompt 478)

**Spec**: industry-style-preset-schema.md; industry-style-preset-application-contract.md; styling subsystem contracts (prompts 242–260); release and QA docs.

**Purpose**: Internal benchmark and review protocol for industry style presets so maintainers can evaluate distinctiveness, appropriateness, accessibility, and compatibility before release. No public style galleries; no redesign of the styling subsystem.

---

## 1. Scope

- **Launch-industry presets**: cosmetology_elegant, realtor_warm, plumber_trust, disaster_recovery_urgency (and subtype-relevant variants where applicable).
- **Internal only**: Evaluation is for QA and release gate evidence; not a public tool.
- **Bounded**: No arbitrary CSS; no relaxation of styling-sanitization or token-name contracts.

---

## 2. Evaluation dimensions

| Dimension | What to assess | Output |
|-----------|----------------|--------|
| **Distinctiveness** | Whether presets are visually distinct across industries (no near-duplicate palettes or token sets). | Per-preset flag; pairwise comparison summary. |
| **Readability and token usage** | Contrast, text/muted usage, spacing; token coverage and consistency with --aio-* contract. | Readability notes; token count and key coverage. |
| **Component override fit** | Whether component_override_refs (if any) align with pb-style-components-spec and do not conflict. | Valid refs; invalid or missing refs flagged. |
| **Preview and admin compatibility** | Preset can be applied in preview and admin selection flows without error; no unsafe values. | Compatibility pass/fail; sanitization pass. |
| **Reduced-motion and accessibility** | Where relevant: motion/animation implications; color contrast and fallback behavior. | Accessibility notes; contrast hints. |

---

## 3. Benchmark harness usage

- **Industry_Style_Preset_Benchmark_Service::run_benchmark()** (or equivalent) loads active presets from Industry_Style_Preset_Registry and produces a **benchmark report** (structured array or object).
- Report includes: preset keys evaluated, per-preset summary (distinctiveness, token count, component refs, compatibility, accessibility notes), and any actionable findings.
- No mutation of live styling state; read-only evaluation.

---

## 4. Review criteria (actionable)

- **Distinctiveness**: Each launch preset should be distinguishable (e.g. primary/accent colors not identical). Pairwise similarity above a threshold should be flagged for review.
- **Readability**: Text and muted colors should meet minimum contrast expectations where applicable; spacing tokens should be consistent.
- **Compatibility**: All token keys must match allowed --aio-* names; values must pass sanitization. Invalid or prohibited values must not be normalized.
- **Accessibility**: Document reduced-motion and contrast considerations; do not relax accessibility requirements.

---

## 5. Integration with release gate

- Style preset quality can be a release gate checkpoint: benchmark run, review criteria met, no contract violations.
- Failure: invalid token names, prohibited value patterns, or unresolved component refs must be fixed before treating preset as release-ready.

---

## 6. Cross-references

- [industry-style-preset-schema.md](../schemas/industry-style-preset-schema.md)
- [industry-style-preset-application-contract.md](../contracts/industry-style-preset-application-contract.md)
- Industry_Style_Preset_Benchmark_Service (Reporting)
- Styling subsystem contracts (spec §styling)
