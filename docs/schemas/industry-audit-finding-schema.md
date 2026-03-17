# Industry Audit Finding Schema (Prompt 585A)

**Spec:** [industry-implementation-audit-entrypoint-map.md](../operations/industry-implementation-audit-entrypoint-map.md); [industry-audit-remediation-ledger.md](../operations/industry-audit-remediation-ledger.md).  
**Purpose:** Machine-readable schema for implementation-audit findings. Used by `plugin/docs/internal/industry-audit-findings.json`. Internal-only; not a runtime contract.

---

## 1. Finding ID format

- **Pattern:** `IND-AUD-NNNN` where NNNN is a zero-padded integer (e.g. IND-AUD-0001, IND-AUD-0002).
- **Uniqueness:** Each finding has exactly one ID; IDs are never reused.
- **Allocation:** Next ID = max(existing IDs) + 1 when appending.

---

## 2. Severity taxonomy

| Value | Meaning |
|-------|--------|
| `critical` | Safety or data integrity; must fix before release. |
| `high` | Correctness or security impact; should fix before release. |
| `medium` | Functional defect or contract deviation; fix or waive. |
| `low` | Polish, consistency, or minor deviation. |
| `informational` | Observation; no mandatory fix. |

---

## 3. Status taxonomy

| Value | Meaning |
|-------|--------|
| `open` | Newly logged; not yet triaged. |
| `triaged` | Severity and release-blocker set; remediation not yet created. |
| `in_progress` | Remediation prompt created and implementation in progress. |
| `blocked` | Blocked on another finding or external dependency. |
| `fixed_pending_verification` | Fix implemented; verification not yet run. |
| `verified` | Fix verified; finding closed. |
| `wont_fix` | Explicitly decided not to fix; rationale recorded. |
| `deferred_post_release` | Deferred to a later release; rationale recorded. |

---

## 4. Finding record structure

Every finding record in the machine-readable tracker must include at least these fields:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `finding_id` | string | Yes | IND-AUD-NNNN. |
| `title` | string | Yes | Short, actionable title. |
| `audit_prompt_number` | string | Yes | Source audit prompt (e.g. "586"). |
| `audit_domain` | string | Yes | Domain from entrypoint map (e.g. bootstrap, registries, profile, admin_ui, recommendation_engines, bundle_resolution, docs_composition, preview_detail, build_plan, simulation, ai, styling, lpagery, export_restore, scaffold_safety, dashboard_reporting, security, performance, release). |
| `summary` | string | Yes | One-paragraph summary. |
| `severity` | string | Yes | One of critical, high, medium, low, informational. |
| `release_blocker` | boolean | Yes | True if finding blocks release per gate. |
| `status` | string | Yes | One of open, triaged, in_progress, blocked, fixed_pending_verification, verified, wont_fix, deferred_post_release. |
| `contracts_referenced` | array of strings | No | Doc paths or contract names. |
| `files_or_services_impacted` | array of strings | No | File paths or service/class names. |
| `reproduction_notes` | string | No | How to reproduce; no secrets. |
| `expected_behavior` | string | No | Per contract/spec. |
| `observed_behavior` | string | No | What actually happens. |
| `root_cause_hypothesis` | string | No | Suspected cause. |
| `recommended_remediation_direction` | string | No | High-level fix direction. |
| `dependency_notes` | string | No | Dependencies on other findings or work. |
| `verification_requirements` | string | No | How to verify the fix. |
| `related_findings` | array of strings | No | Other finding_ids (merged, duplicate, split). |
| `related_remediation_prompt` | string | No | Remediation prompt number if created. |
| `date_opened` | string | Yes | ISO 8601 date or date-time. |
| `date_updated` | string | Yes | ISO 8601; updated when status or fields change. |
| `eligible_for_grouping` | boolean | No | (585B) Whether this finding may be grouped with others. |
| `grouping_notes` | string | No | (585B) Rationale for grouping or not. |
| `remediation_generation_status` | string | No | (585B) e.g. not_started, grouped, single_prompt, deferred. |

---

## 5. No-finding and special records

- **No finding:** An audit prompt may append a record with `finding_id` set to a sentinel (e.g. `IND-AUD-NO-586`) or a dedicated "no material defects" entry with `audit_domain`, `audit_prompt_number`, and `status: verified` (or equivalent) to indicate the domain was reviewed and no material defects were found. Exact convention is defined in the ledger.
- **Merged / superseded / duplicate:** Use `related_findings` to link duplicates; set `status: wont_fix` or a dedicated "merged_into" convention on the superseded finding and reference the canonical finding_id.

---

## 6. References

- [industry-audit-remediation-ledger.md](../operations/industry-audit-remediation-ledger.md)
- [industry-audit-remediation-workflow.md](../operations/industry-audit-remediation-workflow.md)
- [industry-remediation-entry-schema.md](industry-remediation-entry-schema.md)
