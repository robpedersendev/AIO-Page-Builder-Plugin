# Industry Author Task Queue Contract (Prompt 525)

**Spec:** completeness scoring/report docs; coverage gap prioritization docs; override conflict detector docs; release gate docs.  
**Status:** Contract. Defines the internal author task queue that synthesizes completeness reports, gap prioritization, override conflicts, and release blockers into a bounded maintenance queue. No auto-creation of external tickets; no mutation of assets.

---

## 1. Purpose

- **Single queue:** Turn multiple report outputs (completeness, gap prioritization, override conflicts, release-blocker cues) into one categorized, severity-ordered list of maintenance tasks.
- **Evidence-linked:** Each task carries source evidence refs so maintainers can trace back to the originating report.
- **Advisory:** Task generation is internal and advisory; human prioritization remains final. No auto-opening of tickets or mutation of pack state.

---

## 2. Task shape

| Field | Type | Description |
|-------|------|-------------|
| task_key | string | Stable identifier for the task (e.g. `completeness:realtor:blocker`, `gap:realtor:starter_bundle`, `conflict:override_123`). |
| category | string | One of: blocker, cleanup, expansion, documentation, validation. |
| severity | string | high, medium, low (for ordering within category). |
| source_evidence_refs | list<string> | References to the source (e.g. `completeness:pack:realtor`, `gap_prioritization:realtor:starter_bundle`, `override_conflict:row_42`). |
| suggested_action | string | Short human-readable action (e.g. "Add starter bundle for realtor", "Resolve override conflict for plan X"). |

---

## 3. Categories

- **blocker:** Likely release blockers — health errors, completeness blocker_flags, urgent-tier gaps (e.g. missing starter bundle), high-severity override conflicts. Should be addressed or waived before release.
- **cleanup:** Override conflicts (stale, deprecated refs), health warnings, resolution of conflicts. Improves hygiene; not necessarily release-blocking.
- **expansion:** Optional or important-tier coverage gaps, below-minimal completeness, missing optional assets (e.g. question pack, compliance rules). Backlog for future sprints.
- **documentation:** Missing or outdated docs referenced by completeness or gap analysis. Low urgency unless release doc gate applies.
- **validation:** Meta-tasks such as "Run health check", "Run pre-release pipeline", "Review release gate". Remind author to run existing tools.

---

## 4. Inputs

| Input | Source | How used |
|-------|--------|----------|
| Completeness report | Industry_Pack_Completeness_Report_Service::generate_report() | pack_results with band below_minimal or blocker_flags → blocker or expansion tasks; summary counts → optional validation task. |
| Gap prioritization report | Industry_Coverage_Gap_Prioritization_Service::generate_report() or run() | ranked gaps → tasks by tier (urgent→blocker, important→cleanup/expansion, optional→expansion); release_blocker_cues → blocker tasks. |
| Override conflicts | Industry_Override_Conflict_Detector::detect() | Each conflict → cleanup task (or blocker if severity high); source_evidence_refs point to override_ref. |
| Release blocker cues | From health check errors or completeness blocker_flags | Already folded into blocker category; may be merged with gap release_blocker_cues. |

---

## 5. Output

- **tasks:** List of task objects (task_key, category, severity, source_evidence_refs, suggested_action), ordered by category (blocker first, then cleanup, expansion, documentation, validation) and within category by severity (high, medium, low).
- **summary:** Optional counts per category (blocker_count, cleanup_count, expansion_count, documentation_count, validation_count) for dashboard or report header.

---

## 6. Bounds and limits

- Task list is bounded (e.g. cap total tasks or per-category cap) so the queue remains consumable. Implementation may truncate with a "more" indicator.
- No side effects: generating the queue does not create external tickets, change overrides, or mutate pack assets.
- Read-only: Task queue generator only reads report outputs; it does not call registries or mutation services itself (reports are passed in or pulled from services by the caller).

---

## 7. Use in author dashboard

- Dashboard may call the task queue generator with current report outputs and display a "Maintenance tasks" widget with counts and a link to a full task list or to the underlying reports (Health Report, Completeness, Gap report, Override Management).
- Task queue supports "what should I work on next?" without replacing the detailed screens.

---

## 8. Cross-references

- [industry-author-dashboard-contract.md](industry-author-dashboard-contract.md) — Dashboard may consume task queue summary.
- [industry-pack-completeness-scoring-contract.md](industry-pack-completeness-scoring-contract.md) — Completeness report input.
- [industry-coverage-gap-prioritization-contract.md](industry-coverage-gap-prioritization-contract.md) — Gap prioritization input.
- [industry-override-conflict-contract.md](industry-override-conflict-contract.md) — Override conflict input.
- [industry-pack-release-gate.md](../release/industry-pack-release-gate.md) — Release blockers and waiver.
