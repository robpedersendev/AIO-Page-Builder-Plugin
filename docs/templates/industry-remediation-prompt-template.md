# Industry Remediation Prompt Template (Prompt 585B)

**Purpose:** Reusable template for remediation (fix) prompts generated from the audit finding ledger. Fill every section from the finding(s) and remediation entry. Do not leave sections blank; use "N/A" only where the section explicitly allows it. This template enforces the project's 21-section prompt structure.

---

Copy the block below and fill it. Replace every `{{ ... }}` with values from the ledger/tracker.

---

## Prompt {{remediation_prompt_number}}

**1. Prompt Number**  
Prompt {{remediation_prompt_number}}

**2. Prompt Title**  
{{title}}

**3. Objective**  
{{objective_from_findings}}

**4. Spec Sections to Follow**  
{{contracts_referenced_and_spec_refs}}

**5. Build Bucket**  
{{build_bucket e.g. hardening | reporting | execution}}

**6. Dependency Prerequisites**  
Prompts 318–585; remediation of findings: {{dependency_finding_ids_if_any}}. Prompt 585A (ledger) and 585B (this workflow) must exist.

**7. Why This Comes Next**  
This remediation addresses logged finding(s) {{finding_ids}}. {{brief_rationale}}

**8. Implementation Scope**  
{{implementation_scope_from_remediation_entry}}

**9. Explicit Out of Scope**  
{{explicit_out_of_scope}}

**10. Architecture Constraints to Preserve**  
{{constraints_from_contracts_and_spec}}

**11. Functional Requirements**  
{{requirements_derived_from_findings}}

**12. Data / Schema Requirements**  
{{schema_changes_if_any}}

**13. Security / Permission Requirements**  
{{security_requirements}}

**14. Files to Create**  
{{files_to_create}}

**15. Files to Update**  
{{files_to_update}}

**16. Files to Avoid Touching**  
{{files_to_avoid}}

**17. Implementation Notes**  
Retrofit; prefer additive integration; preserve backwards compatibility. Finding IDs: {{finding_ids}}. Root cause: {{root_cause_summary}}. Verification gate: {{verification_gate}}.

**18. Test Requirements**  
{{verification_requirements}}; update or add tests per finding(s).

**19. Acceptance Criteria**  
Finding(s) {{finding_ids}} can be set to verified after implementation and verification. {{additional_acceptance}}

**20. Do Not Break / Preserve**  
{{preserve_list}}

**21. Output Format Required From Cursor**  
Return output in this exact order: Impact analysis; Dependency map; Implementation plan; Complete code changes; Test updates; Documentation updates; Risk notes. No pseudocode.

---

## Required inputs checklist (from ledger/tracker)

- [ ] finding_ids (at least one)
- [ ] remediation_id (IND-REM-NNNN)
- [ ] title
- [ ] severity_rollup
- [ ] release_blocker_rollup
- [ ] root_cause_summary
- [ ] files_or_services_impacted
- [ ] contracts_referenced
- [ ] verification_requirements / verification_gate
- [ ] grouping_rationale (if grouped)

**Source:** [industry-remediation-prompt-generation-workflow.md](../operations/industry-remediation-prompt-generation-workflow.md); [industry-audit-remediation-ledger.md](../operations/industry-audit-remediation-ledger.md).
