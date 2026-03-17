# Industry Audit Remediation Workflow (Prompt 585A)

**Spec:** [industry-audit-remediation-ledger.md](industry-audit-remediation-ledger.md); [industry-implementation-audit-entrypoint-map.md](industry-implementation-audit-entrypoint-map.md).  
**Purpose:** Operating workflow for running implementation-audit prompts, recording findings, triaging, grouping into remediation prompts, and closing findings. Must be followed so findings are not lost, duplicated, or fixed out of order.

---

## 1. Prerequisites

- **Ledger and tracker exist:** [industry-audit-remediation-ledger.md](industry-audit-remediation-ledger.md); `plugin/docs/internal/industry-audit-findings.json`; `plugin/docs/internal/industry-remediation-tracker.json`.
- **Schemas:** [industry-audit-finding-schema.md](../schemas/industry-audit-finding-schema.md); [industry-remediation-entry-schema.md](../schemas/industry-remediation-entry-schema.md).
- **Entrypoint map:** [industry-implementation-audit-entrypoint-map.md](industry-implementation-audit-entrypoint-map.md) defines audit domains and priority.

---

## 2. Run audit prompt → record findings

1. Run the next implementation-audit prompt (e.g. Prompt 586, 587, …) per the entrypoint map and audit clusters.
2. For each **finding** (defect, drift, security concern, performance issue, UX inconsistency, release blocker):
   - Assign next `finding_id` (IND-AUD-NNNN).
   - Fill required fields per finding schema: title, audit_prompt_number, audit_domain, summary, severity, release_blocker, status (open), contracts_referenced, files_or_services_impacted, reproduction_notes, expected_behavior, observed_behavior, root_cause_hypothesis, recommended_remediation_direction, dependency_notes, verification_requirements, date_opened, date_updated.
   - Append to `industry-audit-findings.json`.
3. If the audit prompt results in **no material defects** for a domain:
   - Record a "no finding" entry per ledger §5 (e.g. IND-AUD-NO-586) so the domain is marked as reviewed.
4. Consider the audit prompt **complete** only after the ledger/tracker is updated.

---

## 3. Triage severity and release-blocker

1. For each new finding in `open` status, set:
   - **Severity:** critical | high | medium | low | informational per schema.
   - **Release blocker:** true if the finding blocks release per gate criteria; else false.
2. Set `status` to `triaged` when done.
3. Update `date_updated`.

---

## 4. Group findings into remediation prompts

1. Use [industry-remediation-prompt-generation-workflow.md](industry-remediation-prompt-generation-workflow.md) (585B) to decide:
   - One finding → one remediation prompt, or
   - Multiple findings → one remediation prompt (with grouping rationale).
2. Create a remediation entry in `industry-remediation-tracker.json`: remediation_id, title, finding_ids, severity_rollup, release_blocker_rollup, dependency_order, status (draft or prompt_generated).
3. Update each finding: `related_remediation_prompt`, `remediation_generation_status`, `date_updated`.

---

## 5. Generate remediation prompt and implement

1. Generate the remediation (fix) prompt from the template using the finding(s) and remediation entry. Every remediation prompt must reference at least one finding_id.
2. Implement the fix per the prompt; do not expand scope beyond the finding(s) and remediation scope. New issues discovered during fix must be logged as separate findings.
3. Update finding(s) to `status: in_progress` while work is in progress; then `fixed_pending_verification` when the fix is implemented.
4. Update remediation entry `status` accordingly.

---

## 6. Verify and close

1. Run verification per `verification_requirements` and remediation `verification_gate`. **A finding may only move from `fixed_pending_verification` to `verified` when the verification gate is satisfied** (no shortcut).
2. If verified: set finding(s) to `status: verified`; set remediation entry to `status: verified`; update `date_updated`.
3. If verification fails: leave status as `fixed_pending_verification` and iterate; or log a new finding if the fix introduced a regression.
4. Status transitions for findings (when remediation is created, implemented, verified) follow [industry-remediation-prompt-generation-workflow.md](industry-remediation-prompt-generation-workflow.md) §6.

---

## 7. Deferred, wont_fix, merged

- **Deferred / wont_fix:** Set finding `status` to `deferred_post_release` or `wont_fix`; document rationale in notes. Do not create a remediation prompt for it (or create one that is explicitly "deferred").
- **Merged / duplicate:** Point `related_findings` to the canonical finding; set superseded finding status to `wont_fix` and note "merged into IND-AUD-XXXX."

---

## 8. Summary flow

```
Run audit prompt (586+) → Record findings (or no-finding) in ledger/JSON
       → Triage severity & release_blocker
       → Group into remediation entries (585B workflow)
       → Generate remediation prompt from template
       → Implement fix
       → Verify
       → Close finding(s) and remediation entry
```

---

## 9. References

- [industry-audit-remediation-ledger.md](industry-audit-remediation-ledger.md)
- [industry-remediation-prompt-generation-workflow.md](industry-remediation-prompt-generation-workflow.md)
- [industry-implementation-audit-entrypoint-map.md](industry-implementation-audit-entrypoint-map.md)
