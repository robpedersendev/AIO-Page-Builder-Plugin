# Build Plan execution actions — what actually runs on the site

**Audience:** Operators who **approve** plan items and trigger **execution**, and who need to understand **queue jobs**, **risks**, and **rollback** limits.  
**Parent:** [build-plan-overview.md](build-plan-overview.md)  
**Related:** [build-plan-review-existing-and-new-pages.md](build-plan-review-existing-and-new-pages.md); [build-plan-hierarchy-navigation-tokens-seo.md](build-plan-hierarchy-navigation-tokens-seo.md); [build-plan-finalization-logs-rollback.md](build-plan-finalization-logs-rollback.md); [concepts-and-glossary.md](../concepts-and-glossary.md); [admin-operator-guide.md §7–§8](../../guides/admin-operator-guide.md); [executor-locking-idempotency-contract.md](../../contracts/executor-locking-idempotency-contract.md).

---

## Plan proposal vs execution

| Phase | What it is | Where it lives |
|-------|------------|----------------|
| **Proposal / review** | AI and planners write **recommendations** into the plan; you **approve** or **deny**. | Build Plan workspace steps, plan JSON. |
| **Execution** | Governed **action envelopes** run through **`Single_Action_Executor`**: validate → optional **pre snapshot** → **lock** → **handler** → optional **post snapshot** → update **plan item status** (`completed` / `failed`). | **Queue jobs**, workspace execute controls where wired, cron/worker processing. |

Until execution succeeds, the live site is unchanged for that item (except side effects you already had outside this plan).

**Capabilities (typical):** **`aio_execute_build_plans`** for normal item execution; **`aio_finalize_plan_actions`** for **finalize_plan**; **`aio_execute_rollbacks`** for governed **rollback** requests. Envelopes carry **`actor_context`** recording which capability was checked.

---

## Registry: plan item → action type

`Bulk_Executor` maps **approved** (or **in_progress** retry) items to execution types. Unmapped types are **skipped** in batch builds.

| Plan `item_type` | Execution `action_type` |
|------------------|-------------------------|
| `existing_page_change` | `replace_page` |
| `new_page` | `create_page` |
| `menu_change` | `update_menu` |
| `design_token` | `apply_token_set` |
| `hierarchy_assignment` | `assign_page_hierarchy` |
| `menu_new` | `create_menu` |

**Not mapped to execution:** `seo` (and the reserved **`update_page_metadata`** action type exists in code but is **recommendation-only**, **excluded** from the executable `Execution_Action_Types::ALL` list). **`hierarchy_note`** is advisory.

**Order:** Batches respect **`depends_on_item_ids`** (e.g. parent **new_page** before children), then step order.

---

## Snapshot and rollback (high level)

- **`snapshot_required`** on envelopes is set for **`replace_page`**, **`update_menu`**, and **`apply_token_set`**. The executor attempts **pre-change** capture before the handler runs; if capture fails, execution may still proceed (fail-open policy in the default executor).
- **Post-change** capture links **pre** and **post** snapshot IDs for operational history.
- **`Rollback_Eligibility_Service`** (current implementation) only treats **`replace_page`** and **`apply_token_set`** as action types with a **supported rollback handler** for eligibility checks. **`update_menu`** may still get snapshots for audit/diff, but **governed rollback** from the logs step will typically be **ineligible** with “no handler for action type” unless product code is extended.
- **`create_page`**, **`create_menu`**, **`assign_page_hierarchy`** are **not** `snapshot_required` in `Bulk_Executor`; do not expect first-party **pair-based rollback** for those through the same path as replace/token.

**`rollback_action`:** A separate **queue job type** processed by **`Rollback_Executor`**, not the normal handler map. Triggered from **Logs & rollback** when eligible (see [build-plan-finalization-logs-rollback.md](build-plan-finalization-logs-rollback.md)).

---

## Action reference (operators)

### `create_page`

- **Does:** Runs **`Create_Page_Job_Service`**: validates template and target metadata, renders template/sections into block assembly, **creates** a new **page** post, applies **parent** when valid, runs **ACF field group assignment** where configured.
- **When:** After **`new_page`** items are **approved** and picked up by execution.
- **Prerequisites:** Valid **`template_key`** (or equivalent in payload), non-empty **proposed title**, sensible slug/hierarchy data; template and sections must be resolvable.
- **Visible outcome:** New page in WordPress; plan item → **`completed`** or **`failed`** with handler message.
- **Rollback (first-party pair):** Not in the snapshot-required set; undo is **manual** (trash/delete page) or out of band.

**Edge cases:** Slug already exists (handler should fail or avoid overwrite—verify message); missing template; dependency parent not created yet if ordering wrong.

---

### `replace_page`

- **Does:** Runs **`Replace_Page_Job_Service`** / template replacement pipeline on an **existing** page (rebuild/replace content from template intent).
- **When:** **`existing_page_change`** items are approved and executed.
- **Prerequisites:** Target page resolvable (`page_ref`, slug, or URL in payload); **`snapshot_required`** true.
- **Visible outcome:** Existing page content/template application changes; artifacts may include replacement trace fields.
- **Rollback:** **Eligible** when pre/post snapshots exist and **`Rollback_Eligibility_Service`** passes (no newer conflicting snapshot on same target, snapshots not used/expired/invalidated, permission).

**Edge cases:** Wrong page resolved from slug; large content loss risk—**review before execute**; newer manual edit blocks rollback (**newer change conflict**).

---

### `update_menu`

- **Does:** **`Apply_Menu_Change_Handler`**: either **`Template_Menu_Apply_Service`** when the payload has **template/hierarchy** context (`template_aware_menu` or `page_class` on items), else **`Menu_Change_Job_Service`** — rename, replace, update_existing, location assignment, etc.
- **When:** **`menu_change`** items are approved and executed.
- **Prerequisites:** Payload must match the job’s expectations (menu context, structure); **`snapshot_required`** true in batch envelopes.
- **Visible outcome:** Nav menus / locations change per payload.
- **Rollback:** Snapshots may exist, but **current eligibility** only lists **replace_page** and **apply_token_set** — treat **menu** rollback as **unsupported** in v1 unless your deployment updates eligibility.

**Edge cases:** Theme **location** not registered (template path may skip with artifact reason); confusion with **`create_menu`** — **update** mutates/replaces existing menu flows; **create** is net-new (see below).

---

### `create_menu`

- **Does:** **`Create_Menu_Handler`**: **`wp_create_nav_menu`**, optional **theme location** assignment if registered, optional **seed items**.
- **When:** **`menu_new`** items are approved and executed.
- **Prerequisites:** Non-empty **`menu_name`** in `target_reference`; optional `theme_location`, `items`.
- **Visible outcome:** New menu term; artifacts include `menu_id`, `location_assigned`, `items_applied`, optional `location_skipped_reason`.
- **Rollback:** Not snapshot-gated like replace/token; **manual** cleanup (delete menu) if needed.

**Edge cases:** Duplicate menu names (WordPress may error); location slug not in theme → skipped with reason; **`update_menu`** vs **`create_menu`** — wrong item type → wrong handler.

---

### `assign_page_hierarchy`

- **Does:** **`Assign_Page_Hierarchy_Handler`**: sets **`post_parent`** via **`wp_update_post`** (`parent_page_id` **0** = top-level).
- **When:** **`hierarchy_assignment`** items are approved and executed (when your UI/queue surfaces them).
- **Prerequisites:** Valid **`page_id`**; **`parent_page_id`** ≥ 0; parent post exists when > 0; **no self-parent**; **no circular** ancestor chain (handler walks ancestors).
- **Visible outcome:** Page moves in hierarchy in admin/front menus that follow parentage.
- **Rollback:** No first-party snapshot pair in bulk config; revert by **re-assigning** parent or restoring from backup.

**Edge cases:** Parent trashed later; STP menu plugins ignoring `post_parent`; child executed before parent if dependencies missing.

---

### `apply_token_set`

- **Does:** **`Apply_Token_Set_Handler`** → **`Token_Set_Job_Service`**: writes **global design token** values (plugin option store); does **not** rename selectors or structural markup.
- **When:** **`design_token`** items are approved and executed.
- **Prerequisites:** **`token_group`** + **`token_name`** + proposed value in payload; **`snapshot_required`** true.
- **Visible outcome:** Global tokens updated; front end depends on theme/block consumption.
- **Rollback:** **Eligible** under same rules as **`replace_page`** for eligibility service (token action type).

**Edge cases:** Theme caches; another plugin overwriting options; **visual** mismatch vs **stored** value; token group partially updated.

---

### `finalize_plan`

- **Does:** **`Finalize_Plan_Handler`** → **`Finalization_Job_Service`**: plan-level **closure** / completion machinery (completion summaries, run state — see handler artifacts).
- **When:** **Finalize plan** on **Confirm** step (POST) with **`aio_finalize_plan_actions`**; or finalization envelope from queue tooling.
- **Prerequisites:** Valid plan id; envelope has **empty** `plan_item_id` at contract level for this action shape.
- **Visible outcome:** Plan record/status and summaries updated per job; **not** a substitute for running all item executors first.
- **Rollback:** Not a “content” action; undo is **operational** (restore plan backup, manual status) not snapshot rollback.

**Edge cases:** **Partial execution** before finalize — finalized plan may still show **deferred** / incomplete items in UI summaries; business meaning of “complete” vs “finalized” must be aligned with your process.

---

### `rollback_action`

- **Does:** Separate **queue** path: **`Rollback_Executor`** restores from snapshot pair (when job is processed).
- **When:** Operator submits **Request rollback** on **Logs & rollback** (eligible rows only).
- **Prerequisites:** **`aio_execute_rollbacks`**; passing **eligibility**; valid pre/post IDs.
- **Visible outcome:** Success/error redirect flags on workspace; site state per rollback handler.
- **Rollback:** N/A (this **is** rollback).

---

### `update_page_metadata` (non-executable)

- **Listed in code** as **separate** from executable **`ALL`**. SEO/metadata **recommendations** do **not** enqueue this type today. **Do not expect** automatic metadata writes from the plan for this channel.

---

## Risk awareness

1. **Review before execute** — Approving only marks intent. **Execute** (or batch queue) applies **live** mutations. Use **Export plan** ([build-plan-overview.md](build-plan-overview.md)) before large batches.
2. **Partial execution reality** — Jobs run **asynchronously**; some items may **`completed`** while others **`failed`** or remain **`approved`**. The queue can be **paused** or **backlogged**; always read **Queue → Execution** logs.
3. **Retry vs rollback** — **Retry** re-runs the **forward** handler for a failed **plan item** job. **Rollback** attempts to **revert** a **successful** change using **snapshots** for supported types only. A failed create is not “rolled back” by the same button as a successful replace.
4. **Locks** — The executor acquires **scope locks**; concurrent execution of the same target may be **refused** (`Could not acquire lock`).
5. **Handler missing** — If a type were not registered, the executor returns **action not available** (should not happen for types in `Execution_Provider` today).

---

## User-visible monitoring

- **Queue & Logs** (`aio-page-builder-queue-logs`): **Queue** tab shows job rows; **Execution Logs** tab shows execution-oriented history (capability-gated, redacted per product rules). Use these to correlate **`job_type`** (same string as **`action_type`** for normal jobs) with outcomes.
- **Build Plan workspace:** Row **status** badges (**completed** / **failed**), step messages after **rollback** / **finalize** redirects, token detail **execution** lines.
- **PHP error log:** Workspace and export may write **non-secret** audit lines (e.g. finalize result, export event)—suitable for hosts that expose error logs to admins.

---

## FAQ

**Why did my approved SEO line never run?**  
SEO items are **not** mapped to an executable action in `Bulk_Executor`; metadata writes are **not** the same pipeline.

**Does “Apply navigation” in the UI run `update_menu`?**  
**Approve/deny** updates the plan. **`update_menu`** runs only when **execution** processes **`menu_change`** items.

**Can I roll back a new page?**  
Not via the **same** snapshot rollback path as **replace_page** / **tokens** in current eligibility rules; handle manually.

**What if pre-snapshot failed?**  
Executor may still run the handler; rollback **eligibility** may fail later due to missing or invalid snapshots.

---

## Implementation pointers (read-only)

- Action constants: `plugin/src/Domain/Execution/Contracts/Execution_Action_Types.php`
- Handler registration: `plugin/src/Infrastructure/Container/Providers/Execution_Provider.php`
- Envelope building: `plugin/src/Domain/Execution/Queue/Bulk_Executor.php`
- Pre-snapshot families: `plugin/src/Domain/Rollback/Snapshots/Pre_Change_Snapshot_Builder.php`
- Rollback eligibility: `plugin/src/Domain/Rollback/Validation/Rollback_Eligibility_Service.php`
