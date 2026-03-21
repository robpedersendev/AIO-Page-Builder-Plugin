# AIO Page Builder — Knowledge Base (Index)

**Product:** Privately distributed WordPress plugin — AIO Page Builder.  
**Spec:** [aio-page-builder-master-spec.md](../specs/aio-page-builder-master-spec.md).  
**How to write KB articles:** [WRITING_STANDARD.md](WRITING_STANDARD.md).  
**Workflow → doc routing:** [FILE_MAP.md](FILE_MAP.md).

This index separates **end-user**, **operator/admin**, and **support** entry points, then groups topics by taxonomy. It does not duplicate procedures; it links to the single canonical page per workflow.

---

## Audiences

| Audience | Start here | Typical next steps |
|----------|------------|--------------------|
| **End users** (editors running onboarding / plan review) | [end-user-workflow-guide.md](../guides/end-user-workflow-guide.md) | [admin-operator-guide.md](../guides/admin-operator-guide.md) for capability context; [FILE_MAP.md](FILE_MAP.md) |
| **Operators / site admins** | [admin-operator-guide.md](../guides/admin-operator-guide.md) | [template-library-operator-guide.md](../guides/template-library-operator-guide.md); [form-provider-operator-guide.md](../guides/form-provider-operator-guide.md); KB stubs under `operator/`, `industry/`, `analytics/` |
| **Support & diagnostics** | [support-triage-guide.md](../guides/support-triage-guide.md) | [template-library-support-guide.md](../guides/template-library-support-guide.md); [FILE_MAP.md](FILE_MAP.md) §9–§11 |

---

## Topic taxonomy

### Getting Started

- [admin-operator-guide.md §1–§2](../guides/admin-operator-guide.md) — Menu overview, settings, onboarding.
- [end-user-workflow-guide.md](../guides/end-user-workflow-guide.md) — Editor onboarding and Build Plan review.
- [settings-registry-maintenance.md](operator/settings-registry-maintenance.md) — Registry seed actions on Settings (stub).

### AI & Providers

- [admin-operator-guide.md §3, §5](../guides/admin-operator-guide.md) — Providers and AI Runs.
- [advanced-ai-labs.md](operator/advanced-ai-labs.md) — Profile History, Prompt Experiments (stub).

### Crawl & Discovery

- [admin-operator-guide.md §4](../guides/admin-operator-guide.md) — Crawl Sessions and Crawl Comparison.

### Templates

- [template-library-operator-guide.md](../guides/template-library-operator-guide.md) — Directories, detail, compare, compositions.
- [template-library-editor-guide.md](../guides/template-library-editor-guide.md) — Choosing templates, one-pagers, helper docs.
- [template-library-support-guide.md](../guides/template-library-support-guide.md) — Support-focused template diagnostics.

### Build Plans

- [admin-operator-guide.md §6–§7](../guides/admin-operator-guide.md); [end-user-workflow-guide.md §2](../guides/end-user-workflow-guide.md).

### Execution & Rollback

- [admin-operator-guide.md §7–§8](../guides/admin-operator-guide.md); [end-user-workflow-guide.md §3](../guides/end-user-workflow-guide.md).

### Import / Export / Restore

- [admin-operator-guide.md §11](../guides/admin-operator-guide.md); [support-triage-guide.md §3](../guides/support-triage-guide.md) (support bundle).
- [export-bundle-structure-contract.md](../contracts/export-bundle-structure-contract.md) — Bundle shape (implementer reference).

### Analytics / Diagnostics / Logs

- [operational-analytics.md](analytics/operational-analytics.md) — Build Plan Analytics, Template Analytics, Post-Release Health (stub).
- [diagnostics-screens.md](operator/diagnostics-screens.md) — Diagnostics, ACF Architecture, Form Provider Health (stub).
- [admin-operator-guide.md §9](../guides/admin-operator-guide.md); [support-triage-guide.md §1–§2](../guides/support-triage-guide.md) — Queue & Logs, export.

### Support / Troubleshooting / FAQ

- [support-triage-guide.md](../guides/support-triage-guide.md) — Primary support runbook.
- [industry-support-training-packet.md](../operations/industry-support-training-packet.md) — Industry-specific escalation (internal).
- [known-risk-register.md](../release/known-risk-register.md) — Known product risks at release.

### Industry Pack subsystem

- [industry-admin-workflows.md](industry/industry-admin-workflows.md) — All Industry submenu screens (stub).
- [industry-operator-curriculum.md](../operations/industry-operator-curriculum.md) — Training path (internal).

### Styling (global)

- [global-styling.md](operator/global-styling.md) — Global Style Tokens and Global Component Overrides (stub); cross-links template detail styling.

### Privacy, reporting, uninstall

- [admin-operator-guide.md §10](../guides/admin-operator-guide.md); [REPORTING_EXCEPTION.md](../standards/REPORTING_EXCEPTION.md); [PORTABILITY_AND_UNINSTALL.md](../standards/PORTABILITY_AND_UNINSTALL.md).

### Private distribution & release documentation

- [private-distribution-handoff.md](../release/private-distribution-handoff.md); [changelog.md](../release/changelog.md).

---

## State builders and UI state (implementer reference)

Labels and table columns on many screens are driven by dedicated builders. Use these when aligning copy between UI and docs:

| Area | Representative builders |
|------|-------------------------|
| Dashboard | `Dashboard_State_Builder` |
| Onboarding | `Onboarding_UI_State_Builder` |
| AI providers | `AI_Providers_UI_State_Builder` |
| Page / section directories & detail | `Page_Template_Directory_State_Builder`, `Section_Template_Directory_State_Builder`, `Page_Template_Detail_State_Builder`, `Section_Template_Detail_State_Builder` |
| Compare / compositions | `Template_Compare_State_Builder`, `Composition_Builder_State_Builder` |
| Build Plan workspace | `Build_Plan_UI_State_Builder` |
| Rollback | `Rollback_State_Builder` |
| Import / Export | `Import_Export_State_Builder` |
| Logs / triage / post-release / privacy settings | `Logs_Monitoring_State_Builder`, `Reporting_Health_Summary_Builder`, `Support_Triage_State_Builder`, `Post_Release_Health_State_Builder`, `Privacy_Settings_State_Builder` |
| ACF diagnostics | `ACF_Diagnostics_State_Builder` |
| Per-entity styling | `Entity_Style_UI_State_Builder`, `Form_Section_Field_State_Builder` |

---

## Admin screen inventory (contract)

Authoritative slug and capability mapping (partial table — extended rows in source and [FILE_MAP.md](FILE_MAP.md)): [admin-screen-inventory.md](../contracts/admin-screen-inventory.md).
