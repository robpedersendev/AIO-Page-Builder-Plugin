# Sign-Off Checklist

**Governs:** Spec §60.4 Exit Criteria; §60.8 Sign-Off Requirements; §59.15 Production Readiness Phase.  
**Purpose:** Role-by-role approval and gate status for release. Explicit blocked/waived/approved; traceability to evidence.  
**Reference:** [hardening-release-gate-matrix.md](../contracts/hardening-release-gate-matrix.md) §4.3, §6.

---

## 1. Release-gate status (blocked / waived / approved)

Per hardening matrix §4.3. Mark each gate **Approved**, **Waived** (with waiver_id), or **Blocked** (with reason). No gate may be left unstated for release.

| # | Gate | Criterion | Status | Notes / waiver_id |
|---|------|-----------|--------|-------------------|
| 1 | Security | REST/AJAX nonce+capability; no secrets in logs/exports; permission callbacks. | ☐ Approved ☐ Waived ☐ Blocked | Evidence: [security-redaction-review.md](../qa/security-redaction-review.md). If waived: waiver_id _______. |
| 2 | Accessibility | Admin UI a11y checklist; no critical a11y open. | ☐ Approved ☐ Waived ☐ Blocked | Evidence: [accessibility-remediation-checklist.md](../qa/accessibility-remediation-checklist.md). If waived: waiver_id _______. |
| 3 | Performance | No blocking regressions; long-running work queued/chunked/scheduled; Plugin Check. | ☐ Approved ☐ Waived ☐ Blocked | Evidence: [release-candidate-closure.md](../qa/release-candidate-closure.md) §1. If blocked: reason _______. |
| 4 | Migration | Migrations updated; version consistent; upgrade path tested or N/A. | ☐ Approved ☐ Waived ☐ Blocked | Evidence: [migration-coverage-matrix.md](../qa/migration-coverage-matrix.md). If blocked: reason _______. |
| 5 | Compatibility | WP/PHP matrix current; Plugin Check critical/warning addressed. | ☐ Approved ☐ Waived ☐ Blocked | Evidence: [compatibility-matrix.md](../qa/compatibility-matrix.md). If blocked: reason _______. |
| 6 | Redaction | Logs, exports, reports, diagnostics free of secrets; rules applied. | ☐ Approved ☐ Waived ☐ Blocked | Evidence: [security-redaction-review.md](../qa/security-redaction-review.md). If waived: waiver_id _______. |
| 7 | Documentation | §60.6 artifacts; release notes §58.6; user/admin guidance. | ☐ Approved ☐ Waived ☐ Blocked | Evidence: [release-notes-rc1.md](release-notes-rc1.md), [changelog.md](changelog.md), [release-candidate-closure.md](../qa/release-candidate-closure.md) §5, §7, guides. If blocked: reason _______. |
| 8 | Rollback / reporting / portability | Per product promises. | ☐ Approved ☐ Waived ☐ Blocked | Evidence: rollback queued; reporting disclosed; export/restore/uninstall documented. If blocked: reason _______. |

**Blocked:** Release may not proceed until the gate is resolved or converted to a formal waiver (high only).  
**Waived:** Only high severity; waiver record must exist in hardening matrix issue register/waiver record; waiver_id entered above.  
**Approved:** Gate satisfied; no blocker.

---

## 2. Unresolved-but-waived items (explicit list)

List every high-severity issue that is **waived** for this release. Each must have a corresponding waiver record (hardening matrix §5.2).

| waiver_id | issue_id | Short title | Scope (e.g. Release 1.0.0 only) | Signatory |
|-----------|-----------|-------------|----------------------------------|-----------|
| *(none if no waivers)* | — | — | — | — |

If the table is empty and no high-severity issues are open, write: *No waivers for this release.*

---

## 3. Product Owner approval

**Responsibility (per hardening matrix §6):** Scope, user impact, release notes, known limitations.

| Item | Status |
|------|--------|
| Release content and messaging (release notes, changelog, known limitations) acceptable. | ☐ Approved ☐ Rejected |
| Scope and user impact acceptable for this release. | ☐ Approved ☐ Rejected |

**Sign-off:**  
Name: _______________________  
Role: Product Owner  
Date: _______________________  
Signature / approval: _______________________

**Rejected:** Do not proceed; record reason and follow-up in this doc or issue tracker.

---

## 4. Technical Lead approval

**Responsibility (per hardening matrix §6):** Code quality, security/compat/migration/redaction gates, waiver approval.

| Item | Status |
|------|--------|
| All release gates (§1) either Approved or formally Waived; no Blocked. | ☐ Approved ☐ Rejected |
| Technical release readiness (security, compatibility, migration, redaction, performance) acceptable. | ☐ Approved ☐ Rejected |
| Any waivers in §2 reviewed and accepted by Technical Lead. | ☐ Approved ☐ N/A (no waivers) |

**Sign-off:**  
Name: _______________________  
Role: Technical Lead  
Date: _______________________  
Signature / approval: _______________________

---

## 5. QA approval

**Responsibility (per hardening matrix §6):** Test evidence, acceptance criteria, a11y, regression.

| Item | Status |
|------|--------|
| Acceptance tests and evidence (unit, integration, E2E, migration/compat, role/capability) recorded and acceptable. | ☐ Approved ☐ Rejected |
| Accessibility remediation and checklist complete; no critical a11y open. | ☐ Approved ☐ Rejected |
| Regression and quality evidence sufficient for release. | ☐ Approved ☐ Rejected |

**Sign-off:**  
Name: _______________________  
Role: QA  
Date: _______________________  
Signature / approval: _______________________

---

## 6. Security approval (where applicable)

**Responsibility (per hardening matrix §6):** Security review for security-sensitive milestones/releases. Required where the release touches security-sensitive features.

| Item | Status |
|------|--------|
| Security posture and redaction evidence reviewed and acceptable. | ☐ Approved ☐ Rejected ☐ N/A |

**Sign-off (if applicable):**  
Name: _______________________  
Role: Security  
Date: _______________________  
Signature / approval: _______________________

**N/A:** If this release does not require a separate security signatory per your process, mark N/A and ensure Technical Lead sign-off covers security gates.

---

## 7. Release approval summary

Release may proceed only when:

- All gates in §1 are **Approved** or **Waived** (with waiver_id); none **Blocked**.
- Product Owner (§3), Technical Lead (§4), and QA (§5) have **Approved**.
- Security (§6) has **Approved** or **N/A** as applicable.

**Final approval:**  
☐ All required sign-offs obtained. Release approved.  
☐ One or more sign-offs rejected or pending. Release not approved.

Date of final approval: _______________________

---

*Retain this checklist with the [release-review-packet.md](release-review-packet.md) as the release approval record.*
