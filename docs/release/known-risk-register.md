# Known Risk Register

**Governs:** Spec §59.15 (Production Readiness Phase), §61 (Known Risks, Open Questions, and Decision Log).  
**Purpose:** Record known product and technical risks, mitigations, and release-scope notes for sign-off and support.  
**Audience:** Internal; release and support context. Do not expose secrets or site-specific data.

---

## 1. Product Risks (Spec §61.1)

| Risk | Mitigation | Release scope |
|------|------------|---------------|
| Build Plan complexity may overwhelm users if not staged carefully. | Stepper UI; step-by-step review; approval/deny per item; bulk actions with confirmation. | Document in user guidance; monitor feedback. |
| Too many advanced settings may dilute the plugin's structured nature. | Settings kept minimal; optional integrations (e.g. LPagery) degrade gracefully. | Current scope: no expansion of settings in this release. |
| Aggressive replacement workflows may feel risky without excellent diff/rollback UX. | Rollback queued and revalidated; diff/snapshot placeholders; destructive actions require confirmation. | Rollback and diff UX may be enhanced in future releases. |

---

## 2. Technical Risks (Spec §61.2)

| Risk | Mitigation | Release scope |
|------|------------|---------------|
| Page survivability while retaining rich orchestration metadata. | Built pages are native WordPress content; plugin data (plans, artifacts) separate; uninstall does not delete built pages. | Export/restore and uninstall behavior documented; PORTABILITY_AND_UNINSTALL. |
| Migration complexity across template/schema changes. | Versioned schema (table_schema, export_schema, etc.); migration contract; future-schema block on downgrade. | Migration coverage matrix; no multi-step migrations in current release. |
| Queue reliability on low-quality hosting. | Job queue table; cron for heartbeat and queue processing; reporting failure does not break core. | Document minimum hosting expectations; consider queue health in diagnostics. |

---

## 3. Release-Specific Risks and Limitations

| ID | Category | Description | Mitigation / waiver |
|----|----------|-------------|----------------------|
| TLE-1 | Template library | Large expanded library (254 sections, 580 page templates) may stress admin directory, compare, composition builder, or appendix generation on constrained hosting. | Performance hardening in place: MAX_PER_PAGE 50, preview cache cap 800, compare list 10, compositions list 100; directory/query/preview tuning. See [template-admin-performance-hardening-report.md](../qa/template-admin-performance-hardening-report.md) and [template-library-expansion-review-packet.md](template-library-expansion-review-packet.md) §6. Appendices regenerable from registries. |
| TLE-2 | Template library / compatibility | Compatibility claims for the expanded library (directory, previews, builds, ACF at scale, GenerateBlocks/native, LPagery, themes) require run and recorded checklist. Claiming a template family or environment compatible without testing representative previews and builds is not permitted. | [template-library-compatibility-report.md](../qa/template-library-compatibility-report.md) checklist; [compatibility-matrix.md](../qa/compatibility-matrix.md) §13. Document test date and result; do not overclaim. |
| TLE-3 | Template library / QA | Compliance (CTA, category, count, preview, ACF, LPAGERY, export) and migration/upgrade evidence must exist for sign-off. Automated compliance and accessibility audits support but do not replace human review where required. | [template-library-compliance-matrix.md](../qa/template-library-compliance-matrix.md); [template-library-expansion-review-packet.md](template-library-expansion-review-packet.md); [template-library-expansion-sign-off-checklist.md](template-library-expansion-sign-off-checklist.md); [template-library-accessibility-audit-report.md](../qa/template-library-accessibility-audit-report.md). |
| TLE-4 | Template library / maintenance | Deprecated templates have no automatic replacement; users must select replacement explicitly. Appendix is generated from live registry (no persisted store); regeneration is implicit on export or on demand after upgrade. Version/deprecation continuity depends on registry CPTs not being reset. | Document in operator and support guides; [template-library-migration-coverage-report.md](../plugin/docs/qa/template-library-migration-coverage-report.md). Decision log and changelog deprecation sync per §58.8. |
| FPR-1 | Security / form provider | Provider-backed form sections and request-form template: provider_id and form_id are validated at output (registry + pattern); stored content could theoretically hold non-registry or malformed values until render. Render path never emits arbitrary shortcode. | Form_Provider_Registry validates and sanitizes; build_shortcode returns null for invalid. Public validate_provider_and_form available for save paths. Optional ACF validate_value in follow-up. See [form-provider-security-checklist.md](../qa/form-provider-security-checklist.md), [form-provider-security-review.md](../qa/form-provider-security-review.md). |
| FPR-2 | Form provider / deferred | Diagnostics classification and survivability messaging for provider dependency (Prompt 231); export/restore validation of provider refs (Prompt 232). When complete, evidence in [form-provider-integration-review-packet.md](form-provider-integration-review-packet.md). | Backlog in [form-provider-extension-backlog.md](form-provider-extension-backlog.md). Release gate allows deferral with doc reference. |
| STY-1 | Styling / lifecycle | Styling options removed on uninstall; built content preserved. Theme override continuity depends on fixed selectors/token names (css-selector-contract). No guarantee of plugin-owned styling after uninstall unless exported and restored. | Documented in [styling-portability-and-uninstall.md](../guides/styling-portability-and-uninstall.md); QA checklist for deactivation/uninstall and theme continuity. |
| ACF-1 | ACF registration / cache | Section-key cache is optional and TTL-bound; stale cache falls back to assignment-map resolution. Template/composition cache invalidated on definition save (`aio_page_builder_page_template_definition_saved`, `aio_composition_definition_saved`). Legacy pages with no/incomplete assignment register zero groups; no silent full registration. | Correctness over speed; cache invalidates on assignment and definition change. [acf-registration-performance-release-gate.md](acf-registration-performance-release-gate.md); [acf-registration-cache-invalidation-matrix.md](../contracts/acf-registration-cache-invalidation-matrix.md); [acf-conditional-registration-support-runbook.md](../operations/acf-conditional-registration-support-runbook.md); [acf-conditional-registration-rollback-playbook.md](../operations/acf-conditional-registration-rollback-playbook.md); [acf-legacy-assignment-verification.md](../qa/acf-legacy-assignment-verification.md); [acf-legacy-page-repair-guide.md](../operations/acf-legacy-page-repair-guide.md). |
| ACF-2 | ACF registration / plugin-theme conflict | Third-party plugins or themes that alter $pagenow or post/edit context may cause resolver to treat page edit as non-page admin (zero groups). Fail-safe; no full registration. Conflict verification: [acf-plugin-theme-conflict-verification.md](../qa/acf-plugin-theme-conflict-verification.md). | Support guidance in [acf-third-party-admin-compatibility-matrix.md](../qa/acf-third-party-admin-compatibility-matrix.md). No bespoke integration; document unsupported combinations if found. |
| ACF-3 | ACF uninstall / retention | Runtime-registered field groups do not survive uninstall; saved values and assignment map are retained by default. Preserving editable field groups after uninstall requires explicit handoff (ACF_Native_Handoff_Generator) before uninstall. Misclassification in inventory could affect handoff correctness. | [acf-uninstall-retention-contract.md](../contracts/acf-uninstall-retention-contract.md); [acf-uninstall-preservation-policy.md](../operations/acf-uninstall-preservation-policy.md); [acf-uninstall-preservation-operator-guide.md](../guides/acf-uninstall-preservation-operator-guide.md); [acf-uninstall-preservation-verification.md](../qa/acf-uninstall-preservation-verification.md). Inventory read-only; safe failure favors retaining data. Admin disclosure on Privacy screen (Prompt 317). |
| IND-1 | Industry Pack / first release | Industry profile and applied preset are optional; unknown or unsupported industry export schema version on restore is skipped with log (no fatal). Pack/overlay/CTA pattern references that do not resolve fail safely (skip or generic behavior). | [industry-export-restore-contract.md](../contracts/industry-export-restore-contract.md); [industry-pack-release-gate.md](industry-pack-release-gate.md); [industry-pack-maintenance-checklist.md](../operations/industry-pack-maintenance-checklist.md); [industry-lifecycle-hardening-contract.md](../contracts/industry-lifecycle-hardening-contract.md). No industry = core plugin unchanged. Uninstall/multisite/CLI: [industry-lifecycle-regression-guard.md](../qa/industry-lifecycle-regression-guard.md). Recommendation drift: [industry-recommendation-regression-guard.md](../qa/industry-recommendation-regression-guard.md). |
| IND-2 | Industry Pack / profile change | When the active Industry Profile changes after plans have been approved or pages built, recommendations and snapshot context may diverge. No auto-rebuild or auto-rollback; change-impact is reported as non-destructive warnings only. | [industry-change-impact-contract.md](../contracts/industry-change-impact-contract.md). Built content remains stable; operators see divergence in admin/support contexts. |
| IND-3 | Industry Pack / deprecation | Profile selections may reference deprecated, superseded, or removed pack/bundle keys. No silent clearing of profile; removed keys are not in registry and resolve as missing (safe fallback + warning). Migration to replacement is manual or guided. | [industry-pack-deprecation-contract.md](../contracts/industry-pack-deprecation-contract.md). Export/restore preserves stored keys; re-validate refs after restore. |
| TF-1 | Test suite | **Superseded (2026-03-21).** Prior RC1 note: 25 PHPUnit failures (2026-03-19). Current expectation: `composer run phpunit` **exit 0**; track skips in [release-candidate-closure.md](../qa/release-candidate-closure.md) §2. | N/A — closure doc §4 retracts TF-1. |
| TOOL-1 | PHPCS (`phpcs.xml.dist`) | **Policy (2026-03-22):** Intentional rule relaxations in `plugin/phpcs.xml.dist` (and rare scoped `phpcs:disable`) are **non-blocking for ship** when they match §5 below—typically style/docs/Yoda/alternative-function preferences that do not affect security, behavior, or UX. CI **blocking** gate: `composer run phpcs` **exit 0** on configured paths. | Do not add broad new excludes without Architect triage. See §5 and [hardening-release-gate-matrix.md](../contracts/hardening-release-gate-matrix.md). |
| TOOL-2 | Plugin Check | **Policy (2026-03-22):** Full JSON report must be **reviewed** each release; findings are triaged. Items that are false positives, WordPress-core patterns, or analyzer noise with **no** functional, security, or usability impact may be recorded as **non-ship-blocking** debt. CI may run Plugin Check with `continue-on-error` while ERROR count is driven down; **ship** does not require a zero-count Plugin Check report if remaining issues are documented under §5 / QA closure. | Run `composer run plugin-check` (Docker); attach summarized triage to release evidence. |
| TOOL-3 | PHPStan | **Policy (2026-03-22):** `phpstan-baseline.neon` suppresses **triaged** static-analysis noise (signature drift, stub limits, etc.). Baseline entries are **non-blocking for ship** only when they meet §5—no correctness, security, or UX defect. **Blocking** gate: `composer run phpstan` **exit 0** with current config (includes baseline). Reducing baseline remains quality debt, not a release veto by itself. | Regenerate baseline only with review; prefer code fixes over new baseline lines. |
| *(add further per release)* | — | — | — |

**Industry first-release signoff:** Evidence and production signoff for the first four industries are recorded in [industry-subsystem-final-signoff.md](industry-subsystem-final-signoff.md). IND-1 and IND-2 remain the standing industry risks for this release.

**Phase-two backlog:** Completed capability clusters and remaining work are documented in [industry-phase-two-backlog-map.md](../operations/industry-phase-two-backlog-map.md). Use for prioritization and dependency-aware prompt generation; no new risks added by the backlog map itself.

**Industry maturity matrix:** [industry-subsystem-maturity-matrix.md](../operations/industry-subsystem-maturity-matrix.md) classifies subsystem capability areas by maturity, risks, and evidence gaps (Prompt 468). Use for release and planning discipline; does not replace this risk register.

**Styling deferred enhancements (not blockers):** Extended format validation (e.g. stricter color format checks), styling-specific a11y audit for global/per-entity screens. Recorded in [styling-release-gate.md](styling-release-gate.md) §2.2. Do not block release.

Use this section for risks or limitations specific to a release (e.g. "Export of very large plans may timeout on constrained hosting") with mitigation or formal waiver reference.

---

## 4. Cross-References

- **Spec:** §59.15 Production Readiness Phase; §61 Known Risks.
- **Hardening:** [hardening-release-gate-matrix.md](../contracts/hardening-release-gate-matrix.md) (waiver process, sign-off).
- **QA closure:** [release-candidate-closure.md](../qa/release-candidate-closure.md).
- **Tooling vs ship:** §5 (this register) — which linter/PCP items can be non-blocking for release.
- **Compatibility / migration:** [compatibility-matrix.md](../qa/compatibility-matrix.md), [migration-coverage-matrix.md](../qa/migration-coverage-matrix.md).
- **Release notes / changelog:** [release-notes-rc1.md](release-notes-rc1.md), [changelog.md](changelog.md) (operator-facing release content; do not duplicate internal risk detail here).
- **Template library release addendum:** [template-library-release-notes-addendum.md](template-library-release-notes-addendum.md) (counts, screens, compatibility, migration, limitations).
- **Template ecosystem closure bundle:** [template-ecosystem-archived-evidence-index.md](template-ecosystem-archived-evidence-index.md) (index of all archived evidence); [template-ecosystem-final-closure-summary.md](template-ecosystem-final-closure-summary.md) (closure summary; unresolved items marked waived, deferred, or blocked). Use for internal audit and future maintenance.
- **Form provider maintenance:** [form-provider-maintenance-sop.md](../operations/form-provider-maintenance-sop.md), [form-provider-upgrade-and-support-runbook.md](../operations/form-provider-upgrade-and-support-runbook.md). When provider-related incidents or upgrades introduce new limitations or mitigations, add or amend a row in §3 (e.g. FPR-1, FPR-2) and document in the SOP/runbook.
- **Styling security:** [styling-security-checklist.md](../security/styling-security-checklist.md), [styling-security-review.md](../security/styling-security-review.md). Restore path sanitization and entity-save capability check per Prompt 259.
- **Styling QA and release gate:** [styling-acceptance-report.md](../qa/styling-acceptance-report.md), [styling-release-gate.md](styling-release-gate.md). Evidence pack and blockers vs deferred per Prompt 260.

---

## 5. Tooling findings: ship-blocking vs non-blocking (governance)

**Production-ready / ship-ability** means: the plugin meets spec and security bars; automated tests and **configured** PHPCS + PHPStan pass; Plugin Check (and other reports) are **reviewed** and remaining items are consciously classified.

A static-analysis or coding-standard finding—or a Plugin Check row—does **not** block ship when **all** of the following hold:

1. **Industry-normal** — Common for WordPress plugins, PHPUnit stubs, or PHPStan+WordPress stub interaction (not a one-off hack).
2. **No material impact** — Does not impair **functionality**, **security** (capability checks, escaping, nonces on mutating paths, secret handling), or **usability** (admin flows, recoverability).
3. **Documented** — Reflected in `phpcs.xml.dist` comments, `phpstan-baseline.neon` (path-specific), a scoped inline disable with rationale, and/or a short note in QA/release closure for Plugin Check triage.

**Always ship-blocking:** failing PHPUnit on required suites; PHPCS **errors** on shipped `src/`; PHPStan failures **outside** an explicitly reviewed baseline; confirmed security or data-integrity issues; spec violations that affect user-visible behavior.

**Quality debt (not a ship veto):** shrinking PHPStan baseline, clearing Plugin Check WARNING noise, docblock completeness, style-only PHPCS warnings under the project ruleset—tracked and improved over time.

---

*Update this register when new risks are identified or mitigations change. Keep entries factual and internal.*
