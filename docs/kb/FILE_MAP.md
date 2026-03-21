# KB File Map — Workflows to Documentation

**Purpose:** Single mapping from **exposed product surface** (menus, hidden admin routes, and major embedded panels) to **canonical documentation**.  
**Source of registration order:** `plugin/src/Admin/Admin_Menu.php::register()`.  
**Companion:** [index.md](index.md) (audiences and taxonomy).

**Legend:** **Primary** is the article users should open first. **Secondary** is deep detail, contracts, or support-only material.

---

## 0. Vocabulary and permissions (foundational)

| Topic | Primary |
|-------|---------|
| Product terms, default role caps, UI map, which guide to open, terminology FAQ | [concepts-and-glossary.md](concepts-and-glossary.md) |

---

## 1. Top-level and dashboard

| Workflow / route | Primary | Secondary |
|------------------|---------|-----------|
| Dashboard (`aio-page-builder`) | [admin-operator-guide.md §1](../guides/admin-operator-guide.md) | `Dashboard_State_Builder` (implementation) |

---

## 2. Settings and registry maintenance

| Workflow / route | Primary | Secondary |
|------------------|---------|-----------|
| Settings — version, privacy link, template registry **seed** actions (`aio-page-builder-settings`) | [admin-operator-guide.md §1.1](../guides/admin-operator-guide.md) | [settings-registry-maintenance.md](operator/settings-registry-maintenance.md); [form-provider-integration-contract.md](../contracts/form-provider-integration-contract.md) (form seed) |

---

## 3. Diagnostics (core plugin)

| Workflow / route | Primary | Secondary |
|------------------|---------|-----------|
| Diagnostics (`aio-page-builder-diagnostics`) | [diagnostics-screens.md](operator/diagnostics-screens.md) | [support-triage-guide.md §6](../guides/support-triage-guide.md) |
| ACF Field Architecture (`aio-page-builder-acf-diagnostics`) | [diagnostics-screens.md](operator/diagnostics-screens.md) | `ACF_Diagnostics_State_Builder` |
| Form Provider Health (`aio-page-builder-form-provider-health`) | [diagnostics-screens.md](operator/diagnostics-screens.md); [form-provider-operator-guide.md](../guides/form-provider-operator-guide.md) | [admin-screen-inventory.md §2.6](../contracts/admin-screen-inventory.md) |

---

## 4. Onboarding, profile, and advanced AI menus

| Workflow / route | Primary | Secondary |
|------------------|---------|-----------|
| Onboarding & Profile (`aio-page-builder-onboarding`) | [onboarding-and-profile.md](operator/onboarding-and-profile.md) | [admin-operator-guide.md §2](../guides/admin-operator-guide.md); [end-user-workflow-guide.md §1](../guides/end-user-workflow-guide.md); `Onboarding_UI_State_Builder` |
| Profile History (`aio-page-builder-profile-snapshots`) | [advanced-ai-labs.md](operator/advanced-ai-labs.md) | Panel + history UX (content TBD) |
| AI Runs list + run detail (`aio-page-builder-ai-runs`) | [ai-runs-and-run-details.md](operator/ai-runs-and-run-details.md) | [admin-operator-guide.md §5](../guides/admin-operator-guide.md); [support-triage-guide.md §1](../guides/support-triage-guide.md) (AI Runs tab); `AI_Runs_Screen` / `AI_Run_Detail_Screen` |
| AI Providers (`aio-page-builder-ai-providers`) | [ai-providers-credentials-budget.md](operator/ai-providers-credentials-budget.md) | [admin-operator-guide.md §3](../guides/admin-operator-guide.md); `AI_Providers_UI_State_Builder` |
| Prompt Experiments (`aio-page-builder-prompt-experiments`) | [advanced-ai-labs.md](operator/advanced-ai-labs.md) | Spec / internal QA (content TBD) |

---

## 5. Crawl and discovery

| Workflow / route | Primary | Secondary |
|------------------|---------|-----------|
| Crawl Sessions list + session detail (`aio-page-builder-crawler-sessions`) | [crawler-sessions-and-comparison.md](operator/crawler-sessions-and-comparison.md) | [admin-operator-guide.md §4](../guides/admin-operator-guide.md); `Crawler_Sessions_Screen` / `Crawler_Session_Detail_Screen` |
| Crawl Comparison (`aio-page-builder-crawler-comparison`) | [crawler-sessions-and-comparison.md](operator/crawler-sessions-and-comparison.md) | [admin-operator-guide.md §4](../guides/admin-operator-guide.md); `Crawler_Comparison_Screen` |

---

## 6. Build Plans, execution, rollback

| Workflow / route | Primary | Secondary |
|------------------|---------|-----------|
| Queue & Logs — recovery retry/cancel (`aio-page-builder-queue-logs`, Queue tab) | [build-plan-rollback-and-recovery.md](operator/build-plan-rollback-and-recovery.md) | [admin-operator-guide.md §9](../guides/admin-operator-guide.md); [support-triage-guide.md §1–§2](../guides/support-triage-guide.md); `Queue_Logs_Screen` |
| Build Plans list + workspace (`aio-page-builder-build-plans`) | [build-plan-overview.md](operator/build-plan-overview.md) | [build-plan-review-existing-and-new-pages.md](operator/build-plan-review-existing-and-new-pages.md) (steps `1`–`2` review); [build-plan-hierarchy-navigation-tokens-seo.md](operator/build-plan-hierarchy-navigation-tokens-seo.md) (steps `3`–`6`); [build-plan-finalization-logs-rollback.md](operator/build-plan-finalization-logs-rollback.md) (steps `7`–`8`); [build-plan-execution-actions.md](operator/build-plan-execution-actions.md) (queue action types); [build-plan-rollback-and-recovery.md](operator/build-plan-rollback-and-recovery.md) (retry vs rollback); [admin-operator-guide.md §6–§7](../guides/admin-operator-guide.md); [end-user-workflow-guide.md §2–§3](../guides/end-user-workflow-guide.md); `Build_Plan_UI_State_Builder`; [executor-locking-idempotency-contract.md](../contracts/executor-locking-idempotency-contract.md) |
| Rollback (from plan workspace) | [build-plan-finalization-logs-rollback.md](operator/build-plan-finalization-logs-rollback.md); [build-plan-rollback-and-recovery.md](operator/build-plan-rollback-and-recovery.md) | [admin-operator-guide.md §8](../guides/admin-operator-guide.md); [end-user-workflow-guide.md §3](../guides/end-user-workflow-guide.md) |

---

## 7. Template library (directories, detail, compare, compositions, helper docs)

| Workflow / route | Primary | Secondary |
|------------------|---------|-----------|
| Template system overview (sections vs pages vs compositions vs docs/previews) | [template-system-overview.md](templates/template-system-overview.md) | [template-library-operator-guide.md](../guides/template-library-operator-guide.md); [template-library-editor-guide.md](../guides/template-library-editor-guide.md); deep dives: [section-templates-deep-dive.md](templates/section-templates-deep-dive.md), [page-templates-deep-dive.md](templates/page-templates-deep-dive.md), [compositions-deep-dive.md](templates/compositions-deep-dive.md) |
| Page Templates directory (`aio-page-builder-page-templates`) | [template-library-operator-guide.md](../guides/template-library-operator-guide.md) | [page-templates-deep-dive.md](templates/page-templates-deep-dive.md); [template-system-overview.md](templates/template-system-overview.md); `Page_Template_Directory_State_Builder` |
| Page Template Detail (`aio-page-builder-page-template-detail`) | [template-library-operator-guide.md](../guides/template-library-operator-guide.md) | [page-templates-deep-dive.md](templates/page-templates-deep-dive.md); [template-system-overview.md](templates/template-system-overview.md); `Page_Template_Detail_State_Builder`; `Entity_Style_UI_State_Builder` (per-entity styling panel) |
| Section Templates directory (`aio-page-builder-section-templates`) | [template-library-operator-guide.md](../guides/template-library-operator-guide.md) | [section-templates-deep-dive.md](templates/section-templates-deep-dive.md); [template-system-overview.md](templates/template-system-overview.md); `Section_Template_Directory_State_Builder` |
| Section Template Detail (`aio-page-builder-section-template-detail`) | [template-library-operator-guide.md](../guides/template-library-operator-guide.md); [form-provider-operator-guide.md](../guides/form-provider-operator-guide.md) (form binding panel) | [section-templates-deep-dive.md](templates/section-templates-deep-dive.md); [template-system-overview.md](templates/template-system-overview.md); `Section_Template_Detail_State_Builder`; `Form_Section_Field_State_Builder` |
| Template Compare (`aio-page-builder-template-compare`) | [template-library-operator-guide.md](../guides/template-library-operator-guide.md) | [template-system-overview.md](templates/template-system-overview.md); `Template_Compare_State_Builder` |
| Compositions list + builder (`aio-page-builder-compositions`) | [template-library-operator-guide.md](../guides/template-library-operator-guide.md) | [compositions-deep-dive.md](templates/compositions-deep-dive.md); [template-system-overview.md](templates/template-system-overview.md); `Composition_Builder_State_Builder` |
| Documentation / helper detail (`aio-page-builder-documentation-detail`) | [template-library-editor-guide.md §6](../guides/template-library-editor-guide.md) | [template-system-overview.md](templates/template-system-overview.md); [admin-screen-inventory.md](../contracts/admin-screen-inventory.md) |
| Choosing templates, one-pagers, helper docs (editor lens) | [template-library-editor-guide.md](../guides/template-library-editor-guide.md) | [template-system-overview.md](templates/template-system-overview.md); [template-library-operator-guide.md](../guides/template-library-operator-guide.md) |
| Support — inventory appendices, compliance | [template-library-support-guide.md](../guides/template-library-support-guide.md) | [template-system-overview.md](templates/template-system-overview.md); [support-triage-guide.md](../guides/support-triage-guide.md) |

---

## 8. Analytics and post-release operations

| Workflow / route | Primary | Secondary |
|------------------|---------|-----------|
| Build Plan Analytics (`aio-page-builder-build-plan-analytics`) | [operational-analytics.md](analytics/operational-analytics.md) | `Build_Plan_Analytics_Service` / screen |
| Template Analytics (`aio-page-builder-template-analytics`) | [operational-analytics.md](analytics/operational-analytics.md) | Template analytics screen |
| Post-Release Health (`aio-page-builder-post-release-health`) | [operational-analytics.md](analytics/operational-analytics.md) | [admin-screen-inventory.md §4](../contracts/admin-screen-inventory.md) |

---

## 9. Queue, logs, support triage

| Workflow / route | Primary | Secondary |
|------------------|---------|-----------|
| Queue & Logs — all tabs (`aio-page-builder-queue-logs`) | [admin-operator-guide.md §9](../guides/admin-operator-guide.md); [support-triage-guide.md §1–§2](../guides/support-triage-guide.md) | `Logs_Monitoring_State_Builder`; `Reporting_Health_Summary_Builder` |
| Support Triage (`aio-page-builder-support-triage`) | [support-triage-guide.md](../guides/support-triage-guide.md) | `Support_Triage_State_Builder` |

---

## 10. Privacy, reporting, uninstall choices

| Workflow / route | Primary | Secondary |
|------------------|---------|-----------|
| Privacy, Reporting & Settings (`aio-page-builder-privacy-reporting`) | [admin-operator-guide.md §10](../guides/admin-operator-guide.md) | [REPORTING_EXCEPTION.md](../standards/REPORTING_EXCEPTION.md); `Privacy_Settings_State_Builder` |
| Portability and uninstall | [PORTABILITY_AND_UNINSTALL.md](../standards/PORTABILITY_AND_UNINSTALL.md) | [admin-operator-guide.md §10](../guides/admin-operator-guide.md) |

---

## 11. Import, export, restore

| Workflow / route | Primary | Secondary |
|------------------|---------|-----------|
| Import / Export (`aio-page-builder-export-restore`) | [admin-operator-guide.md §11](../guides/admin-operator-guide.md); [support-triage-guide.md §3](../guides/support-triage-guide.md) (support bundle) | [export-bundle-structure-contract.md](../contracts/export-bundle-structure-contract.md); `Import_Export_State_Builder` |

---

## 12. Industry Pack subsystem (admin menus)

| Workflow / route | Primary | Secondary |
|------------------|---------|-----------|
| Industry Profile (`aio-page-builder-industry-profile`) — includes starter bundle assistant UI | [industry-admin-workflows.md](industry/industry-admin-workflows.md) | [admin-screen-inventory.md §2.1](../contracts/admin-screen-inventory.md); [industry-support-training-packet.md](../operations/industry-support-training-packet.md) |
| Industry Overrides (`aio-page-builder-industry-overrides`) | [industry-admin-workflows.md](industry/industry-admin-workflows.md) | — |
| Industry Author Dashboard (`aio-page-builder-industry-author-dashboard`) | [industry-admin-workflows.md](industry/industry-admin-workflows.md) | [industry-operator-curriculum.md](../operations/industry-operator-curriculum.md) |
| Industry Health / Stale / Pack family / Future readiness / Subtype readiness / Maturity delta / Drift / Scaffold promotion / Guided Repair / Comparisons / Bundle Import / Style Preset / Style layer comparison (all `aio-page-builder-industry-*` slugs) | [industry-admin-workflows.md](industry/industry-admin-workflows.md) | Relevant contracts under `docs/contracts/industry-*.md` |

---

## 13. Global styling (site-wide)

| Workflow / route | Primary | Secondary |
|------------------|---------|-----------|
| Global Style Tokens (`aio-page-builder-global-style-tokens`) | [global-styling.md](operator/global-styling.md) | [styling-release-gate.md](../release/styling-release-gate.md) (release context) |
| Global Component Overrides (`aio-page-builder-global-component-overrides`) | [global-styling.md](operator/global-styling.md) | — |
| Per-template styling (detail screens) | [template-library-operator-guide.md](../guides/template-library-operator-guide.md) | `Entity_Style_UI_State_Builder` |

---

## 14. Embedded controllers and assistants (no separate menu)

These appear as **panels, filters, or inline flows** on other screens:

| Surface | Primary | Secondary |
|---------|---------|-----------|
| Industry filters / badges on template directories | [template-library-operator-guide.md](../guides/template-library-operator-guide.md); [industry-admin-workflows.md](industry/industry-admin-workflows.md) | Section/Page filter controllers |
| Industry composition assistant (compositions) | [template-library-operator-guide.md](../guides/template-library-operator-guide.md) | `Industry_Composition_Assistant` |
| Create page assistant (page template context) | [template-library-operator-guide.md](../guides/template-library-operator-guide.md) | `Industry_Create_Page_Assistant` |
| Industry pack toggle (where surfaced) | [industry-admin-workflows.md](industry/industry-admin-workflows.md) | `Industry_Pack_Toggle_Controller` |

---

## 15. Private distribution, release, and risk (not in-plugin UI)

| Topic | Primary | Secondary |
|-------|---------|-----------|
| Handoff to customers | [private-distribution-handoff.md](../release/private-distribution-handoff.md) | [known-risk-register.md](../release/known-risk-register.md) |
| Release notes / changelog | [changelog.md](../release/changelog.md); [release-notes-rc1.md](../release/release-notes-rc1.md) | [release-review-packet.md](../release/release-review-packet.md) |

---

## 16. ACF preservation and form provider onboarding

| Topic | Primary | Secondary |
|-------|---------|-----------|
| ACF uninstall preservation | [acf-uninstall-preservation-operator-guide.md](../guides/acf-uninstall-preservation-operator-guide.md) | [admin-operator-guide.md §13](../guides/admin-operator-guide.md) |
| Form provider onboarding | [form-provider-operator-guide.md](../guides/form-provider-operator-guide.md) | [form-provider-onboarding-checklist.md](../operations/form-provider-onboarding-checklist.md) |
