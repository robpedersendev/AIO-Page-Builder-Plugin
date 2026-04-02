# Build Plan review — existing page changes & new pages (step indices 1 and 2)

**Audience:** Operators approving or denying the **first two actionable steps** after **Overview** in a default plan.  
**Parent:** [build-plan-overview.md](build-plan-overview.md).  
**Capabilities:** **`aio_view_build_plans`** to see the workspace; **`aio_approve_build_plans`** for every approve/deny/bulk action described here. Running approved work uses **`aio_execute_build_plans`** through your site’s **plan execution** / queue flow ([admin-operator-guide.md §7](../../guides/admin-operator-guide.md))—not the review buttons alone.

---

## 1. Naming: URL index vs stepper number

The workspace URL uses a **0-based step index** (`&step=`). In the **default** nine-step plan from the generator:

| URL `step=` | Step type | Typical stepper **number** (1-based) |
|-------------|-----------|----------------------------------------|
| `0` | Overview | 1 |
| **`1`** | **Existing page changes** | **2** |
| **`2`** | **New pages** | **3** |

This article uses **“Step index 1 / 2”** to match code and URLs. If your plan omits Overview or reorders steps, use the **labels in the stepper**, not the numbers from this table alone.

---

## 2. What you are deciding

### Step index 1 — Existing page changes

Each row is one **`existing_page_change`** recommendation: which **current page** should receive a **template or content update**, with columns such as title, URL, action, target template, and risk. The UI marks **snapshot** expectations for rollback-related flows.

**Your decision:** **Approve** (“accept this update for later execution”) or **Deny** (“do not apply this recommendation”). Nothing on the live site changes **at review time**—the plan record is updated only.

### Step index 2 — New pages

Each row is one **`new_page`** recommendation (title, slug, purpose, template, hierarchy hints, dependency hints, etc.).

**Your decision:** Same pattern: **Approve** records **build intent** (status **approved**); **Deny** sets **rejected** and removes the item from the pending/eligible set. **Build All Pages** / **Build Selected Pages** are **bulk approve** operations—the label says “Build” but the handler **approves** pending items; actual page creation still goes through **execution**, not this button alone.

---

## 3. Approve vs deny (semantics)

| Action | Stored status (model) | Effect on execution |
|--------|------------------------|---------------------|
| **Approve** (row or bulk) | `approved` | Item may proceed when execution runs for that type and plan state. |
| **Deny** | `rejected` | Item is **excluded** from execution and from **unresolved** counts for the stepper. |

**Important:** Single-item updates only apply when the item is still **`pending`**. If the row is already approved or rejected, the same action is a **no-op** (server does not overwrite).

**Denied ≠ “executed and rolled back.”** Deny means **never approved** for this plan—not an undo of site changes.

---

## 4. Row-level vs bulk actions

### Row-level (GET + nonce)

- **View detail** — Always available; opens the detail panel / sections for that item.
- **Approve** / **Deny** — Linked with a **per-item nonce** (`aio_pb_build_plan_row_action_<item_id>`). Uses `action=approve_item` or `deny_item` and `step=1` or `step=2`. The workspace **injects URLs only for approve and deny** on these steps (`add_urls_to_approve_deny`).
- **Execute** / **Retry** — `Build_Plan_Row_Action_Resolver` may still list **Execute** for **`new_page`** items when **approved** and the user has execute capability, but **step 1–2 screen injection does not attach execute URLs** to those actions. **Existing page change** items are **not** in the resolver’s executable-type list. Treat these steps as **review-only**; trigger real work via **plan execution** / queue after approval.

### Bulk (POST + shared bulk nonce)

**Step index 1** (`aio_pb_build_plan_bulk_action`):

- **Make All Updates** — Approves **all** currently **pending** items in this step.
- **Apply to selected** — Approves only checked rows (`aio_step1_selected_ids[]`). If none selected, the UI shows an error: **Select one or more rows to apply selected updates.**
- **Deny All Updates** — Rejects **all** pending items in this step. **No** separate confirmation checkbox.
- **Clear selection** — Link back to the same step URL without selections.

**Step index 2**:

- **Build All Pages** — Bulk **approve** all eligible pending new-page items.
- **Build Selected Pages** — Bulk approve selected (`aio_step2_selected_ids[]`). Empty selection → **Select one or more rows to build selected pages.**
- **Deny All Eligible** — Rejects **all** pending new-page items in this step in one action. On success, a success notice reports how many recommendations were denied.
- **Clear selection** — Same pattern as step 1.

Bulk buttons are **disabled** when there are **no pending** items or the user cannot approve.

---

## 5. Eligibility and visibility

- **Low confidence** items (`confidence` = `low` in payload) are **hidden** from both steps’ lists and bulk counts—the UI only lists items above that threshold.
- Only **`existing_page_change`** items appear in step index 1; only **`new_page`** items in step index 2.

---

## 6. Step-by-step: review one item

1. Open **Build Plans** → **Open** the plan → click the stepper for **Existing page changes** or **New pages** (URL `step=1` or `step=2`).
2. Read the row (title, template, risk, dependency warnings on new pages).
3. Click **View detail** for full sections (template rationale, compliance cautions when configured, etc.).
4. Use **View template** / **Add to compare** links when present to validate the suggested template in the template library.
5. Choose **Approve** or **Deny** on the row or in the detail panel.

---

## 7. Step-by-step: approve items

**One at a time:** **Approve** on the row (or from detail).

**All pending:** **Make All Updates** (step 1) or **Build All Pages** (step 2).

**Subset:** Select checkboxes → **Apply to selected** / **Build Selected Pages** → submit the bulk form.

After submission, the screen reloads on the same `step=`; counts and badges update.

---

## 8. Step-by-step: deny items

**One row:** **Deny** (GET with nonce).

**All pending (step 1):** **Deny All Updates** — confirm mentally before click; there is **no** extra checkbox.

**All pending (step 2):** **Deny All Eligible** — same pattern as step 1; there is **no** extra checkbox.

**Feedback:** Step 2 row deny may show **Denied the selected new page recommendation.** Bulk deny shows a count-based success message.

---

## 9. How this affects later steps

- The stepper’s **unresolved** count for a step drops as items leave **`pending`**.
- When **step index 1** has **no** unresolved items, **step index 2** is no longer **blocked** by step 1 (see `Build_Plan_Stepper_Builder`: a step is blocked if any **earlier** step still has unresolved items).
- **Rejected** items do not block progression—they are **terminal** for review purposes.
- **Partial approval** (some rows approved, some denied, none pending) clears the step’s unresolved count and **unblocks** the next step.

---

## 10. Edge cases

| Situation | What happens |
|-----------|----------------|
| **Denied by mistake** | There is **no** standard “undo deny” control in these screens. Deny persists as **`rejected`**. Mitigation: **export the plan** before risky bulk actions; open a **new plan** or use governed data repair if your process allows resetting item status while the plan is still **`pending_review`**. |
| **Button disabled** | No **`pending`** items, missing **`aio_approve_build_plans`**, or step **blocked**—open an earlier step and finish decisions. |
| **Approve / Deny no-op** | Item was not **`pending`** (already approved/rejected). |
| **Mixed-status plan** | Normal: some items **`approved`**, some **`rejected`**, some may later be **`completed`** / **`failed`** after execution. |
| **Build Selected with zero rows** | Error notices as in §4. |
| **Existing page row has no Execute** | Expected: the resolver does not treat **`existing_page_change`** as a row-executable type; approved items are picked up when the **plan’s execution** runs. |
| **New page row shows Execute but no link** | Approve/deny URLs are wired on this screen; **Execute** may appear in the action list without a GET URL here—use your **execution** workflow after the plan is approved. |

---

## 11. FAQ

**Why can I deny an item but never “execute” it?**  
**Deny** is a **review** outcome (**rejected**). Execution only applies to **approved** items. Denied items are intentionally **out** of the execution set.

**Why does “Build All Pages” not create pages immediately?**  
It **approves** eligible pending new-page items. Creation runs when **execution** is triggered for the plan (queue), subject to caps and handlers.

**Why do new-page rows sometimes list Execute but existing updates do not?**  
The resolver’s **executable type** list includes **`new_page`** (and tokens, hierarchy, new menu) but not **`existing_page_change`**. Even for new pages, **this workspace step** only **injects links** for **Approve** and **Deny**—do not assume **Execute** is clickable here; run jobs from **execution** / **Queue & Logs** per operator docs.

**How does denial affect downstream work?**  
Denied pages are not created or updated by this plan’s execution for those line items. Hierarchy or navigation steps may still reference URLs that **would have** existed—re-read later steps if you denied structural pages.

**Unsure what to do?**  
Do not bulk-approve; use **View detail**, template library links, and [template-system-overview.md](../templates/template-system-overview.md). Escalate to an admin with **execute** capability before running the full plan. Use [support-triage-guide.md](../../guides/support-triage-guide.md) if jobs fail after execution.

---

## 12. Cross-links — execution and rollback

- Plan-wide safety and queue expectations: [build-plan-overview.md §6](build-plan-overview.md)  
- Operator execution summary: [admin-operator-guide.md §7](../../guides/admin-operator-guide.md)  
- Rollback caveats: [admin-operator-guide.md §8](../../guides/admin-operator-guide.md); step detail stub [build-plan-step-logs-rollback.md](build-plan-step-logs-rollback.md)  
- Technical locking/idempotency: [executor-locking-idempotency-contract.md](../../contracts/executor-locking-idempotency-contract.md)  
- Queue visibility: [admin-operator-guide.md §9](../../guides/admin-operator-guide.md)

---

## 13. Related step stubs

- [build-plan-step-existing-page-changes.md](build-plan-step-existing-page-changes.md)  
- [build-plan-step-new-pages.md](build-plan-step-new-pages.md)
