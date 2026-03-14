# AIO Page Builder — Release Notes (Release Candidate 1)

**Spec:** §58.6 Release Notes Standards; §58.7 Breaking Change Policy; §58.8 Deprecation Policy; §59.15, §60.6, §60.8.  
**Audience:** Operators and administrators.  
**Version:** Set at release cut (e.g. 0.1.0 or 1.0.0-rc1). Update this heading and compatibility/migration sections when cutting the release.

---

## 1. Release summary

This release candidate delivers the core AIO Page Builder workflow for privately distributed use: guided onboarding and profile, AI provider configuration, crawl sessions, AI runs, Build Plans with step-by-step review and approval, queued execution, Queue & Logs monitoring, mandatory operational reporting (disclosed), Privacy/Reporting/Uninstall settings, and Import/Export with validate-before-restore. Built pages are intended to survive plugin uninstall; only plugin-owned data is removed on uninstall when the user proceeds.

---

## 2. Major feature areas (implemented)

- **Template library (expanded):** Section Templates directory (254 templates, hierarchical browse by purpose family and CTA/variant), Page Templates directory (580 templates, by category/family), Section/Page Template Detail screens (metadata, preview with synthetic data, version/deprecation, compare links), Template Compare workspace (side-by-side, max 10 per type, observational only), Compositions (list and CTA-aware builder). CTA-rule enforcement; preview cache and performance caps; versioning/deprecation and decision log; ACF assignment at scale; optional LPagery. See **[template-library-release-notes-addendum.md](template-library-release-notes-addendum.md)** for counts, screens, compatibility, migration, and limitations.
- **Dashboard:** Entry point with readiness cards, quick actions, last activity, queue warnings, critical error summary.
- **Onboarding & Profile:** Guided steps for brand/business profile; draft save; data used by planner and AI.
- **AI Providers:** Configure providers; credential status (no raw keys in UI); connection test per provider.
- **Crawl Sessions / Crawl Comparison:** List sessions and session detail; crawler scoped to this site; public-only, normalized URL identity.
- **AI Runs:** List runs and run detail; artifact summaries; link to Build Plan when present; raw prompts/responses restricted.
- **Build Plans:** List plans; open workspace with stepper. Steps include existing page updates (approve/deny, bulk), build intent (approve, Build All/Build selected), navigation (approve/deny, Apply All/Deny All), execution confirmation, logs/rollback. Rollback request from workspace when eligible; rollback is queued, not immediate.
- **Queue & Logs:** Tabs — Queue, Execution Logs, AI Runs, Reporting Logs, Import/Export Logs, Critical Errors. Reporting health summary. Row links to Build Plan or AI Run where applicable. Log export (redacted JSON) for users with export capability; optional date and log-type filters.
- **Privacy, Reporting & Settings:** Reporting disclosure, retention summary, uninstall/export choices, environment and version, report destination, privacy-policy helper text.
- **Import / Export:** Create export (modes: full operational backup, pre-uninstall backup, support bundle, template only, plan/artifact export, uninstall settings/profile only); export history and download; validate package; restore with conflict resolution and confirmation.
- **Settings:** Plugin settings entry; form template seeding for form-provider integration (e.g. NDR Form Manager).
- **Diagnostics:** Placeholder screen (not yet implemented); structured logging available for internal use.

---

## 3. Breaking changes and deprecations

- **Breaking changes:** None in this release. Table schema and export schema are at version 1; no incompatible schema shift.
- **Deprecations:** None in this release. No APIs or templates are marked deprecated.

If a future release introduces breaking changes (removed support, incompatible schema, changed export format without compatible restore, or template meaning change), they will be called out here and in the changelog per §58.7. Deprecations will be marked with reason and replacement per §58.8.

---

## 4. Migration and upgrade notes

- **Table schema:** 1. Custom tables are created or upgraded on activation; install is idempotent. No multi-step migration list in this release.
- **Export schema:** 1. Same-major import only; cross-major import is blocked. Incoming package schema must not exceed current export schema.
- **Upgrade from previous release:** This is the first release candidate; no prior upgrade path. For future releases, upgrade path will be documented (e.g. run activation; migrations if any).
- **Downgrade:** No supported downgrade path. Installing an older plugin after a newer one may leave stored schema version newer than code; activation is blocked with a clear “Unsupported schema” message. Restore from a backup or export taken before upgrade if rollback is required.

See [migration-coverage-matrix.md](../qa/migration-coverage-matrix.md) for test scenarios and version rules.

---

## 5. Compatibility and required dependencies

- **WordPress:** 6.6 or newer (current major at release). Below 6.6: activation blocked.
- **PHP:** 8.1, 8.2, 8.3. Below 8.1: activation blocked.
- **Required plugins:** Advanced Custom Fields Pro 6.2 or newer; GenerateBlocks 2.0 or newer. Missing or below minimum: activation blocked.
- **Preferred environment:** GeneratePress with GenerateBlocks (fully validated target). Other block-capable themes with GenerateBlocks are generally supported; no claim to full validation for every theme.
- **Optional:** LPagery (token workflows; absence triggers warning only). SEO, caching, security plugins: coexistence only unless documented.

See [compatibility-matrix.md](../qa/compatibility-matrix.md) for the full matrix and release-notes snippet.

---

## 6. Reporting and privacy disclosure (operator impact)

This plugin is **privately distributed**. Operational reporting is **mandatory and cannot be disabled**.

- **What is sent:** Install notification, periodic heartbeat, and error reports to an approved destination (e.g. email transport). Included: site identifier, plugin version, WordPress and PHP versions, dependency state, sanitized error summaries. Excluded: API keys, passwords, personal data, raw logs.
- **Where it is disclosed:** **AIO Page Builder → Privacy, Reporting & Settings.** Use that screen and the privacy-policy helper text for site privacy documentation.
- **Failure behavior:** Reporting failure does not break core plugin behavior; delivery outcomes are recorded locally for diagnostics (Queue & Logs → Reporting Logs).

See [REPORTING_EXCEPTION.md](../standards/REPORTING_EXCEPTION.md) for the documented exception and payload/redaction rules.

---

## 7. Rollback support (product behavior)

- **Execution rollback:** From the Build Plan workspace (Step 7), users with the appropriate capability can request a rollback when pre- and post-execution snapshots exist and eligibility is met. Rollback is **queued** (not immediate). Success or failure is indicated in the UI (`rollback_done` / `rollback_error`). Rollback is not guaranteed in all cases; verify critical content after rollback.
- **Plugin rollback:** If you need to revert to a previous plugin version, uninstall (with or without export) and install the older package. Restore from an export taken before upgrade if needed. Downgrade with stored schema newer than code will block activation until schema is resolved or site is restored.

---

## 8. Known limitations and support caveats

- **Template library:** Compare list capped at 10 per type; detail previews use synthetic data only; compositions are governed (registry sections only, CTA rules enforced); no edit-in-place of template definition from detail; deprecated templates have no automatic replacement. Large library may stress directory/preview on constrained hosting (pagination and caps in place). See [template-library-release-notes-addendum.md](template-library-release-notes-addendum.md) §10.
- **Diagnostics screen:** Placeholder only; full environment validation and support-package UI may come in a later release.
- **Multisite:** Site-level operation supported; network-wide centralized management not supported; network activation not officially validated unless separately tested.
- **Theme detection:** No runtime check for GeneratePress vs other themes; preferred-theme messaging is documentation-only until theme posture checks are added.
- **PHP 8.4+:** Not yet in the validated set; add when routinely tested.
- **Build Plan / rollback UX:** Rollback and diff/snapshot UX may be enhanced in future releases; current rollback is queued and eligibility-gated.
- **Queue on constrained hosting:** Job queue and cron-based processing; very large plans or heavy load may require adequate hosting.

See [known-risk-register.md](known-risk-register.md) for internal risk register and mitigations. Do not expose internal-only or sensitive risk detail in operator-facing materials unless approved.

---

## 9. Install and upgrade instructions

- **Install:** Upload plugin (or deploy via your private distribution channel). Ensure WordPress 6.6+, PHP 8.1+, ACF Pro 6.2+, and GenerateBlocks 2.0+ are in place. Activate; environment validator will block activation if requirements are not met.
- **First run:** Complete **Onboarding & Profile**, then configure **AI Providers** and run a connection test. Use **Crawl Sessions** if you use crawl data for planning. Create an AI run and review the resulting Build Plan before execution.
- **Upgrade (future):** When a newer release is available, update the plugin and run activation. Apply any migration or compatibility steps described in that release’s notes.

---

## 10. References

| Topic | Document |
|-------|----------|
| Changelog | [changelog.md](changelog.md) |
| Compatibility | [compatibility-matrix.md](../qa/compatibility-matrix.md) |
| Migration | [migration-coverage-matrix.md](../qa/migration-coverage-matrix.md) |
| Known risks | [known-risk-register.md](known-risk-register.md) |
| Reporting exception | [REPORTING_EXCEPTION.md](../standards/REPORTING_EXCEPTION.md) |
| Portability and uninstall | [PORTABILITY_AND_UNINSTALL.md](../standards/PORTABILITY_AND_UNINSTALL.md) |
| Admin and support guidance | [admin-operator-guide.md](../guides/admin-operator-guide.md), [support-triage-guide.md](../guides/support-triage-guide.md) |
| Template library expansion (counts, screens, compatibility, limitations) | [template-library-release-notes-addendum.md](template-library-release-notes-addendum.md) |
| Template library operator/editor/support guides | [template-library-operator-guide.md](../guides/template-library-operator-guide.md), [template-library-editor-guide.md](../guides/template-library-editor-guide.md), [template-library-support-guide.md](../guides/template-library-support-guide.md) |
| Release packaging and approval (internal) | [release-candidate-packaging-checklist.md](release-candidate-packaging-checklist.md), [final-approval-runbook.md](final-approval-runbook.md), [private-distribution-handoff.md](private-distribution-handoff.md) |

---

*Release notes are part of the product. Update version and date when cutting the release. Do not expose secrets or site-specific data.*
