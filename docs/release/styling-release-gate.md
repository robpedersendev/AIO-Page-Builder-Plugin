# Styling Subsystem — Release Gate

**Governs:** Styling subsystem (Option A) release readiness; Prompts 242–260.  
**Spec:** §17.10, §18, §18.11, §53.5–53.9, §59.14, §60.4.  
**Contract:** [styling-subsystem-contract.md](../contracts/styling-subsystem-contract.md).  
**Acceptance:** [styling-acceptance-report.md](../qa/styling-acceptance-report.md).  
**Purpose:** Evidence-based checklist for go/no-go on styling as a production-ready expansion. Blockers must be closed or formally waived; deferred enhancements recorded separately.

---

## 1. Release gate checklist

Each item must be **Pass**, **N/A** (with rationale), or **Blocked**. No overstatement of survivability or theme-override guarantees.

| # | Gate | Criterion | Evidence | Status |
|---|------|-----------|----------|--------|
| STY-G1 | Storage | Global and per-entity options exist; schema versioned; options in Option_Names for uninstall cleanup. | Option_Names; Global_Style_Settings_Schema; Entity_Style_Payload_Schema; Uninstall_Cleanup_Service. | *Execute and record.* |
| STY-G2 | UI | Global token and component override screens capability-gated and nonce-protected; per-entity style save on Section/Page Template Detail same. | Global_Style_Token_Settings_Screen; Section/Page_Template_Detail_Screen process_entity_style_save; [styling-security-checklist.md](../security/styling-security-checklist.md). | *Execute and record.* |
| STY-G3 | Sanitization | All style writes pass through normalizer and sanitizer; invalid payloads not persisted; prohibited patterns rejected. | Styles_JSON_Sanitizer_Test; styling-sanitization-rules.md; repositories persist_*_result. | *Execute and record.* |
| STY-G4 | Emission | Frontend, page, and section emitters use only repository/sanitized data; no raw CSS or arbitrary selectors emitted. | Frontend_Style_Enqueue_Service; Page_Style_Emitter; Section_Style_Emitter; contract §3, §9. | *Execute and record.* |
| STY-G5 | Preview / compare | Detail and compare previews receive styling from approved pipeline; synthetic data only. | Preview_Style_Context_Builder; Template Compare; [styling-acceptance-report.md](../qa/styling-acceptance-report.md) §1. | *Execute and record.* |
| STY-G6 | Export/restore | Styling included in export; restore normalizes and sanitizes before persist; invalid package data skipped and logged. | Restore_Pipeline styling case; Export_Generator; [styling-security-review.md](../security/styling-security-review.md) §2. | *Execute and record.* |
| STY-G7 | Security | Capability and nonce on all mutation paths; restore path sanitization; no high-severity styling issues open. | [styling-security-checklist.md](../security/styling-security-checklist.md); [styling-security-review.md](../security/styling-security-review.md). | *Execute and record.* |
| STY-G8 | Lifecycle | Deactivation and uninstall documented; styling options removed on uninstall; built content preserved; theme continuity documented without overstatement. | [styling-portability-and-uninstall.md](../guides/styling-portability-and-uninstall.md); known-risk STY-1. | *Execute and record.* |
| STY-G9 | Documentation | Operator and support docs cover styling behavior, lifecycle, and troubleshooting; known risks and deferred enhancements recorded. | template-library-operator-guide §12, §11; template-library-support-guide §2, §6, §7; known-risk-register §3, §4. | *Execute and record.* |
| STY-G10 | Tests | Unit tests for sanitizer, normalizer, repositories, emitters, cache, preview context; unsafe payload rejected test. | [styling-acceptance-report.md](../qa/styling-acceptance-report.md) §2. | *Execute and record.* |

---

## 2. Blockers vs deferred enhancements

### 2.1 Blockers (must close or waive for release)

- Any **Critical** or **High** severity issue in styling scope per [hardening-release-gate-matrix.md](../contracts/hardening-release-gate-matrix.md).
- Any gate STY-G1–STY-G10 that fails with no N/A rationale and no formal waiver.
- Claimed evidence that does not exist or does not match behavior (e.g. claiming theme override guarantees beyond selector/token-name continuity).

**Current known blockers:** None identified at gate creation. Record any in sign-off and in [sign-off-checklist.md](sign-off-checklist.md) if blocked.

### 2.2 Deferred enhancements (not blockers)

| ID | Description | Recorded in |
|----|-------------|-------------|
| STY-D1 | Extended format validation (e.g. color format strictness beyond prohibited patterns) may be tightened in a later release. | styling-sanitization-rules.md; optional. |
| STY-D2 | Styling-specific a11y audit (forms/labels on global and per-entity screens) may be added; general admin a11y applies. | Release gate STY-G2; no separate styling a11y gate. |

Deferred enhancements do **not** block release. They may be scheduled in backlog or future prompts.

---

## 3. Release-ready determination

- **Release-ready:** All gates Pass or N/A with rationale; no open Critical/High in styling scope; known risks (STY-1) documented and mitigated; operator/support docs updated.
- **Blocked:** One or more gates Blocked or Critical/High open with no waiver. Do not ship styling as production-ready until resolved or waived.
- **Waiver:** A High-severity item may be waived per hardening matrix §3.2, §5.2; waiver record and sign-off required.

---

## 4. Cross-references

| Need | Artifact |
|------|----------|
| Full acceptance scope and test evidence | [styling-acceptance-report.md](../qa/styling-acceptance-report.md) |
| Security checklist and review | [styling-security-checklist.md](../security/styling-security-checklist.md); [styling-security-review.md](../security/styling-security-review.md) |
| Lifecycle and portability | [styling-portability-and-uninstall.md](../guides/styling-portability-and-uninstall.md) |
| Known risks | [known-risk-register.md](known-risk-register.md) §3 STY-1, §4 |
| Hardening and waiver process | [hardening-release-gate-matrix.md](../contracts/hardening-release-gate-matrix.md) |
| Release review packet | [release-review-packet.md](release-review-packet.md) §2.9 (styling) |

*Update gate status when checklist is run; record pass/fail/waiver in sign-off checklist.*
