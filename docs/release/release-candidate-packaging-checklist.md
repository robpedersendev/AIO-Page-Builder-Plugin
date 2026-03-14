# Release Candidate Packaging Checklist

**Governs:** Spec §6.1 Private Distribution Method; §6.2 Installation Package Format; §6.5 Update Delivery Strategy; §59.15.  
**Purpose:** Ensure the production ZIP is clean, WordPress-installable, and free of development-only or secret-bearing artifacts. Deterministic and checklist-driven.

---

## 1. Package format and structure (§6.2)

| # | Check | Expected | Pass/Fail |
|---|-------|----------|-----------|
| 1.1 | ZIP is a standard WordPress-compatible archive | Single root directory; all content under that directory. | ☐ |
| 1.2 | Root directory name | One folder (e.g. `aio-page-builder`) so that extraction yields `wp-content/plugins/<folder>/`. | ☐ |
| 1.3 | Deterministic extraction | Unzip into WordPress `wp-content/plugins/` produces a single plugin directory; no stray files at plugins root. | ☐ |
| 1.4 | Uploadable via WordPress plugin installer | ZIP can be uploaded via **Plugins → Add New → Upload Plugin** and installs without error. | ☐ |

---

## 2. Required production files and metadata

| # | Check | Expected | Pass/Fail |
|---|-------|----------|-----------|
| 2.1 | Main plugin file | Exactly one main PHP file at package root (e.g. `aio-page-builder.php`) with WordPress plugin headers. | ☐ |
| 2.2 | Plugin header: Plugin Name | Present and non-empty. | ☐ |
| 2.3 | Plugin header: Version | Present; matches `Constants::PLUGIN_VERSION` / release version. | ☐ |
| 2.4 | Plugin header: Requires at least (WordPress) | Present; minimum 6.6 per spec. | ☐ |
| 2.5 | Plugin header: Requires PHP | Present; minimum 8.1 per spec. | ☐ |
| 2.6 | Plugin header: Text Domain | Present (e.g. `aio-page-builder`). | ☐ |
| 2.7 | Source directory | `src/` present with Bootstrap, Admin, Domain, Infrastructure, Reporting, Support, Options as applicable. | ☐ |
| 2.8 | Bootstrap entry | `src/Bootstrap/Constants.php`, `src/Bootstrap/Plugin.php`; main file requires Constants and Plugin. | ☐ |
| 2.9 | Version markers | Runtime version available via `Constants::plugin_version()`; no hardcoded dev version in production. | ☐ |
| 2.10 | Assets | `assets/` if used; production CSS/JS only (no unminified dev-only bundles unless intended). | ☐ |
| 2.11 | Languages | `languages/` or Domain Path correct if translatable; .pot/.mo as released. | ☐ |

---

## 3. Excluded development-only artifacts

The following must **not** appear in the production ZIP (or must be explicitly justified if included for support):

| # | Excluded item | Reason | Pass/Fail |
|---|----------------|--------|-----------|
| 3.1 | `tests/` | Unit/integration tests are development-only. | ☐ |
| 3.2 | `phpstan.neon.dist`, `phpstan.neon`, `.phpunit.*`, `phpunit.xml*` | Static analysis and test config. | ☐ |
| 3.3 | `.wp-env.json`, `.env`, `.env.*` | Local/dev environment; may contain paths or overrides. | ☐ |
| 3.4 | `.git/`, `.gitignore`, `.gitattributes` | Version control. | ☐ |
| 3.5 | `node_modules/`, `vendor/` (if not used at runtime) | Dev dependencies; Composer autoload for production only if used by plugin. | ☐ |
| 3.6 | `composer.phar`, `package-lock.json`, `yarn.lock` | Dev tooling. | ☐ |
| 3.7 | `.cursor/`, `.vscode/`, `*.sublime-*` | IDE/project files. | ☐ |
| 3.8 | Machine-specific or local paths | No absolute paths to a specific developer machine. | ☐ |
| 3.9 | Secrets, API keys, credentials | Never in package. | ☐ |
| 3.10 | Internal-only diagnostics dumps or logs | No sample logs with real data. | ☐ |
| 3.11 | `plugin/docs/` (optional) | Schema/contract docs under plugin: exclude from production unless bundled for support; if included, ensure no internal-only content. | ☐ |

**Note:** If the repository uses a single `plugin/` directory as the source tree, the ZIP is built from that tree (or a copy) with the root directory renamed to the plugin slug and exclusions applied. Document the exact build procedure (e.g. script or manual steps) in §5.

---

## 4. ZIP cleanliness and installability

| # | Check | Pass/Fail |
|---|-------|-----------|
| 4.1 | ZIP opens without errors in standard tools. | ☐ |
| 4.2 | No duplicate or conflicting paths (e.g. mixed case, trailing slashes). | ☐ |
| 4.3 | File permissions are not executable for PHP/CSS/JS unless required (WordPress will use its own). | ☐ |
| 4.4 | After upload and install, **Plugins** list shows the plugin; **Activate** works when WP/PHP and required dependencies are met. | ☐ |
| 4.5 | After activation, plugin version and identity match release (e.g. visible on Privacy, Reporting & Settings or Diagnostics). | ☐ |

---

## 5. Build procedure (document)

Record how the release ZIP is produced so it is repeatable:

| Step | Description |
|------|-------------|
| 1 | (e.g.) From repo root, create a clean directory or use `git archive` / copy of `plugin/` into a folder named `aio-page-builder`. |
| 2 | Remove or exclude: `tests/`, `phpstan.*`, `.wp-env.json`, `.git*`, `node_modules/`, `vendor/` (if not runtime), other items in §3. |
| 3 | Verify main file and `src/` structure; verify Version header matches release. |
| 4 | Zip the single root directory (e.g. `aio-page-builder/`) so the ZIP root contains that folder. |
| 5 | Run [release_preflight_check.php](../../tools/release_preflight_check.php) (if used) against the unpacked directory and record result. |
| 6 | Upload ZIP to a test WordPress instance and confirm install + activate. |

**Preflight:** If using the optional preflight script, run it before creating the ZIP and attach the result summary to this checklist or the release record.

**Example preflight result** (run against `plugin/` before exclusions):

```
[PASS] Main plugin file present
[PASS] Header 'Plugin Name' present
[PASS] Header 'Version' present
[PASS] Header 'Requires at least' present
[PASS] Header 'Requires PHP' present
[PASS] Header 'Text Domain' present
[PASS] Required file: src/Bootstrap/Constants.php
[PASS] Required file: src/Bootstrap/Plugin.php
[WARN] Development artifact present (exclude for production): tests/bootstrap.php
[WARN] Development artifact present (exclude for production): phpstan.neon.dist
[WARN] Development artifact present (exclude for production): .wp-env.json

Preflight summary: PASS (all required checks passed). Warnings may indicate dev-only content; exclude for production ZIP.
```

For production, exclude the warned paths so the ZIP contains no dev-only artifacts; then re-run preflight on the staged directory if desired.

---

## 6. Cross-references

| Artifact | Purpose |
|----------|---------|
| [private-distribution-handoff.md](private-distribution-handoff.md) | Handoff steps for private delivery. |
| [final-approval-runbook.md](final-approval-runbook.md) | Go/no-go and approval steps before shipment. |
| [release-review-packet.md](release-review-packet.md) | Evidence and sign-off linkage. |
| [template-library-release-candidate-addendum.md](template-library-release-candidate-addendum.md) | Template-library expansion packaging and go/no-go; §7 below. |
| Spec §6.2 | Installation package format. |

---

## 7. Template library expansion (explicit coverage)

The expanded template library (254 section templates, 580 page templates, directories, detail previews, compare, compositions, appendices, QA reports) must be explicitly verified for the release candidate.

| # | Check | Pass/Fail |
|---|--------|-----------|
| 7.1 | Template-library packaging and artifact completeness pass completed per [template-library-release-candidate-addendum.md](template-library-release-candidate-addendum.md) §1 and §2. | ☐ |
| 7.2 | Template-library go/no-go (§3 of addendum) is **Go**; expansion sign-off checklist and evidence links confirmed. | ☐ |
| 7.3 | No development-only registry fixtures or test-only template batches in the production ZIP; registry and preview/compare/composition code are production paths only. | ☐ |

**Rule:** Complete the addendum before signing off this checklist when the release includes the template-library expansion. The addendum does not replace §1–§5; it adds expansion-specific verification.

---

*Complete this checklist for each release candidate. Sign off only when all required checks pass. Do not ship with failures in §1, §2 (required files/metadata), or §3 (exclusions). When the release includes the template-library expansion, §7 must also pass.*
