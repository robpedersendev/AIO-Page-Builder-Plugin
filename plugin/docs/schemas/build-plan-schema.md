# Build Plan Object Schema

**Document type:** Implementation-grade schema contract for Build Plan objects (spec §10.4, §30.1–30.5, §30.11–30.12, §59.9).  
**Governs:** Root plan identity, source references, step grouping, item records, dependency/blocking fields, warning/confidence representation, approval/denial and completion semantics, and historical retention before any Build Plan generation or UI is implemented.  
**Related:** object-model-schema.md (§3.4 Build Plan), ai-output-validation-contract.md (normalized output handoff), Build_Plan_Draft_Schema (normalized AI output shape), **build-plan-state-machine.md** (status model, step/item statuses, transitions, completion and denial logic).

---

## 1. Purpose and scope

### 1.1 Purpose of the Build Plan (spec §30.1)

The **Build Plan** is the **operational bridge between validated planning output and approved site-change execution**. It is a **first-class product object**, not a summary blob. The plan:

- Is produced from **normalized validated output** plus local system logic (never from raw AI output).
- Separates **planning** from **execution**; execution acts only on approved plans.
- Is **structured, legible, resumable, and reviewable step by step**.
- Remains **historically readable** after completion; denial is a legitimate outcome.

### 1.2 Build Plan inputs (spec §30.2)

Plans are generated from:

- **AI run reference** — The run that produced the validated output.
- **Normalized output reference** — The validated structure (e.g. run_summary, site_purpose, existing_page_changes, new_pages_to_create, menu_change_plan, design_token_recommendations, seo_recommendations, warnings, assumptions, confidence).
- **Crawl snapshot / site context** — Where applicable (URLs, page classification).
- **Registry and token context** — Templates, compositions, token state where applicable.

Raw AI output and raw provider responses are **not** plan inputs; only normalized validated output and local logic produce the plan.

### 1.3 Scope

**In scope:** Root plan schema, step object schema, item object schema, source reference blocks, warning/assumption/confidence blocks, dependency and blocking fields, approval/denial and completion state logic, history anchors, retention semantics, and ineligibility rules for incomplete plans.

**Out of scope:** Build Plan generation engine, UI screens, execution, rollback, export bundle implementation. No secrets or unauthorized raw provider data in the plan.

---

## 2. Build Plan status model (spec §30.4)

| Status | Meaning | UI / execution |
|--------|---------|----------------|
| `pending_review` | Awaiting user approval or denial | Plan is reviewable; no execution. |
| `approved` | User has approved (full or partial) | Eligible for execution. |
| `rejected` | User has denied the plan | Terminal; no execution. |
| `in_progress` | Execution in progress | Step-by-step execution state. |
| `completed` | Execution completed | Terminal; history retained. |
| `superseded` | Replaced by a newer plan | Terminal; retained for history. |

**Valid transitions:**  
`pending_review` → `approved` | `rejected`  
`approved` → `in_progress` → `completed` | `superseded`  
No reverse from `completed` or `rejected` to `pending_review`. No transition from `rejected` to `approved`.

**Full state machine:** Root, step, and item status enums, transition tables, blocker rules, completion recognition, denial handling, and resumption are defined in **build-plan-state-machine.md**. Code-level constants and transition helpers: `Build_Plan_Statuses`, `Build_Plan_Item_Statuses`.

---

## 3. Step-based UI structure (spec §30.5)

Plans are organized for a **seven-step UI model**. Each step groups one or more **items** (recommendations or actions). Step order is stable and machine-readable.

### 3.1 Step type enum (step_type)

| step_type | Description | Typical source (normalized output) |
|-----------|-------------|-------------------------------------|
| `overview` | Plan summary, site purpose, site flow | run_summary, site_purpose, site_structure |
| `existing_page_changes` | Changes to existing pages | existing_page_changes |
| `new_pages` | New pages to create | new_pages_to_create |
| `hierarchy_flow` | Hierarchy or structure recommendations | site_structure (recommended_top_level_pages, hierarchy_map) |
| `navigation` | Menu / navigation changes | menu_change_plan |
| `design_tokens` | Design token recommendations | design_token_recommendations |
| `seo` | SEO recommendations | seo_recommendations |
| `confirmation` | Final confirmation or denial | — (UI-only; no items from AI) |

The **confirmation** step is the final step in the UI; it does not contain items from normalized output but holds the approval/denial outcome (spec §30.11).

---

## 4. Root plan schema

### 4.1 Required root fields

| Field | Type | Validation | Notes |
|-------|------|------------|--------|
| `plan_id` | string | Non-empty; unique; immutable; e.g. UUID; max 64 chars | Stable plan identifier (object-model: internal key). |
| `status` | string | One of status enum (§2) | Lifecycle status. |
| `ai_run_ref` | string | Non-empty; max 64 chars | Source AI run id (run_id). |
| `normalized_output_ref` | string | Non-empty; max 64 chars | Reference to stored normalized output (e.g. artifact ref or run-scoped id). |
| `plan_title` | string | Non-empty; max 255 chars | Human-readable plan title. |
| `plan_summary` | string | Max 2048 chars | Short summary (e.g. from run_summary.summary_text). |
| `site_purpose_summary` | string | Max 1024 chars | Site purpose summary derived from normalized site_purpose. |
| `site_flow_summary` | string | Max 1024 chars | Site flow / structure summary (e.g. from site_structure.navigation_summary or hierarchy). |
| `steps` | array of step objects | Non-empty; order preserved; each step per §5 | Step-based structure; at least overview and confirmation. |
| `created_at` | string | ISO 8601 datetime | When the plan was created. |

### 4.2 Optional root fields

| Field | Type | Validation | Notes |
|-------|------|------------|--------|
| `profile_context_ref` | string | Max 64 chars | Profile or onboarding snapshot ref at plan creation. |
| `crawl_snapshot_ref` | string | Max 64 chars | Crawl run or snapshot ref used as context. |
| `registry_snapshot_ref` | string | Max 64 chars | Template registry snapshot ref at plan creation. |
| `affected_page_refs` | array of strings | Each max 512 chars (e.g. URLs or post refs) | Pages affected by the plan (for display and execution). |
| `completed_at` | string | ISO 8601 datetime | When plan reached completed or superseded. |
| `actor_refs` | array of strings | User ids or refs; max 64 each | Who created, approved, or executed. |
| `approval_denial_state` | string | One of: `pending`, `approved`, `rejected`, `partial` | User decision state (spec §30.11). |
| `remaining_work_status` | string | E.g. `review`, `ready`, `executing`, `done` | For UI and resumability. |
| `execution_status` | string | Aligned with status or substep state | Execution progress. |
| `warnings` | array of warning objects | Per §7 | Plan-level warnings. |
| `assumptions` | array of assumption objects | Per §7 | Plan-level assumptions. |
| `confidence` | object | Per §7 | Overall confidence notes. |
| `execution_history_anchor` | string | Max 64 chars | Reference to execution log or history record (placeholder until execution exists). |
| `history_retention` | string | One of: `retain`, `archive`, `policy` | Completion retention semantics (spec §30.12). |
| `schema_version` | string | Non-empty; max 16 chars | Build plan schema version for migration. |

**Note:** Legacy or alternate names such as `generated_site_purpose_summary` may map to `site_purpose_summary`; `step_based_recommendations` to `steps`. This schema uses the names above as canonical.

---

## 5. Step object schema

Each element of `steps` has the following shape.

### 5.1 Required step fields

| Field | Type | Validation | Notes |
|-------|------|------------|--------|
| `step_id` | string | Non-empty; unique within plan; max 64 chars | Stable step identifier. |
| `step_type` | string | One of step_type enum (§3.1) | overview, existing_page_changes, new_pages, hierarchy_flow, navigation, design_tokens, seo, confirmation. |
| `title` | string | Non-empty; max 255 chars | Step title for UI. |
| `order` | integer | Non-negative | Display and execution order (0-based). |
| `items` | array of item objects | Per §6; may be empty for overview/confirmation | Items (recommendations or actions) in this step. |

### 5.2 Optional step fields

| Field | Type | Notes |
|-------|------|--------|
| `summary` | string | Max 512 chars; step-level summary. |
| `status` | string | E.g. `pending`, `reviewed`, `approved`, `skipped` for step-level UI. |

---

## 6. Item object schema

Each item represents one recommendation or action (e.g. one existing-page change, one new page, one menu change).

### 6.1 Required item fields

| Field | Type | Validation | Notes |
|-------|------|------------|--------|
| `item_id` | string | Non-empty; unique within plan; max 64 chars | Stable item identifier. |
| `item_type` | string | One of: `existing_page_change`, `new_page`, `menu_change`, `design_token`, `seo`, `hierarchy_note`, `overview_note`, `confirmation` | Aligned with normalized output sections. |
| `payload` | object | Shape depends on item_type; see §6.3 | Recommendation or action data (no secrets). |

### 6.2 Optional item fields (dependency and blocking)

| Field | Type | Validation | Notes |
|-------|------|------------|--------|
| `depends_on_item_ids` | array of strings | Each item_id present in plan | Items that must be satisfied before this item is eligible. |
| `blocks_item_ids` | array of strings | Each item_id present in plan | Items that are blocked until this item is done (inverse dependency). |
| `blocking` | boolean | — | If true, execution cannot proceed to dependent items until this item is completed or skipped. |
| `status` | string | E.g. `pending`, `approved`, `rejected`, `skipped`, `completed` | Item-level approval or execution state. |
| `source_section` | string | Max 64 chars | Normalized output section key (e.g. existing_page_changes, new_pages_to_create). |
| `source_index` | integer | Non-negative | Index in that section (for traceability and dropped-record correlation). |
| `confidence` | string | One of: `high`, `medium`, `low` | Per-item confidence. |
| `risk_level` | string | One of: `low`, `medium`, `high` | Where applicable. |

### 6.3 Item payload shapes (by item_type)

- **existing_page_change:** `current_page_url`, `current_page_title`, `action`, `reason`, `risk_level`, `confidence`; optional `target_template_ref`, `target_composition_ref`.
- **new_page:** `proposed_page_title`, `proposed_slug`, `purpose`, `template_key`, `menu_eligible`, `section_guidance`, `confidence`; optional `page_type`.
- **menu_change:** `menu_context`, `action`, `proposed_menu_name`, `items` (array of menu item refs).
- **design_token:** `token_group`, `token_name`, `proposed_value`, `rationale`, `confidence`.
- **seo:** `target_page_title_or_url`, `confidence`; optional recommendation text.
- **hierarchy_note:** `recommended_top_level_pages` or hierarchy snippet; freeform summary.
- **overview_note:** Text or structured summary (site purpose, flow).
- **confirmation:** Empty or `{ "outcome": "approved" | "rejected" }`; set at confirmation step.

Payloads must not contain secrets or raw provider data. All fields are machine-readable and stable.

---

## 7. Warning, assumption, and confidence blocks

### 7.1 Warning object

| Field | Type | Notes |
|-------|------|--------|
| `id` | string | Optional; max 64 chars. |
| `message` | string | Non-empty; max 1024 chars. |
| `severity` | string | One of: `low`, `medium`, `high`. |
| `source_section` | string | Optional; normalized output section. |

### 7.2 Assumption object

| Field | Type | Notes |
|-------|------|--------|
| `id` | string | Optional; max 64 chars. |
| `description` | string | Non-empty; max 1024 chars. |

### 7.3 Confidence object (plan-level)

| Field | Type | Notes |
|-------|------|--------|
| `overall` | string | One of: `high`, `medium`, `low`. |
| `planning_mode` | string | E.g. new_site, restructure_existing_site, mixed. |
| `notes` | string or object | Optional freeform or structured notes. |

---

## 8. Source references block (summary)

Root fields `ai_run_ref`, `normalized_output_ref`, and optional `profile_context_ref`, `crawl_snapshot_ref`, `registry_snapshot_ref` form the **source references**. No additional nested block is required; references are at root. For token context, future schema revision may add `token_context_ref` or equivalent.

---

## 9. Confirmation and denial logic (spec §30.11)

- **Confirmation step:** The last step (`step_type: confirmation`) is where the user approves or rejects the plan. It may contain a single synthetic item with `outcome: approved` or `outcome: rejected`, or the outcome is stored in root `approval_denial_state`.
- **approved:** Plan is eligible for execution. `approval_denial_state` = `approved` (or `partial` if product supports partial approval).
- **rejected:** Plan is denied. Status becomes `rejected`; no execution. Plan is retained for history.
- **Denial is a legitimate outcome;** the schema does not treat rejection as an error. Rejected plans remain readable and auditable.

---

## 10. Final completion state and history (spec §30.12)

- **completed:** All executed steps (or skipped items) are done. `completed_at` is set. Plan is **historically readable**; no mutation of plan content after completion.
- **superseded:** Plan was replaced by a newer plan (e.g. new AI run produced a new plan). Reference from new plan to superseded plan may be stored elsewhere; `status` = `superseded` and `completed_at` (or `superseded_at`) mark terminal state.
- **History anchors:** `execution_history_anchor` (and future execution log refs) link the plan to execution records. Placeholder until execution is implemented.
- **Retention:** `history_retention` indicates whether the plan is retained, archived, or governed by policy. Completed and superseded plans are retained per policy; schema does not mandate deletion.

---

## 11. Ineligibility rules for incomplete plans

A plan is **ineligible for execution** when:

- `status` is not `approved`.
- `status` is `pending_review`, `rejected`, `superseded`, or missing.
- Required root fields are missing (`plan_id`, `status`, `ai_run_ref`, `normalized_output_ref`, `steps`, `created_at`).
- `steps` is empty or does not contain at least one step with `step_type: overview` and one with `step_type: confirmation`.
- Any step required by the seven-step UI model is missing (overview, existing_page_changes, new_pages, hierarchy_flow, navigation, design_tokens, seo, confirmation) when the normalized output contained data for that step — *or* the generator may emit all seven step types with empty items where no data exists; then ineligibility is only status and required fields.

Schema does not require every step type to be present; steps may be omitted when the normalized output has no items for that category. Ineligibility is primarily **status** and **required root fields**.

---

## 12. Valid example: plan skeleton

```json
{
  "plan_id": "plan_550e8400-e29b-41d4-a716-446655440000",
  "status": "pending_review",
  "ai_run_ref": "aio-run-550e8400-e29b-41d4-a716-446655440001",
  "normalized_output_ref": "aio-run-550e8400-e29b-41d4-a716-446655440001:normalized_output",
  "plan_title": "Site audit plan – March 2025",
  "plan_summary": "Draft plan for contact and consultation focus; mixed planning mode.",
  "site_purpose_summary": "Local accounting firm; contact and consultation focus.",
  "site_flow_summary": "Home, About, Services, Contact as top-level; FAQ and pricing as children.",
  "steps": [
    {
      "step_id": "step_overview_0",
      "step_type": "overview",
      "title": "Overview",
      "order": 0,
      "items": [
        {
          "item_id": "item_overview_0",
          "item_type": "overview_note",
          "payload": { "summary_text": "Draft plan.", "planning_mode": "mixed", "overall_confidence": "medium" }
        }
      ]
    },
    {
      "step_id": "step_epc_1",
      "step_type": "existing_page_changes",
      "title": "Existing page changes",
      "order": 1,
      "items": [
        {
          "item_id": "item_epc_0",
          "item_type": "existing_page_change",
          "payload": {
            "current_page_url": "/",
            "current_page_title": "Home",
            "action": "keep",
            "reason": "Keep as is.",
            "risk_level": "low",
            "confidence": "high"
          },
          "source_section": "existing_page_changes",
          "source_index": 0,
          "status": "pending"
        }
      ]
    },
    {
      "step_id": "step_new_2",
      "step_type": "new_pages",
      "title": "New pages",
      "order": 2,
      "items": []
    },
    {
      "step_id": "step_hierarchy_3",
      "step_type": "hierarchy_flow",
      "title": "Hierarchy & flow",
      "order": 3,
      "items": []
    },
    {
      "step_id": "step_nav_4",
      "step_type": "navigation",
      "title": "Navigation",
      "order": 4,
      "items": []
    },
    {
      "step_id": "step_tokens_5",
      "step_type": "design_tokens",
      "title": "Design tokens",
      "order": 5,
      "items": []
    },
    {
      "step_id": "step_seo_6",
      "step_type": "seo",
      "title": "SEO",
      "order": 6,
      "items": []
    },
    {
      "step_id": "step_confirm_7",
      "step_type": "confirmation",
      "title": "Confirm",
      "order": 7,
      "items": []
    }
  ],
  "created_at": "2025-03-11T12:00:00Z",
  "approval_denial_state": "pending",
  "remaining_work_status": "review",
  "warnings": [],
  "assumptions": [],
  "confidence": { "overall": "medium", "planning_mode": "mixed" },
  "schema_version": "1"
}
```

---

## 13. Invalid examples

**Invalid: missing required root fields**

```json
{
  "plan_id": "plan_x",
  "status": "pending_review"
}
```

Missing: `ai_run_ref`, `normalized_output_ref`, `plan_title`, `plan_summary`, `site_purpose_summary`, `site_flow_summary`, `steps`, `created_at`. Plan is ineligible.

**Invalid: status not approved (ineligible for execution)**

```json
{
  "plan_id": "plan_x",
  "status": "rejected",
  "ai_run_ref": "run_1",
  "normalized_output_ref": "run_1:normalized_output",
  "plan_title": "Test",
  "plan_summary": "",
  "site_purpose_summary": "",
  "site_flow_summary": "",
  "steps": [ { "step_id": "s0", "step_type": "overview", "title": "Overview", "order": 0, "items": [] }, { "step_id": "s1", "step_type": "confirmation", "title": "Confirm", "order": 1, "items": [] } ],
  "created_at": "2025-03-11T12:00:00Z"
}
```

Valid as a stored plan (rejected and retained for history). Ineligible for execution.

**Invalid: step missing required item fields**

```json
{
  "step_id": "step_epc_1",
  "step_type": "existing_page_changes",
  "title": "Existing page changes",
  "order": 1,
  "items": [
    {
      "item_id": "item_1",
      "item_type": "existing_page_change",
      "payload": {}
    }
  ]
}
```

Item payload is incomplete (missing current_page_url, action, reason, etc.). Schema validators should reject or flag such items.

---

## 14. Security and permissions

- Build Plans are **reviewable by specific capabilities** (e.g. aio_view_build_plans, aio_approve_build_plans). Raw artifact access remains separate (e.g. aio_view_sensitive_diagnostics for raw prompts/responses).
- Schema **must not embed secrets** or unauthorized raw provider data. Plan payloads contain only derived, normalized, and local data.
- Future plan mutation and execution decisions are **server-authoritative**; schema does not define the permission checks, only the data shape.

---

## 15. Cross-references

- **Object model:** object-model-schema.md §3.4 Build Plan — required/optional fields, status enum, lifecycle, relationships.
- **State machine:** build-plan-state-machine.md — root/step/item statuses, transition tables, blocker rules, completion recognition, denial handling, resumption, messaging patterns.
- **Normalized output:** ai-output-validation-contract.md — only validated normalized output may feed plan generation; Build_Plan_Draft_Schema (code) defines the draft output shape.
- **AI run and artifacts:** Artifact_Category_Keys (normalized_output); AI run stores run_id and artifact refs; plan stores ai_run_ref and normalized_output_ref.
