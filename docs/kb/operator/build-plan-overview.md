# Build Plan overview — list, workspace, steps, and safe review

**Audience:** Operators and editors who open **Build Plans** without reading internal specs.  
**Primary screen:** **AIO Page Builder → Build Plans** (`aio-page-builder-build-plans`).  
**Capabilities:** Viewing the list and workspace requires **`aio_view_build_plans`**. **Approving or denying** line items requires **`aio_approve_build_plans`**. **Executing** queued work requires **`aio_execute_build_plans`** (not granted to the default Editor role). See [concepts-and-glossary.md](../concepts-and-glossary.md).

**Related:** [admin-operator-guide.md §6–§7](../../guides/admin-operator-guide.md); [end-user-workflow-guide.md §2–§3](../../guides/end-user-workflow-guide.md); [onboarding-and-profile.md](onboarding-and-profile.md); [operational-analytics.md](../analytics/operational-analytics.md) (Build Plan Analytics); [executor-locking-idempotency-contract.md](../../contracts/executor-locking-idempotency-contract.md) (technical).

---

## 1. What a Build Plan is

A **Build Plan** is a **structured, human-reviewed package of proposed work** for your site. It is usually produced from **AI planning** (an **AI Run**) and stored as a single plan record. The plan is **not** the live site: it is a **checklist** of steps and **items** (page updates, new pages, navigation changes, tokens, SEO notes, etc.) that you **review**, **approve or deny**, and only then **execute** through governed actions.

**Planner vs executor (plain language):**

- **Planning / AI** proposes *what could change* and writes it into the plan.
- **You** (and your team) decide *what should change* by reviewing, approving, and denying items.
- **Execution** applies approved work **asynchronously** (queue jobs), not as invisible magic. Someone with execution capability must trigger it where the UI allows.

Nothing in the plan list or workspace should be treated as “already done” on the public site until execution has finished and you have verified the outcome.

---

## 2. Build Plans list screen

**Route:** `aio-page-builder-build-plans` with **no** `plan_id` (or `id`) query argument.

**Copy on screen:** Short description that you **review and manage** plans and **open** a plan for steps and items.

**Table columns:**

- **Plan** — Title (or fallback to ID).
- **Plan ID** — Internal identifier (use when matching queue logs or support tickets).
- **Status** — Plan-level lifecycle (e.g. pending review, approved, in progress, completed—exact values come from the plan record).
- **Source run** — Reference to the **AI Run** that produced or informed the plan, when present.
- **Actions** — **Open** → loads the **workspace** for that plan (`plan_id` in the URL).

**Empty state:** Message that there are no plans yet and to **create a plan from an AI Run**.

**List size:** Recent plans are loaded from storage (implementation lists a capped recent set—use analytics or support tools if you need older history).

---

## 3. Build Plan workspace

**Opening:** From the list, **Open**, or a direct URL with `plan_id=` (legacy `id=` is also accepted).

**Layout (three zones):**

1. **Context rail (side)** — **Plan title**, **Plan ID**, **Source AI run**, **plan Status**, **Site purpose** and **Site flow** summaries, optional **Industry / subtype** notes, **Warnings**, and actions: **Save and exit** (back to list), **Export plan** (if your user has export/download capability), optional **View source artifacts** (sensitive diagnostics capability + valid run ref).
2. **Stepper (top)** — Numbered steps with **title**, **badge**, and **unresolved count**. Steps link with `&step=<index>` or `&step=<step_type>` (0-based index is the common form in URLs).
3. **Main workspace** — Content for the **active** step: item table, bulk actions, messages, and **View detail** / row actions. If a step is **blocked**, the workspace shows that **earlier steps must be completed** first.

**Row vs detail:** Rows summarize each **plan item**. **View detail** (and similar actions) open a **detail panel** or focused view for one item’s payload without leaving the overall plan.

**Not found:** If the plan ID is invalid, you see **Plan not found** and a link **Back to Build Plans**.

---

## 4. Plan review vs action execution

| **Review phase** | **Execution phase** |
|------------------|---------------------|
| Read items, open detail, use **Approve**, **Deny**, or bulk review where offered. | After items are **approved**, use **Execute** (or step-specific execute controls) where the product supports it. |
| Changes **only** the plan record (what is allowed to run later). | Enqueues **real site changes** (pages, hierarchy, menus, tokens, etc., depending on item type). |
| Needs **`aio_approve_build_plans`** for mutations. | Needs **`aio_execute_build_plans`** for execute actions. |
| Reversible **before** execution by changing review decisions where the state machine allows. | Jobs run over time; track **Queue & Logs**; rollback is **separate** and **not** guaranteed (see logs step). |

**Advisory-only items:** Some item types are **guidance or notes** (for example overview notes, hierarchy notes, or SEO text). They may **not** show **Execute** because there is **no direct automated action**—you use the information elsewhere or apply changes manually. Executable types include, for example, **new page**, **design token**, **hierarchy assignment**, and **new menu** items, subject to status and capabilities.

---

## 5. Stepper: moving through steps 1–9 (default labels)

The generator seeds **nine** steps in order. **Titles** are stored on the plan; defaults below match `Build_Plan_Generator` English labels. **Always trust the stepper on screen** if titles differ.

| Step # (UI) | Default title | Role |
|-------------|---------------|------|
| 1 | **Overview** | Orientation, summary, **Start review** entry point. |
| 2 | **Existing page changes** | Updates to current pages (review/approve/deny; execution per item rules). |
| 3 | **New pages** | Pages to create (template/rationale; approve then execute when supported). |
| 4 | **Hierarchy & flow** | Parent/child and flow assignments (execute when supported). |
| 5 | **Navigation** | Menu / navigation items (review pattern; labels may use **Apply** / **Deny** where shown). |
| 6 | **Design tokens** | Token-related work (execute when supported). |
| 7 | **SEO** | SEO/media recommendations—often **advisory**; may lack execute. |
| 8 | **Confirm** | Finalization / confirmation step (governed bulk actions when enabled). |
| 9 | **Logs & rollback** | Execution history and **rollback** requests when eligible (queued; may fail). |

**Badges** on each step (from the stepper builder) use keys such as **`not_started`**, **`in_progress`**, **`blocked`**, **`complete`**, **`error`**. A step is **blocked** if **any earlier step** still has **unresolved** items (items whose status is not in a terminal review/execution state—see §8).

**Navigation rule:** You can open a later step only when it is **not blocked**; otherwise the stepper shows it disabled until earlier work is resolved.

---

## 6. Before you execute anything (safety)

1. **Confirm plan identity** — Check **Plan ID** and **Source AI run** in the context rail; wrong plan = wrong site impact.
2. **Read warnings** — Context rail **Warnings** and inline notices may flag dependency or policy issues.
3. **Finish review** — **Approve** only items you accept; **Deny** or **Skip** what you do not want executed. Execution generally applies **approved** work, not the whole plan blindly.
4. **Check capabilities** — Without **`aio_execute_build_plans`**, you should not see working execute controls; do not share admin accounts to bypass role design.
5. **Expect async work** — After execute, open **Queue & Logs** for job status; large plans do not finish instantly.
6. **Verify after run** — Spot-check critical pages, menus, and legal content. **Rollback** (logs step) is not a perfect undo; eligibility and failures are shown in the UI.
7. **Export if needed** — **Export plan** (when permitted) captures a JSON snapshot for audit or support **before** large changes.

---

## 7. How to read plan item status

Items use a **server-controlled** status model. Common values you will see:

| Status | Meaning (user-facing) |
|--------|------------------------|
| **Pending** | Awaiting your **Approve** / **Deny** / **Skip** (or bulk equivalent). |
| **Approved** | Accepted for the execution path; **Execute** may appear when the item type supports it and your role allows. |
| **Rejected** | Excluded from execution (terminal for that item’s review outcome). |
| **Skipped** | Intentionally not executed (terminal). |
| **In progress** | A job is running or was started for that item. |
| **Completed** | Finished successfully (terminal). |
| **Failed** | Execution failed; **Retry** or **Skip** may appear per rules. |

**Terminal** statuses stop further automatic movement for that item until an operator takes a supported follow-up action.

**Note types:** Overview notes, hierarchy notes, and confirmation-style items may be **excluded from “unresolved” counts** in the stepper so the step can show **complete** while you still read the text—treat them as **read-only guidance** unless the UI offers an explicit action.

---

## 8. FAQ and troubleshooting

**Why are some items advisory only?**  
The product only offers **Execute** for item types that have a **safe, automatable handler** (see resolver logic: design tokens, hierarchy assignment, new menu, new page, etc.). **SEO** recommendations or **notes** may be **informational** so you can apply changes in your SEO tools or editorial process.

**Why can I approve but not execute?**  
**Approve** uses **`aio_approve_build_plans`**; **Execute** uses **`aio_execute_build_plans`**. Many sites keep execution with administrators only.

**What if the plan feels too large or complex?**  
Use **Export plan** (if allowed) and review offline; focus one **step** at a time; **Deny** items you defer to a later plan; split work by **regenerating** a smaller plan from a fresh run if your process allows; use **Build Plan Analytics** (`aio-page-builder-build-plan-analytics`) for trends and blockers—not for editing plans.

**Why is a step “blocked”?**  
An **earlier** step still has items that are not in a **terminal** status. Clear those decisions first.

**Where is rollback?**  
**Logs & rollback** step (typically **step 9** in the default stack). Rollback is **queued** and can be **ineligible** or **error**—read the messages and verify the site afterward.

**Does Save and exit save my review?**  
It returns you to the **list**; review mutations are saved when you submit the relevant **Approve/Deny/Execute** forms with valid nonces, not merely by leaving the screen.

---

## 9. Build Plan Analytics (read-only)

**Menu:** **AIO Page Builder → Build Plan Analytics** (`aio-page-builder-build-plan-analytics`). Same view capability as Build Plans.

**Purpose:** **Observational** trends—plan review patterns, common blockers, execution failures, rollback frequency—with optional **date range**. **No** plan mutation. Link **Back to Build Plans** returns to the list.

Detail: [operational-analytics.md](../analytics/operational-analytics.md).

---

## 10. Step-specific guides (anchor links)

This overview is the **home** for Build Plan documentation. Each step article links back here; stubs reserve filenames for deeper content.

| Step # (UI) | Default title | Step KB |
|-------------|---------------|---------|
| 1 | Overview | [build-plan-step-overview.md](build-plan-step-overview.md) |
| 2 | Existing page changes | [build-plan-step-existing-page-changes.md](build-plan-step-existing-page-changes.md) |
| 3 | New pages | [build-plan-step-new-pages.md](build-plan-step-new-pages.md) |
| 4 | Hierarchy & flow | [build-plan-step-hierarchy-flow.md](build-plan-step-hierarchy-flow.md) |
| 5 | Navigation | [build-plan-step-navigation.md](build-plan-step-navigation.md) |
| 6 | Design tokens | [build-plan-step-design-tokens.md](build-plan-step-design-tokens.md) |
| 7 | SEO | [build-plan-step-seo.md](build-plan-step-seo.md) |
| 8 | Confirm | [build-plan-step-confirmation.md](build-plan-step-confirmation.md) |
| 9 | Logs & rollback | [build-plan-step-logs-rollback.md](build-plan-step-logs-rollback.md) |

**Existing + new pages (review depth):** [build-plan-review-existing-and-new-pages.md](build-plan-review-existing-and-new-pages.md) — approve/deny/bulk for URL **`step=1`** and **`step=2`**.

**Hierarchy, navigation, design tokens, SEO (indices `3`–`6`, UI steps 4–7):** [build-plan-hierarchy-navigation-tokens-seo.md](build-plan-hierarchy-navigation-tokens-seo.md) — executable vs advisory rows, token execution, SEO posture, edge cases.

**Confirm & Logs / rollback (indices `7`–`8`, UI steps 8–9):** [build-plan-finalization-logs-rollback.md](build-plan-finalization-logs-rollback.md) — finalization buckets, conflicts, rollback eligibility, export, edge cases.

---

## 11. Cross-links

- Glossary and capabilities: [concepts-and-glossary.md](../concepts-and-glossary.md)  
- End-user review summary: [end-user-workflow-guide.md §2–§3](../../guides/end-user-workflow-guide.md)  
- Operator menu and execution: [admin-operator-guide.md §6–§7](../../guides/admin-operator-guide.md)  
- Crawl context for plans: [crawler-sessions-and-comparison.md](crawler-sessions-and-comparison.md)  
- Doc routing: [FILE_MAP.md](../FILE_MAP.md) §6
