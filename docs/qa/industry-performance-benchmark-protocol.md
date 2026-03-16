# Industry Subsystem Performance Benchmark Protocol (Prompt 451)

**Purpose:** Internal harness to measure representative industry subsystem costs (recommendation resolution, overlay composition, preview assembly, bundle comparison, health report). Results inform optimization; no public dashboard. Site-local and no-industry fallback scenarios are supported where relevant.

---

## 1. Scope

- **Service:** `Industry_Performance_Benchmark_Service` (plugin/src/Domain/Industry/Reporting/Industry_Performance_Benchmark_Service.php).
- **Container key:** `industry_performance_benchmark_service` (when registered in Industry_Packs_Module).
- **Use:** Internal/support only. Run before and after optimization work to compare timings. No sensitive data in benchmark artifacts.

---

## 2. Scenarios

| Scenario key | What is measured |
|--------------|------------------|
| `section_preview_resolution` | Section preview resolver: recommendation + helper-doc composition for one section (e.g. hero_cred_01). |
| `page_preview_resolution` | Page template preview resolver: recommendation + one-pager composition for one template (e.g. pt_home_trust_01). |
| `bundle_comparison` | Starter bundle diff service: compare two bundles (e.g. plumber_starter, plumber_residential_starter). |
| `health_report` | Health check service: full `Industry_Health_Check_Service::run()`. |

Scenarios that depend on container-registered services are skipped when the service is not available (e.g. null container or industry module not loaded).

---

## 3. How to run

1. **From code:** Resolve `industry_performance_benchmark_service` from the container; call `run_benchmark( int $iterations )`. Iterations are capped at 20 per scenario.
2. **From tests:** Instantiate `Industry_Performance_Benchmark_Service` with or without a container; call `run_benchmark( 2 )`; assert result structure and that skipped scenarios have `skipped => true`.
3. **Interpretation:** Each scenario returns `iterations`, `total_ms`, `mean_ms`, `skipped`. Use mean_ms to compare before/after tuning; use skipped to see which operations are available in the current environment.

---

## 4. Result shape (per scenario)

| Field | Description |
|-------|-------------|
| iterations | Number of runs (0 if skipped). |
| total_ms | Total time in milliseconds. |
| mean_ms | total_ms / iterations. |
| skipped | true if the scenario was skipped (missing service or null container). |

---

## 5. Bounded and internal-only

- No user content or secrets in results.
- Production runtime behavior is unchanged by the harness; it only measures existing operations.
- Benchmark results should guide later optimization, not replace it. Do not over-optimize blindly based on a single run.

---

## 6. References

- **Cache contract:** [industry-cache-contract.md](../contracts/industry-cache-contract.md).
- **Diagnostics checklist:** [industry-subsystem-diagnostics-checklist.md](industry-subsystem-diagnostics-checklist.md).
- **Preview/detail tuning:** [industry-preview-performance-tuning-notes.md](industry-preview-performance-tuning-notes.md) — What was tuned for section/page preview and residual hot spots.
- **Recommendation benchmark (quality):** [industry-recommendation-benchmark-protocol.md](industry-recommendation-benchmark-protocol.md) (different purpose: recommendation quality, not performance).
