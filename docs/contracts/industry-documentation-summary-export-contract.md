# Industry Documentation Summary Export Contract

**Spec**: diagnostics/reporting contracts; export/restore docs; support/runbook.  
**Status**: Contract. Defines the internal exportable documentation summary for the industry subsystem (Prompt 458).

---

## 1. Purpose

- Provide a **single bounded, exportable summary** of active industry profile state, pack/subtype/bundle selections, override counts, health-check state, and major warnings for support handoffs and internal migration review.
- **Audience**: Admin/support only. Not public.
- **Use**: Internal review, support triage, migration operators; do not expose publicly or include raw internal payloads or secrets.

---

## 2. Report content (shape)

- **generated_at**: ISO 8601 timestamp.
- **profile_state**: primary_industry, secondary_industries, profile_readiness, selected_starter_bundle_key (or null), industry_subtype_key (or null). No raw profile content.
- **active_pack_refs**: List of industry keys (primary + secondary) currently active.
- **override_summary**: total_count; by_type counts (section, page_template, build_plan_item). No per-item detail; counts only for bounded size.
- **health**: error_count, warning_count; sample_errors and sample_warnings (capped; each item: object_type, key, issue_summary). No unbounded lists.
- **major_warnings**: Combined list of diagnostic snapshot warnings and health warning summaries (capped). Strings only; no secrets.

No secrets, API keys, tokens, or raw user content. Artifact refs and counts only.

---

## 3. Security and access

- **Access**: Admin/support-only. Same capability as diagnostics or support bundle (e.g. VIEW_LOGS / aio_export_data where applicable).
- **Handling**: Output is bounded and safe for internal sharing; no redaction of the summary itself beyond what the service omits by design.
- **No public surfaces**: Export is never exposed on front-end or unauthenticated endpoints.

---

## 4. Integration

- **Support package / runbook**: Optional inclusion of the summary in support bundle or runbook flows (e.g. industry_documentation_summary.json or equivalent). Callers use `Industry_Documentation_Summary_Export_Service::generate()`.
- **CLI / inspection**: Can be consumed by Industry_Inspection_Command_Service or scripts for scripted export.
- **Diagnostics**: Complements (does not replace) Industry_Diagnostics_Service snapshot, Industry_Health_Check_Service::run(), and Industry_Override_Audit_Report_Service::build_report(); composes them into one report.

---

## 5. Files

- **Service**: plugin/src/Domain/Industry/Reporting/Industry_Documentation_Summary_Export_Service.php
- **Contract**: docs/contracts/industry-documentation-summary-export-contract.md
