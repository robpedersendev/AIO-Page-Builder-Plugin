# Final Approval Runbook

**Governs:** Spec §59.15 Production Readiness Phase; §60.4 Exit Criteria; §60.8 Sign-Off Requirements.  
**Purpose:** Go/no-go procedure before release candidate is packaged and handed off. References sign-off artifacts and release notes; decision-ready.

---

## 1. Prerequisites

Before running the final approval:

- [ ] All work for the release candidate (code, docs, evidence) is complete per Prompts 100–116.
- [ ] [release-candidate-packaging-checklist.md](release-candidate-packaging-checklist.md) is available and understood.
- [ ] [release-review-packet.md](release-review-packet.md), [sign-off-checklist.md](sign-off-checklist.md), and [demo-review-walkthrough.md](demo-review-walkthrough.md) exist and are current.
- [ ] [release-notes-rc1.md](release-notes-rc1.md) and [changelog.md](changelog.md) are updated with version and date for the release.

---

## 2. Go/no-go checklist

Execute in order. **No-go** at any step stops the runbook until the issue is resolved or explicitly waived (high only, per hardening matrix).

| # | Gate | Criterion | Go / No-go |
|---|------|-----------|------------|
| 2.1 | Exit criteria (§60.4) | Acceptance tests pass; no critical/high unresolved in scope; documentation updated; sign-off recorded. | ☐ Go ☐ No-go |
| 2.2 | Sign-off | Product Owner, Technical Lead, and QA have approved in [sign-off-checklist.md](sign-off-checklist.md). Security approved or N/A as applicable. | ☐ Go ☐ No-go |
| 2.3 | Release gates | All eight release gates in [sign-off-checklist.md](sign-off-checklist.md) §1 are **Approved** or **Waived** (with waiver_id); none **Blocked**. | ☐ Go ☐ No-go |
| 2.4 | Waivers | Every waived high-severity issue has a waiver record; listed in [sign-off-checklist.md](sign-off-checklist.md) §2. | ☐ Go ☐ No-go |
| 2.5 | Release notes and changelog | Version and date set; compatibility, migration, reporting disclosure, and known limitations reflected. | ☐ Go ☐ No-go |
| 2.6 | Packaging | [release-candidate-packaging-checklist.md](release-candidate-packaging-checklist.md) completed for the build; required files present, exclusions verified, ZIP installability confirmed. | ☐ Go ☐ No-go |
| 2.7 | No secrets in package | ZIP and any attached operator docs contain no credentials, local paths, or internal-only diagnostics. | ☐ Go ☐ No-go |
| 2.8 | Handoff ready | [private-distribution-handoff.md](private-distribution-handoff.md) pre-handoff checklist complete; delivery mode and recipient documented. | ☐ Go ☐ No-go |

---

## 3. Decision

| Outcome | Condition | Next step |
|---------|-----------|-----------|
| **Go** | All steps in §2 are **Go**. | Produce final ZIP per packaging checklist; execute handoff per private-distribution-handoff for the chosen mode; retain approval record. |
| **No-go** | Any step is **No-go**. | Do not package or hand off. Resolve the failing gate (fix, waiver where allowed, or defer release); re-run this runbook from §2. |

**Record:**

- Date of run: _______________________
- Result: ☐ Go  ☐ No-go
- If No-go: first failing step _______________; reason _______________________
- Approver(s) present: _______________________

---

## 4. Post-approval steps (when Go)

| # | Action | Owner |
|---|--------|-------|
| 4.1 | Build the production ZIP per [release-candidate-packaging-checklist.md](release-candidate-packaging-checklist.md) §5. | Technical Lead / release owner |
| 4.2 | Run optional [release_preflight_check.php](../../tools/release_preflight_check.php) against the unpacked directory; attach summary to release record. | Technical Lead / release owner |
| 4.3 | Store the approved ZIP and version tag in release storage. | Release owner |
| 4.4 | Execute handoff per [private-distribution-handoff.md](private-distribution-handoff.md) for the chosen delivery mode. | Release owner |
| 4.5 | Retain [sign-off-checklist.md](sign-off-checklist.md) and this runbook result as the approval record. | Release owner |

---

## 5. Traceability

| Artifact | Role in runbook |
|----------|------------------|
| [release-review-packet.md](release-review-packet.md) | Evidence and exit-criteria linkage; referenced by §2.1. |
| [sign-off-checklist.md](sign-off-checklist.md) | Role sign-offs and gate status; §2.2, §2.3, §2.4. |
| [release-notes-rc1.md](release-notes-rc1.md), [changelog.md](changelog.md) | Release content and version; §2.5. |
| [release-candidate-packaging-checklist.md](release-candidate-packaging-checklist.md) | ZIP validation; §2.6, §4.1. |
| [private-distribution-handoff.md](private-distribution-handoff.md) | Handoff steps; §2.8, §4.4. |
| [known-risk-register.md](known-risk-register.md) | Known limitations; no duplication of internal risk detail in runbook. |

---

*This runbook closes the production-readiness gap between “implemented and reviewed” and “actually shippable.” Do not skip steps; document any waiver or exception.*
