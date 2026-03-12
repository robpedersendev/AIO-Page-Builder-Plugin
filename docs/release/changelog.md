# Changelog

**Purpose:** Release-by-release changelog per spec §58.6, §60.6. Each entry covers what changed, what was added, what was fixed, migrations/compatibility, deprecations, and known limitations. Do not expose secrets or site-specific data.

**Structure:** Reverse chronological. Version and date set when cutting the release. Link to [release-notes-rc1.md](release-notes-rc1.md) (or the current release notes) for full operator-facing details.

---

## [Unreleased]

### Added
- (Add items as work completes.)

### Changed
- (None.)

### Fixed
- (None.)

### Migration / compatibility
- (Carry forward from last release unless updated.)

### Deprecations
- (None unless announced.)

### Known limitations
- See [known-risk-register.md](known-risk-register.md).

---

## [0.1.0] — Release Candidate 1 (date TBD)

*Full release notes: [release-notes-rc1.md](release-notes-rc1.md).*

### Added

- **Admin:** Dashboard (readiness cards, quick actions, last activity, queue warnings, critical errors). Settings (form template seeding). Diagnostics (placeholder). Onboarding & Profile (guided steps, draft save). Crawl Sessions and Crawl Comparison. AI Providers (configure, credential status, connection test). AI Runs (list and detail, artifact summaries). Build Plans (list and workspace with stepper: existing page updates, build intent, navigation, execution, logs/rollback). Queue & Logs (Queue, Execution Logs, AI Runs, Reporting Logs, Import/Export Logs, Critical Errors; reporting health; log export for authorized users). Privacy, Reporting & Settings (disclosure, retention, uninstall choices, environment/version, report destination, privacy helper). Import / Export (create export, export history, validate package, restore with conflict resolution).
- **Execution:** Queued job execution; rollback request from Build Plan workspace when eligible (queued).
- **Reporting:** Mandatory operational reporting (install, heartbeat, error reports); disclosed on Privacy/Reporting screen; failure does not break core.
- **Export modes:** Full operational backup, pre-uninstall backup, support bundle, template only, plan/artifact export, uninstall settings/profile only. Redacted support bundle and log export.
- **Documentation:** Admin operator guide, end-user workflow guide, support triage guide. Documentation completeness checklist in QA closure.

### Changed

- (First release; no prior behavior.)

### Fixed

- (First release; N/A.)

### Migration / compatibility

- **Table schema:** 1. Export schema: 1. Same-major import only. No breaking schema change. See [migration-coverage-matrix.md](../qa/migration-coverage-matrix.md).
- **Tested:** WordPress 6.6+; PHP 8.1, 8.2, 8.3; ACF Pro 6.2+; GenerateBlocks 2.0+. Preferred: GeneratePress with GenerateBlocks. See [compatibility-matrix.md](../qa/compatibility-matrix.md).

### Deprecations

- None.

### Breaking changes

- None.

### Known limitations

- Diagnostics screen placeholder; multisite posture (site-level only); theme detection documentation-only; PHP 8.4+ not in validated set; rollback/diff UX may be enhanced later; queue behavior on constrained hosting. See [known-risk-register.md](known-risk-register.md).

---

*Bump version and date when cutting release. For breaking changes or deprecations in future releases, add explicit subsections per §58.7, §58.8.*
