# Industry Admin Screen Profiling Report (Prompt 453)

**Purpose:** Document load patterns of industry admin screens, identify redundant work, and record optimizations applied. Bounded, measurable improvements; screen correctness preserved.

---

## 1. Screens profiled

| Screen | Entry point | Primary data load |
|--------|-------------|--------------------|
| Industry Health Report | Industry_Health_Report_Screen::render() → get_state() | Industry_Health_Check_Service::run(); Industry_Repair_Suggestion_Engine::suggest_for_issue() per error and per warning |
| Industry Overrides | Industry_Override_Management_Screen::render() | Industry_Override_Read_Model_Builder::build() → section_service::list_overrides(), page_template_service::list_overrides(), build_plan_service::list_all_overrides() |
| Bundle comparison | Industry_Starter_Bundle_Comparison_Screen::render() → get_state() | Starter bundle registry list_all(); Industry_Starter_Bundle_Diff_Service::compare() when 2+ keys selected |
| Subtype comparison | Industry_Subtype_Comparison_Screen::render() → get_state() | Profile repo get_profile(); Industry_Subtype_Comparison_Service::get_comparison() → bundle_list() x2, recommendation resolvers (parent + subtype) |
| Industry Profile / Settings | Industry_Profile_Settings_Screen | Profile repo, pack registry, bundle registry, readiness |
| Status summary widget (dashboard) | Industry_Status_Summary_Widget::build_view_model() | Profile, pack registry, bundle registry, toggle, readiness, **Industry_Health_Check_Service::run()** |

---

## 2. Load patterns and redundant work

- **Health check run:** The health check traverses all packs, profile, starter bundles, and ref registries (CTA, SEO, LPagery, presets, overlays). It is invoked by (1) Health Report screen get_state(), (2) Status summary widget when industry is set. These are different requests (dashboard vs health page), so no duplicate within one request. If any future code path called run() twice in the same request (e.g. multiple widgets or nested screens), the second call would repeat the full traversal. **Optimization applied:** Request-scoped cache in Industry_Health_Check_Service::run(): first call in a request runs the check and stores the result; subsequent calls in the same request return the cached result. Cache is keyed by service instance so different instances (e.g. sandbox vs live) do not share cache.
- **Repair suggestions:** Health Report screen calls suggest_for_issue() once per error and once per warning. Cost is linear in issue count; no duplicate traversal of registries. No optimization applied; acceptable.
- **Override management:** build() aggregates three override sources in one pass. Single traversal per source; no duplicate work. No change.
- **Bundle comparison:** list_all() is one pass; compare() is O(bundles × fields). Only runs when 2+ keys selected. No optimization applied.
- **Subtype comparison:** get_comparison() fetches parent and subtype bundles and runs recommendation resolution for parent and subtype profiles. Single call per screen load. No optimization applied.
- **Status widget:** Builds view model with profile, pack get(), bundle get(), readiness, and health run(). Readiness may read profile again (internal). No duplicate health run in same request after health-service cache.

---

## 3. Optimizations applied

| Change | Location | Effect |
|--------|----------|--------|
| Request-scoped health result cache | Industry_Health_Check_Service::run() | Second run() in the same request (same instance) returns cached result; avoids repeated registry graph traversal. No change to first-call behavior or to cross-request semantics. |

---

## 4. Residual hot spots

- **Health check:** First run() in a request remains the heaviest single operation (all packs × refs, profile, bundles). Consider running only when Health Report or Status widget is actually shown if profiling shows impact.
- **Override read model:** Three separate list_overrides/list_all_overrides calls; each may hit options or Build Plan storage. Bounded; no change in this pass.
- **Subtype comparison:** Recommendation resolution for parent and subtype (templates + sections) when both resolvers and page repo are present. Bounded; documented.

---

## 5. No-industry fallback

- When no industry is configured, Health Report and Status widget still resolve the health check service; run() returns quickly with profile/pack checks. Subtype and Bundle comparison screens show empty or parent-only state. No additional load in no-industry case.

---

## 6. References

- [industry-performance-benchmark-protocol.md](industry-performance-benchmark-protocol.md) — Use run_benchmark() to compare before/after.
- [industry-preview-performance-tuning-notes.md](industry-preview-performance-tuning-notes.md) — Preview/detail pipeline tuning.
- [industry-cache-contract.md](../contracts/industry-cache-contract.md) — Site-local caching rules.
- [industry-admin-screen-contract.md](../contracts/industry-admin-screen-contract.md) — Admin screen contracts.
