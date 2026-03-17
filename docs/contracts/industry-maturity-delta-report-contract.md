# Industry Maturity Delta Reporting Over Time Contract (Prompt 559)

**Spec:** maturity matrix docs; release and maintenance docs; author dashboard docs.  
**Status:** Contract. Defines the **internal** reporting model for how subsystem maturity changes over time so maintainers can see whether a family, pack, or capability area is improving, stagnating, or regressing. No implementation of the report or a trend dashboard in this prompt; no mutation of roadmap state.

---

## 1. Purpose

- **Trend visibility:** Static maturity scoring (per [industry-subsystem-maturity-matrix.md](../operations/industry-subsystem-maturity-matrix.md)) answers "what is the current state?" Delta reporting answers "is this area improving, flat, or regressing over time?"
- **Bounded and honest:** Trend categories are defined and evidence-based. No misleading readiness claims; no public exposure. Internal planning and maintenance only.
- **No auto-prioritization:** Delta report is an input to human roadmap and release review; it does not automatically change backlog order or maturity level.

---

## 2. Maturity delta dimensions

| Dimension | What is compared | Evidence source |
|-----------|------------------|-----------------|
| **Level change** | Maturity level (production-ready, stable, experimental, draft, gap) at time T1 vs T2. | Maturity matrix snapshot or capability-area assessment at each time. |
| **Evidence change** | Count or presence of tests, QA evidence, regression guards, fallback docs at T1 vs T2. | Test/QA run results, checklist completion, contract coverage. |
| **Risk/blocker change** | Must-fix items, known risks, or evidence gaps at T1 vs T2. | Risk register, maturity matrix "Key risks / evidence gaps" row. |
| **Completeness trend** | Pack/subtype completeness band or total score at T1 vs T2. | Industry_Pack_Completeness_Report_Service (or equivalent) summary at each time. |

Deltas are computed by comparing two snapshots (or a snapshot to a baseline). Exact formula is implementation- or runbook-defined; this contract fixes the dimensions and intent.

---

## 3. Comparison windows

- **Window:** A pair of points in time (T1, T2) or a baseline label (e.g. "last release") and a current snapshot. Windows should be documented (e.g. "quarterly", "per release", "on-demand").
- **Cadence:** Intended cadence for taking or comparing snapshots is runbook-defined (e.g. pre-release snapshot, post-release baseline, quarterly review). No requirement for real-time trend; batch or periodic comparison is sufficient.
- **Bounded:** Comparison is limited to a defined set of capability areas and/or families (e.g. the same set as in the maturity matrix and completeness report). No unbounded "all possible metrics over all time."

---

## 4. Trend categories

| Category | Meaning | Typical use |
|----------|---------|-------------|
| **Improvement** | Maturity level increased (e.g. experimental → stable), or evidence/coverage increased, or risks reduced. | Area is on a positive trajectory; maintain or invest. |
| **Stagnation** | No material change in level, evidence count, or risk between T1 and T2. | Area is stable; schedule next review or optional enhancement. |
| **Regression** | Maturity level decreased, or evidence/coverage decreased, or new risks/blockers appeared. | Flag for review; do not treat as stable without mitigation. |

Trend semantics must remain **honest**: small fluctuations (e.g. one test added) need not be labeled "improvement" if the maturity level and risk posture are unchanged; conversely, a level drop is regression even if some other metric improved. Level and risk take precedence over raw counts when categorizing.

---

## 5. Family-level and capability-level deltas

- **Capability-level delta:** Compare one capability area (e.g. "Section overlays", "Diagnostics and health") between T1 and T2. Output: level at T1, level at T2, trend category, optional evidence-delta summary. Aligns with [industry-subsystem-maturity-matrix.md](../operations/industry-subsystem-maturity-matrix.md) §2 rows.
- **Family-level delta:** Compare a pack or subtype family (e.g. one industry_key or pack+subtype scope) between T1 and T2 using completeness band, gap count, blocker count, or other family-scoped metrics. Output: scope, band/score at T1, band/score at T2, trend category. Supports "which families are improving or regressing?" for roadmap balance.
- **Subsystem-level summary:** Optional rollup (e.g. "N areas improved, M stagnated, K regressed") for release or planning review. Bounded to the same capability set as the matrix.

---

## 6. Evidence sources and cadence

- **Evidence sources:** Maturity matrix itself (manual or exported snapshot); completeness report summary; risk register; test/QA run artifacts; release checklist completion. No single source is mandatory; implementation chooses which sources to compare and how often.
- **Cadence:** Document intended cadence (e.g. "snapshot at each release gate", "quarterly delta run"). Delta report is advisory; missing a cadence run does not block release unless policy explicitly requires it.
- **Storage:** Snapshot storage (if any) and retention are out of scope of this contract; define in runbook or implementation. Contract only defines the **model** for what is compared and how trend categories are interpreted.

---

## 7. Relation to existing artifacts

- **Maturity matrix:** Remains the authoritative current-state view. Delta report compares two matrix-like snapshots or matrix + completeness/risk over time.
- **Release and maintenance:** Delta report can feed release review ("any regressions?") and maintenance planning ("which areas stagnated?"). It does not replace the pre-release checklist or risk register.
- **Author dashboard:** Dashboard may link to a delta report or trend summary when implemented; contract does not require a specific UI.

---

## 8. References

- [industry-subsystem-maturity-matrix.md](../operations/industry-subsystem-maturity-matrix.md) — Capability areas and maturity levels.
- [industry-pack-completeness-scoring-contract.md](industry-pack-completeness-scoring-contract.md) — Completeness bands and report.
- [industry-subsystem-roadmap-contract.md](industry-subsystem-roadmap-contract.md) — Roadmap and extension seams.
- [industry-author-dashboard-contract.md](industry-author-dashboard-contract.md) — Dashboard widgets and links.
