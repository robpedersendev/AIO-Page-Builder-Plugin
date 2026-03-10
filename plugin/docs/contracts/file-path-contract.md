# File path contract (spec §9.8, §9.9, §52.2)

Plugin-owned filesystem paths are resolved by `Plugin_Path_Manager` under the WordPress uploads directory. This contract defines the naming rules, directory roles, and security constraints.

## Base path

- **Location:** `{wp_upload_dir}/aio-page-builder/`
- **Resolution:** Via `wp_upload_dir()['basedir']`. No paths outside this tree for plugin outputs.
- **Creation:** Base and child dirs are created on demand by `ensure_base()` / `ensure_child()`; callers must perform capability checks before creating.

## Uploads subdirectory contract

| Subdirectory       | Constant                       | Purpose                                      | Exportable | Temporary | Excluded by default |
|--------------------|--------------------------------|----------------------------------------------|------------|-----------|----------------------|
| `artifacts/`       | `CHILD_ARTIFACTS`              | AI outputs, file refs                        | When included | No     | No                   |
| `exports/`         | `CHILD_EXPORTS`                | Export package staging, manifests, ZIPs      | N/A (are the export) | No  | No                   |
| `docs/`            | `CHILD_DOCS`                   | Documentation bundles, one-pager outputs     | Yes        | No       | No                   |
| `restore-temp/`    | `CHILD_RESTORE_TEMP`           | Temporary workspace for restore preparation  | No         | Yes      | Yes                  |
| `support-bundles/` | `CHILD_SUPPORT_BUNDLES`        | Support bundle outputs                       | When requested | No   | No                   |

- **Exportable:** Content may be included in export packages (permission-gated).
- **Temporary:** Cleanup-managed; not intended for long-term retention; excluded from default export.
- **Excluded by default:** Not included in export unless explicitly requested; see §52.6 for secrets exclusion.

## Path segment rules

- User-supplied or dynamic path segments must be passed through `get_child_path_with_segment()` only.
- Allowed segment characters: alphanumeric, hyphen, underscore. No `..`, `/`, `\`, or other path traversal.
- `is_safe_segment()` and `is_under_base()` are available for validation and cleanup logic.

## Security

- No front-end exposure of plugin-owned file paths.
- No concatenation of user input into paths without sanitization via the manager.
- Exports and artifacts are permission-gated for download; paths must not contain secrets.
- Never place API keys, passwords, or tokens in path names or public filenames by convention.

## Implementation

- **Class:** `AIOPageBuilder\Infrastructure\Files\Plugin_Path_Manager`
- **Methods:** `get_uploads_base()`, `get_child_path()`, `get_child_path_with_segment()`, `base_exists()`, `child_exists()`, `ensure_base()`, `ensure_child()`, `is_under_base()`, `is_safe_segment()`.
