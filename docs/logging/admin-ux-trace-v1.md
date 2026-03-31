# Admin UX trace log format (v1)

## Purpose

Structured, grep-friendly lines for debugging admin navigation, notices, and user actions when **`WP_DEBUG` is true**. Parsers and LLMs should treat each physical line as one JSON object prefixed by `[AIO_UX]`.

## Parsing

1. Match lines starting with `[AIO_UX]`.
2. Parse JSON from the first `{` through the matching end of object (single line per event).
3. Validate against [admin-ux-trace-v1.schema.json](admin-ux-trace-v1.schema.json).

## Activation and sinks

| Condition | Behavior |
|-----------|----------|
| `WP_DEBUG` false | No records emitted (PHP and AJAX handler reject batches). |
| `WP_DEBUG` true and `WP_DEBUG_LOG` true | Each record is passed to PHP `error_log()` (typically `wp-content/debug.log`). |
| `WP_DEBUG` true and `WP_DEBUG_LOG` false or undefined | Records append to `wp-content/uploads/aio-admin-ux-trace.log` when uploads are writable. |

## Privacy

Do not put API keys, nonces, passwords, raw POST bodies, or PII in `detail`, `tags`, or `query_snapshot`. Plan and run identifiers in `query_snapshot` appear as `len=N,h=xxxxxxxx` (md5 prefix). Use stable **`message_id`** values for notices instead of translated strings.

## Severity semantics

| Value | Use |
|-------|-----|
| `info` | Context that is not abnormal. |
| `warning` | Recoverable anomaly or truncation. |
| `error` | User-visible failure or blocked action. |
| `critical` | Severe inconsistency (rare). |
| `flow` | Process step / boundary (navigation, admin-post start/end). |
| `expected` | Declared expected surface or outcome. |
| `actual` | Observed surface or outcome. |
| `assert_ok` / `assert_fail` | Lightweight expectation checks. |

## Facets

| Facet | Meaning |
|-------|---------|
| `navigation` | Hub/tab/subtab resolution or tab click (server). |
| `form_submit` | Form submission boundary. |
| `admin_post` | `admin-post.php` action handling. |
| `redirect` | Redirect about to be sent (optional future use). |
| `notice` | Admin notice rendered. |
| `capability` | Capability gate outcome (optional future use). |
| `render` | Generic render / truncation marker. |
| `client_interaction` | Browser click/submit (via `data-aio-ux-*` + AJAX batch). |
| `dom_marker` | Structural DOM signal (optional future use). |

## Tag vocabulary

Tags are lowercase slugs with optional prefixes:

- `hub:<page_slug>`
- `tab:<tab_key>`
- `subtab:<subtab_key>`
- `section:<stable_section_id>`
- `action:<stable_control_id>`

## Machine workflow

1. Filter log by `hub`, `tab`, or `tags`.
2. Order by `ts_utc` and `sequence` / `client_sequence`.
3. Pair `severity=expected` with `severity=actual` or `flow` rows sharing the same `detail` / `message_id` where documented in code.

## Client batch transport (admin-ajax)

The browser batch script posts to `admin-ajax.php` with:

- **Content-Type:** `application/x-www-form-urlencoded; charset=UTF-8`
- **Body fields:** `action` (registered AJAX action), `nonce` (WordPress AJAX nonce), `batch` (JSON array of trace rows)

**Why not `multipart/form-data` or `sendBeacon(FormData)`?** Some hosts, reverse proxies, or WAF rules reject non-GET beacons or multipart bodies on admin AJAX. URL-encoded POST matches typical WordPress admin AJAX expectations.

**Unload delivery:** On `pagehide` and `beforeunload`, the client sends remaining queued rows using `fetch(url, { method: 'POST', credentials: 'same-origin', keepalive: true, ... })` (one request per chunk, same encoding). This is best-effort: the browser or network may still drop in-flight work when the document goes away.

**Reliable path while the tab is open:** A **5 second** `setInterval` flush and debounced flushes after interactions use the same encoding without `keepalive`. If unload batches never arrive, traces accumulated during the session should still appear from those periodic or interaction-triggered requests.

## Examples

See [admin-ux-trace-v1.examples.jsonl](admin-ux-trace-v1.examples.jsonl).
