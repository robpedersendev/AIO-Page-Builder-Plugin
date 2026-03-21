# Crawler — sessions, page snapshots, and comparison

**Audience:** Operators and support who run site crawls and interpret results for AI planning.  
**Screens:** **Crawl Sessions** (`aio-page-builder-crawler-sessions`), **Crawl Comparison** (`aio-page-builder-crawler-comparison`).  
**Related:** [onboarding-and-profile.md](onboarding-and-profile.md); [admin-operator-guide.md §4](../../guides/admin-operator-guide.md); [concepts-and-glossary.md](../concepts-and-glossary.md); [end-user-workflow-guide.md §1](../../guides/end-user-workflow-guide.md); [FILE_MAP.md](../FILE_MAP.md) §5.

---

## 1. What the crawler is for (operator view)

The crawler **walks the current WordPress site’s public pages** (same site only), discovers URLs within **bounded profiles**, and stores **structured snapshots** per page (title, classification, navigation participation, status, etc.) — **not** full HTML bodies in the admin UI. That data gives the **AI planner** richer **site structure and content-context** when building recommendations and Build Plans. Onboarding links here from **Existing site** and **Crawl preferences** so operators can run a crawl before **Request AI plan**.

---

## 2. What content is inspected (high level)

- **Scope:** This **site only**; **public-facing** crawl model; **normalized URL identity**; **meaningful-page** focus (screen reader text and list copy summarize rules: no arbitrary external hosts, bounded behavior).
- **Per page (session detail table):** URL, trimmed **title** snapshot, **classification**, **Nav** (navigation participation), row **Status** (`crawl_status`).
- **Profiles** (fixed set — no custom unbounded profiles): each profile caps **max pages** and **max depth** within product ceilings (e.g. quick refresh vs full baseline vs support triage — labels come from `Crawl_Profile_Service`).

---

## 3. What a crawl session is

- A **session** is one **crawl run** identified by a **Run ID**.
- **Session list** columns: Run ID, site **host**, **profile** (label), **final status**, counts (**Discovered**, **Accepted**, **Excluded**, **Failed**), **Started**, actions.
- **Storage:** Copy on the list screen states sessions come from the **crawl snapshot table**, merged with **per-run session metadata** in options when present.
- **Detail view:** **View pages** opens the same menu page with `run_id` in the query string; shows up to **500** page rows for that run (implementation limit on the detail query).

---

## 4. Starting and retrying runs (permissions and semantics)

| Action | Capability |
|--------|------------|
| Open **Crawl Sessions** / **Crawl Comparison**, view lists and detail | `aio_view_sensitive_diagnostics` |
| **Start crawl** and **Retry crawl** (POST + nonce) | `aio_run_onboarding` |

**Start crawl:** Choose a **Crawl profile** from the dropdown → **Start crawl**. Success redirects back with a notice (e.g. crawl **queued**). **Failure messages** include: site host could not be determined; **a crawl is already active or recently queued** for this site (short lock window); failed to queue.

**Retry crawl:** Per-row button. Creates a **new** queued session using the **prior session’s stored settings** (including profile), linked as a retry in metadata. It does **not** mutate the old run in place — expect a **new Run ID** when the worker completes registration in the snapshot table.

**Lock:** Duplicate starts/retries for the same site host are blocked while the lock option is valid (**30 minutes** TTL in code).

---

## 5. How the sessions list is populated (truthful behavior)

Distinct **Run IDs** in the UI list are derived from **rows in the crawl snapshot database table** (recent runs first). Until at least **one page row** exists for a new run, that run may **not appear** in the table even though enqueue returned success — the worker must persist snapshots. If you see **No crawl sessions yet** immediately after **Start crawl**, wait for the background crawl to write the first page, then refresh.

---

## 6. Crawl Comparison — what it means

- **Screen:** Read-only. GET form: **Prior run (baseline)** and **New run** → **Compare**.
- **Summary table:** Prior/new **profile** labels, counts **Added**, **Removed**, **Changed**, **Unchanged**, **Reclassified**, **Meaningful (prior/new)**.
- **Page changes:** Each URL gets a **Category** (`added`, `removed`, `changed`, `unchanged`, `reclassified`) and **Reasons** (machine codes such as `title_changed`, `classification_changed`, `canonical_changed`, `nav_participation_changed`, `content_or_summary_changed`).
- **Comparison logic:** URLs are matched by **normalized URL**. **Changed** vs **unchanged** is driven by diffs on title, classification, canonical, navigation participation, and **content hash** (proxy for content/summary change). **Reclassified** is a subset where classification changed.

**Empty or sparse results**

- If you have **not** chosen both runs and submitted the form, **no summary** appears.
- **No page changes** appears when the comparison yields **zero** page change rows — e.g. **both runs have no page records**, or the service returned an empty change set after an error (comparison is wrapped in try/catch; failures may show nothing beyond the form).
- If both runs exist but **share the same URLs** and **no fields differ**, you still get one row per URL with category **unchanged** and **empty** reasons — the summary counts reflect that (high **Unchanged**, **Added/Removed/Changed** near zero).

---

## 7. Crawl freshness and onboarding / planning

- **Crawl preferences** step shows **Latest crawl run** when the prefill service finds sessions; otherwise **No crawl runs recorded yet**, plus a button to **Crawl Sessions**.
- **Stale crawl advisory (logic):** `Onboarding_UI_State_Builder` compares **now** to `latest_crawl_session_timestamp` from the latest crawl session (ended time, else started). If the crawl is **older than the threshold**, it adds a **non-blocking** warning with code `stale_crawl_context` (message: consider a new crawl if site content changed).
- **Threshold:** Default **30 days** if main settings `onboarding_stale_crawl_warning_days` is **0** or unset; if that setting is a **positive** integer, that value is used (days). There is **no dedicated admin settings field** for this key in core screens at present; it is stored in main settings if your deployment sets it.
- **Display:** On the **Submission** step (**Request AI plan**), these warnings (including stale crawl and **profile updated since last run**) are rendered as **warning notices** when present — advisory only; they **do not** block the button by themselves.
- **Build Plans:** Plan definitions can carry a **`crawl_snapshot_ref`** when planning context included crawl data — treat an **old plan** as potentially out of date if the site changed; run a **new crawl** and new planning when structure matters.

---

## 8. Step-by-step workflows

### 8.1 First crawl

1. Confirm **`aio_view_sensitive_diagnostics`** (view) and **`aio_run_onboarding`** (start).
2. **AIO Page Builder → Crawl Sessions**.
3. Select a **Crawl profile** → **Start crawl**.
4. Read the top **admin notice** (success or error).
5. Refresh the list until the new **Run ID** appears (after page rows exist).
6. **View pages** to scan URLs, titles, classifications, and statuses.

### 8.2 Retry a failed or incomplete run

1. Open **Crawl Sessions**.
2. Locate the run → **Retry crawl** (creates a **new** queued run from the same settings).
3. If you see **already active or recently queued**, wait for the lock to expire or the current job to finish.

### 8.3 Review sessions

1. Use the list for **status** and **counts** (discovered / accepted / excluded / failed).
2. Open **View pages** for page-level **classification** and **crawl_status**.
3. Cross-check **Excluded** / **Failed** counts when a run looks incomplete.

### 8.4 Compare two runs

1. **AIO Page Builder → Crawl Comparison**.
2. **Prior run** = older baseline; **New run** = more recent.
3. **Compare** — read **summary** counts, then **Page changes** for URL-level reasons.
4. Use **Meaningful** counts to see how many **meaningfully classified** pages existed in each run.

---

## 9. Edge cases

| Situation | What to expect |
|-----------|----------------|
| **No crawl data yet** | List: **No crawl sessions yet.** Onboarding crawl step: **No crawl runs recorded yet.** Planning may still run with **empty crawl context** in artifacts. |
| **Stale crawl data** | Submission-step **warning** when age &gt; threshold (see §7). |
| **Incomplete crawl** | Low **Accepted**, high **Failed/Excluded**, or few rows in detail — investigate site availability, robots, or profile bounds. |
| **Heavy site changes** | Comparison shows many **Added/Removed/Changed**; run a **fresh** full baseline before relying on plans. |
| **Retry vs new Start** | **Retry** reuses prior session **settings** and links **retry_of**; **Start crawl** uses the profile you pick now — both create **new** runs when queued successfully. |
| **Enqueue succeeded, list empty** | Normal until the first **snapshot row** is stored — refresh later. |
| **Comparison “No page changes”** | Often both runs have **no** page rows, or comparison failed silently — verify both runs have **View pages** data. |

---

## 10. FAQ and troubleshooting

**Why did the crawl not start?**  
Read the redirect **notice**: host missing, **duplicate lock**, or queue/session creation failure. Confirm **`aio_run_onboarding`** and that the **crawl enqueue service** is available.

**Why does comparison look empty?**  
Select **both** runs and click **Compare**. If the summary never appears, both IDs may be invalid or the comparison service threw — confirm runs exist in **Crawl Sessions**.

**When should I run a fresh crawl before planning?**  
After **major** IA/content changes, before an important **Request AI plan**, or when the **stale crawl** warning appears (or your own policy says the last run is too old).

**Why can’t I see Crawl Sessions?**  
You need **`aio_view_sensitive_diagnostics`** (defaults are administrator-oriented — see [concepts-and-glossary.md](../concepts-and-glossary.md)).

**Can I crawl arbitrary external URLs?**  
No — **this site only**, bounded profiles; rules are stated on the Crawl Sessions screen.

---

## 11. Cross-links — onboarding and Build Plans

- **Onboarding:** Steps **Existing site**, **Crawl preferences**, and **Submission** — [onboarding-and-profile.md](onboarding-and-profile.md).
- **Editors:** High-level flow — [end-user-workflow-guide.md §1](../../guides/end-user-workflow-guide.md).
- **Build Plans:** After planning — [admin-operator-guide.md §6–§7](../../guides/admin-operator-guide.md); [end-user-workflow-guide.md §2](../../guides/end-user-workflow-guide.md).

---

## 12. Implementation pointers (maintainers)

- Screens: `Crawler_Sessions_Screen.php`, `Crawler_Session_Detail_Screen.php`, `Crawler_Comparison_Screen.php`
- Enqueue: `Crawl_Enqueue_Service.php`
- List/detail data: `Crawl_Snapshot_Service.php`, `Crawl_Snapshot_Repository.php`
- Compare: `Recrawl_Comparison_Service.php`, `Session_Comparison_Result.php`, `Page_Change_Summary.php`
- Profiles: `Crawl_Profile_Service.php`, `Crawl_Profile_Keys.php`
- Onboarding prefill / warnings: `Onboarding_Prefill_Service.php`, `Onboarding_UI_State_Builder.php`
