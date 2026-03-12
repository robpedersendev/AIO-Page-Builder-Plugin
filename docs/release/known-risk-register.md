# Known Risk Register

**Governs:** Spec §59.15 (Production Readiness Phase), §61 (Known Risks, Open Questions, and Decision Log).  
**Purpose:** Record known product and technical risks, mitigations, and release-scope notes for sign-off and support.  
**Audience:** Internal; release and support context. Do not expose secrets or site-specific data.

---

## 1. Product Risks (Spec §61.1)

| Risk | Mitigation | Release scope |
|------|------------|---------------|
| Build Plan complexity may overwhelm users if not staged carefully. | Stepper UI; step-by-step review; approval/deny per item; bulk actions with confirmation. | Document in user guidance; monitor feedback. |
| Too many advanced settings may dilute the plugin's structured nature. | Settings kept minimal; optional integrations (e.g. LPagery) degrade gracefully. | Current scope: no expansion of settings in this release. |
| Aggressive replacement workflows may feel risky without excellent diff/rollback UX. | Rollback queued and revalidated; diff/snapshot placeholders; destructive actions require confirmation. | Rollback and diff UX may be enhanced in future releases. |

---

## 2. Technical Risks (Spec §61.2)

| Risk | Mitigation | Release scope |
|------|------------|---------------|
| Page survivability while retaining rich orchestration metadata. | Built pages are native WordPress content; plugin data (plans, artifacts) separate; uninstall does not delete built pages. | Export/restore and uninstall behavior documented; PORTABILITY_AND_UNINSTALL. |
| Migration complexity across template/schema changes. | Versioned schema (table_schema, export_schema, etc.); migration contract; future-schema block on downgrade. | Migration coverage matrix; no multi-step migrations in current release. |
| Queue reliability on low-quality hosting. | Job queue table; cron for heartbeat and queue processing; reporting failure does not break core. | Document minimum hosting expectations; consider queue health in diagnostics. |

---

## 3. Release-Specific Risks and Limitations

| ID | Category | Description | Mitigation / waiver |
|----|----------|-------------|----------------------|
| *(add per release)* | — | — | — |

Use this section for risks or limitations specific to a release (e.g. "Export of very large plans may timeout on constrained hosting") with mitigation or formal waiver reference.

---

## 4. Cross-References

- **Spec:** §59.15 Production Readiness Phase; §61 Known Risks.
- **Hardening:** [hardening-release-gate-matrix.md](../contracts/hardening-release-gate-matrix.md) (waiver process, sign-off).
- **QA closure:** [release-candidate-closure.md](../qa/release-candidate-closure.md).
- **Compatibility / migration:** [compatibility-matrix.md](../qa/compatibility-matrix.md), [migration-coverage-matrix.md](../qa/migration-coverage-matrix.md).
- **Release notes / changelog:** [release-notes-rc1.md](release-notes-rc1.md), [changelog.md](changelog.md) (operator-facing release content; do not duplicate internal risk detail here).

---

*Update this register when new risks are identified or mitigations change. Keep entries factual and internal.*
