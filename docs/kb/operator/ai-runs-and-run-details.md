# AI Runs — list, month-to-date spend, and run details

**Audience:** Operators and support reviewing AI activity.  
**Screen:** **AIO Page Builder → AI Runs** (`aio-page-builder-ai-runs`).  
**Related:** [ai-providers-credentials-budget.md](ai-providers-credentials-budget.md); [concepts-and-glossary.md](../concepts-and-glossary.md); [admin-operator-guide.md §5](../../guides/admin-operator-guide.md); [support-triage-guide.md §1](../../guides/support-triage-guide.md); [FILE_MAP.md](../FILE_MAP.md) §4.

---

## 1. Permissions

| Action | Capability |
|--------|------------|
| Open **AI Runs**, view list and run detail | `aio_view_ai_runs` |
| See **raw** prompt/response-style artifact content on run detail | `aio_view_sensitive_diagnostics` |

Without **`aio_view_ai_runs`**, the screen is inaccessible. **Sensitive diagnostics** unlocks fuller artifact review per the detail screen copy.

---

## 2. List view — what you see

- Intro text states that you can **review AI runs and artifact summaries**, and that **raw prompts and provider responses are restricted** (unless sensitive diagnostics applies on detail).
- **Month-to-date spend by provider** (when services are available): table of **Provider**, **Spent (USD)**, **Cap (USD)**, **Status** — same cap logic as **AI Providers** (no cap → **No cap set**; approaching **≥80%**; exceeded → **Cap exceeded** with styling).
- **Run table:** Up to **50** most recent runs (implementation: `list_recent(50, 0)`). Columns: **Run ID**, **Status**, **Provider**, **Model**, **Prompt pack**, **Created**, **Actions → View details**.
- **Experiment runs** may show a badge with variant/experiment label when run metadata marks an experiment.
- Empty list: **No AI runs yet.**

Opening **View details** navigates to the same menu page with **`run_id`** in the query string; the screen renders **run detail** instead of the list.

---

## 3. Run detail — metadata

- **Back to AI Runs** returns to the list.
- **Run not found** if the ID does not resolve.
- **Run metadata** table (values are **redacted** for sensitive keys via artifact redaction helpers):
  - **Run ID**, **Status**, **Actor**, **Created**, **Completed**, **Provider**, **Model**, **Prompt pack**, **Retry count**, **Build plan ref** (reference text when present — not necessarily a hyperlink).
  - If **failover** metadata is present: **Effective provider used** and **Failover** explanation plus an **attempt log** table (provider, model, outcome category, time).
  - **Experiment** row when flagged in metadata.

---

## 4. Token usage and estimated cost (detail)

- Sourced from the **usage metadata artifact** for the run, **not** from generic run metadata alone.
- **Token usage** row:
  - If **total_tokens** is present: shows **prompt + completion = total** (prompt/completion default to **0** in the formatted string when missing).
  - If **total_tokens** is missing: shows **Not available**.
- **Estimated cost** row:
  - If **`cost_usd`** is present: shows a **dollar** amount with **six** decimal places in the UI.
  - If **`cost_usd`** is null/absent: shows **Not available (model not in pricing registry)** — this matches the admin string used when cost is missing on that artifact.
- If there is **no** usage metadata artifact (or it could not be loaded as an array), the **token usage** and **estimated cost** **rows are omitted entirely** from the table (not shown as empty).

---

## 5. Artifact summary table

- Lists **categories** with **Present**, **Redacted**, and **Summary** columns.
- Without **sensitive diagnostics**, a note explains that **raw prompt and provider response content is hidden** and that users with that permission can see **full** content.
- Summary cells may be plain text or JSON-encoded structures for array summaries.

---

## 6. Step-by-step — normal workflows

### 6.1 Review recent AI runs

1. Open **AIO Page Builder → AI Runs** as a user with **`aio_view_ai_runs`**.
2. Scan **Month-to-date spend by provider** (if shown) for cap status.
3. Use the run table to find the **Run ID**, **status**, **provider**, and **model**.

### 6.2 Open run details

1. Click **View details** on a row (or open the admin URL with `page=aio-page-builder-ai-runs&run_id=...`).
2. Read **Run metadata** for timing, provider/model, prompt pack, and build plan reference.
3. Check **Token usage** and **Estimated cost** when those rows appear.

### 6.3 Interpret cost fields

- **Estimated cost** is **not** a guaranteed invoice; it depends on **pricing registry** coverage and provider usage payloads.
- **Not available (model not in pricing registry)** means the UI did not have a **`cost_usd`** value on the usage artifact (commonly because pricing could not be resolved for the model).
- **Missing rows** for tokens/cost mean the usage artifact was absent or unusable — treat cost as **unknown** for that run in the UI.

### 6.4 Cross-check against provider billing

Use the provider’s dashboard for authoritative spend. Align with [ai-providers-credentials-budget.md §6.4](ai-providers-credentials-budget.md) for how month-to-date totals are accumulated.

---

## 7. Edge cases

| Situation | What you see / implication |
|-----------|----------------------------|
| **No runs** | List message **No AI runs yet.** |
| **Spend summary missing** | Section omitted if monthly spend / pricing registry services are unavailable or erroring. |
| **Partial token fields** | Format may show **0** for missing prompt/completion when **total_tokens** exists. |
| **No usage artifact** | No token/cost rows on detail. |
| **`cost_usd` missing** | **Estimated cost** shows **Not available (model not in pricing registry)** when the usage artifact exists but cost is absent. |
| **Run ID wrong / stale link** | **Run not found.** |
| **Failover** | Extra rows and **Failover attempt log**; **Effective provider used** may differ from primary metadata. |

---

## 8. FAQ and troubleshooting

**Why is there no run history?**  
No runs have been recorded yet, the repository failed to load (rare), or your user lacks **`aio_view_ai_runs`**.

**Why is cost blank or “Not available”?**  
Either the **usage metadata artifact** is missing, **`cost_usd`** was never computed (e.g. **model not in pricing registry**), or the detail UI omitted the rows when no usage payload exists.

**Why don’t token numbers match the provider?**  
Displayed tokens come from the **stored usage artifact**; providers may label or aggregate tokens differently.

**Who can see raw prompts/responses?**  
Users with **`aio_view_sensitive_diagnostics`** (see detail screen notice).

**How does this relate to Queue & Logs?**  
The **AI Runs** tab under **Queue & Logs** links into the same run-detail concept for log workflows — see [support-triage-guide.md §1](../../guides/support-triage-guide.md).

---

## 9. Privacy and reporting

AI runs can contain **business context**. Treat exports and support bundles under your data-handling policy. **Operational reporting** to the vendor is separate from AI provider traffic; see [admin-operator-guide.md §10](../../guides/admin-operator-guide.md) and [REPORTING_EXCEPTION.md](../../standards/REPORTING_EXCEPTION.md).

---

## 10. Implementation pointers (for maintainers)

- List + routing: `AI_Runs_Screen.php`
- Detail: `AI_Run_Detail_Screen.php`
- Redaction: `AI_Run_Artifact_Service::redact_sensitive_values`
- Usage artifact category: `Artifact_Category_Keys::USAGE_METADATA`
