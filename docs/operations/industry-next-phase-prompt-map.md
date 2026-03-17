# Industry Subsystem Next-Phase Prompt Map (Prompt 470)

**Spec**: [industry-subsystem-roadmap-contract.md](../contracts/industry-subsystem-roadmap-contract.md); [industry-phase-two-backlog-map.md](industry-phase-two-backlog-map.md); [industry-subsystem-maturity-matrix.md](industry-subsystem-maturity-matrix.md); [industry-subsystem-v2-guardrails.md](../contracts/industry-subsystem-v2-guardrails.md).

**Purpose**: Structured, dependency-aware prompt map for the next phase of industry subsystem work. Enables future Cursor prompt generation to continue from a clear handoff without restarting discovery. Internal planning only; no runtime changes.

---

## 1. Dependency baseline

- **Prerequisites**: Prompts 318–469 (industry packs, profile, recommendation, overlays, subtypes, health, linting, repair, coverage, pre-release pipeline, sandbox, scaffold generator, overlap analyzer, bundle-to-plan, explanation view model, override conflict detector, audit trail, what-if simulation, degraded-mode contract, maturity matrix, v2 guardrails).
- **Handoff**: This map assumes the subsystem state documented in the phase-two backlog map and maturity matrix. All new prompts must remain within v2 guardrails and approved extension seams.

---

## 2. Prompt clusters (next phase)

Clusters are grouped by theme. Ordering within a cluster is dependency-aware; cross-cluster dependencies are noted.

### 2.1 Subtype expansion

| Prompt focus | Priority | Depends on | Notes |
|--------------|----------|------------|-------|
| Additional subtype definitions per existing pack | Optional | Subtype schema, resolver, overlay registries | Per industry-subtype-schema; expand subtype bundles/overlays; no new core seams. |
| Subtype health warning for invalid ref in profile | Should-have | Subtype resolver, health check | Maturity matrix optional next step; bounded UX. |
| Subtype overlay coverage (T2/T3) per industry | Optional | Overlay registries, coverage matrices | Schema-valid only; update overlay catalog. |

**Guardrails**: Subtype count per pack must remain justified; no unbounded subtype sprawl (v2 guardrails §4).

### 2.2 Broader seeded content

| Prompt focus | Priority | Depends on | Notes |
|--------------|----------|------------|-------|
| Deeper section-helper overlay coverage (T2/T3) | Medium | Overlay registries, expansion plan | industry-helper-overlay-expansion-plan; update coverage matrix. |
| Deeper page-one-pager overlay coverage (T2/T3) | Medium | Page-onepager overlay registry | Same pattern as section; catalog and matrix updates. |
| Additional starter bundles per industry | Optional | Starter bundle schema, registry, bundle-to-plan | No new execution contract. |
| Question-pack updates or new packs | Optional | Question-pack contract, profile/onboarding | Onboarding flow only. |

**Guardrails**: Content only; no new core registries or execution paths.

### 2.3 Deeper performance work

| Prompt focus | Priority | Depends on | Notes |
|--------------|----------|------------|-------|
| ACF/registration performance (if applicable) | Should-have | Bootstrap, industry module load | Per briefs; no new contracts. |
| Admin screen profiling and tuning | Low | Health/diagnostics, admin screens | Bounded; industry-admin-screen-profiling-report. |
| Cache and read-model performance | Low | Cache contract, read-model cache service | No relaxation of cache semantics. |

**Guardrails**: No change to security or capability boundaries; diagnostics remain bounded.

### 2.4 Richer author tooling

| Prompt focus | Priority | Depends on | Notes |
|--------------|----------|------------|-------|
| Optional CLI/script wrappers for linter, health, sandbox | Low | Definition linter, health check, sandbox services | No new contracts; convenience only. |
| Repair suggestion integration in import preview UI | Medium | Conflict service, repair engine | Surface only; no auto-apply. |
| Scaffold generator enhancements | Low | industry-scaffold-generator-contract | File/placeholder skeletons; additive. |

**Guardrails**: Human review required; no auto-promotion or bypass of approval.

### 2.5 Future-industry onboarding

| Prompt focus | Priority | Depends on | Notes |
|--------------|----------|------------|-------|
| Intake dossier workflow and template (471) | Must-have | Evaluation framework, scorecard template | Consistent evidence gathering before scorecard. |
| Scorecard executor and report generator (472) | Must-have | Intake dossier workflow | Dossier → scorecard dimensions → report. |
| Candidate comparison matrix (473) | Should-have | Scorecard executor | Multi-candidate side-by-side; comparison matrix template and Future_Industry_Comparison_Matrix_Service. |
| Evaluation framework refinements | Low | Framework, scorecard, dossier | Weighting, thresholds, evidence expectations. |

**Guardrails**: Internal-only; no auto-approve; no mutation of runtime pack registries.

### 2.6 Optional advanced reporting

| Prompt focus | Priority | Depends on | Notes |
|--------------|----------|------------|-------|
| What-if simulation surface in comparison or profile screen | Optional | What-if service (466), comparison screen | UI only; no new persistence. |
| Overlay coverage summary in diagnostics snapshot | Optional | Diagnostics contract, overlay registries | Bounded fields; no secrets. |
| Richer diagnostics optional fields | Low | industry-subsystem-diagnostics-checklist | Document any new fields. |

**Guardrails**: Bounded payloads; admin/support-only; no public exposure.

---

## 3. Must-have vs should-have vs optional

| Level | Meaning | Clusters / prompts |
|-------|--------|--------------------|
| **Must-have (hardening)** | Required for disciplined future-industry workflow and planning continuity. | Intake dossier (471), scorecard executor (472). |
| **Should-have** | Improves prioritization and maturity without blocking. | Comparison matrix (473), subtype health warning, ACF/performance if applicable. |
| **Optional** | Backlog; do when capacity and roadmap allow. | New subtypes, deeper overlays, author CLI, what-if UI, overlay coverage in diagnostics. |

Must-have hardening remains distinct from optional expansion; v2 guardrails forbid normalizing unsafe expansion paths.

---

## 4. Cross-references and capability areas

- **Roadmap categories**: [industry-subsystem-roadmap-contract.md](../contracts/industry-subsystem-roadmap-contract.md) §6 — new industries, deeper overlays, starter bundles, diagnostics, recommendation rules, export/restore, question packs, subtypes.
- **Maturity matrix**: [industry-subsystem-maturity-matrix.md](industry-subsystem-maturity-matrix.md) — use for evidence-based ordering and release decisions.
- **v2 guardrails**: [industry-subsystem-v2-guardrails.md](../contracts/industry-subsystem-v2-guardrails.md) — no separate product, no AI auto-approve, no freeform industry forks; subtype/bundle boundaries.
- **Backlog map**: [industry-phase-two-backlog-map.md](industry-phase-two-backlog-map.md) — remaining gaps and future work clusters; this prompt map refines prompt-level sequencing.

---

## 5. Usage for prompt generation

1. **Start from dependency baseline**: Any new prompt in this map assumes 318–469 and the current roadmap/maturity/guardrail docs.
2. **Pick a cluster**: Choose subtype expansion, seeded content, performance, author tooling, future-industry onboarding, or advanced reporting.
3. **Respect dependencies**: Within a cluster, run prompts in the order implied by "Depends on"; across clusters, future-industry onboarding (471→472→473) has a strict order.
4. **Do not duplicate**: This map does not replace the backlog map; it adds prompt-level sequencing. Do not copy backlog prose verbatim into new prompts.
5. **Stay within guardrails**: If a prompt would require new core seams, AI auto-approve, or unbounded sprawl, it is out of scope unless the master spec or governance explicitly overrides.

---

## 6. Greenfield closure and implementation-audit handoff

- **Greenfield closure:** The late-stage greenfield program (through Prompts 318–567) is summarized in [industry-greenfield-closure-report.md](industry-greenfield-closure-report.md) (Prompt 568). That report marks completed capability layers, remaining optional expansion, and the transition to implementation-audit.
- **Archive map:** [industry-greenfield-prompt-archive-map.md](industry-greenfield-prompt-archive-map.md) (Prompt 570) is the final archival map: prompt ranges by capability cluster, authoritative contracts, core artifact families, and the transition point into implementation-audit. Use it to trace what built which capability and where to start audit work.
- **Implementation-audit entrypoint:** The next phase of prompts should target **implementation audit** (correctness, safety, failure modes, test coverage) rather than new features. Use [industry-implementation-audit-entrypoint-map.md](industry-implementation-audit-entrypoint-map.md) (Prompt 569) for priority audit domains, high-risk codepaths, and audit clusters. Audit prompts must not add new runtime features unless explicitly scoped elsewhere; they produce audits, tests, or doc updates.
- **Optional late-stage backlog:** [industry-optional-late-stage-greenfield-backlog.md](industry-optional-late-stage-greenfield-backlog.md) (Prompt 585) lists Prompts 571–584 (secondary-goal surfacing/benchmark/what-if/style/bundle/conflict, variance report, scaffold export, evidence packet) as optional. Do not treat them as blocking; implementation-audit comes first.

---

*This map provides a clean handoff for future Cursor prompt generation while preserving roadmap discipline and v2 architectural guardrails.*
