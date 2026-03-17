# Industry Subtype + Goal Combined Benchmark Protocol (Prompt 535)

**Spec**: subtype benchmark docs; conversion-goal benchmark docs; Build Plan and preview contracts.

**Purpose**: Internal benchmark harness to compare parent-only, subtype-only, goal-only, and subtype+goal scenarios so maintainers can evaluate whether the layered system behaves coherently when both nuance layers are active. No public analytics; no live-state mutation.

---

## 1. Scope

- **Comparison dimensions:** Parent-only vs subtype-only vs goal-only vs combined (subtype+goal) across:
  - **Recommendations:** Section and page-template recommendation outputs (top keys, ordering).
  - **Bundle outputs:** Starter bundle lists and bundle content where applicable.
  - **Build Plan posture:** Build Plan proposal differences (page families, CTA posture, section emphasis) when available.
  - **Preview/doc guidance:** Preview and one-pager guidance differences when available.
- **Launch sets:** Launch subtype set and launch goal set first (see subtype and goal benchmark protocols).
- **Output:** Readable summaries of strong vs weak combined behavior; bounded report structure.

---

## 2. Scenarios

| Scenario | Profile shape | Description |
|----------|---------------|-------------|
| **Parent-only** | primary_industry_key only; no subtype, no goal | Baseline industry behavior. |
| **Subtype-only** | primary_industry_key + industry_subtype_key | Subtype layer applied; no goal. |
| **Goal-only** | primary_industry_key + conversion_goal_key | Goal layer applied; no subtype. |
| **Combined** | primary_industry_key + industry_subtype_key + conversion_goal_key | Both subtype and goal applied. |

---

## 3. Harness usage

- **Industry_Subtype_Goal_Benchmark_Service::run_benchmark( array $profile_base, string $subtype_key = '', string $goal_key = '' )**
  - **profile_base:** Normalized industry profile (primary_industry_key required).
  - **subtype_key:** Optional; empty = skip subtype dimension.
  - **goal_key:** Optional; one of launch goal set; empty = skip goal dimension.
- Returns: `generated_at`, `profile_base`, `scenarios` (parent_only, subtype_only, goal_only, combined), `recommendation_differentiation` (parent_vs_subtype, parent_vs_goal, parent_vs_combined, combined_strength), `readable_summary`, `warnings`.
- **No live-state mutation.** Uses in-memory profile variants; does not write to site options or plan storage.
- **Bounded:** Report size and comparison items capped; no unbounded expansion.

---

## 4. Differentiation quality

- **Strong combined behavior:** Combined (subtype+goal) outputs differ meaningfully from both subtype-only and goal-only; recommendations, bundle posture, or Build Plan/preview guidance show distinct combined effect.
- **Weak combined behavior:** Combined outputs match subtype-only or goal-only; layered effect is nominal or not yet reflected in compared dimensions (e.g. when recommendation resolvers are not goal-aware).

---

## 5. Integration

- **Subtype benchmark:** [industry-subtype-benchmark-protocol.md](industry-subtype-benchmark-protocol.md); Industry_Subtype_Benchmark_Service.
- **Goal benchmark:** [conversion-goal-benchmark-protocol.md](conversion-goal-benchmark-protocol.md); Conversion_Goal_Benchmark_Service.
- **Release gate / maturity:** Maturity and release gate docs may reference this protocol; "combined subtype+goal benchmark run" can inform quality assessment.
- **Planning docs:** Subtype and goal planning docs can reference this protocol for multi-layer expansion quality.

---

## 6. Cross-references

- [industry-subtype-benchmark-protocol.md](industry-subtype-benchmark-protocol.md)
- [conversion-goal-benchmark-protocol.md](conversion-goal-benchmark-protocol.md)
- [industry-recommendation-benchmark-protocol.md](industry-recommendation-benchmark-protocol.md)
- Build Plan and preview contracts (spec §27, §29).
