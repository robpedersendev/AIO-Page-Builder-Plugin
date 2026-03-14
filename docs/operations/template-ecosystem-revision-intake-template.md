# Template Ecosystem Revision Intake Template

**Spec:** §0.13 Revision History; §0.14 Change Approval Process; §61.9 Decision Log Structure; §61.10 Escalation Rules; §59.15 Production Readiness Phase.  
**Purpose:** Structured format for proposing template-ecosystem changes so real-world findings (analytics, support, compatibility, recommendation quality) become governed revisions instead of ad hoc drift. Internal use only.

**Rule:** No change may bypass this intake when it affects template families, registry rules, compliance, or documented behavior. Security/privacy issues must be escalated explicitly (§61.10) and not mixed into generic backlog.

---

## 1. Intake header

| Field | Value |
|-------|--------|
| **Revision intake ID** | RI-XXX (sequential) |
| **Date submitted** | YYYY-MM-DD |
| **Submitted by** | Role or name |
| **Source of finding** | analytics \| support \| QA report \| compatibility run \| recommendation feedback \| other (describe) |

---

## 2. Summary and evidence

| Field | Description |
|-------|-------------|
| **One-line summary** | Short description of the proposed change or issue. |
| **Evidence linkage** | Concrete references. At least one required. |
| | • Support: ticket ref, support bundle ref, or template-library-support-guide §2 symptom. |
| | • Analytics: Build Plan Analytics / Template Analytics screen or exported summary; date range. |
| | • QA: report path (e.g. template-library-compliance-matrix, template-library-accessibility-audit-report, template-library-compatibility-report). |
| | • Compatibility: compatibility-matrix, template-library-compatibility-report, or environment validator outcome. |
| **Detailed description** | What was observed; why it warrants a change; what “done” looks like. |

---

## 3. Change classification (per §0.14)

| Type | Use when |
|------|----------|
| Editorial | Wording, formatting, clarity only; no behavior change. |
| Clarification | Makes requirement explicit without materially changing behavior. |
| Functional | Adds, removes, or modifies product behavior or UX. |
| Architectural | Affects structure, data model, rendering, execution, or integration. |
| Security or compliance | Permissions, reporting, privacy, secret handling, compliance. |
| Scope | Redefines in/out of scope, deferred, or required. |

**Selected type:** _______________  
**Justification (one line):** _______________

---

## 4. Escalation category

| Category | Escalate to | When to use |
|----------|-------------|-------------|
| **Security / privacy** | Product Owner + security reviewer | Template payloads, support bundle content, reporting, permissions, or data exposure. Do not treat as generic backlog. |
| **Compatibility** | Technical Lead; PO if scope | Theme/plugin/env mismatch, preview/export/restore failure, dependency version. |
| **UX** | Product Owner | Directory, compare, preview, composition UX; recommendation quality; discoverability. |
| **Release-blocking** | Formal milestone review | Blocks release or sign-off until resolved or waived. |
| **Other** | Technical Lead or PO by default | Policy, deprecation, category coverage, documentation-only. |

**Selected category:** _______________  
**Escalation path chosen:** _______________

---

## 5. Proposed change

| Field | Content |
|-------|---------|
| **Proposed change (spec/contract/doc/code)** | What exactly should change (section, contract, template key, doc path). |
| **Impacted section keys** | Section template internal_keys affected, or “none” / “policy only”. |
| **Impacted page template keys** | Page template internal_keys affected, or “none” / “policy only”. |
| **Alternatives considered** | What was weighed and not chosen (brief). |

---

## 6. Approval and traceability

| Field | Content |
|-------|---------|
| **Decision log entry** | When approved: Decision ID (e.g. DL-002) assigned; entry added to [template-library-decision-log.md](../release/template-library-decision-log.md) per §61.9. Use `Template_Deprecation_Service::build_decision_log_entry()` for consistency. |
| **Revision history** | If spec or contract changes: revision number, date, author, summary, approval status per §0.13. |
| **Changelog / release notes** | If user-facing: add to release changelog or release notes per §58.6. |
| **Approval status** | proposed \| approved \| rejected \| deferred |
| **Approved by** | _______________ |
| **Date** | _______________ |

---

## 7. Evidence requirements for template-family changes

For any intake that proposes **adding, deprecating, or changing template families** (section or page):

1. **Compliance:** Run `Template_Library_Compliance_Service::run()` after the change; no unwaived hard-fail. Evidence: compliance result or waiver_id in hardening-release-gate-matrix.
2. **Coverage:** Impact must align with [template-library-coverage-matrix.md](../contracts/template-library-coverage-matrix.md) and [template-library-compliance-matrix.md](../qa/template-library-compliance-matrix.md); document any waiver.
3. **Appendices:** Regenerate section/page inventory appendices after registry change (runbook §2).
4. **Decision log:** Entry in [template-library-decision-log.md](../release/template-library-decision-log.md) with impacted keys and effective version.

Future dependency additions (e.g. new theme/plugin requirement) follow §0.15.14 and the formal change approval process; document in this intake and in the decision log.

---

*Copy this template for each new revision intake. Store completed intakes in a designated location (e.g. docs/operations/intakes/ or project tracking) and link from the decision log entry when approved.*
