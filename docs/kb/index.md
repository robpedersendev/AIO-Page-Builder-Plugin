# AIO Page Builder — Knowledge Base (Index)

**Product:** Privately distributed WordPress plugin — AIO Page Builder.  
**Spec:** [aio-page-builder-master-spec.md](../specs/aio-page-builder-master-spec.md).  
**How to write KB articles:** [WRITING_STANDARD.md](WRITING_STANDARD.md).  
**Workflow → doc routing:** [FILE_MAP.md](FILE_MAP.md).  
**Vocabulary and permissions (read this first):** [concepts-and-glossary.md](concepts-and-glossary.md).

This index separates **end-user**, **operator/admin**, and **support** entry points, then groups topics by taxonomy. It does not duplicate procedures; it links to the single canonical page per workflow.

---

## Audiences

| Audience | Start here | Typical next steps |
|----------|------------|--------------------|
| **End users** (editors running onboarding / plan review) | [end-user-workflow-guide.md](../guides/end-user-workflow-guide.md) | [admin-operator-guide.md](../guides/admin-operator-guide.md) for capability context; [FILE_MAP.md](FILE_MAP.md) |
| **Operators / site admins** | [admin-operator-guide.md](../guides/admin-operator-guide.md) | [template-library-operator-guide.md](../guides/template-library-operator-guide.md); [form-provider-operator-guide.md](../guides/form-provider-operator-guide.md); KB stubs under `operator/`, `industry/`, `analytics/` |
| **Support & diagnostics** | [support-triage-guide.md](../guides/support-triage-guide.md); [master-faq.md](master-faq.md) | [template-library-support-guide.md](../guides/template-library-support-guide.md); [FILE_MAP.md](FILE_MAP.md) §9–§11 |

---

## Topic taxonomy

### Getting Started

- [concepts-and-glossary.md](concepts-and-glossary.md) — Terms, default permissions, UI map, guide picker, FAQ.
- [onboarding-and-profile.md](operator/onboarding-and-profile.md) — Full onboarding/profile workflow (drafts, prefill, submission).
- [admin-operator-guide.md §1–§2](../guides/admin-operator-guide.md) — Menu overview, settings, onboarding summary.
- [end-user-workflow-guide.md](../guides/end-user-workflow-guide.md) — Editor onboarding and Build Plan review.
- [settings-registry-maintenance.md](operator/settings-registry-maintenance.md) — Registry seed actions on Settings (stub).

### AI & Providers

- [onboarding-and-profile.md](operator/onboarding-and-profile.md) — Onboarding through **Request AI plan** and profile storage.
- [ai-providers-credentials-budget.md](operator/ai-providers-credentials-budget.md) — AI Providers: credentials, connection tests, spend caps, month-to-date behavior.
- [ai-runs-and-run-details.md](operator/ai-runs-and-run-details.md) — AI Runs list, spend summary, run detail, token/cost fields.
- [admin-operator-guide.md §3, §5](../guides/admin-operator-guide.md) — Short operator summary for Providers and AI Runs.
- [profile-snapshots-and-history.md](operator/profile-snapshots-and-history.md) — Profile History: snapshots, diff, restore, export/import, audit, vs rollback.
- [advanced-ai-labs.md](operator/advanced-ai-labs.md) — Prompt Experiments (stub); links profile snapshot guide.

### Crawl & Discovery

- [crawler-sessions-and-comparison.md](operator/crawler-sessions-and-comparison.md) — Sessions, start/retry, comparison, freshness vs onboarding.
- [admin-operator-guide.md §4](../guides/admin-operator-guide.md) — Short operator summary for Crawl Sessions and Crawl Comparison.

### Templates

- [template-system-overview.md](templates/template-system-overview.md) — Umbrella: sections vs page templates vs compositions vs helper docs and previews (**start here**).
- [section-templates-deep-dive.md](templates/section-templates-deep-dive.md) — Section templates: browse, detail, metadata, previews, helper docs, industry fit, edge cases.
- [page-templates-deep-dive.md](templates/page-templates-deep-dive.md) — Page templates: browse, detail, used sections, one-pager, previews, vs compositions.
- [compositions-deep-dive.md](templates/compositions-deep-dive.md) — Compositions: list, builder, CTA validation, readiness badges, relationship to page templates.
- [template-library-operator-guide.md](../guides/template-library-operator-guide.md) — Directories, detail, compare, compositions.
- [template-library-editor-guide.md](../guides/template-library-editor-guide.md) — Choosing templates, one-pagers, helper docs.
- [template-library-support-guide.md](../guides/template-library-support-guide.md) — Support-focused template diagnostics.

### Build Plans

- [build-plan-overview.md](operator/build-plan-overview.md) — **Start here:** list, workspace, steps 1–9 (default labels), review vs execution, safety, item status, FAQ. Step stubs: nine `build-plan-step-*.md` files (linked from the overview).
- [build-plan-review-existing-and-new-pages.md](operator/build-plan-review-existing-and-new-pages.md) — **Existing page changes** & **New pages** (`step=1` / `step=2`): approve, deny, bulk bars, edge cases.
- [build-plan-hierarchy-navigation-tokens-seo.md](operator/build-plan-hierarchy-navigation-tokens-seo.md) — **Hierarchy, navigation, design tokens, SEO** (`step=3`–`6`): executable vs advisory, tokens, SEO v1 posture, troubleshooting.
- [build-plan-finalization-logs-rollback.md](operator/build-plan-finalization-logs-rollback.md) — **Confirm** & **Logs & rollback** (`step=7`–`8`): finalization, conflicts, rollback, JSON export.
- [build-plan-execution-actions.md](operator/build-plan-execution-actions.md) — **Execution action types** (`create_page`, `replace_page`, menus, tokens, hierarchy, finalize, rollback): prerequisites, snapshot/rollback, edge cases.
- [build-plan-rollback-and-recovery.md](operator/build-plan-rollback-and-recovery.md) — **Rollback vs retry**, queue recovery, partial failures, escalation, what not to do.
- [admin-operator-guide.md §6–§7](../guides/admin-operator-guide.md); [end-user-workflow-guide.md §2–§3](../guides/end-user-workflow-guide.md).

### Execution & Rollback

- [build-plan-execution-actions.md](operator/build-plan-execution-actions.md) — Per-action behavior, risks, retry vs rollback.
- [build-plan-rollback-and-recovery.md](operator/build-plan-rollback-and-recovery.md) — Safe recovery playbook, Queue tab retry, eligibility limits.
- [admin-operator-guide.md §7–§8](../guides/admin-operator-guide.md); [end-user-workflow-guide.md §3](../guides/end-user-workflow-guide.md).

### Import / Export / Restore

- [import-export-and-restore.md](operator/import-export-and-restore.md) — **Operator workflow:** export modes, preview/validate, restore scope, conflicts, ZIP rules, uninstall/deactivation vs built pages, step-by-step, edge cases, FAQ.
- [admin-operator-guide.md §11](../guides/admin-operator-guide.md); [support-triage-guide.md §3](../guides/support-triage-guide.md) (support bundle).
- [export-bundle-structure-contract.md](../contracts/export-bundle-structure-contract.md) — Bundle shape (implementer reference).

### Analytics / Diagnostics / Logs

- [monitoring-analytics-and-reporting.md](operator/monitoring-analytics-and-reporting.md) — **Operator guide:** analytics screens, Diagnostics, Queue & Logs (tabs, limits, placeholders), reporting health, log export, Privacy/Reporting disclosure, troubleshooting; avoids overclaiming (e.g. Import/Export Logs tab, import/export failure aggregation).
- [operational-analytics.md](analytics/operational-analytics.md) — FILE_MAP anchor for analytics routes (links to guide above).
- [diagnostics-screens.md](operator/diagnostics-screens.md) — FILE_MAP anchor for diagnostics routes (links to guide above).
- [admin-operator-guide.md §9](../guides/admin-operator-guide.md); [support-triage-guide.md §1–§2](../guides/support-triage-guide.md) — Short summary; triage runbook.

### Support / Troubleshooting / FAQ

- [support-triage-guide.md](../guides/support-triage-guide.md) — **Primary runbook:** symptom router, logs, exports, redaction, escalation, weird edge cases.
- [master-faq.md](master-faq.md) — Short cross-cutting FAQ with KB deep links.
- [build-plan-rollback-and-recovery.md](operator/build-plan-rollback-and-recovery.md) — Build Plan queue retry, rollback eligibility, partial failures (with triage links).
- [industry-support-training-packet.md](../operations/industry-support-training-packet.md) — Industry-specific escalation (internal).
- [known-risk-register.md](../release/known-risk-register.md) — Known product risks at release.

### Industry Pack subsystem

- [industry-bundle-import-and-apply.md](industry/industry-bundle-import-and-apply.md) — JSON bundle preview, hash conflicts, replace/skip, scopes, safe apply, vs Import/Export ZIP.
- [industry-admin-workflows.md](industry/industry-admin-workflows.md) — All Industry submenu screens (stub); links bundle apply guide.
- [industry-operator-curriculum.md](../operations/industry-operator-curriculum.md) — Training path (internal).

### Styling (global)

- [global-styling.md](operator/global-styling.md) — Global Style Tokens and Global Component Overrides (stub); cross-links template detail styling.

### Privacy, reporting, uninstall

- [monitoring-analytics-and-reporting.md](operator/monitoring-analytics-and-reporting.md) — Reporting disclosure blocks, retention copy, privacy helper, report destination summary (aligned with `Privacy_Settings_State_Builder`).
- [admin-operator-guide.md §10](../guides/admin-operator-guide.md); [REPORTING_EXCEPTION.md](../standards/REPORTING_EXCEPTION.md); [PORTABILITY_AND_UNINSTALL.md](../standards/PORTABILITY_AND_UNINSTALL.md).

### Private distribution & release documentation

- [private-distribution-handoff.md](../release/private-distribution-handoff.md); [changelog.md](../release/changelog.md).

---

## State builders and UI state (implementer reference)

Labels and table columns on many screens are driven by dedicated builders. Use these when aligning copy between UI and docs:

| Area | Representative builders |
|------|-------------------------|
| Template system (umbrella doc) | [template-system-overview.md](templates/template-system-overview.md) |
| Dashboard | `Dashboard_State_Builder` |
| Onboarding | `Onboarding_UI_State_Builder` |
| AI providers | `AI_Providers_UI_State_Builder` |
| Page / section directories & detail | `Page_Template_Directory_State_Builder`, `Section_Template_Directory_State_Builder`, `Page_Template_Detail_State_Builder`, `Section_Template_Detail_State_Builder` |
| Compare / compositions | `Template_Compare_State_Builder`, `Composition_Builder_State_Builder` |
| Build Plan workspace | [build-plan-overview.md](operator/build-plan-overview.md); `Build_Plan_UI_State_Builder` |
| Rollback | `Rollback_State_Builder` |
| Import / Export | `Import_Export_State_Builder` |
| Logs / triage / post-release / privacy settings | `Logs_Monitoring_State_Builder`, `Reporting_Health_Summary_Builder`, `Support_Triage_State_Builder`, `Post_Release_Health_State_Builder`, `Privacy_Settings_State_Builder` |
| ACF diagnostics | `ACF_Diagnostics_State_Builder` |
| Per-entity styling | `Entity_Style_UI_State_Builder`, `Form_Section_Field_State_Builder` |

---

## Admin screen inventory (contract)

Authoritative slug and capability mapping (partial table — extended rows in source and [FILE_MAP.md](FILE_MAP.md)): [admin-screen-inventory.md](../contracts/admin-screen-inventory.md).
