# Build Plan — hierarchy, navigation, design tokens, and SEO (steps 4–7)

**Audience:** Operators reviewing a Build Plan after **Existing page changes** and **New pages**.  
**Parent:** [build-plan-overview.md](build-plan-overview.md)  
**Related:** [build-plan-review-existing-and-new-pages.md](build-plan-review-existing-and-new-pages.md); [admin-operator-guide.md §6–§8](../../guides/admin-operator-guide.md); [executor-locking-idempotency-contract.md](../../contracts/executor-locking-idempotency-contract.md).

This article uses **URL step indices** (`&step=` on the workspace), which are **zero-based** in the default plan shape. **UI step numbers** in the overview table are **one higher** (Overview is UI step 1 = index `0`).

| URL `step` index | Typical UI step # | Default step title |
|------------------|-------------------|--------------------|
| `3` | 4 | Hierarchy & flow |
| `4` | 5 | Navigation |
| `5` | 6 | Design tokens |
| `6` | 7 | SEO |

---

## 1. What appears in these steps (content model)

- **Index `3` — Hierarchy & flow**  
  The plan can store **`hierarchy_assignment`** items (concrete parent/child assignments) and **`hierarchy_note`** items (unresolved or explanatory notes). In the **current workspace**, this step renders the plan’s **site flow / hierarchy narrative** (`site_flow_summary`) as readable text, **not** an item table with row actions.

- **Index `4` — Navigation**  
  Rows come from **`menu_change`** (updates to existing menus) and **`menu_new`** (net-new menu creation). The list shows context, action, current vs proposed menu naming, and a short diff hint where the payload provides it.

- **Index `5` — Design tokens**  
  Rows are **`design_token`** items: global style token **group**, **name**, **current** value (from the site’s global token store when available), **proposed** value, and **confidence**. Approved items can be **executed** so the plugin enqueues work to apply tokens (asynchronous execution).

- **Index `6` — SEO**  
  Rows are **`seo`** items: target page reference, fixed **action type** label (“Advisory recommendation (no direct write)”), and **confidence**. The list does **not** populate generic “current vs proposed” columns from payload in this version; rationale lives in the item payload and detail text.

---

## 2. Executable vs advisory (row action model)

The product’s **row action resolver** drives which controls appear per item type and status. Capabilities still apply: **`aio_approve_build_plans`** for approve/deny, **`aio_execute_build_plans`** for execute/retry.

| Item type | Approve / Deny | Execute / Retry (when approved / failed) | Interpretation |
|-----------|----------------|------------------------------------------|----------------|
| **`hierarchy_assignment`** | Yes | Yes | Automatable parent assignment when the UI exposes the row (see §1). |
| **`hierarchy_note`** | Yes | No | **Advisory** — no queue execution for this type. |
| **`menu_change`** | Yes | No (v1) | Review/decision only for menu **updates**; no row **Execute** in the resolver. |
| **`menu_new`** | Yes | Yes (in model) | New menu creation is an executable type in the resolver; **see §7** for workspace wiring caveats. |
| **`design_token`** | Yes | Yes | Apply proposed token values via execution queue after approval. |
| **`seo`** | Yes | No | **Advisory-only** in v1 — no direct write execution path for SEO/meta/media. |

**View detail** is intended to be available on rows; **View dependencies** may be present in the payload but is **disabled** in the workspace layer where no handler is wired.

---

## 3. Token-related actions (index `5`)

1. **Review** — Compare **current** vs **proposed** for each token; read **rationale** in the detail panel.  
2. **Approve or deny** — Per row (linked actions) or bulk: **Apply all tokens**, **Apply to selected**, **Deny all**, plus **Clear selection** when selection exists.  
3. **Execute** — After **Approved**, **Execute** / **Retry** (if failed) runs only when your user has execution capability and the state machine allows the transition. Execution is **queued**, not instant.  
4. **Detail: execution and rollback** — The token detail panel can show rollback-related hints when snapshot data exists for that token set; “rollback eligible” is informational for follow-up in **Logs & rollback**.

If the **live** theme or another integration overrides how tokens surface on the front end, the **stored** global value and **visual** result can differ — verify in the editor and front end after jobs complete.

---

## 4. SEO / media advice (index `6`)

- The step shows an **informational banner**: recommendations are **advisory**; **approving** records acceptance in **plan artifacts**, not a first-party SEO write.  
- **Approve selected** / **Approve all** / **Deny all** / **Clear selection** behave like other review steps (bulk bar inside the list form).  
- **Do not expect Execute** for SEO rows — by design there is **no** execute URL wiring for this step. Apply guidance in your SEO plugin, editor, or media workflows manually.

---

## 5. Step-by-step procedures

### 5.1 Review a row (navigation, tokens, SEO)

1. Open the plan workspace and select the step (`step=4`, `5`, or `6`).  
2. Read the **status** badge (e.g. Pending, Approved).  
3. Scan **summary columns** for that step (navigation: context and menu labels; tokens: group/name/values; SEO: target and confidence).  
4. If **validation messages** exist for navigation, read the **warning** notice above the list.

### 5.2 Open detail for one item

1. Click **View detail** on the row (adds `detail=<item_id>` to the URL) or follow the equivalent link from the detail column pattern your build uses.  
2. Use the **detail panel** sections (navigation: structured comparison; tokens: token identity, values, execution/rollback notes; SEO: advisory copy and confidence).  
3. Use **Approve** / **Deny** from the detail panel when links are present and enabled.

### 5.3 Execute eligible items (tokens)

1. **Approve** the token row (or use bulk apply for pending items).  
2. When status is **Approved** and **Execute** is enabled, use row **Execute** or bulk **Execute all remaining** / **Execute selected** (token step bulk bar).  
3. Monitor **Queue & Logs** and re-check token values after completion.

### 5.4 Retry failed eligible items (tokens)

1. Identify **Failed** token rows.  
2. If **Retry** is enabled and you still intend to apply the value, use **Retry** (same permission rules as Execute).  
3. If retries keep failing, use diagnostics/logs and consider **denying** the item or adjusting the proposed value in a new plan.

### 5.5 Interpret advisory recommendations (hierarchy notes, SEO, navigation decisions)

- **Hierarchy notes** — Treat as **guidance**; resolve structure manually or in a later plan if no assignment row is available.  
- **SEO** — Use the text as a **checklist** for titles, meta, schema, and media; approval only **documents** agreement with the recommendation.  
- **`menu_change` approved but not “executed” in-row** — In v1 the resolver does not offer **Execute** for this type; implementing the menu change may happen through other execution surfaces or manual work — confirm against your deployment and queue behavior.

---

## 6. Edge cases

- **Action visible but prerequisites not met** — **Execute** requires **Approved** status, execution capability, and allowed transitions. **Pending** rows show disabled execution messaging in token detail. **Blocked** earlier steps can prevent reaching the step in a useful state.  
- **Token applied but the site looks wrong** — Caching, theme CSS, block styles, or plugin overrides may mask the global token value; compare **current** in the plan after reload and inspect the front end with cache cleared.  
- **SEO row has no Execute** — Expected; use external tools.  
- **Mixed navigation rows (`menu_change` + `menu_new`)** — Only **`menu_new`** is treated as executable in the resolver; **`menu_change`** remains approve/deny-only at the row-action level.  
- **Hierarchy step shows only a paragraph** — There is **no** item table on this step in the current workspace; assignments may still exist inside the plan data for other tooling.  
- **Navigation bulk bar: “Apply to selected” / “Clear selection” appear disabled** — The current navigation bulk template wires **Apply All** and **Deny All**; per-row **Approve** / **Deny** links remain the path for individual decisions.  
- **Execute shown as a button without navigation (navigation / menu_new)** — If **Execute** appears enabled but behaves like a non-navigating control, prefer any **bulk create-menu** workflow your build exposes, or treat execution as **not yet wired** for that row pattern and follow up manually.

---

## 7. FAQ and troubleshooting

**Why does Hierarchy not look like the other steps?**  
The screen is intentionally **summary-first** today: you read **site flow** text. Detailed assignment rows are not rendered in this workspace module.

**Why can I approve a menu update but never execute it from the row?**  
**`menu_change`** is **approve/deny-only** in the row action model for v1.

**Why do SEO approvals not change rankings or meta?**  
Approvals **record** the recommendation on the plan; **no** automated SEO write runs from this step.

**I approved tokens; nothing changed immediately.**  
Execution is **asynchronous**. Check **Queue & Logs** and job outcomes.

**Who can approve vs execute?**  
Different capabilities — see [concepts-and-glossary.md](../concepts-and-glossary.md) and [build-plan-overview.md §4](build-plan-overview.md).

---

## 8. Step stub cross-links

Per-step filenames still anchor short entry points: [build-plan-step-hierarchy-flow.md](build-plan-step-hierarchy-flow.md), [build-plan-step-navigation.md](build-plan-step-navigation.md), [build-plan-step-design-tokens.md](build-plan-step-design-tokens.md), [build-plan-step-seo.md](build-plan-step-seo.md).
