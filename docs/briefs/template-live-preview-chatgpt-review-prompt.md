# ChatGPT prompt: Template live preview — review, improve, and derive Cursor tasks

Copy everything below the line into ChatGPT (or another model). Adjust tone if you want a shorter answer.

---

## Prompt (paste below)

You are a senior WordPress security engineer and product architect reviewing a **privately distributed** plugin (AIO Page Builder). The goal is **faithful template preview** in the browser: same theme, `wp_head` / `wp_footer`, plugin CSS, animations, and block output as on the live site.

### What was implemented

**1. Problem**

- Admin template detail screens previously showed only an **inline** preview (synthetic data + `wp_kses_post`) with optional injected base CSS. That does not match full front-end fidelity (theme, global styles, JS-driven animations).

**2. Approach**

- **Signed GET URL** on the **public front end** (not `wp-admin`), not one WordPress post per template.
- Query shape: `?aio_pb_tpl_live=1&t=<base64url-json>&sig=<hmac-sha256-hex>`.
- Payload (JSON, versioned): `v`, `typ` (`page` | `section`), `key` (template key), `uid` (user id), `exp` (unix expiry), optional `cc`/`fam`/`pf` (category/family/purpose_family), optional `rm` (reduced motion).
- **HMAC** over the base64url token `t` using `wp_salt('aio_pb_tpl_live_preview_v1')` (or a stable salt action).
- **Verification**: signature must match; payload must decode; `exp` must be in the future; `uid` must equal `get_current_user_id()`; user must be logged in; capability must match template type (`MANAGE_PAGE_TEMPLATES` or `MANAGE_SECTION_TEMPLATES`, with site-admin bridge consistent with detail screens).
- **Controller** hooks `template_redirect` at priority 1, validates token, builds HTML via the same **state builders** as admin (`Page_Template_Detail_State_Builder` / `Section_Template_Detail_State_Builder`) with **`live_preview => true`** in request params so **application-level** `Preview_Cache_Service` is skipped (always fresh render for this route).
- **Output**: minimal HTML document: `language_attributes`, charset, `noindex` meta, `body_class` includes `aio-template-live-preview`, wrapper div, inner content from `rendered_preview_html` passed through `wp_kses_post`, then `wp_head()` and `wp_footer()` in correct order so enqueues run.
- **Filter**: `aio_page_builder_should_enqueue_base_styles` forced true so plugin base styles load even when the main query is not a normal post.
- **Admin UI**: Page and section template detail screens embed an **iframe** whose `src` is `home_url('/')` with the signed query args; hint text explains “theme styles, animations.”

**3. Cache / HTTP (intentionally aggressive)**

- **Constant**: `DONOTCACHEPAGE` defined when serving preview.
- **WordPress**: `nocache_headers()`.
- **Raw headers**: `Cache-Control` no-store / private / max-age=0, `Pragma`, `Expires` (WP-style past date), `X-Robots-Tag` noindex/nofollow/noarchive/nosnippet, `Surrogate-Control` no-store, `Vary: Cookie`.
- **Filter** `wp_headers` (late priority): also sets `CDN-Cache-Control`, `X-LiteSpeed-Cache-Control` for common edge cases.

**4. Security properties (as designed)**

- No secrets in URL except time-limited signed blob; no API keys.
- Token bound to user id; must match logged-in user.
- Capability-gated by template type.
- Public GET; no nonce on the URL (HMAC replaces nonce for shareable-but-expiring link within same browser session).

**5. Known limitations / tradeoffs**

- Same-origin iframe from `admin.php` to `home_url()` typically works if cookies are same-site; cross-domain admin would break auth unless SSO/cookie domain is aligned.
- Full `wp_kses_post` on inner HTML may strip some block markup if kses differs from front expectations (tradeoff vs XSS).
- `template_redirect` short-circuits normal theme template; some themes/plugins assume global `$post` or main query — not set here.
- Token TTL fixed (e.g. 15 minutes); no one-time / revocation list.

---

### What I want from you

1. **Threat model**  
   List realistic abuse cases (token theft, replay within TTL, CSRF, XSS via preview HTML, cache poisoning at CDN, log leakage) and rate at which you care (high/medium/low).

2. **Improvements — security**  
   Concrete changes: e.g. one-time tokens, server-side session binding, `SameSite` cookie considerations, `Content-Security-Policy` for preview responses, stricter kses vs. trusted renderer pipeline, audit logging, rate limiting, IP binding (and when *not* to do it).

3. **Improvements — UX / UI**  
   Iframe sizing, loading states, errors (403/404) inside iframe vs. parent, mobile admin, “open in new tab,” accessibility (title, focus trap), reduced-motion parity, comparing with inline preview side-by-side.

4. **Improvements — functionality**  
   Optional: query real published page as shell when theme requires `WP_Post`; block theme / FSE; multisite; query args for previewing specific industry overrides; WebSocket vs. refresh for long renders.

5. **Cursor prompts**  
   Produce **3–6 separate Cursor-ready prompts** (each self-contained) that a developer could paste into Cursor to implement the highest-value items. For each prompt, specify:
   - **Goal** (one sentence)
   - **Files to touch** (guess paths like `plugin/src/Frontend/Template_Live_Preview_Controller.php`, `.../Template_Live_Preview_Token_Service.php`, admin screens, CSS)
   - **Acceptance criteria** (security, UX, tests)
   - **Out of scope** (to avoid scope creep)

Order prompts by **impact / risk reduction** first.

Be specific to WordPress 6.6+ and PHP 8.1+; assume Composer autoload and PHPUnit exist; do not suggest storing secrets in the repo.

---

## End of prompt

---

## Optional: use this after ChatGPT replies

1. Paste ChatGPT’s suggested Cursor prompts into your backlog or run them one at a time in Cursor.
2. Re-run PHPCS, PHPStan, and PHPUnit after each change.
3. Manually verify in Network tab: preview response headers remain non-cacheable.
