# Build Plan — Confirm (finalization) and Logs & rollback

**Audience:** Operators closing out a Build Plan, auditing execution, or considering rollback.  
**Parent:** [build-plan-overview.md](build-plan-overview.md)  
**Related:** [build-plan-hierarchy-navigation-tokens-seo.md](build-plan-hierarchy-navigation-tokens-seo.md); [build-plan-overview.md §6–§8](build-plan-overview.md); [admin-operator-guide.md §7–§8](../../guides/admin-operator-guide.md); [executor-locking-idempotency-contract.md](../../contracts/executor-locking-idempotency-contract.md).

In the **default generated plan**, these are the **last two** stepper steps after SEO:

| URL `step` index | Typical UI step # | Default title | Service constant (code) |
|------------------|-------------------|----------------|-------------------------|
| `7` | 8 | **Confirm** | `Finalization_Step_UI_Service::STEP_INDEX_CONFIRMATION` |
| `8` | 9 | **Logs & rollback** | `History_Rollback_Step_UI_Service::STEP_INDEX_LOGS_ROLLBACK` |

Always match the **labels on your stepper** if your plan was edited or migrated.

---

## 1. What the Logs & rollback step is for

- **Purpose:** Surface **execution-related history** for the plan: what ran, with enough **snapshot references** to support **rollback requests** when the system recorded **pre** and **post** operational snapshots.
- **v1 scope (on-screen copy):** Rollback is aimed at **page replacements** and **design token** changes. Other change types may appear in logs without a rollback path.
- **Data source:** For newly generated plans, the logs step is created as a **shell** with **no items** until history rows exist (for example from plan items populated elsewhere, or from execution/snapshot pipelines your site uses). The UI service can also render rows when **rollback entries** are supplied with paired snapshot IDs (advanced wiring).
- **Bulk rollback:** The bulk bar labels exist but **enabled controls are off** in the current payload — use **per-row Request rollback** when shown.

---

## 2. What rollback-capable rows mean

A row is **rollback-capable in the UI** when:

1. The row carries both a **pre** and **post** snapshot identifier, and  
2. Your user has **`aio_execute_rollbacks`** **or** (for the row action affordance only) **`aio_execute_build_plans`** — the history builder treats either as enough to show **Request rollback**.

**Server enforcement:** Submitting **Request rollback** requires **`aio_execute_rollbacks`**. If you see the control but lack that capability, the server will not honor the action.

The server then runs **rollback eligibility** for the pair; if not eligible, you are redirected back with an **error** query flag (see §7).

Rollback **queues** work (with `run_immediately` when supported); it is **not** guaranteed to succeed or to restore every side effect (themes, caches, manual edits).

---

## 3. When to rollback vs when not to

**Consider rollback when**

- A **token** or **page** change applied through this plan clearly broke layout, content, or branding, and you trust the **captured pre/post snapshots** for that action.
- You need a **governed, auditable** undo attempt rather than ad-hoc DB edits.

**Avoid relying on rollback when**

- The problem is **editorial** (copy, SEO plugins, unrelated manual edits) — rollback may not target those layers.
- **Downstream work** already depends on the new state (new links, campaigns, legal publication). Undoing can cause **new** inconsistencies.
- Eligibility fails — treat the row as **audit-only** and fix forward manually or with a new plan.

---

## 4. What finalization does

On **Confirm**, **Finalize plan** (POST) is available when you have **`aio_finalize_plan_actions`**.

- It builds an execution **envelope** of type **finalize plan** and passes it to the **single action executor** (same family of machinery as other governed actions).
- It does **not** magically mark every line item “done” in the UI by itself; it triggers the **finalize** execution path for the plan record. Exact downstream effects depend on executor handlers and plan state.
- After submit, the browser is redirected with **`finalize_result=done`** on the URL. In the current build that redirect uses **`step=6`**, which is the **SEO** step index in the default nine-step stack—not **Confirm**. Use the **stepper** to return to **Confirm** or **Logs & rollback** to continue reviewing.

The confirmation workspace also shows **render-only** copy: if the plan **status** is **completed**, a short **completion** banner appears; otherwise text explains that **execution** is driven from the **plan run/queue** flow, not from re-running items on this step alone.

---

## 5. How to read completion, conflict, and finalization summaries

### 5.1 Finalization queue buckets (detail panel)

The **Finalization queue** section lists four counts:

| Label | Meaning (implementation) |
|-------|-------------------------|
| **Publish-ready** | From `completion_summary.published` when present on the plan definition; otherwise **0** unless your pipeline writes it. |
| **Blocked** | From `completion_summary.blocked`, or else the **detected conflict count** (see below). |
| **Failed** | From `completion_summary.failed`, or else the count of items in **failed** status across steps. |
| **Deferred** | Items still **pending** plus items **approved** or **in_progress** (work not treated as finished). |

These buckets are **summaries** for orientation; they are not a substitute for walking earlier steps item by item.

### 5.2 Conflicts

The product runs a **slug collision** check across **completed** items: duplicate `page_slug_candidate`, `proposed_slug`, or `target_slug` values become **conflict** rows. Up to **five** conflict messages can surface; the detail panel shows a short list.

**Blocked** finalization messaging states that finalization **re-checks readiness** and **will not proceed when blocked** — treat conflicts as **hard stops** until you resolve duplicates or item outcomes.

### 5.3 After plan completion

When plan **status** is **completed**, the service may expose **`run_completion_state`** and **`finalization_summary`** (or fall back to **`completion_summary`**) for display consumers. If your skin does not print those fields, rely on **plan status** in the context rail and **Logs & rollback** for outcomes.

---

## 6. Plan export (user-facing)

**Export plan** appears in the **context rail** (left column) on the workspace for any step when you have **`aio_export_data`** **or** **`aio_download_artifacts`**.

- **Mechanism:** GET with `aio_export_build_plan=1` and a **per-plan nonce** (reload the workspace if the link is stale).
- **Result:** Browser download of a **JSON** file (`build-plan-<plan-id>.json`, sanitized filename).
- **Contents:** `export_version`, `exported_at_utc`, `plan_id`, `plan_post_id`, **redacted** `plan_definition`, **redacted** `context_rail`, and a `stepper_snapshot` of stepper steps.
- **Not included as a separate wizard:** Export does not replace **Queue & Logs** or **rollback**; it is a **snapshot for audit/support**.

---

## 7. Step-by-step guides

### 7.1 Review execution history

1. Open the plan workspace and select **Logs & rollback** (`step=8` in the default stack).  
2. Read the **info** message describing v1 rollback scope.  
3. Scan columns: **event time**, **action type**, **scope**, **result**, **rollback** eligibility.  
4. If there are **no rows**, the step may still be valid — history often **fills as execution and snapshots** are recorded.

### 7.2 Request rollback (when offered)

1. Confirm you have **`aio_execute_rollbacks`**.  
2. Click **Request rollback** on a row that has both snapshot IDs (POST form with nonce).  
3. Wait for redirect back to the logs step.  
4. Interpret **`rollback_done=1`** (success) or **`rollback_error=`** (`nonce`, `missing_snapshots`, `ineligible`, `unavailable`, or a failure message).  
5. **Verify** the site manually; do not assume full visual parity.

### 7.3 Review finalization summaries

1. Open **Confirm** (`step=7`).  
2. Read the **info** step message (conflicts and finalize readiness).  
3. Open the **detail panel** sections **Finalization queue** and **Conflicts**.  
4. If **Deferred** is non-zero, unfinished or queued work still exists somewhere in the plan item graph — return to earlier steps or the queue as your process requires.

### 7.4 Confirm completion

1. When conflicts and business checks are acceptable, use **Finalize plan** if you hold **`aio_finalize_plan_actions`**.  
2. After redirect, use the **stepper** to revisit **Confirm** / **Logs** — do not assume the URL `step=` matches **Confirm** (see §4).  
3. Confirm **plan status** and site behavior outside the plugin if needed.

---

## 8. Edge cases

- **Rollback not available for a row** — Missing snapshot IDs, ineligible pair, or missing services → error redirect or no **Request rollback** button.  
- **Partial execution before rollback** — Only the **scoped** snapshot pair is considered; other completed items may remain.  
- **Finalization shows unresolved / deferred counts** — **Pending** and **approved/in_progress** items inflate **Deferred**; clear or complete work per your runbook.  
- **Plan “complete” but items denied or advisory** — **Rejected** and **advisory-only** items are expected; completion is **plan-level** and does not imply every row was executed.  
- **Execute shown for rollback row but POST rejected** — Row visibility can use **`aio_execute_build_plans`**; handler requires **`aio_execute_rollbacks`**.  
- **Empty logs step** — Normal until history items or rollback entries exist.

---

## 9. FAQ and troubleshooting

**Why does Finalize send me to a different step in the URL?**  
The redirect currently appends `step=6` (SEO index) while adding `finalize_result=done`. Use the stepper to navigate.

**Why is Export plan missing?**  
You need **export** or **download artifacts** capability; the control is on the **context rail**, not inside Confirm.

**Does rollback restore SEO or menu changes?**  
v1 messaging emphasizes **pages** and **tokens**; do not assume other systems are reverted.

**Finalize did nothing visible.**  
Finalization runs through the **executor**; check **queue/logs** and plan **status**, not only this screen.

---

## 10. Stub cross-links

Short entry points: [build-plan-step-confirmation.md](build-plan-step-confirmation.md), [build-plan-step-logs-rollback.md](build-plan-step-logs-rollback.md).
