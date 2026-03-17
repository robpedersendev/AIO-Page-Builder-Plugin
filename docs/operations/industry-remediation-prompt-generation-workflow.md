# Industry Remediation Prompt Generation Workflow (Prompt 585B)

**Spec:** [industry-audit-remediation-ledger.md](industry-audit-remediation-ledger.md); [industry-audit-remediation-workflow.md](industry-audit-remediation-workflow.md); [industry-audit-finding-schema.md](../schemas/industry-audit-finding-schema.md); [industry-remediation-entry-schema.md](../schemas/industry-remediation-entry-schema.md).  
**Purpose:** Formal workflow for converting logged audit findings into remediation (fix) prompts. Every remediation prompt must trace back to one or more finding IDs. Use this workflow and the [industry-remediation-prompt-template.md](../templates/industry-remediation-prompt-template.md) so remediation prompts stay consistent, dependency-aware, and within scope.

---

## 1. Strict rule: no remediation without a finding

- **No remediation prompt may exist without at least one `finding_id`** from the audit finding ledger.
- If a fix is needed that was not logged as a finding, log the finding first (with audit_prompt_number set to the audit that would have caught it, or a dedicated "ad-hoc" convention), then generate the remediation prompt.

---

## 2. One finding vs grouped findings

- **One finding → one remediation prompt:** Use when the fix is isolated, the root cause is single, and dependency order is clear.
- **Multiple findings → one remediation prompt:** Use when findings share:
  - **Subsystem** (e.g. same registry or same screen), or
  - **Root cause** (same underlying defect), or
  - **Dependency order** (must be fixed together), or
  - **Verification strategy** (same test or manual check), or
  - **Risk profile** (same severity and release-blocker level).
- Document **grouping rationale** in the remediation entry (`grouping_rationale`). Do not group findings from unrelated domains or with different severity/blocker levels unless explicitly justified.

---

## 3. Severity and release-blocker rollup

- **Severity rollup:** When grouping, remediation severity = highest among linked findings: critical > high > medium > low > informational.
- **Release-blocker rollup:** Remediation is a release blocker if **any** linked finding has `release_blocker: true`.

---

## 4. Dependency order

- Set `dependency_order` on the remediation entry so that:
  - Remediations that fix bootstrap or registries run before remediations that depend on them.
  - Remediations that fix a finding A which depends on finding B run after B's remediation.
- When generating remediation prompts, generate and implement in dependency order (lowest `dependency_order` first).

---

## 5. Required remediation prompt inputs

When filling the [industry-remediation-prompt-template.md](../templates/industry-remediation-prompt-template.md), the following must be supplied from the ledger/tracker:

| Input | Source |
|-------|--------|
| Finding IDs | `finding_ids` or `source_finding_ids` from remediation entry. |
| Affected subsystem | `audit_domain` and `files_or_services_impacted` from finding(s). |
| Severity | `severity_rollup` from remediation entry. |
| Release-blocker status | `release_blocker_rollup` from remediation entry. |
| Root cause summary | `root_cause_summary` (remediation entry) or `root_cause_hypothesis` (finding). |
| Impacted files/services | `files_or_services_impacted` from finding(s). |
| Governing contracts | `contracts_referenced` from finding(s). |
| Verification requirements | `verification_requirements` (finding) and `verification_gate` (remediation entry). |

---

## 6. Status transitions for findings

- When a **remediation prompt is created:** Update finding(s) with `related_remediation_prompt`, `remediation_generation_status` (e.g. `single_prompt` or `grouped`), `date_updated`. Optionally set `status` to `in_progress` when implementation starts.
- When **implementation is complete:** Set finding(s) to `status: fixed_pending_verification`; update remediation entry to same.
- When **verification passes:** Set finding(s) to `status: verified`; set remediation entry to `status: verified`. A finding may only move from `fixed_pending_verification` to `verified` when verification requirements are met (no shortcut).
- When **remediation is deferred:** Set finding(s) to `deferred_post_release` or `wont_fix`; document rationale; do not generate a fix prompt (or generate one marked deferred).

---

## 7. Deferred, merged, split, duplicate, superseded

- **Deferred / wont_fix:** No remediation prompt generated; finding remains in ledger with status and rationale.
- **Merged:** Superseded finding points to canonical finding via `related_findings`; its status is `wont_fix` with note "merged into IND-AUD-XXXX." Only the canonical finding gets a remediation prompt.
- **Duplicate:** Same as merged; one canonical finding, one remediation.
- **Superseded:** Same as merged.
- **Split:** One finding may be split into two remediations only if scope is clearly split and both remediation entries reference the same finding_id with a note that the fix was split (e.g. "Part 1 of 2"). Prefer one remediation per finding unless split is justified.

---

## 8. New findings during remediation

- If **new issues** are discovered while implementing a fix, **do not** silently expand the remediation scope. Log them as **new findings** in the ledger (new finding_id, audit_prompt_number can reference the remediation prompt or "discovered_during_remediation"), then create separate remediation entries/prompts for them. This keeps dependency and verification clear.

---

## 9. Prompt numbering for remediation prompts

- Remediation prompts continue the project prompt sequence (e.g. 586, 587, … for audit; remediation prompts may be 590, 591, … or interleaved per project convention). Document the chosen convention in the ledger or this workflow.
- Store the assigned **remediation_prompt_number** in the remediation entry so the tracker and ledger stay aligned.

---

## 10. Standard workflow summary

1. **Identify findings** — From ledger/JSON; filter by status (e.g. triaged, open).
2. **Group or isolate** — Apply §2 rules; create or update remediation entry in tracker; set `finding_ids`, `severity_rollup`, `release_blocker_rollup`, `dependency_order`, `grouping_rationale`, `root_cause_summary`, `verification_gate`.
3. **Assign remediation ID** — IND-REM-NNNN; assign prompt number.
4. **Generate prompt** — Fill [industry-remediation-prompt-template.md](../templates/industry-remediation-prompt-template.md) from finding(s) and remediation entry.
5. **Implement fix** — Per prompt; no scope creep; log new findings separately.
6. **Update ledger/tracker** — Finding(s) to fixed_pending_verification; remediation entry status updated.
7. **Verify** — Run verification per `verification_gate`; if pass, set finding(s) and remediation to verified.
8. **Close** — Finding(s) and remediation entry closed.

---

## 11. References

- [industry-audit-remediation-ledger.md](industry-audit-remediation-ledger.md)
- [industry-audit-remediation-workflow.md](industry-audit-remediation-workflow.md)
- [industry-remediation-prompt-template.md](../templates/industry-remediation-prompt-template.md)
- [industry-audit-finding-schema.md](../schemas/industry-audit-finding-schema.md)
- [industry-remediation-entry-schema.md](../schemas/industry-remediation-entry-schema.md)
