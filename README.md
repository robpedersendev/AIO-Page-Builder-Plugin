# AIO Page Builder

Privately distributed WordPress plugin. Provides AI-assisted page planning, Build Plan review, template library, and execution for WordPress sites.

---

## Requirements

| Dependency | Minimum | Notes |
|------------|---------|-------|
| WordPress | 6.6 | Activation blocked below minimum |
| PHP | 8.1 | Activation blocked below minimum |
| Advanced Custom Fields Pro | 6.2 | Required; activation blocked if missing or below minimum |
| GenerateBlocks | 2.0 | Required; activation blocked if missing or below minimum |
| GeneratePress (theme) | — | Preferred; other standards-compliant block-capable themes supported |
| LPagery | — | Optional; token workflows degrade gracefully when absent |

---

## Installation

1. Upload the plugin ZIP to **WordPress Admin → Plugins → Add New → Upload Plugin**.
2. Activate. The plugin validates environment requirements at activation; a clear admin notice is shown if requirements are not met.
3. Complete onboarding via **AIO Page Builder → Onboarding & Profile**.
4. Configure an AI provider via **AIO Page Builder → AI Providers**.

---

## Distribution

This plugin is privately distributed. It is not published to the WordPress Plugin Directory. Update delivery is managed via the private distribution channel. Do not redistribute without authorization.

---

## Operational Reporting

This plugin sends mandatory operational reports (install notification, periodic heartbeat, developer error reports) to the plugin operator's server. Reporting is disclosed on **AIO Page Builder → Privacy, Reporting & Settings**. Reporting failure does not break core plugin functionality. No user personal data is included in reports. See the admin operator guide for full disclosure details.

---

## Local CI (GitHub Actions parity)

The workflow [`.github/workflows/ci.yml`](.github/workflows/ci.yml) runs from **`plugin/`**: `composer install --prefer-dist --no-progress`, then `composer run phpcs`, `composer run phpstan`, `composer run phpunit`. Optional **Plugin Check** uses Docker in `tools/plugin-check/`.

| What | How |
|------|-----|
| **One-liner (host PHP)** | `cd plugin` then `composer run ci` (or `composer run ci:no-install` if `vendor/` is already installed). |
| **Windows script (+ optional Plugin Check)** | From repo root: `powershell -NoProfile -ExecutionPolicy Bypass -File tools\ci-local.ps1` — add `-IncludePluginCheck` for Docker Plugin Check; `-StrictPluginCheck` fails on ERROR findings. (`pwsh` works the same if installed.) |
| **Linux / macOS / Git Bash** | `SKIP_INSTALL=1 ./tools/ci-local.sh` skips install; `INCLUDE_PLUGIN_CHECK=1 STRICT_PLUGIN_CHECK=1 ./tools/ci-local.sh` runs Plugin Check strictly. |
| **PHP 8.1–8.3 matrix in Docker** | `powershell -NoProfile -ExecutionPolicy Bypass -File tools\ci-matrix-docker.ps1` or `bash tools/ci-matrix-docker.sh` (slower; installs Composer inside each container). |

---

## Documentation

| Guide | Purpose |
|-------|---------|
| [Admin & Operator Guide](docs/guides/admin-operator-guide.md) | Full admin screen and workflow reference |
| [End-User Workflow Guide](docs/guides/end-user-workflow-guide.md) | Onboarding, Build Plan review, and execution |
| [Template Library Operator Guide](docs/guides/template-library-operator-guide.md) | Section/page template directories, compare, compositions |
| [Template Library Editor Guide](docs/guides/template-library-editor-guide.md) | Choosing templates; one-pagers; compositions |
| [Template Library Support Guide](docs/guides/template-library-support-guide.md) | Template diagnostics, compliance, support bundles |
| [Support Triage Guide](docs/guides/support-triage-guide.md) | Logs, support bundle, redaction, issue triage |

---

## Known Limitations (RC1)

- Diagnostics screen is registered but does not yet surface environment/validation summaries in the UI (de-scoped for v1).
- Multisite: site-level operation supported; network-wide centralized management not validated.
- PHP 8.4+ not in validated set; add to compatibility matrix when routinely tested.
- Rollback and diff UX may be enhanced in future releases.
- **Quality gates:** Full PHPUnit passes locally at this repo state (**exit 0**; see [release-candidate-closure.md](docs/qa/release-candidate-closure.md) §2 for skips/deprecations). PHPCS (`src/`), Plugin Check, and PHPStan are **not** all green — see [known-risk-register.md](docs/release/known-risk-register.md) §3 (TOOL-1–TOOL-3) and the release checklist.

---

## Changelog

See [docs/release/changelog.md](docs/release/changelog.md).

---

## License

Privately distributed. All rights reserved. Not for public redistribution.
