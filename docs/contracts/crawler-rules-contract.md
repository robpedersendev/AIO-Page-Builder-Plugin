# Crawler Rules Contract

**Spec**: §24 Crawler Architecture; §24.1–24.17; §2.3.6 Private-Content Crawler by Default; §42 Job Queue (site-read safety where applicable)

**Related**: Future crawler implementation, snapshot storage, diagnostics. No fetcher, discovery, or snapshot code in this contract.

**Status**: Contract definition only. Implementation must follow these rules without drift.

---

## 1. Purpose

This contract formalizes the crawler’s exact operating rules so that implementation is bounded, public-only, same-host-only, indexable-only, meaningful-page-focused, respectful, and rate-limited. The crawler must not become a broad scraping system, private-content harvester, or generic spider. All eligibility, exclusion, and behavioral rules are explicit; there is no “best judgment” escape hatch.

---

## 2. Scope and Non-Goals

| Concept | Rule |
|--------|------|
| Crawl purpose | Bounded, planning-grade representation of the current **public** website for structure, page, and navigation recommendations. |
| Non-goals | The crawler is **not** a general search spider, penetration tool, or unrestricted archival crawler. |
| Trigger | Crawl runs are initiated by authorized admin actions only. All triggers must be capability-gated; document that requirement for later implementation. |
| Credentials | The crawler must **not** use authenticated sessions, cookies, or credential-bearing URLs. No secrets or cookies stored. |

---

## 3. URL and Host Eligibility Rules

### 3.1 Allowed Targets

A URL is **allowed for crawl consideration** only if all of the following are true:

| Condition | Requirement |
|-----------|-------------|
| Host | Same as the site’s **current canonical host** used for the crawl. No subdomains unless explicitly whitelisted in a future, documented exception. |
| Scheme | `http` or `https`. |
| Resource type | Intended as a public HTML page (not a binary file, feed, or API response). |
| Discovery path | Reachable from approved discovery sources (see §7). |
| Within bounds | Total HTML pages requested in the run do not exceed the page limit (see §9). Crawl depth from seed does not exceed the depth limit (see §9). |

### 3.2 Prohibited Targets (Never Fetch)

The crawler **must not** intentionally request or analyze:

| Category | Examples / Rule |
|----------|------------------|
| Admin | `/wp-admin/`, `/wp-admin/*`, any path segment or query indicating admin. |
| Login / auth | `/wp-login.php`, `login`, `signin`, `auth`, `register`, `signup` (when clearly auth endpoints). |
| Authenticated/member | Pages that require cookies or logged-in state; member-only areas. |
| Draft/preview | Draft, pending, or private post preview URLs; preview tokens. |
| Non-HTML endpoints | `admin-ajax.php`, REST API roots, or other endpoints not intended as public HTML pages. |
| External hosts | Any URL whose host is different from the crawl’s canonical site host. Always excluded. |
| Subdomains | Excluded by default (e.g. `www.` vs apex is host-normalization; other subdomains are out of scope unless a future rule whitelists them). |
| Credential-bearing | URLs containing tokens, keys, or session identifiers in path or query. |
| File/media | Direct links to binary files (e.g. `.pdf`, `.zip`, image-only URLs) when the crawler is in “HTML page” mode; stop expansion on non-HTML content. |

### 3.3 URL Eligibility Rule Matrix

| URL type | Same host? | Path/query | Eligible? | Notes |
|----------|------------|------------|-----------|--------|
| Homepage | Yes | `/` or canonical home | Yes | Seed. |
| Blog post | Yes | `/blog/post-slug` | Yes | If indexable and meaningful per §5–§6. |
| Contact | Yes | `/contact` | Yes | If indexable and meaningful. |
| Cart | Yes | `/cart`, `/basket` | **No** | Ignored page type (§6). |
| Checkout | Yes | `/checkout`, `/pay` | **No** | Ignored. |
| Account | Yes | `/account`, `/my-account` | **No** | Ignored. |
| Login | Yes | `/wp-login.php`, `/login` | **No** | Prohibited + ignored. |
| Search results | Yes | `?s=`, `/search?q=` | **No** | Ignored. |
| Feed | Yes | `/feed/`, `?feed=rss2` | **No** | Ignored. |
| Thank-you | Yes | `/thank-you`, `/order-received` | **No** | Ignored. |
| Preview | Yes | `?preview=`, `&p=123&preview=true` | **No** | Ignored. |
| Pagination (archive) | Yes | `/page/2`, `?paged=2` (beyond 1) | **No** | Ignored (archive page 2+). |
| UTM / tracking | Yes | `?utm_source=`, `?fbclid=` | Normalize then dedupe | Strip before deduplication; do not treat as distinct page. |
| Fragment only | Yes | `#section` | Same as base URL | Strip fragment for normalization. |
| Different host | No | Any | **No** | Never eligible. |
| wp-admin | Yes | `/wp-admin/`, `/wp-admin/*` | **No** | Prohibited. |
| REST / ajax | Yes | `/wp-json/`, `admin-ajax.php` | **No** | Prohibited. |

### 3.4 Host Normalization

| Step | Rule |
|------|------|
| Canonical host | The crawl run is bound to a single canonical host (e.g. the site’s home URL host). All URLs must use this host after normalization. |
| Scheme | Normalize to one scheme (e.g. `https`) per run when comparing. |
| Subdomain | Do not treat different subdomains as same host unless product explicitly defines a whitelist (currently: subdomains excluded). |
| Port | Default port for scheme (80/443) may be implied; non-default ports are allowed only if they belong to the same canonical host. |

---

## 4. Public-Only and Indexability Rules

### 4.1 Public-Only

- The crawler **only** analyzes content that is **publicly accessible without authentication**.
- If a URL that was considered public **returns** a login-gated or protected response (e.g. 401, 302 to login, or body indicating login), the crawler **shall** mark it as skipped and record the reason (e.g. `login_gated`, `unexpected_protected`).
- No use of cookies, session cookies, or auth headers for fetching.

### 4.2 Indexability Eligibility

A page is **planning-eligible (indexable)** only if **all** of the following are true:

| Condition | Requirement |
|-----------|-------------|
| HTTP status | Response is **HTTP 200**. |
| Content type | Response is **HTML** (or equivalent declared type for web page). |
| noindex | Page is **not** marked `noindex` via HTML meta robots or `X-Robots-Tag`. |
| robots.txt | Page is **not** disallowed for crawl expansion by `robots.txt`. |
| Ignored types | Page is **not** classified as ignored utility/system content under §6. |

A page that is **discovered** but fails indexability may still be **recorded as excluded** with a reason code for audit; it does not become a planning-eligible (meaningful) page.

### 4.3 Indexability Handling Matrix

| Scenario | Action | Reason code / note |
|----------|--------|---------------------|
| HTTP 200, HTML, no noindex, allowed by robots | Eligible for meaningful classification | — |
| HTTP 404, 403, 500, etc. | Do not treat as indexable | `http_error` |
| Content-Type not HTML | Stop expansion; do not treat as page | `unsupported_content_type` |
| noindex in meta or X-Robots-Tag | Exclude from meaningful; may record as discovered | `noindex` |
| Disallowed by robots.txt for crawl | Do not fetch or exclude from expansion | `robots_disallowed` |
| Redirect to login or auth | Mark skipped | `login_gated` / `redirect_to_auth` |
| Redirect chain > 3 hops | Stop; do not follow further | `excessive_redirects` |

---

## 5. Meaningful-Page Classification (Preconditions)

A page is classified as **meaningful** only if it is **indexable** (§4) and meets **at least one** of the following:

| Criterion | Description |
|-----------|-------------|
| In navigation | Appears in **primary or footer navigation** (discovered via nav detection rules). |
| Content weight | Has a **visible H1** and **at least 150 words** of content (word-count estimate). |
| Likely role | Is a likely **service, product, location, hub, FAQ, about, contact, event, pricing, or request** page (by URL, title, or structure). |
| Link weight | Is **linked repeatedly** from other meaningful internal pages. |
| Structure role | **Clearly functions as a site-structure page** rather than a utility endpoint. |

A page is classified as **non-meaningful** if it is primarily:

- Transactional (cart, checkout, payment, order status)
- Ephemeral (session-specific, one-time)
- System-generated (feeds, search results, pagination-only)
- Duplicate (see §8)
- Utility-oriented (login, account, thank-you, preview, etc.)

These criteria are **preconditions** at the rules level; implementation must encode them in a deterministic, auditable way (e.g. reason codes per page).

---

## 6. Ignored Page Types and Exclusions

The crawler **shall exclude** the following patterns by default. Ignored pages are **marked as excluded** with a **reason code** when recorded.

| Pattern / type | Examples | Reason code |
|----------------|----------|-------------|
| Cart | `/cart`, `/basket` | `ignored_cart` |
| Checkout | `/checkout`, `/pay`, `/payment` | `ignored_checkout` |
| Account | `/account`, `/my-account`, `/dashboard` | `ignored_account` |
| Login / register | `/login`, `/register`, `/signup`, `/wp-login.php` | `ignored_login` |
| Search results | `?s=`, `/search`, `?q=` | `ignored_search` |
| Feeds | `/feed/`, `?feed=`, `/rss` | `ignored_feed` |
| Attachment endpoints | Attachment URLs with no meaningful page content | `ignored_attachment` |
| Thank-you | `/thank-you`, `/order-received`, `/confirmation` | `ignored_thankyou` |
| Order status | `/order-status`, `/track-order` | `ignored_order_status` |
| Preview | `?preview=`, `preview=true` | `ignored_preview` |
| Tag/date/author archives | `/tag/`, `/date/`, `/author/` | `ignored_archive` (unless whitelisted later) |
| Paginated archives (beyond 1) | `/page/2`, `?paged=2` for archives | `ignored_pagination` |
| Faceted/filter URLs | Obvious faceted-filter query strings | `ignored_faceted` |
| UTM / tracking variants | `?utm_*`, `?fbclid=` | Normalize and dedupe; not a separate exclusion reason for “type” but excluded as duplicate. |

---

## 7. Discovery Boundaries

### 7.1 Discovery Order

The crawler **shall** use this discovery order:

1. **Homepage URL** (canonical home for the site).
2. **Sitemap.xml and sitemap index files**, if publicly available and same-host.
3. **Primary navigation links** discovered on the homepage.
4. **Footer navigation links** discovered on the homepage.
5. **Internal links** on already **accepted meaningful pages** (do not expand from non-meaningful or excluded pages for further discovery beyond what is needed for classification).

### 7.2 Normalization Before Deduplication

- **Remove fragments** (`#section`) for URL identity.
- **Remove tracking parameters** (e.g. UTM, `fbclid`) and other agreed query parameters before deduplication so that the same logical page is not fetched twice.
- Normalized URL key is used internally for deduplication and crawl bounds.

### 7.3 Discovery Boundary Matrix

| Source | May discover | May not discover |
|--------|--------------|-------------------|
| Homepage | Same-host HTML links, sitemap URLs (same host) | External links, non-HTML links |
| Sitemap | Same-host URLs listed in sitemap | External URLs, non-HTML |
| Nav (header/footer) | Same-host links in `<nav>`, header, footer | Links in scripts, iframes (out of scope) |
| Meaningful page body | Same-host internal links | External, mailto:, tel:, javascript: |
| Excluded/ignored page | — | Do not use as source for further discovery (except as needed for classification). |

---

## 8. Duplicate and Low-Value Handling

### 8.1 Duplicate Detection

A page is **likely duplicate** if one or more of the following hold:

| Condition | Action |
|-----------|--------|
| Same canonical URL as another discovered page | Treat as duplicate; do not count as separate meaningful page. |
| Identical normalized title + H1 + major content hash | Treat as duplicate. |
| Redirects to an already accepted meaningful page | Record redirect; do not count as new meaningful page. |
| Same content hash and same normalized structure as another page | Treat as duplicate. |

Duplicates **shall not** become separate planning pages unless explicitly justified by a unique navigational role (future rule; by default they are not).

### 8.2 Unsupported / Low-Value Pages

| Case | Behavior |
|------|----------|
| Unsupported content type | Do not fetch or expand; record as `unsupported_content_type` if discovered. |
| Obvious utility (e.g. thank-you, cart) | Exclude with appropriate `ignored_*` reason. |
| Thin content (e.g. &lt; 150 words, no H1) | May be discovered and recorded but not classified as meaningful unless another criterion (e.g. nav participation) is met. |
| Non-200 response | Record failure reason; do not treat as indexable. |

---

## 9. Crawl Bounds (Depth and Volume)

| Bound | Limit | Rule |
|-------|--------|------|
| Page limit | **500 HTML pages** per crawl run | Hard cap; stop requesting new pages when reached. |
| Depth | **4 hops** from approved seed URLs | Seeds = depth 0; links from seeds = 1; etc. Do not follow links beyond depth 4. |
| Redirect chain | **3 hops** | Cap redirect following at 3; then treat as failure or final URL. |
| Consecutive hard failures | **25** | Stop crawl run (or pause and mark partial) after 25 consecutive hard failures. |
| Same URL | **1 request** per normalized URL per run | Do not fetch the same normalized URL more than once per crawl run. |

---

## 10. Rate Limiting and Request Behavior

| Rule | Value / behavior |
|------|-------------------|
| Concurrency | **Single worker** by default (one page at a time). |
| Delay between requests | **250 ms** between HTTP requests. |
| Request timeout | **8 seconds** per request. |
| Method | **GET** only for HTML page retrieval. |
| User-Agent | `AIOPageBuilderCrawler/{plugin_version} ({site_url})` — identify requests clearly. |
| Respect | **Do not** attempt to bypass robots.txt, auth, rate limits, or access controls. |
| Retry | Retry policy (e.g. transient failures) must be bounded and documented in implementation; no unbounded retries. |

---

## 11. Diagnostic and Error Classification

Crawl failure and exclusion states **shall** be classified for diagnostics. Suggested categories (implementation must map to these or document deviations):

| Category | Examples |
|----------|----------|
| `provider/transport_failure` | DNS, connection refused, TLS error. |
| `excessive_redirects` | More than 3 redirect hops. |
| `timeout_failure` | Request exceeded 8 s. |
| `malformed_response` | Invalid HTML or broken response. |
| `robots_exclusion` | Disallowed by robots.txt. |
| `unsupported_content_type` | Not HTML. |
| `page_limit_reached` | Hit 500-page cap. |
| `fatal_crawl_abort` | e.g. 25 consecutive hard failures. |
| `login_gated` / `unexpected_protected` | Public URL returned auth requirement. |
| `noindex` | Page has noindex. |
| `ignored_*` | One of the ignored page types (§6). |
| `duplicate` | Duplicate of an already accepted page. |

**Partial success**: If a crawl run partially succeeds (e.g. some pages fetched, then limit or abort), the snapshot **shall** be marked **partial** rather than fully failed. Partial snapshots may be used for planning only if they meet minimum data thresholds (to be defined in implementation).

---

## 12. Snapshot and Storage (Rule-Level Only)

- Each crawl run creates **one** crawl snapshot record and associated **page records**.
- Snapshot stores: crawl ID, site host, start/end timestamps, settings, total discovered URLs, accepted meaningful pages, excluded pages, failed requests, final status.
- Page records store normalized fields needed for later AI input and Build Plan generation; **no** unlimited raw page text in the snapshot table (see spec §24.13–24.15).
- **Re-crawl**: A new crawl **does not overwrite** the prior snapshot; it creates a **new** snapshot linked to the previous one. Comparison (new/removed/changed pages) is a separate concern (spec §24.17).

---

## 13. Security and Permissions (Summary)

- **No** authenticated or private routes.
- **No** credential-bearing URLs or cookies.
- **No** storage of secrets or session tokens.
- **Capability-gated**: Crawl triggers are admin-only and must be capability-checked in implementation.
- **Safe by default**: Any ambiguity in URL or host eligibility is resolved to **exclude** (do not fetch).

---

## 14. Examples: Included vs Excluded

### 14.1 Examples of URLs That Are Allowed (Eligible for Fetch and Meaningful Classification)

- `https://example.com/`
- `https://example.com/about`
- `https://example.com/services/consulting`
- `https://example.com/contact`
- `https://example.com/blog/my-post` (if indexable and meaningful)
- `https://example.com/faq`
- `https://example.com/locations/nyc`

(Same host, public, HTML, not in ignored list, within depth and page limits.)

### 14.2 Examples of URLs That Are Excluded or Prohibited

- `https://example.com/wp-admin/` — prohibited.
- `https://example.com/wp-login.php` — prohibited + ignored.
- `https://example.com/cart` — ignored.
- `https://example.com/checkout` — ignored.
- `https://example.com/my-account` — ignored.
- `https://example.com/?s=query` — ignored (search).
- `https://example.com/feed/` — ignored.
- `https://example.com/thank-you` — ignored.
- `https://example.com/page/2` (blog archive) — ignored (pagination beyond 1).
- `https://other.com/page` — different host; never fetch.
- `https://example.com/?utm_source=email` — normalize (strip UTM), then dedupe with canonical URL.
- `https://example.com/preview?p=123` — ignored (preview).

---

## 15. Revision History

| Version | Date | Change |
|---------|------|--------|
| 1 | Prompt 049 | Initial crawler rules contract; rule matrices and examples. |
