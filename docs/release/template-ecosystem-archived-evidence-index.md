# Template Ecosystem — Archived Evidence Index

**Spec:** §60.4 Exit Criteria; §60.6 Documentation Completion Requirements; §60.7 Demo/Review; §60.8 Sign-Off; §61.9 Decision Log Structure; §62.11 Section Template Inventory Appendix; §62.12 Page Template Inventory Appendix.  
**Purpose:** Single index of all archived evidence for the expanded template ecosystem. Use for internal audit, future maintenance, and traceability. Internal only. No secrets or unsafe artifacts.

**Closure bundle:** This index plus [template-ecosystem-final-closure-summary.md](template-ecosystem-final-closure-summary.md) form the final closure package. Every linked artifact is part of the governed expansion; unresolved items are marked in the closure summary.

---

## 1. Count summaries and inventory appendices

| Artifact | Path | Description |
|----------|------|-------------|
| Count and capacity summary | [template-library-expansion-review-packet.md](template-library-expansion-review-packet.md) §1 | Section ≥ 250 (254), page ≥ 500 (580); evidence links. |
| Inventory manifest (counts, batches) | [template-library-inventory-manifest.md](../contracts/template-library-inventory-manifest.md) | §7.1 section, §7.2 page counts; batch progress SEC-01–SEC-09, PT-01–PT-14. |
| Coverage matrix | [template-library-coverage-matrix.md](../contracts/template-library-coverage-matrix.md) | Category minimums, max share, coverage worksheet. |
| Section template inventory appendix | [section-template-inventory.md](../appendices/section-template-inventory.md) | Generated from live registry; §62.11. Section_Inventory_Appendix_Generator. |
| Page template inventory appendix | [page-template-inventory.md](../appendices/page-template-inventory.md) | Generated from live registry; §62.12. Page_Template_Inventory_Appendix_Generator. |

---

## 2. QA and compliance reports

| Artifact | Path | Description |
|----------|------|-------------|
| Compliance matrix | [template-library-compliance-matrix.md](../qa/template-library-compliance-matrix.md) | Rule families (COUNT, CATEGORY, CTA_*, SEMANTIC, ANIMATION, PREVIEW, ACF, LPAGERY, EXPORT); severity; evidence. |
| Automated compliance report | [template-library-automated-compliance-report.md](../qa/template-library-automated-compliance-report.md) | How to run Template_Library_Compliance_Service::run(); result format. |
| Accessibility audit report | [template-library-accessibility-audit-report.md](../qa/template-library-accessibility-audit-report.md) | Semantic/accessibility/CTA rule codes; Template_Accessibility_Audit_Service. |
| Animation fallback report | [template-library-animation-fallback-report.md](../qa/template-library-animation-fallback-report.md) | Animation tier, reduced-motion; Animation_QA_Service; manual checklist. |
| End-to-end acceptance report | [template-ecosystem-end-to-end-acceptance-report.md](../qa/template-ecosystem-end-to-end-acceptance-report.md) | Acceptance scenarios and evidence. |

---

## 3. Performance, migration, and compatibility

| Artifact | Path | Description |
|----------|------|-------------|
| Admin performance hardening | [template-admin-performance-hardening-report.md](../qa/template-admin-performance-hardening-report.md) | MAX_PER_PAGE 50, preview cache 800, compare 10, compositions 100; Large_Library_Query_Service. |
| Template library compatibility report | [template-library-compatibility-report.md](../qa/template-library-compatibility-report.md) | Directory, previews, builds, ACF, GenerateBlocks, LPagery, themes; checklist. |
| Compatibility matrix | [compatibility-matrix.md](../qa/compatibility-matrix.md) | WP/PHP, dependencies, theme, extension pack; §9 multisite site-level; template library §13. |
| Migration coverage report | [template-library-migration-coverage-report.md](../../plugin/docs/qa/template-library-migration-coverage-report.md) | Upgrade paths, appendix regen, version/deprecation continuity. (Under plugin/docs/qa if present.) |
| Migration coverage matrix | [migration-coverage-matrix.md](../qa/migration-coverage-matrix.md) | Version keys, upgrade flow. |
| Multisite site isolation | [template-ecosystem-multisite-site-isolation-report.md](../qa/template-ecosystem-multisite-site-isolation-report.md) | Site-level-only evidence; compare list fix; no cross-site leakage. |

---

## 4. Security and redaction

| Artifact | Path | Description |
|----------|------|-------------|
| Template ecosystem security/redaction review | [template-ecosystem-security-redaction-review.md](../qa/template-ecosystem-security-redaction-review.md) | Preview payloads, support summary, export, capability checks; redaction and prohibited-key checks. |
| Security redaction review (general) | [security-redaction-review.md](../qa/security-redaction-review.md) | Broader reporting and export redaction. |

---

## 5. Support and reporting

| Artifact | Path | Description |
|----------|------|-------------|
| Template library support guide | [template-library-support-guide.md](../guides/template-library-support-guide.md) | Diagnostics table, appendices, compliance/compatibility refs, support bundle, safe troubleshooting. |
| Operator guide | [template-library-operator-guide.md](../guides/template-library-operator-guide.md) | Screens, directory, compare, detail, compositions, maintenance/release links. |
| Editor guide | [template-library-editor-guide.md](../guides/template-library-editor-guide.md) | Template choice, one-pagers. |
| Install notification / reporting | [install-notification-email-template.md](../appendices/install-notification-email-template.md) | template_library_report_summary in install payload. |

---

## 6. Release and sign-off artifacts

| Artifact | Path | Description |
|----------|------|-------------|
| Expansion review packet | [template-library-expansion-review-packet.md](template-library-expansion-review-packet.md) | Counts, category, CTA, preview, appendix, a11y, performance, versioning, planner; evidence table; go/no-go. |
| Expansion sign-off checklist | [template-library-expansion-sign-off-checklist.md](template-library-expansion-sign-off-checklist.md) | Expansion criteria; waivers; role approval. |
| Release notes addendum | [template-library-release-notes-addendum.md](template-library-release-notes-addendum.md) | Counts, screens, preview, CTA, compositions, compatibility, migration, limitations. |
| Release candidate addendum | [template-library-release-candidate-addendum.md](template-library-release-candidate-addendum.md) | Packaging completeness, artifact completeness, template-library go/no-go. |
| Known-risk register | [known-risk-register.md](known-risk-register.md) | TLE-1–TLE-4 template-library risks; mitigations; cross-refs. |

---

## 7. Decision log and maintenance SOPs

| Artifact | Path | Description |
|----------|------|-------------|
| Template library decision log | [template-library-decision-log.md](template-library-decision-log.md) | §61.9 structure; entries; deprecation record format; post-release revision intake linkage. |
| Maintenance runbook | [template-ecosystem-maintenance-runbook.md](../operations/template-ecosystem-maintenance-runbook.md) | Add/deprecate/version, appendices, compliance/accessibility/animation, escalation, decision log, support triage, post-release revision intake. |
| Release SOP | [template-ecosystem-release-sop.md](../operations/template-ecosystem-release-sop.md) | Pre-release appendix regen, compliance gate, sign-off, release notes, post-release evidence. |
| Revision intake template | [template-ecosystem-revision-intake-template.md](../operations/template-ecosystem-revision-intake-template.md) | Structured revision proposal; evidence; escalation categories; decision log/revision history linkage. |
| Post-release review cadence | [template-ecosystem-post-release-review-cadence.md](../operations/template-ecosystem-post-release-review-cadence.md) | Review timing; what to collect; finding → intake → decision log; traceability example. |

---

## 8. Contracts and schemas (authoritative)

| Artifact | Path | Description |
|----------|------|-------------|
| Template preview and dummy data | [template-preview-and-dummy-data-contract.md](../contracts/template-preview-and-dummy-data-contract.md) | Preview safety; synthetic data. |
| CTA sequencing and placement | [cta-sequencing-and-placement-contract.md](../contracts/cta-sequencing-and-placement-contract.md) | CTA rules for pages/compositions. |
| Template library scale extension | [template-library-scale-extension-contract.md](../contracts/template-library-scale-extension-contract.md) | Scale and large-library behavior. |

---

## 9. Evidence-link completeness

All paths above are relative to this document (`docs/release/`). Before closure:

- Confirm every linked file exists at the given path (or the documented alternate, e.g. migration report under `plugin/docs/qa`).
- If an artifact is moved or renamed, update this index and the closure summary.
- Unresolved issues (waived, deferred, blocked) are listed in [template-ecosystem-final-closure-summary.md](template-ecosystem-final-closure-summary.md); no false completion claims.
