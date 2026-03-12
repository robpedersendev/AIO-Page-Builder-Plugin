# Release Review Packet

**Governs:** Spec §59.15 Production Readiness Phase; §60.4 Exit Criteria; §60.5–60.8 Acceptance, Documentation, Demo/Review, Sign-Off.  
**Purpose:** Internal release-review evidence pack for final approval. Traceable to QA, security, compatibility, migration, documentation, and known-risk artifacts. Decision-ready; no claimed evidence without a link.  
**Audience:** Product Owner, Technical Lead, QA, Security (where applicable). Internal only. Do not include secrets or unsafe diagnostics.

---

## 1. Exit criteria (§60.4) — Checklist

| Criterion | Status | Evidence |
|-----------|--------|----------|
| Acceptance tests pass | *Record in §2* | [release-candidate-closure.md](../qa/release-candidate-closure.md) §2; [RELEASE_CHECKLIST.md](../qa/RELEASE_CHECKLIST.md). |
| No critical or high-severity unresolved in scope | *Record in §5* | [release-candidate-closure.md](../qa/release-candidate-closure.md) §4; [sign-off-checklist.md](sign-off-checklist.md) waiver section. |
| Documentation updated | Yes | §60.6 artifacts; see [release-candidate-closure.md](../qa/release-candidate-closure.md) §5, §7; [release-notes-rc1.md](release-notes-rc1.md), [changelog.md](changelog.md), guides. |
| Sign-off recorded | *Record in sign-off checklist* | [sign-off-checklist.md](sign-off-checklist.md) — Product Owner, Technical Lead, QA (Security where applicable). |

---

## 2. Evidence summaries and traceability

### 2.1 QA closure summary

| Area | Summary | Artifact |
|------|---------|----------|
| Performance posture | Bounded list sizes; queue offloading; no global admin asset dump. | [release-candidate-closure.md](../qa/release-candidate-closure.md) §1. |
| Unit / integration / E2E | Test suite; migration/compat scenarios; role/capability audit. | [release-candidate-closure.md](../qa/release-candidate-closure.md) §2. Final run: record pass/fail/waiver in closure §2. |
| Release gates | Security, a11y, performance, migration, compatibility, redaction, documentation, rollback/reporting/portability. | [release-candidate-closure.md](../qa/release-candidate-closure.md) §3; [hardening-release-gate-matrix.md](../contracts/hardening-release-gate-matrix.md) §4.3. |

### 2.2 Security and redaction summary

| Area | Summary | Artifact |
|------|---------|----------|
| Capability enforcement | Admin screens, Build Plan approve/execute/rollback, Queue & Logs, AI Run detail, Onboarding, Export/Import (caller-enforced). | [security-redaction-review.md](../qa/security-redaction-review.md) §1. |
| Nonce coverage | Build Plan steps 1/2/navigation/rollback, Onboarding POST. | [security-redaction-review.md](../qa/security-redaction-review.md) §2. |
| Import/export safety | Permission at caller; ZIP/manifest/schema/path checks; no code execution from package. | [security-redaction-review.md](../qa/security-redaction-review.md) §3, §4. |
| Redaction | Logs, exports, reports free of secrets; rules documented and applied. | [security-redaction-review.md](../qa/security-redaction-review.md) §4, §5. |

### 2.3 Migration and compatibility summary

| Area | Summary | Artifact |
|------|---------|----------|
| Migration | Table schema 1; export schema 1; same-major import; idempotent table install; future-schema blocks activation. | [migration-coverage-matrix.md](../qa/migration-coverage-matrix.md). |
| Compatibility | WP 6.6+; PHP 8.1–8.3; ACF Pro 6.2+, GenerateBlocks 2.0+; preferred GeneratePress. Activation blocked when requirements not met. | [compatibility-matrix.md](../qa/compatibility-matrix.md). |

### 2.4 Accessibility summary

| Area | Summary | Artifact |
|------|---------|----------|
| A11y checklist | Focus, headings, landmarks/ARIA, contrast, forms, keyboard. Remediation applied; manual QA recommended. | [accessibility-remediation-checklist.md](../qa/accessibility-remediation-checklist.md). |

### 2.5 Documentation completion summary (§60.6)

| Artifact | Status | Location |
|----------|--------|----------|
| Changelog | Done | [changelog.md](changelog.md). |
| Release notes | Done | [release-notes-rc1.md](release-notes-rc1.md). |
| User/admin guidance | Done | [admin-operator-guide.md](../guides/admin-operator-guide.md), [end-user-workflow-guide.md](../guides/end-user-workflow-guide.md), [support-triage-guide.md](../guides/support-triage-guide.md). |
| QA notes | Done | [release-candidate-closure.md](../qa/release-candidate-closure.md); RELEASE_CHECKLIST. |
| Spec impacts / implementation notes | Per development | In-repo; no separate doc required for this packet. |

### 2.6 Known-risk register reference

| Purpose | Artifact |
|---------|----------|
| Product/technical risks and mitigations; release-specific limitations | [known-risk-register.md](known-risk-register.md). |

**Rule:** Sensitive or internal-only risk detail stays in the register; release notes and this packet reference it without duplicating confidential content.

---

## 3. Unresolved and waived items

| Status | Description | Where recorded |
|--------|-------------|----------------|
| **Blocked** | Any criterion that blocks release until resolved. | [sign-off-checklist.md](sign-off-checklist.md) — mark gate as Blocked and list. |
| **Waived** | High-severity issues explicitly waived per hardening matrix §5.2 (waiver record with rationale, scope, approver, date). | [hardening-release-gate-matrix.md](../contracts/hardening-release-gate-matrix.md) issue register + waiver record; [sign-off-checklist.md](sign-off-checklist.md) waiver section. |
| **Approved** | Gates and evidence accepted for release. | [sign-off-checklist.md](sign-off-checklist.md) role sign-off sections. |

**Current state (template):** No critical open; no high open in referenced registers. If any high is waived, a waiver record must exist and be linked in the sign-off checklist. Unresolved-but-waived items must be listed explicitly in [sign-off-checklist.md](sign-off-checklist.md).

---

## 4. Demo and sign-off artifacts

| Artifact | Purpose |
|----------|---------|
| [demo-review-walkthrough.md](demo-review-walkthrough.md) | Script or walkthrough outline for formal review/demo to Product Owner (§60.7). QA review for release milestone. |
| [sign-off-checklist.md](sign-off-checklist.md) | Role-by-role approval (Product Owner, Technical Lead, QA, Security where applicable); gate status (blocked/waived/approved); waiver references. |
| [release-candidate-packaging-checklist.md](release-candidate-packaging-checklist.md) | Production ZIP build and validation; required files, exclusions, installability. |
| [private-distribution-handoff.md](private-distribution-handoff.md) | Handoff checklist for private delivery modes (direct ZIP, manual deploy, private update, environment-specific). |
| [final-approval-runbook.md](final-approval-runbook.md) | Go/no-go procedure before packaging and handoff; references sign-off and release notes. |

---

## 5. How to use this packet

1. **Before the review meeting:** Complete [RELEASE_CHECKLIST.md](../qa/RELEASE_CHECKLIST.md); record final run results in [release-candidate-closure.md](../qa/release-candidate-closure.md) §2; ensure every evidence link above resolves to current content.
2. **Sign-off completeness pass:** Every required evidence source in §2 must be linked; every required approver section in [sign-off-checklist.md](sign-off-checklist.md) must exist; every unresolved high must have a waiver record or be closed.
3. **Review meeting:** Use [demo-review-walkthrough.md](demo-review-walkthrough.md) for the demo; use [sign-off-checklist.md](sign-off-checklist.md) to capture approvals and any blocked/waived items.
4. **After approval:** Retain this packet and sign-off checklist as the release approval record. Do not expose internal evidence or waiver detail outside approved channels.

---

*This packet is the conversion of prior closure work into an approvable release-review package. Update evidence links if artifact paths change.*
