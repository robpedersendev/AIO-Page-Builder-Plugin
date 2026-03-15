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

### 2.7 Template library expansion

| Area | Summary | Artifact |
|------|---------|----------|
| Counts and capacity | Section templates ≥ 250 (achieved 254); page templates ≥ 500 (achieved 580). | [template-library-expansion-review-packet.md](template-library-expansion-review-packet.md) §1; [template-library-inventory-manifest.md](../contracts/template-library-inventory-manifest.md). |
| Category coverage | Section purpose-family and page template_category_class / template_family minimums; max share rules. | [template-library-coverage-matrix.md](../contracts/template-library-coverage-matrix.md); review packet §2. |
| CTA-law compliance | CTA count, bottom CTA, non-adjacent CTAs, non-CTA range. | [template-library-compliance-matrix.md](../qa/template-library-compliance-matrix.md); [template-library-accessibility-audit-report.md](../qa/template-library-accessibility-audit-report.md); review packet §3. |
| Preview, appendix, export | Synthetic preview and cache; section/page inventory appendices; export/restore validators. | Review packet §4; [section-template-inventory.md](../appendices/section-template-inventory.md), [page-template-inventory.md](../appendices/page-template-inventory.md). |
| Accessibility and animation QA | Semantic/a11y audit; animation tier/fallback/reduced-motion QA. | [template-library-accessibility-audit-report.md](../qa/template-library-accessibility-audit-report.md); [template-library-animation-fallback-report.md](../qa/template-library-animation-fallback-report.md); review packet §5. |
| Admin performance hardening | MAX_PER_PAGE 50; preview cache cap; compare/compositions limits. | [template-admin-performance-hardening-report.md](../qa/template-admin-performance-hardening-report.md); review packet §6. |
| Decision log and planner/Build Plan | Versioning/deprecation; Template_Recommendation_Context_Builder; Build_Plan_Template_Explanation_Builder. | [template-library-decision-log.md](template-library-decision-log.md); review packet §7. |
| Sign-off (expansion-specific) | Criteria and role approval for template-library expansion only. | [template-library-expansion-sign-off-checklist.md](template-library-expansion-sign-off-checklist.md). |
| Packaging and approval addendum | Template-library packaging completeness and go/no-go runbook for the expansion. | [template-library-release-candidate-addendum.md](template-library-release-candidate-addendum.md). |

### 2.8 Form provider integration (extension pack)

| Area | Summary | Artifact |
|------|---------|----------|
| Release gate and evidence | Form provider release checklist; UI, functionality, security, diagnostics, export/restore, docs, acceptance. | [form-provider-integration-review-packet.md](form-provider-integration-review-packet.md). |
| Operator and support | Operator guide; support references; known-risk FPR-1, FPR-2. | [form-provider-operator-guide.md](../guides/form-provider-operator-guide.md); [template-library-support-guide.md](../guides/template-library-support-guide.md); [known-risk-register.md](known-risk-register.md). |
| Extension backlog | Next-wave prompts (additional providers, form-list API, auto-provisioning, survivability, maintenance). | [form-provider-extension-backlog.md](form-provider-extension-backlog.md). |

### 2.9 Styling subsystem (Option A expansion)

| Area | Summary | Artifact |
|------|---------|----------|
| Contract and scope | Plugin-owned token/component styling; global and per-entity; whitelist sanitization; no arbitrary CSS/selectors. | [styling-subsystem-contract.md](../contracts/styling-subsystem-contract.md); [css-selector-contract.md](../contracts/css-selector-contract.md). |
| QA acceptance | Storage, UI, rendering, preview, compare, cache, export/restore, security, lifecycle. | [styling-acceptance-report.md](../qa/styling-acceptance-report.md). |
| Release gate | Blockers vs deferred; evidence-based checklist; no overstatement of survivability or theme-override guarantees. | [styling-release-gate.md](styling-release-gate.md). |
| Security | Capability/nonce; restore sanitization; security checklist and review. | [styling-security-checklist.md](../security/styling-security-checklist.md); [styling-security-review.md](../security/styling-security-review.md). |
| Lifecycle and portability | Deactivation/uninstall; theme continuity; export/restore. | [styling-portability-and-uninstall.md](../guides/styling-portability-and-uninstall.md). |
| Known risks and deferred | STY-1 lifecycle; deferred enhancements (e.g. format strictness, styling a11y audit) not blockers. | [known-risk-register.md](known-risk-register.md) §3, §4; [styling-release-gate.md](styling-release-gate.md) §2.2. |
| Operator and support | Styling behavior, troubleshooting, removal/override expectations. | [template-library-operator-guide.md](../guides/template-library-operator-guide.md) §8.1, §11, §12; [template-library-support-guide.md](../guides/template-library-support-guide.md) §2, §6, §7. |

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
| [release-candidate-packaging-checklist.md](release-candidate-packaging-checklist.md) | Production ZIP build and validation; required files, exclusions, installability; §7 template-library expansion. |
| [template-library-release-candidate-addendum.md](template-library-release-candidate-addendum.md) | Template-library packaging completeness and expansion go/no-go; run as part of final approval when release includes expansion. |
| [private-distribution-handoff.md](private-distribution-handoff.md) | Handoff checklist for private delivery modes (direct ZIP, manual deploy, private update, environment-specific). |
| [final-approval-runbook.md](final-approval-runbook.md) | Go/no-go procedure before packaging and handoff; references sign-off and release notes; §2.9 template-library when in scope; §2.9 styling when in scope. |

---

## 5. How to use this packet

1. **Before the review meeting:** Complete [RELEASE_CHECKLIST.md](../qa/RELEASE_CHECKLIST.md); record final run results in [release-candidate-closure.md](../qa/release-candidate-closure.md) §2; ensure every evidence link above resolves to current content.
2. **Sign-off completeness pass:** Every required evidence source in §2 must be linked; every required approver section in [sign-off-checklist.md](sign-off-checklist.md) must exist; every unresolved high must have a waiver record or be closed.
3. **Review meeting:** Use [demo-review-walkthrough.md](demo-review-walkthrough.md) for the demo; use [sign-off-checklist.md](sign-off-checklist.md) to capture approvals and any blocked/waived items.
4. **After approval:** Retain this packet and sign-off checklist as the release approval record. Do not expose internal evidence or waiver detail outside approved channels.

---

*This packet is the conversion of prior closure work into an approvable release-review package. Update evidence links if artifact paths change.*
