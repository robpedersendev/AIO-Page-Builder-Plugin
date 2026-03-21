# Changelog

**Purpose:** Release-by-release changelog per spec §58.6, §60.6. Each entry covers what changed, what was added, what was fixed, migrations/compatibility, deprecations, and known limitations. Do not expose secrets or site-specific data.

**Structure:** Reverse chronological. Version and date set when cutting the release. Link to [release-notes-rc1.md](release-notes-rc1.md) (or the current release notes) for full operator-facing details.

---

## [Unreleased]

### Added
- **Template library:** For expansion counts, screens, compatibility, and limitations, see [template-library-release-notes-addendum.md](template-library-release-notes-addendum.md).
- **Form provider integration:** Provider-backed form sections (category form_embed) and request-form page template (`pt_request_form`). Form_Provider_Registry (provider_id + form_id); shortcode assembly; Build Plan/execution dependency validation (block when provider missing); finalization form_dependency; security validation and hardening; E2E acceptance structure. Operator guide: [form-provider-operator-guide.md](../guides/form-provider-operator-guide.md). Release gate and extension backlog: [form-provider-integration-review-packet.md](form-provider-integration-review-packet.md), [form-provider-extension-backlog.md](form-provider-extension-backlog.md).

### Changed (Release evidence & R-6 — 2026-03-21)
- **Docs:** [RELEASE_CHECKLIST.md](../qa/RELEASE_CHECKLIST.md), [release-candidate-closure.md](../qa/release-candidate-closure.md), [compatibility-matrix.md](../qa/compatibility-matrix.md), [migration-coverage-matrix.md](../qa/migration-coverage-matrix.md), [known-risk-register.md](known-risk-register.md), root [README.md](../../README.md), and [project-wide-full-production-readiness-gap-report.md](../operations/project-wide-full-production-readiness-gap-report.md) updated with measured commands, exit codes, and counts (PHPUnit; PHPCS `src/` + `tests/`; Plugin Check summarize; PHPStan). TF-1 superseded; TOOL-1–TOOL-3 added for open tooling debt.
- **R-6:** `Object_Status_Families` class docblock aligned — bootstrap owns custom status registration; this class remains the authoritative allowed-status sets for validation/repositories.

### Changed (Production Hardening Passes — 2026-03-19)
- **P1 — Tokens step (Build Plan):** Tokens step UI aligned with real execution pipeline (`Token_Set_Job_Service`, `Apply_Token_Set_Handler`). Removed "execution not implemented" copy. Placeholder diff fields removed. Row/bulk action paths governed with capability, nonce, validation, and structured audit logging.
- **P2 — SEO step (Build Plan):** SEO step explicitly advisory-only; execution affordances removed where no handler exists. Step summary, row state, and detail payloads state advisory posture consistently.
- **P3 — Industry Bundle import/apply screen:** Stale "preview-only / apply not implemented" language removed. Screen contract and copy reflect the real upload→preview→conflict-review→apply flow.
- **P4 — Versions, Bootstrap, Lifecycle wording:** Placeholder-driven and "later prompt / future" framing removed from `Versions.php`, `Plugin.php`, `Module_Registrar.php`, `Lifecycle_Manager.php`. Comments describe stable production behavior.
- **P2A — Onboarding forms:** Full guided step forms implemented in `Onboarding_Screen` for all 7 profile steps. Real save/load/prefill using `Profile_Store`. Provider setup routes through existing `AI_Providers_Screen`. Review step shows stored data and readiness. All actions capability- and nonce-gated.
- **P2B — Placeholder copy removal:** User-visible placeholder/deferred-work copy removed from `Onboarding_Screen` and `New_Page_Creation_Detail_Builder`. No "future update," "coming soon," or "out of scope" copy in released surfaces.
- **P3B — Stale comment/docblock cleanup:** Stale implementation-history wording removed from `Industry_Packs_Module`, `Crawler_Comparison_Screen`, `Build_Plan_Workspace_Screen`, `Admin_Router_Provider`, `Page_Templates_Directory_Screen`, `Section_Templates_Directory_Screen`, `Onboarding_Screen`.
- **P4A — Execution action types de-scope:** `assign_page_hierarchy` and `create_menu` explicitly de-scoped from executable action set (mirrors `UPDATE_PAGE_METADATA` posture). Removed from `Execution_Action_Types::ALL`; documented with rationale. No misleading executable affordances.
- **P5B — Profile snapshot persistence de-scope:** `Profile_Snapshot_Data` remains schema-only for v1; no persistence implemented. Formally documented.
- **P6B — AI cost/usage de-scope:** `cost_placeholder => null` removed from `Concrete_AI_Provider_Driver` and `Additional_AI_Provider_Driver` usage structs. Token counts (prompt, completion, total) are the authoritative v1 operational metric. Docblocks updated.

### Fixed
- Stale-count test drift corrected: `Assignment_Types_Test` (5→6 types), `Export_Bundle_Schema_Test` (5→6 modes), `Onboarding_State_Machine_Contract_Test` (11→12 steps), `Composition_Filter_State_Test` (100→`MAX_PER_PAGE`=50).
- `support-triage-guide.md` §6 Diagnostics screen copy updated from placeholder to production-accurate de-scoped wording.
- Test bootstrap: `wp_parse_url()` stub for PHPUnit parity with WordPress. Queue health test: stable `started_at` via `gmdate( 'c', … )` for stale-lock visibility.

### Migration / compatibility
- (Carry forward from last release unless updated.)

### Deprecations
- (None unless announced.)
- *Template library:* Sync deprecations with [template-library-decision-log.md](template-library-decision-log.md); add lines via `Template_Deprecation_Service::build_changelog_snippet_for_deprecation()`. Example line: `- **Section template** \`st_old_hero\` deprecated. Superseded by layout variant. Recommended replacement(s): \`st01_hero_intro\`.`

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
- **Template library expansion:** 254 section templates and 580 page templates (coverage matrix and compliance evidenced). Section Templates directory (hierarchical by purpose family and CTA/variant; filters, pagination, View, Add/Remove compare, helper link). Page Templates directory (by category/family; same actions). Section and Page Template Detail screens (metadata, version/deprecation, rendered preview with synthetic data; no menu entry, via View). Template Compare workspace (section or page type; max 10 per type; observational only; user meta). Compositions (list and Build composition with CTA-aware section selection; governed builder; list cap 100). CTA-rule enforcement (min CTA per page class, bottom-of-page CTA, non-adjacent CTAs). Preview cache cap (800); directory per-page cap (50). Template_Library_Upgrade_Helper (registry_schema in version_markers); versioning/deprecation services and decision log; ACF assignment at scale; optional LPagery compatibility. Template library operator, editor, and support guides; in-product help refs on directory/compare/compositions. See [template-library-release-notes-addendum.md](template-library-release-notes-addendum.md).

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
