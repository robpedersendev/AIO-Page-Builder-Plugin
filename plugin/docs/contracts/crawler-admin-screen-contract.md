# Crawler Admin Screen Contract

**Document type:** Contract for crawler-focused admin screens (spec §24.17, §62.10).  
**Governs:** Crawler Sessions, Session Detail, Crawl Comparison screens; diagnostics and action placeholders.  
**Reference:** Master Specification §24.17 Crawl Diagnostics, §45.3–45.4 Error Messages, §59.7 Crawler and Site Context Phase.

---

## 1. Scope

Crawler admin screens provide list/detail views for crawl sessions, page-level snapshot summaries, and comparison results. They are diagnostics-oriented and do not implement AI onboarding, Build Plan UI, or generic job execution. All screens are capability-gated and do not accept user-supplied arbitrary host input.

---

## 2. Screen inventory

| Screen slug | Title | Purpose |
|-------------|--------|---------|
| `aio-page-builder-crawler-sessions` | Crawl Sessions | List crawl runs; link to session detail; readiness/rules copy; future crawl start/retry placeholder. |
| (detail) | Crawl Session Detail | Page-level snapshot list for one run (URL, title, classification, nav, status). No raw HTML. |
| `aio-page-builder-crawler-comparison` | Crawl Comparison | Select prior and new run; show Session_Comparison_Result summary and page changes table. |

Session detail is rendered by the same screen class when `run_id` is present in the request (no separate slug).

---

## 3. Columns and panels

### 3.1 Crawl Sessions (list)

- **Columns:** Run ID, Site host, Status, Discovered, Accepted, Excluded, Failed, Started, Actions (View pages).
- **Readiness panel:** Short copy that crawler is site-scoped, public-only, normalized URL identity, meaningful-page focus. No arbitrary host input.
- **Action placeholder:** Reserved for future crawl start/retry; nonce pattern reserved.

### 3.2 Crawl Session Detail

- **Columns:** URL, Title (trimmed), Classification, Nav (participation), Status.
- **No raw HTML or unbounded content.** Summary data may be present in snapshot but is not dumped in full in list view.

### 3.3 Crawl Comparison

- **Form:** Prior run (baseline) dropdown, New run dropdown, Compare button.
- **Summary table:** Added, Removed, Changed, Unchanged, Reclassified, Meaningful (prior), Meaningful (new).
- **Page changes table:** URL, Category (added/removed/changed/unchanged/reclassified), Reasons.
- **Action placeholder:** Nonce reserved for future mutating actions.

---

## 4. Data source

- **Sessions list:** `Crawl_Snapshot_Service::list_sessions( $limit )` (run ids from snapshot table; session payload from options where present).
- **Session detail:** `Crawl_Snapshot_Service::list_pages_by_run( $run_id )`, `get_session( $run_id )`.
- **Comparison:** `Recrawl_Comparison_Service::compare( $prior_run_id, $new_run_id )` → `Session_Comparison_Result`.

---

## 5. Security and capability

- All crawler screens use a capability (e.g. `manage_options` until capability mapping is finalized). Menu and screen rendering must be capability-aware.
- No user-supplied arbitrary host or URL for crawl targeting. Crawler remains site-scoped.
- Mutating actions (future crawl start/retry) require nonces; pattern is reserved in markup (`data-nonce-placeholder="reserved"`).
- No leakage of internal secrets or unnecessary raw response bodies.

---

## 6. Errors and warnings

- **User-facing:** Short, actionable messages (e.g. "No crawl sessions yet.", "Invalid run ID.") per §45.3.
- **Admin-facing detail:** Exceptions from service calls must not expose stack traces or secrets; catch and show a generic message or hide the section per §45.4.

---

## 7. Action placeholders

- **Crawl start/retry:** Not implemented. Button/link and nonce are reserved; future implementation will be capability-gated and nonce-verified.
- No generic crawler console or queue UI in scope.
