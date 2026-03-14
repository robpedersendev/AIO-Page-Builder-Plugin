# Template Ecosystem Post-Release Review Cadence

**Spec:** §0.13 Revision History; §0.14 Change Approval Process; §59.15 Production Readiness Phase; §61.9 Decision Log Structure; §61.10 Escalation Rules.  
**Purpose:** Internal cadence for post-release review of the template ecosystem so findings from real usage, support, and analytics feed into governed revisions. Complements [post-release-health-review-template.md](../qa/post-release-health-review-template.md) and [template-ecosystem-maintenance-runbook.md](template-ecosystem-maintenance-runbook.md).

**Audience:** Internal operators and maintainers. No customer-facing or ungoverned changes.

---

## 1. Review cadence

| When | Activity |
|------|----------|
| **First 2–4 weeks after release** | Initial post-release health review using [post-release-health-review-template.md](../qa/post-release-health-review-template.md). Capture domain health (reporting, queue, build_plan_review, ai_run_validity, rollback, import_export, support_package). Export summary to JSON and retain. |
| **Same window** | Template-ecosystem-specific pass: review support triage for template-related issues ([template-library-support-guide.md](../guides/template-library-support-guide.md)); review Build Plan Analytics and Template Analytics (if available) for template selection and recommendation patterns; check compatibility matrix and any template-library compatibility reports. |
| **Ongoing (e.g. monthly or per milestone)** | Re-run template-ecosystem pass when support volume or analytics suggest template usage pain, compatibility gaps, or recommendation quality issues. |

No fixed automation is required; the cadence is procedural and driven by the review template and runbook.

---

## 2. What to collect

| Source | What to collect | Where it lives |
|--------|-----------------|----------------|
| **Post-release health** | Overall status, domain scores, recommended investigation items, deep-link verification, exported JSON summary. | [post-release-health-review-template.md](../qa/post-release-health-review-template.md); Post-Release Health screen. |
| **Support** | Template-related tickets: directory slowness, compare full, preview blank/error, composition validation, template not found, deprecated behavior, export/restore mismatch. Symptom → check mapping per support guide §2. | [template-library-support-guide.md](../guides/template-library-support-guide.md); support bundles (template_library_support_summary). |
| **Analytics** | Build Plan approval/denial trends, execution failure trends, template selection or recommendation patterns if exposed. | Build Plan Analytics screen; Template Analytics screen; exported summaries. |
| **Compatibility** | Environment validator outcomes; theme/plugin conflicts; preview or export failures by environment. | [compatibility-matrix.md](../qa/compatibility-matrix.md); [template-library-compatibility-report.md](../qa/template-library-compatibility-report.md). |
| **QA / compliance** | Compliance run results, accessibility audit, animation QA. | [template-library-compliance-matrix.md](../qa/template-library-compliance-matrix.md); [template-library-automated-compliance-report.md](../qa/template-library-automated-compliance-report.md); runbook §3. |

Findings that warrant a **change** to the template ecosystem (spec, contract, registry rules, templates, or docs) must be turned into a **revision intake** (§3), not applied ad hoc.

---

## 3. From finding to revision and decision record

1. **Capture:** Record the finding with evidence (support ref, analytics date range, QA report path, or compatibility run).
2. **Triage:** Classify by escalation category (security, compatibility, UX, release-blocking, other) per [template-ecosystem-revision-intake-template.md](template-ecosystem-revision-intake-template.md) §4. Security/privacy issues go to Product Owner + security reviewer; do not mix into generic backlog.
3. **Intake:** Fill [template-ecosystem-revision-intake-template.md](template-ecosystem-revision-intake-template.md) (revision intake ID, source, summary, evidence linkage, change type, escalation category, proposed change, impacted keys).
4. **Approval:** Follow §0.14 change approval process. Approval authority per change type; no shortcut may silently override the spec.
5. **Decision log:** When approved, add an entry to [template-library-decision-log.md](../release/template-library-decision-log.md) per §61.9. Use `Template_Deprecation_Service::build_decision_log_entry()` for consistency. Link the decision ID to the revision intake ID.
6. **Revision history:** If the change affects the spec or an authoritative contract, update the revision history per §0.13 (revision number, date, author, summary, approval status).
7. **Implementation:** Implement the change (code, registry, or doc) in line with the runbook (§1–§2 for add/deprecate/version and appendices; §3 for compliance/accessibility/animation). Regenerate appendices and run compliance if template families changed.

---

## 4. Procedural traceability example

**Hypothetical:** Support reports “preview blank for section template X when using theme Y.”

| Step | Action | Artifact |
|------|--------|----------|
| 1. Capture | Support triage per template-library-support-guide §2: preview blank → check ACF/GenerateBlocks, compatibility report. Finding: theme Y not in validated set; preview pipeline fails for one section. | Support bundle ref; [template-library-compatibility-report.md](../qa/template-library-compatibility-report.md). |
| 2. Triage | Category: **Compatibility**. Escalate to Technical Lead. Not security. | Escalation path chosen. |
| 3. Intake | Fill revision intake: source = support; evidence = support bundle + compatibility report; change type = clarification or functional (e.g. “Document theme Y limitation” or “Add fallback for theme Y”). Proposed change: update compatibility matrix and/or add theme Y to extension pack with evidence, or document as known limitation. Impacted section keys: section X (or “none” if doc-only). | [template-ecosystem-revision-intake-template.md](template-ecosystem-revision-intake-template.md) RI-001. |
| 4. Approval | Product Owner (or Technical Lead for doc-only) approves. | Intake approval status = approved. |
| 5. Decision log | Add entry to template-library-decision-log.md: e.g. DL-002, summary “Document theme Y preview limitation for section X; add to compatibility matrix.” Impacted section keys: [section X]. Effective version: 1. | [template-library-decision-log.md](../release/template-library-decision-log.md) Entries. |
| 6. Revision history | If spec or contract is updated (e.g. compatibility matrix or extension pack), add revision record per §0.13. | Spec or contract revision log. |
| 7. Implementation | Update compatibility-matrix or extension-pack evidence; regenerate appendices only if registry changed (here: no). Run compliance if any template change. | Updated docs; no appendix regen if doc-only. |

This example shows one path from support finding → intake → decision log and (when applicable) revision history. Security findings would use the same intake but escalation category “Security / privacy” and path Product Owner + security reviewer.

---

## 5. Integration with existing docs

| Doc | Role |
|-----|------|
| [post-release-health-review-template.md](../qa/post-release-health-review-template.md) | Structured post-release health review; export summary; tuning and follow-up. |
| [template-ecosystem-maintenance-runbook.md](template-ecosystem-maintenance-runbook.md) | Day-to-day maintenance (add/deprecate/version, appendices, compliance, escalation, decision log). Runbook §8 links to this cadence and the revision-intake template. |
| [template-ecosystem-revision-intake-template.md](template-ecosystem-revision-intake-template.md) | Single revision proposal format; evidence and escalation; decision log and revision history linkage. |
| [template-library-decision-log.md](../release/template-library-decision-log.md) | Record of approved decisions; each approved revision intake that affects the library or its rules should have a corresponding entry. |

No “continuous improvement” without a revision intake and approval. This cadence keeps post-release feedback disciplined and auditable.
