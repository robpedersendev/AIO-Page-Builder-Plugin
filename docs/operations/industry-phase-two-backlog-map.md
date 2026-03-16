# Industry Subsystem Phase-Two Backlog Map (Prompt 445)

**Spec**: [industry-subsystem-roadmap-contract.md](../contracts/industry-subsystem-roadmap-contract.md); authoring and maintenance guides; release/signoff docs and risk register.

**Purpose**: Checkpoint and backlog map for the next phase of industry subsystem work. Supports dependency-aware prompt generation and prioritization. No new runtime features in this document.

---

## 1. Completed capability clusters (through Prompt 445)

| Cluster | Key deliverables | Notes |
|---------|------------------|-------|
| **Industry packs and profile** | Pack registry, profile repository, profile validator, settings screen, export/restore | First four industries; activation toggle. |
| **Recommendation and overlays** | Section/page recommendation resolvers, helper/one-pager composers, overlay registries, subtype overlays | Subtype resolution and extenders. |
| **Admin and preview** | Section/page directory filtering, detail preview resolvers, subtype influence in preview (441), subtype comparison screen (442) | Read-only; no public exposure. |
| **Health and validation** | Health check service, definition linter (438), repair suggestion engine (443), coverage-gap analyzer (439) | Advisory; no auto-fix. |
| **Pre-release and authoring** | Pre-release validation pipeline (440), checklist, dry-run sandbox (444), authoring and maintenance guides | Human review required. |
| **Subtypes** | Subtype registry, resolver, section/page subtype overlays, subtype starter bundles, comparison screen | Parent fallback; bounded. |

---

## 2. Remaining gaps and optional extensions

| Gap / extension | Priority | Dependency | Notes |
|-----------------|----------|------------|-------|
| **More industries** | Optional | Roadmap evaluation; authoring guide | Use future-industry-candidate-evaluation-framework; no new core seams. |
| **Deeper overlay coverage** | Medium | Existing overlay registries | T2/T3 section and page overlays per industry; coverage matrices. |
| **More subtypes** | Optional | Subtype schema; parent pack | Per industry-subtype-schema; expand subtype bundles/overlays. |
| **Richer diagnostics** | Low | Bounded snapshot contract | Optional fields (e.g. overlay coverage summary); no secrets. |
| **Recommendation rule refinement** | Low | Recommendation contracts | Weights, substitute quality; regression guard must pass. |
| **Import conflict + repair UI** | Medium | Conflict service; repair engine | Surface repair suggestions in import preview; no auto-apply. |

---

## 3. Future work clusters (prompt-ready)

- **Hardening**: Lifecycle/fallback audits; deprecation migration guidance; ACF/performance checks. No new industries.
- **Content seeding**: Additional overlay coverage (T2/T3), new subtype definitions, question-pack updates. Schema-valid only.
- **New industry**: Full authoring flow per industry-pack-authoring-guide; evaluation framework; release gate.
- **Author tooling**: Optional CLI/script wrappers for linter, health, sandbox; no new contracts.
- **Reporting and support**: Optional repair suggestion integration in import preview; bounded diagnostics extensions.

---

## 4. Architectural hardening vs expansion

- **Hardening (must-have for stability)**: Regression guards, release gate criteria, deprecation handling, no-industry fallback, export/restore validation. Keep current; do not relax.
- **Expansion (optional)**: New industries, new subtypes, deeper overlays, more bundles. All must use approved seams (roadmap contract §2); dependency-aware ordering.

---

## 5. References

- [industry-subsystem-roadmap-contract.md](../contracts/industry-subsystem-roadmap-contract.md) — Extension seams and roadmap categories.
- [industry-pack-maintenance-checklist.md](industry-pack-maintenance-checklist.md) — Ongoing maintenance.
- [known-risk-register.md](../release/known-risk-register.md) — Risks and mitigations.
- [industry-pre-release-validation-pipeline.md](../release/industry-pre-release-validation-pipeline.md) — Pre-release steps.
