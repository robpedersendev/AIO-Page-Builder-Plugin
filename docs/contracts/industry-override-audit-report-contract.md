# Industry Override Audit Report Contract

**Spec**: industry-override-contract.md; diagnostics/reporting contracts.  
**Status**: Contract. Defines the override audit summary for support and internal diagnostics (Prompt 437).

---

## 1. Purpose

- Provide a **bounded, exportable summary** of current industry overrides for support packets and internal troubleshooting.
- **Audience**: Admin/support only. No public exposure.
- **Use**: Include in support bundle exports or diagnostics snapshots; do not expose override audit data publicly.

---

## 2. Report content

- **Grouping**: By target_type (section, page_template, build_plan_item); optionally by industry_context_ref or "unknown".
- **Per group**: Count of overrides; list of artifact refs (section_key, template_key, or plan_id + item_id) and state (accepted/rejected). Reason notes included only as bounded text (sanitized; no raw user content beyond reason field).
- **No expansion**: Report does not pull in full plan definitions, template bodies, or unrelated internal data. Artifact refs are identifiers only.

---

## 3. Security and access

- **Access**: Admin/support-only. Same capability as override management or diagnostics (e.g. MANAGE_SETTINGS or VIEW_SENSITIVE_DIAGNOSTICS where applicable).
- **Handling**: Reason notes and refs are handled safely; report is bounded and safe for inclusion in support bundles with existing redaction rules.
- **No public surfaces**: Report is never exposed on front-end or unauthenticated endpoints.

---

## 4. Integration

- **Support package**: Optional inclusion of override audit summary in Support_Package_Generator output (e.g. industry_override_audit_summary.json in docs/).
- **Diagnostics snapshot**: Industry_Diagnostics_Service or Support_Triage_State_Builder may include override_summary when the audit report service is available.
- **Build Plan and artifact history** remain authoritative; the report is a read-only snapshot.

---

## 5. Files

- **Service**: plugin/src/Domain/Industry/Reporting/Industry_Override_Audit_Report_Service.php
- **Contract**: docs/contracts/industry-override-audit-report-contract.md
