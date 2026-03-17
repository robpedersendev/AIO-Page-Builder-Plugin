# Industry Subtype Benchmark Protocol (Prompt 480)

**Spec**: industry-subtype-extension-contract.md; subtype overlays and registries; recommendation benchmark docs; AI evaluation fixtures; preview and Build Plan docs.

**Purpose**: Internal benchmark to assess whether subtype-aware recommendations, overlays, bundles, cautions, and previews are meaningfully differentiated from parent-industry behavior. No public tool; no live-state mutation.

---

## 1. Scope

- **Launch subtype set**: Subtypes under cosmetology_nail, realtor, plumber, disaster_recovery (e.g. buyer-agent, listing-agent, residential, commercial).
- **Parent as baseline**: Parent-industry behavior is the control; subtype support should be additive and explainable.
- **No mutation**: Benchmark runs do not change profile, overlays, or registries; read-only evaluation.

---

## 2. Comparison dimensions

| Dimension | What is compared | Meaningful differentiation |
|-----------|------------------|----------------------------|
| **Recommendations** | Section/page recommendation output for profile(parent only) vs profile(parent + subtype). | Subtype yields different top-N or ordering where relevant. |
| **Helper overlays** | Section-helper overlay content for parent vs parent+subtype (same section_key). | Subtype overlay adds or overrides content for at least some sections. |
| **Page one-pager overlays** | Page one-pager overlay content for parent vs parent+subtype. | Subtype overlay adds or overrides for at least some page templates. |
| **Starter bundles** | Bundle ref and bundle content for parent vs subtype. | Subtype has distinct starter_bundle_ref or bundle content where applicable. |
| **Build Plan scoring** | Build Plan scoring or proposal output for parent vs subtype context. | Subtype context changes scores or suggested items where relevant. |
| **Caution surfacing** | Compliance/caution rules for parent vs parent+subtype. | Subtype adds or refines caution rules. |

---

## 3. Benchmark harness usage

- **Industry_Subtype_Benchmark_Service::run_benchmark()** (or equivalent) loads active subtypes and compares registry-level and, where available, resolver-level outputs (parent vs subtype).
- Report includes: per-subtype summary (overlay count delta, bundle distinct, caution distinct), overall "meaningful vs weak" differentiation note, and any actionable findings.
- Support launch subtype set first; extensible to future subtypes.

---

## 4. Readable summaries

- **Meaningful differentiation**: Subtype has distinct overlays, bundle ref, or caution rules and/or recommendation/scoring differences in at least one dimension.
- **Weak differentiation**: Subtype is registered but overlay/bundle/caution and recommendation outputs are identical or nearly identical to parent; consider documenting or consolidating.

---

## 5. Integration with release and planning

- Subtype benchmark can inform release gate or maturity: "subtype support is meaningfully distinct" vs "nominal only."
- Second-wave planning (launch-subtype-second-wave-planning-framework.md) can use benchmark results to prioritize or defer new subtypes.

---

## 6. Cross-references

- [industry-subtype-extension-contract.md](../contracts/industry-subtype-extension-contract.md)
- [launch-subtype-second-wave-planning-framework.md](../operations/launch-subtype-second-wave-planning-framework.md)
- [industry-recommendation-benchmark-protocol.md](industry-recommendation-benchmark-protocol.md)
- Industry_Subtype_Benchmark_Service (Reporting)
