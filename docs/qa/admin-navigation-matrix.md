# Admin navigation QA matrix

Manual closure before release; automate failures that recur. Automated coverage: PHPUnit contracts (`tests/Unit/Admin_*`, `Admin_Url_Emission_Scanner_Test`), `composer run admin-route-inventory`, `composer run admin-url-emission`, Playwright `admin-hubs-matrix.spec.ts`, `admin-restricted-role.spec.ts`, and `admin-link-crawler.spec.ts` (seeds include tab deep-links in `e2e/helpers/admin-routes.ts`).

## Scope

- In scope: all `admin.php?page=aio-page-builder*` routes, hub tabs, deep links (`plan_id`, `id`, `run_id`, `aio_tab`, `aio_subtab`), forms posting to `admin-post.php`, legacy slug redirects.
- Out of scope: front-end theme, third-party admin pages; external URLs smoke-only.

## Surfaces

| Surface | Examples | Check |
|--------|-----------|--------|
| Hub tabs | Settings, Diagnostics, AI workspace, Plans & analytics, Template library, Operations, Industry, Global styling | Tab URL updates `aio_tab`; content matches tab; caps hide inaccessible tabs. |
| Deep links | `plan_id` / `id` on build plans; `run_id` on AI workspace; template keys on library | Correct screen (list vs workspace); args not stripped when switching tabs (Plans hub preserves via tab links). |
| POST + redirect | Bulk actions, exports, create-from-run, industry save | Valid nonce succeeds; invalid nonce shows error; redirect URL returns to expected hub. |
| Legacy slugs | Old bookmarks (e.g. `aio-page-builder-ai-runs`) | Redirect to hub + `aio_tab`; passthrough keys preserved per `redirect_legacy_to_hub` (`plan_id`, `id`, `step`, `run_id`, …). |

## POST handlers (sample grouping)

- `admin_post_*` in `Admin_Menu.php` / `Admin_Post_Handler_Registrar`: verify capability + nonce per action name.
- `Build_Plan_Workspace_Screen::maybe_handle_*`: POST-only; redirect targets include `plan_id` where applicable.

## Drift prevention

- Add a new screen with `public const SLUG` or `HUB_PAGE_SLUG`: update `Admin_Route_Inventory::ALL_DISCOVERED_ADMIN_PAGE_SLUGS` (scanner parity + `composer run admin-route-inventory`).
- Any new **literal** `'page' => 'aio-page-builder-…'` or `admin.php?page=aio-…` in PHP must match that list or `composer run admin-url-emission` / PHPUnit `Admin_Url_Emission_Scanner_Test` fails. Prefer `SomeScreen::SLUG` in code when possible to avoid duplicate literals.
- New **visible** hub: also update `VISIBLE_HUB_PAGE_SLUGS`, `HUB_PRIMARY_TABS_BY_PAGE`, `e2e/helpers/admin-routes.ts` (`AIO_ADMIN_SEED_PATHS` and any relevant `AIO_ADMIN_DEEP_LINK_SEED_PATHS`).
- Add `Admin_Router` named route: update `ADMIN_ROUTER_ROUTE_NAMES` and `register_defaults()`.

## E2E users (wp-env)

- `global-setup.ts` creates `aio_e2e_subscriber` / `password` (subscriber) when wp-env is reachable. Override with `WP_SUBSCRIBER_USER` / `WP_SUBSCRIBER_PASSWORD`. Set `AIO_E2E_SKIP_SUBSCRIBER=1` to skip user creation (restricted-role tests skip if login fails).

## Link crawler scope

- Crawler visits each URL in `AIO_ADMIN_ALL_SEED_PATHS` (hub roots + tab deep-links), collects `admin.php` links under `#wpbody-content`, then GETs each once. It does not walk the full admin tree; expand seeds when new high-traffic surfaces need coverage.
