# Build Plan Admin Information Architecture Contract

**Document type:** Authoritative contract for Build Plan UI structure, layout, navigation, and state presentation.  
**Governs:** Build Plan list/detail screens, stepper, context rail, list/detail views, status/error/progress/completion patterns (spec §31, §59.9).  
**Reference:** Master Specification §31.1–31.12, §49.2–49.6, §59.9; Build Plan schema (Build_Plan_Schema, Build_Plan_Item_Schema); build-plan-state-machine.

**Out of scope for this contract:** UI class implementation, execution handlers, queue monitoring UI beyond IA placeholders, rollback UI, export package UI implementation.

---

## 1. Purpose and constraints

- **Viewing does not imply approving.** **Approving does not imply executing.** The IA must support step-by-step review without forcing approval or execution.
- **Context rail** preserves plan clarity and source traceability; it is always visible on desktop and collapses to a persistent summary drawer on narrow layouts.
- **Empty states and blocked steps** must show explanatory copy; no blank opaque screens.
- **Completed plans** remain readable as historical records; completion does not remove or hide the plan.
- **Permissions:** Build Plan UI access is capability-sensitive. View, approve/deny, execute, finalize, and raw artifact access are distinct; the IA must not imply permissions not granted. Error views must not leak raw provider artifacts to unauthorized users.

---

## 2. Screen entry points and slugs

All slugs use parent `aio-page-builder`. The following are **locked**; do not invent alternate slugs.

| Screen | Slug | Purpose |
|--------|------|---------|
| Build Plan list | `aio-page-builder-build-plans` | List plans (by status, date); entry from AI Runs "Create Build Plan"; open plan → detail. |
| Build Plan detail (stepper) | `aio-page-builder-build-plans` with query `plan_id` or `id` (e.g. `?page=aio-page-builder-build-plans&plan_id={plan_id}`) | Single-plan view: three-zone layout, stepper, context rail, step workspace. |

**Entry from other screens:**

- **AI Runs:** "Create Build Plan" (or equivalent) → creates plan from normalized output and redirects to Build Plan detail for that plan.
- **Dashboard:** "Active Build Plans" summary → links to list or directly to a plan detail when exactly one active.
- **Queue & Logs (future):** "Open related plan" → Build Plan detail.

**Logs, History, and Rollback:** Spec §31.2 mentions "Logs, History, and Rollback" as a step. Per §59.11, rollback UI is a separate phase. For the stepper, **Logs/History/Rollback is not a step in the main stepper order.** It is a separate screen or post-completion entry point (e.g. "View logs" / "History" from completion state or context rail). The stepper step order below is canonical and matches Build_Plan_Schema.

---

## 3. Three-zone layout

The Build Plan detail screen uses a **three-zone** layout. No single unstructured list; no alternate zone arrangement.

| Zone | Position | Contents | Desktop | Narrow layout |
|------|----------|----------|---------|----------------|
| **Context rail** | Left | Plan metadata, source refs, status, summaries, unresolved counts, critical warnings, primary actions (see §5). | Always visible, fixed width. | Collapses to a **persistent summary drawer** (e.g. icon + slide-out or top summary bar that expands). Content per §5 unchanged. |
| **Stepper + plan controls** | Top (above main content) | Step list (number, title, status badge, unresolved count); active step distinct; plan-level actions (e.g. Approve plan, Reject plan) where applicable. | Full horizontal stepper. | Stepper may wrap or become a dropdown/accordion; step order and semantics unchanged. |
| **Main content workspace** | Center/right | For the **active step:** overview content, or table/grid of items + detail drawer/panel (see §6, §7). | Table left; detail right (drawer or side panel). | Detail may move below table or to overlay; table + detail pattern preserved. |

**Layout rules:**

- Zones are clearly separated (borders, background, or spacing).
- Context rail width is fixed on desktop (e.g. 280–320px); main area uses remaining width.
- Stepper and plan controls stay visible when scrolling the main workspace (sticky or fixed per implementation).
- Narrow layout breakpoint and drawer behavior are implementation-defined; the contract requires that context content remains accessible (drawer or equivalent).

---

## 4. Step order and navigation

**Canonical step order** (from Build_Plan_Schema; do not reorder or rename step types):

| Order | Step type (schema) | Title (display) |
|-------|--------------------|-----------------|
| 0 | `overview` | Overview |
| 1 | `existing_page_changes` | Existing page changes |
| 2 | `new_pages` | New pages |
| 3 | `hierarchy_flow` | Hierarchy & flow |
| 4 | `navigation` | Navigation |
| 5 | `design_tokens` | Design tokens |
| 6 | `seo` | SEO |
| 7 | `confirmation` | Confirm |

**Navigation rules:**

- **Backward:** User may jump to any **earlier** step freely.
- **Forward:** User may jump to a **later** step only if it is **not blocked**. A step is blocked when required predecessors have unresolved required items (e.g. pending or denied items that block progression per state machine).
- **Active step:** Exactly one step is active; its content is shown in the main workspace. Active step is visually distinct in the stepper.

**Step status badges (per step):** One of:

- `not_started`
- `in_progress`
- `blocked`
- `complete`
- `error`

**Unresolved item count:** Shown per step in the stepper (and in the context rail). "Unresolved" means items not yet in a terminal state (e.g. pending review, approved but not executed, in progress, failed retriable).

---

## 5. Context rail contents

The context rail (or its drawer equivalent) **must** contain the following. All fields are read-only in the rail; editing is not part of this contract.

| Field | Source | Notes |
|-------|--------|------|
| Plan title | `plan_title` | From plan definition. |
| Plan ID | `plan_id` | Stable identifier (e.g. UUID). |
| Source AI run ID | `ai_run_ref` | Link or copy for traceability; raw artifacts gated by capability. |
| Normalized output ref | `normalized_output_ref` | Optional display; supports traceability. |
| Plan status | `status` | Root plan status (e.g. pending_review, approved, in_progress, completed, rejected, superseded). |
| Site purpose summary | `site_purpose_summary` | From plan definition. |
| Site flow summary | `site_flow_summary` | From plan definition. |
| Unresolved item counts by step | Derived | Per-step counts of non-terminal items; same semantics as stepper counts. |
| Critical warnings summary | `warnings` (or filtered) | Short list or count; link/expand for full list. |
| Primary actions | — | Save and exit; Export plan; View source artifacts (capability-gated). |

**Primary actions (context rail):**

- **Save and exit:** Persist any in-memory review state and return to Build Plan list (or previous screen).
- **Export plan:** Export plan record (and optionally related refs); capability and scope TBD in implementation.
- **View source artifacts:** Opens normalized output / AI run artifact view; **requires** capability for raw/sensitive content; otherwise show redacted or summary only.

No other actions are mandated in the context rail; step-level and row-level actions are in the workspace (§7, §8).

---

## 6. Step workspace expectations

| Step type | Workspace content |
|-----------|-------------------|
| `overview` | Single overview content block: plan summary text, planning mode, overall confidence. No table. Optional link to "Start review" (go to first actionable step). |
| `existing_page_changes`, `new_pages`, `navigation`, `design_tokens`, `seo` | **Table/grid** of items (rows) + **detail drawer or lower detail panel** for selected item. Columns fixed per step; sortable only where meaningful. Empty state per §11. |
| `hierarchy_flow` | Read-only hierarchy/flow summary (e.g. recommended top-level pages, flow summary). May be list or structured text. No table of actionable items unless schema adds such. Empty state if no hierarchy data. |
| `confirmation` | Summary of approved/denied items; final "Confirm" or "Start execution" (or "Reject plan") actions. Completion state when plan is already completed (§12). |

**Table + detail pattern (steps with items):**

- **List:** Table or grid with one row per plan item. Columns are step-specific (e.g. page title, URL, action, status, risk).
- **Detail:** Selecting a row opens a **right-side detail drawer** (desktop) or **lower detail panel** (narrow). Detail shows full item payload, source section/index, rationale, dependencies, and row-level actions (§8).
- No blank list without explanatory empty-state text (§11).

---

## 7. Row and detail patterns

- **Row:** Each row corresponds to one plan item (item_id). Row shows: key identifying info (e.g. title, URL, action type), status badge, and optional risk/confidence. Row-level actions: view detail, approve, deny, execute (if approved), retry (if failed), view diff, view dependencies — only **enabled when valid** for current item state per state machine.
- **Detail drawer/panel:** Same item; full payload, source_section, source_index, rationale, warnings, dependencies. Same row-level actions available. No raw provider output in detail unless user has artifact/sensitive capability.

---

## 8. Bulk action UI patterns

Bulk-action controls sit **above** the item list (for steps that have a list).

| Control | Behavior |
|---------|----------|
| Apply to all eligible | Apply the chosen action (e.g. approve) to all items in the step that are eligible. |
| Apply to selected | Apply to selected rows only (selection state is per step). |
| Deny all eligible | Deny all eligible items in the step. |
| Clear selection | Clear current row selection. |

- Bulk actions are **disabled** when no eligible rows exist (or no selection for "apply to selected").
- "Eligible" is defined by the Build Plan state machine (e.g. pending → approved/denied).

---

## 9. Individual (row) action UI patterns

Per row (and in detail view), the following actions may appear; only those **valid for the current row state** are enabled.

| Action | Typical use |
|--------|-------------|
| View detail | Open or focus detail drawer/panel. |
| Approve | Mark item approved for execution. |
| Deny | Mark item denied. |
| Execute | Run this item now (if approved and execution allowed). |
| Retry | Retry after failure (if retriable). |
| View diff | Show before/after or diff (when implemented). |
| View dependencies | Show depends_on / blocks. |

Visibility and enablement are determined by item status and state machine; no actions that would violate transitions.

---

## 10. Status messaging patterns

Status messages appear at **three levels**:

| Level | Placement | Content |
|-------|-----------|---------|
| **Global plan** | Top of workspace or context rail | Plan-level status (e.g. "Plan pending review", "Execution in progress"). |
| **Step** | Step header or just below stepper | Step-level status (e.g. "3 items pending", "Step blocked"). |
| **Row/item** | In row or detail | Item status (e.g. "Approved", "Failed", "Pending"). |

All messages use a **severity style** (info, warning, error, success) and **plain-language explanation**. No raw codes alone.

---

## 11. Error display patterns

- Errors appear **inline** with the affected object (e.g. row or step).
- **Severe step-level errors** also appear in the step header.
- Every error state must include:
  - **Summary** (short, user-facing).
  - **Related object** (which item or step).
  - **Retry eligibility** (yes/no or "Retry" action).
  - **Log reference** (for authorized users only; no raw provider artifacts to unauthorized users).

---

## 12. Progress tracking patterns

The Build Plan UI must expose (in context rail and/or stepper area):

| Metric | Description |
|--------|-------------|
| Total plan completion percentage | Derived from item states (e.g. completed / total). |
| Step completion percentage | Per step. |
| Queued/running job count | When execution is in progress (queue integration). |
| Failed item count | Items in failed state. |
| Approved-not-yet-executed count | Approved items not yet run. |

Display placement (e.g. progress bar, counts in rail) is implementation-defined; the data must be available.

---

## 13. Empty state patterns

Each step that can show an empty list **must** use one of these exact messages (or equivalent i18n):

| Condition | Message |
|-----------|---------|
| No recommendations for this step | "No recommendations were generated for this step." |
| All resolved | "All recommendations in this step have already been resolved." |
| Step blocked | "This step is blocked until earlier required actions are completed." |

**No blank lists without explanatory text.**

---

## 14. Completion state patterns

When the plan status is **completed** (or equivalent terminal success state), the UI must show:

- **Completion banner** (e.g. "Plan completed" with success style).
- **Counts:** Executed actions; denied actions; failed actions (if any).
- **Link to logs/history** (if available and permitted).
- **Link to export the final plan record** (if permitted).

The plan **remains visible** as a readable record; it does not disappear. User can still navigate steps and view read-only content.

---

## 15. Capability and security

| Capability (conceptual) | Scope |
|-------------------------|--------|
| View Build Plans | List and open plan detail; view plan metadata and item list/detail within permission. |
| Approve/deny plan and items | Approve plan, reject plan, approve/deny per item. |
| Execute plan | Start execution, run approved items (and queue). |
| Finalize | Mark plan complete / finalize flow (if separate from execute). |
| Raw artifact access | View source artifacts, raw provider output, logs; must not be granted by view/approve/execute alone. |

The IA does not assign specific capability names; implementation must map to Capabilities (or equivalent) so that each action is gated. Error and artifact views must not leak raw provider data to users without raw artifact capability.

---

## 16. IA validation checklist

Use this checklist when implementing or reviewing the Build Plan UI:

- [ ] Build Plan list and detail use only the defined slugs (`aio-page-builder-build-plans`, with `plan_id`/`id` for detail).
- [ ] Three-zone layout present: left context rail, top stepper + plan controls, main workspace.
- [ ] On narrow layout, context rail collapses to a persistent summary drawer (or equivalent); content unchanged.
- [ ] Step order matches §4 exactly (overview → existing_page_changes → new_pages → hierarchy_flow → navigation → design_tokens → seo → confirmation).
- [ ] Backward navigation free; forward navigation only to non-blocked steps.
- [ ] Context rail contains all fields and primary actions in §5.
- [ ] Steps with items use table + detail drawer/panel; overview and hierarchy_flow use non-table content; confirmation uses summary and completion as in §6, §12.
- [ ] Empty states use one of the three messages in §13; no blank lists.
- [ ] Status at global, step, and row level; errors with summary, related object, retry, log reference (gated).
- [ ] Progress metrics in §12 available in UI.
- [ ] Completion state shows banner, counts, links to logs and export; plan remains visible.
- [ ] All actions gated by capability; no raw artifacts to unauthorized users.

---

## 17. Layout-state scenarios

| Scenario | Expected behavior |
|----------|--------------------|
| **Desktop** | Full three-zone: rail visible, stepper horizontal, table + detail side-by-side. |
| **Narrow admin layout** | Rail collapses to drawer or summary bar; stepper may wrap or collapse to dropdown; detail may move below table or to overlay. |
| **Blocked step** | Step shows "blocked" badge; workspace shows empty-state message "This step is blocked until earlier required actions are completed." (or equivalent). |
| **Empty step (no recommendations)** | Workspace shows "No recommendations were generated for this step." |
| **All items resolved in step** | Workspace shows "All recommendations in this step have already been resolved."; step badge can be complete. |
| **Completed plan** | Completion banner, counts, links to logs and export; plan and steps still navigable read-only. |

---

## 18. Related documents

- **Build Plan schema:** Build_Plan_Schema, Build_Plan_Item_Schema (step types, item types, status enums).
- **State machine:** build-plan-state-machine (root and item transitions).
- **Admin screen inventory:** admin-screen-inventory.md (slug registry, capability placeholders).
- **Spec:** §31.1–31.12, §49.2–49.6, §59.9.
