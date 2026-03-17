# Industry Subsystem Maturity Matrix (Prompt 468)

**Spec**: Roadmap contract; release signoff docs; maintenance and authoring guides; risk register.  
**Purpose**: Classify each capability area by current maturity, remaining risk, required evidence, and recommended next steps. Internal and maintenance-focused; supports future planning and release decisions. Evidence-based; not aspirational.

---

## 1. Maturity levels

| Level | Meaning |
|-------|--------|
| **Production-ready** | Contract in place; tests/QA and release evidence exist; used in production signoff. |
| **Stable** | Implemented and contract-aligned; regression guards and fallback documented; minor gaps only. |
| **Experimental** | Implemented but limited evidence or edge-case coverage; use with caution. |
| **Draft** | Defined in contract or backlog; implementation partial or not yet validated. |
| **Gap** | Known missing or high-risk; must-fix before treating area as stable. |

---

## 2. Capability areas

| Area | Maturity | Key risks / evidence gaps | Recommended next steps |
|------|----------|---------------------------|-------------------------|
| **Packs (registry, definitions)** | Stable | Deprecation migration path tested; no automated deprecation sweep. | Keep deprecation contract; add migration runbook if more packs deprecated. |
| **Industry profile (repository, schema, validation)** | Production-ready | Export/restore and uninstall evidence in place; IND-1, IND-2 in risk register. | Maintain; no relaxation of validation. |
| **Subtypes (registry, resolver, fallback)** | Stable | Subtype fallback audit done; deprecated subtype status yields parent-only. | Optional: health warning for invalid subtype ref in profile. |
| **Starter bundles** | Stable | Bundle registry and get_for_industry; bundle-to-plan conversion (462). | Deeper bundle coverage per roadmap; no new schema. |
| **Section overlays (helper, subtype)** | Stable | Overlay registries; composition and preview; allowed regions only. | T2/T3 coverage per expansion plan; coverage matrix updated. |
| **Page overlays (one-pager, subtype)** | Stable | Same pattern as section; page-onepager overlay registry. | Same as section overlays. |
| **Recommendation resolvers (section, page template)** | Stable | Contracts; cache; neutral fallback; regression guard. | Weights/refinement low priority; guard must pass. |
| **Build Plan scoring (industry, subtype)** | Stable | Additive metadata; context profile injection; no execution change. | Keep planner/executor separation; no auto-apply. |
| **AI overlays (prompt pack, subtype)** | Experimental | Prompt overlay and subtype AI overlay in use; evaluation fixtures exist; full prompt regression coverage TBD. | Expand evaluation fixtures; document limits. |
| **Diagnostics and health** | Stable | Bounded snapshot; health check; override audit; conflict detector; audit trail. | Optional: overlay coverage summary in snapshot. |
| **Import/export and restore** | Stable | Export/restore contract; invalid ref handling; no fatal on restore. | Conflict and repair UI (medium priority in backlog). |
| **Cache layers** | Stable | Cache contract; site scoping; invalidation triggers; safe miss. | No relaxation; document simulation isolation (what-if). |
| **Support tooling** | Stable | Diagnostics snapshot; support package; override audit; conflict detector; audit trail; training packet. | Keep support docs updated with degraded-mode and runbooks. |
| **Release and authoring tooling** | Stable | Pre-release pipeline; checklist; sandbox dry-run; promotion workflow; authoring guide; maintenance checklist. | Human review required; no auto-promotion. |
| **What-if simulation** | Stable | Bounded service (466); no persistence; comparison integration. | Optional: surface in comparison or profile screen. |
| **Subtype+goal combined benchmark** | Stable | Industry_Subtype_Goal_Benchmark_Service; compares parent vs subtype vs goal vs combined; protocol doc in place. | Run for launch subtype and goal sets; use for quality assessment. |
| **Degraded mode** | Stable | Contract (467); lifecycle and fallback docs aligned. | Tests for representative degraded scenarios. |

---

## 3. Must-fix vs optional

- **Must-fix (before elevating maturity)**: Any area with unresolved regression guard failure, missing fallback for missing/invalid refs, or security/capability bypass. Currently none in this matrix that block production use of the subsystem as a whole; IND-1, IND-2, IND-3 are documented and mitigated.
- **Optional enhancement**: Richer diagnostics, deeper overlays, more subtypes, recommendation refinement, what-if UI surface—all documented in phase-two backlog map.

---

## 4. Completeness assessment

- **Pack completeness scoring:** Maintainers can assess whether a pack or subtype asset set is minimally complete, strong, or release-grade using the advisory [industry-pack-completeness-scoring-contract.md](../contracts/industry-pack-completeness-scoring-contract.md) (Prompt 519). Score is advisory only; it does not replace release gate or human review.
- **Asset aging / stale-content scoring:** Maintainers can reason about stale but technically valid assets (overlays, rules, docs, bundles, presets, scaffolds) using the internal [industry-asset-aging-scoring-contract.md](../contracts/industry-asset-aging-scoring-contract.md) (Prompt 555). Stale score is advisory; no auto-edit, no public status; supports long-term maintenance planning.
- **Maturity delta over time:** To model how maturity changes over time (improvement, stagnation, regression) for capability areas or families, use the internal [industry-maturity-delta-report-contract.md](../contracts/industry-maturity-delta-report-contract.md) (Prompt 559). The report is generated by `Industry_Maturity_Delta_Report_Service` and is available from the Industry Author Dashboard via **Maturity delta report** (Prompt 560). Delta reporting is advisory; no auto-prioritization; supports long-term planning and release review.

---

## 5. References

- [industry-greenfield-closure-report.md](industry-greenfield-closure-report.md) — Late-stage greenfield closure (Prompt 568); handoff to implementation-audit.
- [industry-subtype-goal-benchmark-protocol.md](../qa/industry-subtype-goal-benchmark-protocol.md) — Combined subtype+goal benchmark (Prompt 535).
- [industry-subsystem-roadmap-contract.md](../contracts/industry-subsystem-roadmap-contract.md)
- [industry-phase-two-backlog-map.md](industry-phase-two-backlog-map.md)
- [known-risk-register.md](../release/known-risk-register.md) §3 IND-1, IND-2, IND-3
- [industry-subsystem-final-signoff.md](../release/industry-subsystem-final-signoff.md)
- [industry-degraded-mode-contract.md](../contracts/industry-degraded-mode-contract.md)
- [industry-pack-completeness-scoring-contract.md](../contracts/industry-pack-completeness-scoring-contract.md) — Advisory completeness dimensions and bands for packs, subtypes, bundles, overlays, docs, QA.
- [industry-asset-aging-scoring-contract.md](../contracts/industry-asset-aging-scoring-contract.md) — Internal stale-content scoring dimensions and intended use for maintenance planning (Prompt 555).
- [industry-maturity-delta-report-contract.md](../contracts/industry-maturity-delta-report-contract.md) — Internal maturity delta (trend) reporting model for improvement/stagnation/regression over time (Prompt 559).
