# Conversion-Goal Benchmark Protocol (Prompt 500)

**Spec**: conversion-goal contracts; recommendation benchmark docs; Build Plan and bundle docs.

**Status**: Internal benchmark protocol. Measures whether goal-aware recommendations, bundles, and Build Plans differ meaningfully from no-goal outputs and align with intended funnel posture.

---

## 1. Purpose

- **Quality measurement**: Maintainers can run a bounded benchmark comparing no-goal vs goal-aware outcomes across sections, page templates, starter bundles, and Build Plan proposals.
- **Decision support**: Results inform whether conversion-goal support is materially affecting outputs; supports launch goal set first.
- **Internal only**: No public analytics; no auto-grade of release readiness; no mutation of live state.

---

## 2. Scope

- Compare **no-goal** vs **goal-aware** section recommendations (top section keys, emphasis).
- Compare **no-goal** vs **goal-aware** page-template recommendations (top template keys).
- Compare **no-goal** vs **goal-aware** bundle outputs (when bundle conversion is run with vs without goal).
- Compare **no-goal** vs **goal-aware** Build Plan proposals (page families, CTA posture, section emphasis in draft).
- Produce **readable summaries** of meaningful differentiation (e.g. "Goal X changed top 3 section keys by N").
- **Launch goal set** first: calls, bookings, estimates, consultations, valuations, lead_capture.

---

## 3. Execution

- **Conversion_Goal_Benchmark_Service**: Internal service. Method(s): e.g. `run_for_goal_set( array $goal_keys ): array` or `compare_no_goal_vs_goal( string $goal_key, array $profile_base ): array`.
- Input: Base industry profile (primary_industry_key, subtype, bundle); optional list of goal keys. No live profile mutation.
- Output: Bounded result structure: per goal, comparison summary (sections_diff, templates_diff, bundle_diff, plan_diff), and a short readability summary.
- **No persistence** of benchmark results to production storage; results are ephemeral or written to a dedicated log path for analysis.

---

## 4. Safety

- **No live-state mutation**. Benchmark runs use in-memory or copied profile; never overwrite site option or plan storage.
- **Bounded**: Cap iterations and result size; no unbounded expansion.
- **Admin/internal** only; no public surfaces.

---

## 5. Integration

- Release gate docs and maturity docs may reference this protocol. Benchmark findings can inform roadmap and future goal expansion.
- **Combined subtype+goal benchmark:** [industry-subtype-goal-benchmark-protocol.md](industry-subtype-goal-benchmark-protocol.md) (Prompt 535) compares parent-only, subtype-only, goal-only, and combined scenarios using this service and the subtype benchmark.
