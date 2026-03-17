# Industry Remediation Entry Schema (Prompt 585A / 585B)

**Spec:** [industry-audit-remediation-ledger.md](../operations/industry-audit-remediation-ledger.md); [industry-remediation-prompt-generation-workflow.md](../operations/industry-remediation-prompt-generation-workflow.md).  
**Purpose:** Machine-readable schema for remediation entries that link findings to remediation/fix prompts. Used by `plugin/docs/internal/industry-remediation-tracker.json`. Internal-only; not a runtime contract.

---

## 1. Remediation ID format

- **Pattern:** `IND-REM-NNNN` where NNNN is a zero-padded integer (e.g. IND-REM-0001).
- **Uniqueness:** Each remediation entry has exactly one ID; IDs are never reused.
- **Allocation:** Next ID = max(existing IDs) + 1 when appending.

---

## 2. Remediation entry structure

Every remediation entry in the machine-readable tracker must include at least these fields:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `remediation_id` | string | Yes | IND-REM-NNNN. |
| `title` | string | Yes | Short title for the remediation. |
| `finding_ids` | array of strings | Yes | One or more IND-AUD-NNNN; at least one required. |
| `severity_rollup` | string | Yes | Rollup of finding severities: critical if any critical; else high; else medium; else low; else informational. |
| `release_blocker_rollup` | boolean | Yes | True if any linked finding has release_blocker true. |
| `dependency_order` | integer | No | Order relative to other remediations (lower = earlier). |
| `status` | string | Yes | e.g. draft, prompt_generated, in_progress, fixed_pending_verification, verified, deferred. |
| `owner_or_assignee_placeholder` | string | No | Placeholder for ownership; no runtime use. |
| `implementation_scope` | string | No | Brief scope description. |
| `verification_requirements` | string | No | How to verify the fix. |
| `notes` | string | No | Free-form notes. |
| `remediation_prompt_number` | string | No | (585B) Project prompt number assigned (e.g. "587"). |
| `source_finding_ids` | array of strings | No | (585B) Same as finding_ids; explicit for generation. |
| `grouping_rationale` | string | No | (585B) Why these findings were grouped. |
| `root_cause_summary` | string | No | (585B) Consolidated root cause. |
| `verification_gate` | string | No | (585B) Required verification before closure. |
| `closure_requirements` | string | No | (585B) What must be true to close. |

**585B mapping:** The fields `remediation_prompt_number`, `source_finding_ids` (same as `finding_ids`), `grouping_rationale`, `root_cause_summary`, `verification_gate`, and `closure_requirements` are consumed by the [industry-remediation-prompt-generation-workflow.md](../operations/industry-remediation-prompt-generation-workflow.md) when generating remediation prompts from the template.

---

## 3. Severity rollup rule

When multiple findings are grouped into one remediation:

- `severity_rollup` = highest severity among linked findings: critical > high > medium > low > informational.
- `release_blocker_rollup` = true if any linked finding has `release_blocker` true.

---

## 4. References

- [industry-audit-finding-schema.md](industry-audit-finding-schema.md)
- [industry-audit-remediation-ledger.md](../operations/industry-audit-remediation-ledger.md)
- [industry-remediation-prompt-generation-workflow.md](../operations/industry-remediation-prompt-generation-workflow.md)
