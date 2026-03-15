# Industry Approval Snapshot Contract

**Spec**: Build Plan approval and artifact sections in the master spec; industry-build-plan-scoring-contract.md; industry-execution-safeguard-contract.md.

**Status**: Defines the bounded industry context snapshot captured at Build Plan approval/execution-request time for traceability and execution safeguards (Prompt 374).

---

## 1. Purpose

- **Capture a stable snapshot** of the industry context used when a Build Plan becomes approved for execution so that later execution, rollback, diagnostics, and support flows can understand which industry state influenced the plan.
- **Keep payload bounded and exportable**: No large content or full registry dumps; only refs and short summaries.
- **Support execution safeguard**: Execution uses this snapshot (or plan item payloads derived at planning time), not live industry state.

---

## 2. When to capture

- **At first execution request**: When the site requests bulk execution for a plan (e.g. `Execution_Queue_Service::request_bulk_execution`), if the plan definition does not yet contain an industry approval snapshot, capture current industry context and merge it into the definition before building envelopes. Persist the updated definition.
- **Alternative**: If the product adds an explicit "Approve plan" action that transitions root status from `pending_review` to `approved`, the snapshot may be captured at that transition and stored in the definition. Until then, capture at first execution request is the reference implementation.

---

## 3. Snapshot schema (bounded)

Stored under root key `industry_approval_snapshot` (Build_Plan_Schema::KEY_INDUSTRY_APPROVAL_SNAPSHOT). All fields optional for safe fallback.

| Field | Type | Description |
|-------|------|-------------|
| **primary_industry_key** | string | Primary industry key at capture time. |
| **secondary_industry_keys** | list\<string\> | Secondary industry keys at capture time. |
| **active_pack_refs** | list\<string\> | Industry keys for which a pack was active (primary + secondary with active pack). |
| **override_refs_summary** | string or null | Short summary of override usage (e.g. "section:2, template:1") or null. |
| **weighted_resolution_summary** | string or null | E.g. "primary_only", "primary_secondary_aligned", "primary_secondary_conflicts:1". |
| **style_preset_ref** | string or null | Applied style preset key at capture time. |
| **lpagery_posture_summary** | string or null | Short LPagery posture (e.g. "central", "local", "none"). |
| **captured_at** | string | ISO 8601 timestamp of capture. |

- No secrets; no raw user content; admin/export safe.
- Safe failure: if profile or registries are missing, produce minimal snapshot (e.g. captured_at only) or omit snapshot; do not block execution.

---

## 4. Builder and storage

- **Industry_Approval_Snapshot_Builder**: Builds the snapshot array from current profile (Industry_Profile_Repository), optional pack registry, optional override counts, optional preset service, optional LPagery advisor. Returns associative array conforming to the schema above.
- **Persistence**: The caller (e.g. Execution_Queue_Service) merges the snapshot into the plan definition under KEY_INDUSTRY_APPROVAL_SNAPSHOT and persists via Build_Plan_Repository::save_plan_definition (or equivalent). Plan_State_For_Execution_Interface may expose save_plan_definition for this purpose.

---

## 5. Files

- **Contract**: docs/contracts/industry-approval-snapshot-contract.md (this file).
- **Builder**: plugin/src/Domain/Industry/AI/Industry_Approval_Snapshot_Builder.php.
- **Schema**: Build_Plan_Schema::KEY_INDUSTRY_APPROVAL_SNAPSHOT (optional root field).
- **Integration**: Execution_Queue_Service (optional snapshot builder; capture when definition lacks snapshot, then save).

---

## 6. Related

- industry-execution-safeguard-contract.md: Execution must not read live industry state; snapshot in the artifact supports traceability.
- industry-build-plan-scoring-contract.md: Scoring runs at plan generation; snapshot freezes the context used for approval/execution.
