# Build plan `_aio_plan_definition` meta persistence — trace runbook

Use this on a **staging clone** that mirrors production plugins and mu-plugins (for example `ndr-form-manager` under `wp-content/mu-plugins`).

## 1. Enable AIO diagnostics

Set `WP_DEBUG` and `WP_DEBUG_LOG` to true. Reproduce the failing save, then search the log for:

- `AIO_BP_PLAN_DEFINITION_PERSIST_VERIFY_FAIL`
- `AIO_BUILD_PLAN_GENERATOR_SAVE_FAIL`
- `AIO_BP_PLAN_DEFINITION_UPDATE_META_FAILED` (update path returned false or no row)
- `AIO_BP_PLAN_DEFINITION_DB_INSERT_FALLBACK_OK` (direct `postmeta` insert succeeded after API failure)
- `AIO_BP_PLAN_DEFINITION_CHUNKED_WRITE` (definition stored as chunked meta)

## 2. Hook inventory

Install **Query Monitor** (or equivalent) and list callbacks on:

- `update_post_metadata`, `add_post_metadata`, `delete_post_metadata`
- `updated_post_meta`, `added_post_meta`, `deleted_post_meta`

Note any plugin that returns `false` from `update_post_metadata` or mutates `meta_value` for all post types.

## 3. Binary isolation (mu-plugins / plugins)

Disable suspect mu-plugins one at a time; retry the same build-plan save. If behavior changes, the last disabled loader is implicated.

Repeat for regular plugins only if mu-plugins are ruled out.

## 4. Infrastructure

- **MySQL:** error log, `max_allowed_packet`, and whether `INSERT`/`UPDATE` on `wp_postmeta` errors for large `meta_value`.
- **PHP:** `memory_limit` (large `wp_json_encode` / decode).
- **Object cache:** compare behavior with persistent cache disabled; confirm large values are not truncated by the backend.

## 5. Database ground truth

For the affected plan post ID:

- Count rows: `meta_key = '_aio_plan_definition'` (expect one logical row after save; duplicates indicate a historical issue).
- If chunked: `_aio_plan_definition__chunk_count` and `_aio_plan_definition__part_*` rows exist; concatenate parts and confirm valid JSON and non-empty `steps`.

## 6. Optional probe mu-plugin

See [tools/wp-env/mu-plugins-example/aio-plan-meta-persistence-probe.php.example](../../tools/wp-env/mu-plugins-example/aio-plan-meta-persistence-probe.php.example): copy into `wp-content/mu-plugins/` (rename to `.php`), reproduce, then remove. It blocks `update_post_meta` only for `aio_build_plan` posts and `_aio_plan_definition` to validate the plugin’s direct-DB fallback path.
