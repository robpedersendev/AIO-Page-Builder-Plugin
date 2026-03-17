# Industry Subsystem Optional Late-Stage Greenfield Backlog (Prompt 585)

**Spec:** Greenfield closure report; archive map; roadmap contract; implementation-audit entrypoint map; v2 guardrails.  
**Purpose:** Final optional backlog for remaining greenfield work (Prompts 571–584). Clearly separates these **optional** items from **must-do** implementation-audit work. Implementation-audit is the next priority; this backlog is for when capacity allows or when a specific expansion need arises.

---

## 1. Scope and use

- **Optional only:** Nothing in this backlog is required for subsystem stability, release readiness, or implementation-audit. Doing implementation-audit first is the recommended path.
- **Trigger-based:** Revisit an item when (a) implementation-audit for the relevant area is done, (b) a concrete product need appears, or (c) internal tooling for mixed-funnel/secondary-goal/scaffold handoff becomes a priority.
- **No dilution of audit:** This document must not be used to defer or skip implementation-audit. The [industry-implementation-audit-entrypoint-map.md](industry-implementation-audit-entrypoint-map.md) defines where audit work should start.

---

## 2. Optional greenfield opportunities (Prompts 571–584)

Grouped by theme. All are **optional**; tiers indicate relative value if pursued later.

### 2.1 Secondary-goal visibility and measurement (571–573)

| Prompt | Title | Tier | Why optional | Trigger to revisit |
|--------|--------|------|--------------|--------------------|
| **571** | Secondary-goal preview and detail surfacing | Medium | Primary-goal and no-goal flows are sufficient for launch; secondary-goal nuance is late-stage polish. | Mixed-funnel sites need admin preview explanation for secondary goal. |
| **572** | Secondary-goal benchmark harness | Low | Existing conversion-goal benchmarks cover primary goal; secondary is additive. | Need to measure that secondary-goal effects stay bounded and non-noisy. |
| **573** | Secondary-goal what-if simulation extension | Medium | What-if already supports industry, subtype, primary goal; secondary extends it. | Operators need to compare mixed-funnel scenarios without touching live profile. |

### 2.2 Secondary-goal styling and bundle UX (574–576)

| Prompt | Title | Tier | Why optional | Trigger to revisit |
|--------|--------|------|--------------|--------------------|
| **574** | Secondary-goal style preset overlay contract | Low | Styling is stable without secondary-goal presets; contract defines a bounded extension. | Mixed-funnel presentation nuance requested; must stay sanitized and low-weight. |
| **575** | Seed secondary-goal style preset overlays | Low | Depends on 574; small seed set only. | After 574; need minimal proof-of-value for secondary-goal styling. |
| **576** | Secondary-goal bundle comparison and selection refinement | Medium | Bundle selection works for single-goal; secondary-goal bundle effects are additive. | Bundle comparison UI needs to show primary+secondary bundle effects clearly. |

### 2.3 Secondary-goal conflict and precedence (577–578)

| Prompt | Title | Tier | Why optional | Trigger to revisit |
|--------|--------|------|--------------|--------------------|
| **577** | Secondary-goal conflict and precedence extension | Medium | Primary-goal authority is already clear; formal precedence doc reduces ambiguity for mixed-funnel. | Secondary-goal usage grows; need deterministic rules vs subtype, bundles, overrides, cautions. |
| **578** | Secondary-goal conflict detector | Medium | Depends on 577; advisory only. | Operators need visibility into weak-fit or contradictory secondary-goal choices. |

### 2.4 Scaffold and expansion tooling (579–584)

| Prompt | Title | Tier | Why optional | Trigger to revisit |
|--------|--------|------|--------------|--------------------|
| **579** | Authored-vs-scaffold variance report contract | Low | Scaffold completeness and promotion-readiness already give progress signals. | Maintainers want to see where authored packs diverged from scaffold assumptions. |
| **580** | Authored-vs-scaffold variance report generator | Low | Depends on 579. | After 579; need a practical variance report for authoring/scaffold improvement. |
| **581** | Scaffold package export contract | Low | Scaffolds are file/code-based; export is for handoff/review, not release. | Internal scaffold handoff or archival needs a defined export format. |
| **582** | Scaffold package exporter implementation | Low | Depends on 581. | After 581; need to export future-industry/future-subtype scaffolds for review. |
| **583** | Future-expansion evidence packet contract | Low | Readiness screens and reports already exist; packet bundles them. | Single consolidated artifact for expansion proposal review. |
| **584** | Future-expansion evidence packet generator | Low | Depends on 583. | After 583; need to generate evidence packets for future-industry/subtype proposals. |

---

## 3. Separation from implementation-audit

- **Implementation-audit (must-do):** Verify existing codepaths, safety, failure modes, test coverage, and alignment with contracts. See [industry-implementation-audit-entrypoint-map.md](industry-implementation-audit-entrypoint-map.md). No new features required for audit.
- **This backlog (optional):** New capabilities or extensions (secondary-goal surfacing, benchmarks, what-if, style overlays, bundle comparison, conflict detector, variance reporting, scaffold export, evidence packets). None of these are prerequisites for audit.
- **Order of operations:** Complete implementation-audit work first; then consider items from this backlog when roadmap or product need justifies them.

---

## 4. References

- [industry-greenfield-closure-report.md](industry-greenfield-closure-report.md) — Closure report; completed layers; where audit starts.
- [industry-greenfield-prompt-archive-map.md](industry-greenfield-prompt-archive-map.md) — Prompt ranges and authoritative contracts.
- [industry-implementation-audit-entrypoint-map.md](industry-implementation-audit-entrypoint-map.md) — Priority audit domains and next phase.
- [industry-subsystem-roadmap-contract.md](../contracts/industry-subsystem-roadmap-contract.md) — Extension seams and categories.
- [industry-subsystem-v2-guardrails.md](../contracts/industry-subsystem-v2-guardrails.md) — Boundaries; no new core seams without explicit decision.
