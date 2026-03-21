# Rollback, retry, and recovery — safe operator playbook

**Audience:** Operators handling **failed jobs**, **bad executions**, or **partial plan runs**.  
**Parent:** [build-plan-overview.md](build-plan-overview.md)  
**Related:** [build-plan-execution-actions.md](build-plan-execution-actions.md); [build-plan-finalization-logs-rollback.md](build-plan-finalization-logs-rollback.md); [concepts-and-glossary.md](../concepts-and-glossary.md); [admin-operator-guide.md §7–§9](../../guides/admin-operator-guide.md); [support-triage-guide.md](../../guides/support-triage-guide.md); [diagnostics-screens.md](diagnostics-screens.md); [executor-locking-idempotency-contract.md](../../contracts/executor-locking-idempotency-contract.md).

---

## 1. Rollback vs retry (unambiguous)

| | **Rollback** | **Retry** |
|---|----------------|-----------|
| **Intent** | **Undo** a change that **already succeeded** (or was captured with pre/post snapshots), restoring prior state where the rollback handler can. | **Run the same forward job again** after a **failure** or transient error (e.g. lock conflict). |
| **Typical trigger** | **Logs & rollback** step: **Request rollback** on an eligible history row ([build-plan-finalization-logs-rollback.md](build-plan-finalization-logs-rollback.md)). | **Queue** tab: **Retry** on a **failed** job row; or **Retry** on a **failed** plan item in the workspace where that control is wired (e.g. design tokens). |
| **Capability** | **`aio_execute_rollbacks`** (server-enforced on POST). | Queue UI: **`aio_manage_queue_recovery`** for **Retry** / **Cancel** on the Queue tab. Plan row retry uses **`aio_execute_build_plans`** where applicable. |
| **Risk** | May **conflict** if something else changed the same target later; not a full site restore. | May **duplicate** work if the first run partially succeeded (e.g. page created but job marked failed)—read **failure_reason** first. |

**Do not** use rollback to “fix” a **failed** queue job that never completed successfully—address the **error**, then **retry** or **correct data** and enqueue again.

---

## 2. Which actions may support rollback (governed)

**Eligibility** (what the product will allow for **Request rollback**) is decided by **`Rollback_Eligibility_Service`**. In the current implementation, only these **original execution action types** are treated as having a **supported rollback handler** for that check:

- **`replace_page`**
- **`apply_token_set`**

**`update_menu`** may still participate in **pre/post operational snapshots** for audit, but **governed rollback** from the logs flow will typically be **ineligible** (“no handler for action type”) until product code extends eligibility.

**Not expected** for first-party pair rollback through this path: **`create_page`**, **`create_menu`**, **`assign_page_hierarchy`**, **`finalize_plan`** (see [build-plan-execution-actions.md](build-plan-execution-actions.md)).

**UI copy** on **Logs & rollback** aligns with **page replacement + token** scope for v1.

---

## 3. Why some actions do not support rollback

- **No pre-change snapshot contract** for that action in the bulk executor (e.g. new page, new menu, hierarchy move).
- **Eligibility** explicitly excludes the action type even if snapshots exist elsewhere (menus today).
- **Snapshots expired, used, invalidated**, or **newer change** on the same target blocks rollback.
- **Target** no longer resolvable (page deleted, etc.).

Recovery then means **manual** edits, **staging restore**, **export/import** workflows, or a **new plan**—not the **Request rollback** button.

---

## 4. Partial failures

- **Bulk / batch execution** can end **`partial`**: some jobs **completed**, some **failed** or **refused**. Per-item results may include **`retry_eligible`** flags for **automatic** eligibility marking (today tied to specific **error codes** such as **lock conflict** on the job result—not every failure).
- **Queue health** summary can show **stale locks**, **bottlenecks**, **long-running** jobs, and a count of **failed jobs eligible for retry** (same policy as the Queue tab row flags: failed + under max **retry_count** + **retryable job type**).

**Practical approach**

1. Open **Queue & Logs** → **Queue**; read **failure_reason** per row.  
2. Fix **root cause** (template missing, bad slug, permissions, theme).  
3. **Retry** only when the row shows **Retry** and you have **`aio_manage_queue_recovery`**.  
4. Re-open the **Build Plan** and confirm **item statuses** (`failed` / `completed`) match what you expect before running more executes.

---

## 5. Step-by-step guides

### 5.1 Check whether rollback is available

1. Go to the plan workspace → **Logs & rollback** ([build-plan-finalization-logs-rollback.md](build-plan-finalization-logs-rollback.md)).  
2. Find a row with **pre** and **post** snapshot IDs and **Request rollback** enabled (and hold **`aio_execute_rollbacks`**).  
3. If no button or POST returns **ineligible** / **missing_snapshots**, rollback is **not** available—stop and use §5.4.

### 5.2 Retry a failed action (queue)

1. **AIO Page Builder → Queue & Logs** → **Queue** tab (`aio-page-builder-queue-logs`).  
2. Locate **`failed`** rows; confirm **`job_type`** (`create_page`, `replace_page`, `update_menu`, `apply_token_set`, `finalize_plan`, `rollback_action` are **manually retryable** types in recovery policy).  
3. If **Retry** appears and you have **`aio_manage_queue_recovery`**, use it; read the redirect **message** (`aio_recovery` / `aio_recovery_msg`).  
4. Wait for the worker/cron to **process** the job again; re-check **Execution** logs tab if needed.

**Limits:** **`retry_count`** must stay **below 5** (`Queue_Recovery_Service` / queue row normalization). **`assign_page_hierarchy`** and **`create_menu`** failures are **not** in the queue recovery retry type list—handle via plan/workspace execution or support.

### 5.3 Retry a failed plan item (workspace)

Where the UI wires **Retry** (e.g. **design tokens** in **`failed`** state with execute capability), use **Retry** after fixing the underlying issue. **Hierarchy** and **create menu** flows have analogous handlers when exposed.

### 5.4 Escalate when no safe recovery path exists

1. **Export plan** (if permitted) and note **Plan ID**, **job_ref**, **failure_reason**.  
2. Open [support-triage-guide.md](../../guides/support-triage-guide.md); gather **Queue** row + **Execution** log lines (redacted as appropriate).  
3. Use **Diagnostics** screens if the failure involves ACF, forms, or registry ([diagnostics-screens.md](diagnostics-screens.md)).  
4. Avoid **re-running** large batches until the **root cause** is understood—risk of **duplicate pages** or **double application** of tokens/menus.

---

## 6. Edge cases

- **Rollback unavailable** — Expect **redirect errors** (`ineligible`, `missing_snapshots`, `nonce`, `unavailable`). Do not hammer **Request rollback**; verify snapshots and permissions.  
- **Retried job still fails** — You are likely hitting a **data** or **environment** error; **cancel** the job if appropriate (same recovery capability) and fix forward.  
- **History row exists but is not reversible** — Row may be **audit-only** or action type **unsupported** for eligibility; **newer change conflict** also blocks rollback.  
- **Accidental overwrite / replace** — **`replace_page`** is high impact; **approve carefully**, use **export**, and prefer **rollback** only when **eligible** and no **newer** change exists.  
- **Retry after partial success** — If a **create_page** job failed **after** post creation, retry may **error** or create **duplicates** depending on idempotency—inspect the site and logs before retrying.  
- **Rollback completed but site still “wrong”** — Cache, CDN, or manual edits; rollback does not replace **full backup** discipline.

---

## 7. What not to do

- Do **not** treat **Approve** as undo.  
- Do **not** assume **SEO** or **metadata-only** recommendations have a rollback button.  
- Do **not** share **admin** accounts to bypass **`aio_execute_rollbacks`** / **`aio_manage_queue_recovery`**.  
- Do **not** rely on rollback for **compliance** “right to be forgotten” or legal erasure—use proper WordPress tools.

---

## 8. FAQ

**Why is Retry missing on my failed job?**  
Wrong **status** (not `failed`), **retry_count** too high, **job_type** not in the recovery allowlist, or missing **`aio_manage_queue_recovery`**.

**Does cancel remove the bad page?**  
**Cancel** stops the **queue job**; it does **not** delete content already written.

**Where is recovery audited?**  
Queue recovery actions are appended to the **`aio_page_builder_queue_recovery_audit`** option (capped list) and may hit the **PSR logger** when configured.

---

## 9. Cross-links (quick)

| Topic | Doc |
|-------|-----|
| Per-action behavior & snapshots | [build-plan-execution-actions.md](build-plan-execution-actions.md) |
| Logs step & rollback POST flow | [build-plan-finalization-logs-rollback.md](build-plan-finalization-logs-rollback.md) |
| Queue screen & tabs | [admin-operator-guide.md §9](../../guides/admin-operator-guide.md); [support-triage-guide.md §1–§2](../../guides/support-triage-guide.md) |
| Locks / idempotency | [executor-locking-idempotency-contract.md](../../contracts/executor-locking-idempotency-contract.md) |
| Terms & caps | [concepts-and-glossary.md](../concepts-and-glossary.md) |
