# Industry Author Dashboard Contract (Prompt 521)

**Spec:** industry-pack-authoring-guide.md; industry-pack-completeness-scoring-contract.md; health report contracts; industry-pack-release-gate.md.  
**Status:** Contract. Defines the internal author dashboard information architecture, widget set, data sources, severity groupings, and navigation expectations for an internal-only author/admin dashboard. No UI implementation in this contract.

---

## 1. Purpose

- **Single place:** Maintainers view pack health, completeness, warnings, missing coverage, scaffold state, and release readiness without relying on scattered reports.
- **Read-only summary:** Dashboard aggregates existing report outputs and links out to detailed screens for actions. No destructive controls on the dashboard itself.
- **Internal-only:** Admin-only surface; no public reporting.

---

## 2. Scope and constraints

- **In scope:** Summary widgets for health, completeness, release blockers, scaffold/incomplete assets, coverage gaps, and key warnings. Links to Industry Profile, Health Report, comparison screens, release gate docs, and maintenance checklists.
- **Out of scope:** Full redesign of existing admin screens; public status dashboard; embedded write actions (e.g. "Fix all" that mutates data). Repair or apply actions live on dedicated screens; dashboard links to them.

---

## 3. Required dashboard widgets and summaries

| Widget | Purpose | Data source |
|--------|---------|-------------|
| **Pack health summary** | Count of health errors and warnings; indicator (healthy / warnings / errors). | Industry_Health_Check_Service::run() — errors, warnings. |
| **Completeness summary** | Count of packs/subtypes by band (release-grade, strong, minimal, below minimal); link to completeness report or breakdown. | Industry_Pack_Completeness_Report_Service::generate_report() — summary. |
| **Release blocker / major warnings** | Count of items that are likely release blockers (e.g. health errors, completeness blocker_flags). | Health errors + completeness report blocker_flags; optional override conflict count. |
| **Scaffold / incomplete asset warning** | Count or list of draft/incomplete scaffold assets (packs, subtypes, bundles with status draft or scaffold metadata). | Completeness report or dedicated filter on registries (draft status). |
| **Coverage gap summary** | Count of coverage gaps by priority (high/medium/low) or link to gap report. | Industry_Coverage_Gap_Analyzer::analyze() — gaps; optional prioritization report when available. |
| **Subtype and goal support summary** | Count of active packs, active subtypes, goal-related assets (if in scope). | Pack registry, subtype registry; optional goal overlay/caution counts. |
| **Quick links** | Links to Industry Profile, Health Report, Bundle comparison, Subtype comparison, Conversion goal comparison, release gate doc, pre-release checklist, maintenance checklist. | Static or config. |
| **Maintenance task queue** | Optional: count of tasks by category (blocker, cleanup, expansion) and link to full queue or underlying reports. | Industry_Author_Task_Queue_Service::generate_queue() — summary. |

Widgets are bounded: no unbounded lists on the dashboard; use counts and "View full report" links. Data is loaded on dashboard render (or cached per request); see §7 for refresh expectations.

---

## 4. How completeness, health, warnings, and coverage gaps are surfaced

- **Completeness:** Show summary counts (e.g. "3 release-grade, 2 strong, 1 minimal, 1 below minimal"). Optionally show one line per pack/subtype with band and total score; link to full completeness report or breakdown screen if implemented.
- **Health:** Show error count and warning count; if errors > 0, show "Release blocker" or "Fix before release" cue. Link to Industry Health Report screen for full list and repair suggestions.
- **Warnings:** Aggregate from health warnings and optionally override conflict detector; show count and link to Health Report or Override Management.
- **Coverage gaps:** Show total gap count or by priority (high/medium/low). Link to coverage gap analysis guide or a gap report screen if implemented.

Severity grouping: **blocker** (health errors, completeness blocker_flags), **major** (health warnings, below_minimal completeness), **advisory** (coverage gaps, optional expansion).

---

## 5. Pack, subtype, goal, and scaffold status visibility

- **Pack status:** Number of active packs; number of draft/deprecated if needed for author context. Do not list every pack key on the dashboard unless in a compact table with band/status only.
- **Subtype status:** Number of active subtypes; optional "subtypes per pack" summary. Link to Subtype comparison screen.
- **Goal support:** If conversion goal layer is in scope, show whether goal overlays/cautions exist and link to Conversion goal comparison screen.
- **Scaffold / incomplete:** Clearly distinguish "draft or scaffold" assets from "release-ready." Show count of draft packs or scaffold-marked assets; link to scaffold guardrail contract or authoring guide. Do not present scaffold assets as production-ready.

---

## 6. Links to deeper maintenance screens and reports

- **Industry Profile** — Edit primary/secondary industry, subtype, bundle, conversion goal.
- **Industry Health Report** — Full health check output and repair suggestions.
- **Subtype comparison** — Parent vs subtype bundles and recommendations.
- **Bundle comparison** — Compare two or more starter bundles.
- **Conversion goal comparison** — No-goal vs goal-aware comparison.
- **Release gate / pre-release checklist** — Docs and pipeline for release validation.
- **Maintenance checklist** — Ongoing maintenance tasks.
- **Override Management** — Override conflict resolution (if applicable).

All links open existing admin screens or docs; dashboard does not replace them.

---

## 7. Refresh and stale-data expectations

- **On load:** Dashboard data is computed when the dashboard screen is rendered (e.g. one health run, one completeness report, one gap analysis per request). No requirement for real-time push updates.
- **Stale data:** Data may be stale until the user refreshes the page or reopens the dashboard. Document that dashboard reflects state at time of load; for latest validation, run pre-release pipeline or open Health Report.
- **Caching:** If caching is used (e.g. request-scoped or short TTL), it must be clearly bounded and invalidated on relevant profile/pack changes or documented as "best effort" for author planning only.
- **Performance:** Dashboard should remain bounded: avoid running heavy reports multiple times; prefer a single aggregation pass or cached result per request.

---

## 8. Bounded and role-appropriate

- **Bounded:** Widget list is fixed by this contract; no open-ended "all reports" dump. Summaries are counts and short labels; full detail is behind links.
- **Role:** Dashboard is for users with admin/settings or view-logs capability (same as other industry screens). No public or contributor-facing dashboard.
- **No hidden actions:** Any "Fix" or "Apply" must link to a dedicated screen with explicit confirmation and nonce; dashboard does not perform state-changing actions itself.

---

## 9. Implementation notes (for Prompt 522)

- **Screen:** Single admin screen (e.g. Industry_Author_Dashboard_Screen) under AIO Page Builder menu; slug and capability aligned with industry screens (e.g. VIEW_LOGS or MANAGE_SETTINGS).
- **View model:** Optional Industry_Author_Dashboard_View_Model to hold aggregated data (health summary, completeness summary, blocker count, gap count, link URLs) for the template.
- **Data aggregation:** Dashboard screen or a small aggregator service calls Health_Check_Service::run(), Completeness_Report_Service::generate_report(), Coverage_Gap_Analyzer::analyze() (and optionally override conflict detector) and maps results into the view model. Optionally call Industry_Author_Task_Queue_Service::generate_queue() with those report outputs to show a maintenance task queue summary. Keep aggregation in one place so the dashboard remains maintainable.

---

## 10. Cross-references

- [industry-pack-authoring-guide.md](../operations/industry-pack-authoring-guide.md) — Author workflow; dashboard supports it.
- [industry-pack-completeness-scoring-contract.md](industry-pack-completeness-scoring-contract.md) — Completeness dimensions and report generator.
- [industry-pack-release-gate.md](../release/industry-pack-release-gate.md) — Release criteria; dashboard surfaces blocker cues.
- [industry-admin-screen-contract.md](industry-admin-screen-contract.md) — Industry screens; dashboard links to them.
- [industry-coverage-gap-analysis-guide.md](../operations/industry-coverage-gap-analysis-guide.md) — Coverage gap analyzer and usage.
- [scaffold-incomplete-state-guardrail-contract.md](scaffold-incomplete-state-guardrail-contract.md) — Scaffold/incomplete asset handling.
