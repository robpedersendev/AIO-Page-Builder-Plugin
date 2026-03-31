# Admin UX trace — coverage audit prompts (Cursor)

Use with `WP_DEBUG` on and, when possible, `WP_DEBUG_LOG` on. Grep logs for `[AIO_UX]`.

## Recursive hub audit (one hub per pass)

Copy and replace `HUB_NAME` and `HUB_SLUG` (e.g. Plans hub, `aio-page-builder-build-plans`).

```
You are auditing the AIO Page Builder admin UX trace coverage for hub HUB_NAME (page=HUB_SLUG).

1. From Admin_Menu_Hub_Renderer and related screen classes, list every primary tab and sub-tab key and its capability.
2. For each tab/sub-tab, confirm Admin_Ux_Trace::hub_entry (or equivalent) runs after resolution; if missing, add it and list files changed.
3. List every admin-post action reachable from this hub; ensure Admin_Ux_Trace::admin_post_boundary at validate + redirect/failure paths.
4. List notices driven by query args; ensure Admin_Ux_Trace::notice_rendered with stable message_id when the notice prints.
5. List primary buttons/links/forms; ensure data-aio-ux-action (and section/hub/tab where helpful) on each.
6. Output a markdown table: Surface | PHP hub trace | admin-post trace | notice trace | data-aio-ux | Notes.
```

## Single screen deep dive

```
Open SCREEN_FILE (path). For every user-visible control and every hidden form that submits, either cite existing data-aio-ux-* and trace calls or propose minimal additions. Do not log secrets. Prefer stable message_id and action slugs.
```

## Schema diff

```
Compare all Admin_Ux_Trace::emit / compose_record fields and client batch payloads in plugin/src to docs/logging/admin-ux-trace-v1.schema.json. List any drift; if drift is intentional, bump schema_version and update the schema JSON, examples jsonl, and admin-ux-trace-v1.md in one PR.
```

## Client-only ordering check

```
With browser devtools Network tab, confirm admin-ajax.php receives aio_admin_ux_trace_batch after clicks on instrumented controls. Order client_sequence in JSON lines; pair with server sequence for the same page load.
```

## Full product pass (staged)

Repeat the hub audit prompt for each hub in order: Settings, Diagnostics, Onboarding, AI, Crawler, Plans & analytics, Template library, Operations, Industry, Global styling — then standalone admin pages not in hubs.
