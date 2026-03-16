# Industry Recommendation Benchmark Protocol (Prompt 392)

**Purpose:** Repeatable, internal process to evaluate industry recommendation quality and surface metadata coverage gaps. Informs future metadata and overlay improvements.

---

## 1. Scope

- **Launch industries:** cosmetology_nail, realtor, plumber, disaster_recovery.
- **Outputs:** Per-industry top page template recommendations, top section recommendations, fit distribution (recommended / neutral / discouraged / allowed_weak_fit), starter bundle availability, metadata gaps (e.g. missing token_preset_ref).
- **Use:** Human review of benchmark report; identify weak metadata, poor substitutes, or missing overlays; no live user tracking; no public dashboards.

---

## 2. Harness

- **Service:** `Industry_Recommendation_Benchmark_Service` (plugin/src/Domain/Industry/Reporting/Industry_Recommendation_Benchmark_Service.php).
- **Input:** Pack registry, page template recommendation resolver, section recommendation resolver (optional), page template repository, starter bundle registry, optional section list provider.
- **Method:** `run( int $template_cap = 0, int $top_n = 0 )` returns:
  - `scenarios`: list of per-industry results (industry_key, pack_found, page_recommendations, section_recommendations, starter_bundle_keys, metadata_gaps).
  - `run_at`: ISO 8601 timestamp.
  - `launch_industries`: list of industry keys run.

---

## 3. Scenario shape (per industry)

| Field | Description |
|-------|-------------|
| industry_key | Launch industry key. |
| pack_found | Whether the pack exists in the registry. |
| page_recommendations | total_evaluated, fit_distribution (recommended, neutral, discouraged, allowed_weak_fit), top_template_keys (first N by score). |
| section_recommendations | Same structure when section list provider is provided; otherwise empty. |
| starter_bundle_keys | Bundle keys available for this industry. |
| metadata_gaps | List of gap codes (e.g. no_token_preset_ref) when pack is present but refs missing. |

---

## 4. How to run

1. **From tests:** Instantiate the service with real or stubbed registries/repos; call `run()`; assert report structure and that each launch industry has a scenario.
2. **From support/internal script:** Resolve service from container (if registered) or build with container registries; call `run( 200, 15 )`; export result to JSON for review.
3. **Review:** Compare top_template_keys and top_section_keys across industries; check fit_distribution for “all neutral” (possible metadata gap); use metadata_gaps to prioritize pack definition updates.

---

## 5. Bounded and repeatable

- No user content or secrets in the report.
- Same registries and cap/top_n produce deterministic scenario order and keys (order may vary by resolver implementation; run_at is the only variable).
- Results are for internal evaluation only; do not expose publicly.

---

## 6. Reference

- **Scoring contract:** [industry-build-plan-scoring-contract.md](../contracts/industry-build-plan-scoring-contract.md).
- **Acceptance report:** [industry-subsystem-acceptance-report.md](industry-subsystem-acceptance-report.md).
- **Release gate:** [industry-pack-release-gate.md](../release/industry-pack-release-gate.md).
