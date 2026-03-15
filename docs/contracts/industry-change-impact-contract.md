# Industry Change Impact Contract

**Spec**: Rollback sections of aio-page-builder-master-spec.md; industry-approval-snapshot-contract.md; industry-execution-safeguard-contract.md.

**Status**: Defines non-destructive change-impact behavior when the active Industry Profile diverges from the approved snapshot used for prior plans/builds (Prompt 375).

---

## 1. Purpose

- **Detect divergence** between live industry state and the industry context captured at Build Plan approval/execution-request time (industry_approval_snapshot).
- **Surface warnings** in admin/support contexts so operators know when existing plans or builds are "out of sync" with current industry context—without auto-rebuilding, auto-rollback, or any silent mutation of built content.
- **Integrate with rollback/change-impact reporting** where appropriate: e.g. when showing rollback eligibility or plan history, optionally show that industry profile has changed since the plan was approved.

---

## 2. Principles

- **Non-destructive**: No automatic rebuild, rollback, or content change when profile diverges. Built content remains stable until an approved new plan says otherwise.
- **Visible**: Industry profile changes should be visible (warnings, indicators), not silently ignored or acted upon.
- **Bounded**: Warnings are actionable and bounded; no unbounded payloads or secrets. Safe when snapshot is missing (no crash; optional "no snapshot" in result).
- **Rollback remains snapshot-based**: Rollback behavior is unchanged; this contract adds optional *reporting* of industry drift alongside existing rollback/change-impact data.

---

## 3. Divergence detection

- **Inputs**: (1) Current live industry profile (from Industry_Profile_Repository) or a normalized summary; (2) Approved snapshot (from plan definition under KEY_INDUSTRY_APPROVAL_SNAPSHOT), or null if plan has no snapshot.
- **Comparison**: Compare primary_industry_key, secondary_industry_keys (order-independent set), and optionally style_preset_ref / active_pack_refs. If any differ, report divergence.
- **Missing snapshot**: If the plan (or artifact) has no industry_approval_snapshot, treat as "no baseline" — optionally report as informational (e.g. "Industry context at approval was not recorded") rather than as divergence. Do not block or fail.

---

## 4. Change-impact result shape

Additive result (e.g. Industry_Profile_Change_Impact_Result or array) for callers:

| Field | Type | Description |
|-------|------|-------------|
| **has_divergence** | bool | True when live profile differs from approved snapshot. |
| **severity** | string | `info`, `warning`, or `none`. `warning` when primary or secondary industries changed; `info` when only preset/pack refs differ or snapshot missing. |
| **explanation_summary** | string | Short human-readable summary (e.g. "Primary industry changed from X to Y"). |
| **affected_artifact_refs** | list | Optional plan_id or artifact refs this result applies to. |
| **snapshot_missing** | bool | True when no approved snapshot was available for comparison. |

- Admin/support-only; no public exposure of raw payloads.

---

## 5. Integration points

- **Industry_Profile_Change_Impact_Service**: Accepts (live_profile, approved_snapshot_or_null, optional artifact_refs). Returns the result shape above. Safe when snapshot is null or malformed.
- **Rollback / change-impact reporting**: When building rollback UI state or plan history, callers may pass the plan's industry_approval_snapshot (if present) and current profile to the service and merge the result into state (e.g. `industry_profile_divergence` key) for display. No change to rollback eligibility or execution.
- **Support/diagnostics**: Diagnostics or support triage views may list plans (or site-wide) with industry drift and surface the explanation summary. Optional; not required for minimal compliance.

---

## 6. Files

- **Contract**: docs/contracts/industry-change-impact-contract.md (this file).
- **Service**: plugin/src/Domain/Industry/Rollback/Industry_Profile_Change_Impact_Service.php.
- **Consumers**: Rollback_State_Builder or Build Plan UI state (optional enrichment); Industry_Diagnostics_Service or support views (optional).
- **Known risks**: docs/release/known-risk-register.md — add entry for industry profile change and non-destructive warnings.

---

## 7. Related

- industry-execution-safeguard-contract.md: Execution does not read live industry; snapshot in artifact is for traceability. This contract uses that snapshot only for *comparison and reporting*.
- industry-approval-snapshot-contract.md: Schema of the snapshot used as baseline for comparison.
