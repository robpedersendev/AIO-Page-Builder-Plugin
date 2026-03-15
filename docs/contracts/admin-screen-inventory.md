# Admin Screen Inventory

**Document type:** Contract for admin menu and screen routing (spec §49, §49.11).  
**Governs:** Submenu slugs, screen classes, capability mapping, and Queue & Logs tabs.  
**Guidance:** For operator and support workflows, see [admin-operator-guide.md](../guides/admin-operator-guide.md) and [support-triage-guide.md](../guides/support-triage-guide.md).

---

## 1. Top-level menu

- **Slug:** `aio-page-builder`
- **Label:** AIO Page Builder

---

## 2. Submenu and screen mapping

| Submenu label           | Screen class / slug                         | Capability              |
|-------------------------|---------------------------------------------|-------------------------|
| Dashboard               | `Dashboard_Screen` / `aio-page-builder`    | (menu default)          |
| Settings                | `Settings_Screen` / `aio-page-builder-settings` | `aio_manage_settings`   |
| Diagnostics             | `Diagnostics_Screen` / `aio-page-builder-diagnostics` | `manage_options`        |
| ACF Field Architecture  | `ACF_Architecture_Diagnostics_Screen` / `aio-page-builder-acf-diagnostics` | `aio_view_logs` |
| Form Provider Health    | `Form_Provider_Health_Screen` / `aio-page-builder-form-provider-health` | `aio_view_logs` |
| Onboarding & Profile    | `Onboarding_Screen` / `aio-page-builder-onboarding` | `aio_run_onboarding`    |
| Crawl Sessions          | `Crawler_Sessions_Screen` / slug            | (crawler cap)           |
| Crawl Comparison        | `Crawler_Comparison_Screen` / slug          | (crawler cap)           |
| AI Runs                 | `AI_Runs_Screen` / `aio-page-builder-ai-runs` | `aio_view_ai_runs`      |
| AI Providers            | `AI_Providers_Screen` / `aio-page-builder-ai-providers` | `aio_manage_ai_providers` |
| Prompt Experiments      | `Prompt_Experiments_Screen` / `aio-page-builder-prompt-experiments` | `aio_manage_ai_providers` |
| Build Plans             | `Build_Plans_Screen` / `aio-page-builder-build-plans` | `aio_view_build_plans`  |
| Build Plan Analytics   | `Build_Plan_Analytics_Screen` / `aio-page-builder-build-plan-analytics` | `aio_view_build_plans`  |
| **Page Templates**     | `Page_Templates_Directory_Screen` / `aio-page-builder-page-templates`   | `aio_view_build_plans` |
| **Section Templates**  | `Section_Templates_Directory_Screen` / `aio-page-builder-section-templates` | `aio_view_build_plans` |
| **Compositions**       | `Compositions_Screen` / `aio-page-builder-compositions` | `aio_view_build_plans` |
| **Queue & Logs**        | `Queue_Logs_Screen` / `aio-page-builder-queue-logs` | `aio_view_logs`         |
| **Support Triage**     | `Support_Triage_Dashboard_Screen` / `aio-page-builder-support-triage` | `aio_view_logs`         |
| **Post-Release Health**| `Post_Release_Health_Screen` / `aio-page-builder-post-release-health` | `aio_view_logs`         |
| **Industry Profile**   | `Industry_Profile_Settings_Screen` / `aio-page-builder-industry-profile` | `aio_manage_settings`   |

### 2.1 Industry Profile settings (industry-admin-screen-contract)

The **Industry Profile** screen (`Industry_Profile_Settings_Screen` / `aio-page-builder-industry-profile`) lets admins view and edit the site Industry Profile: primary and secondary industry selection, readiness/completeness status, active pack reference and warnings. Save action: `admin_post_aio_save_industry_profile`. Nonce: `aio_industry_profile_nonce`; action `aio_save_industry_profile`. Capability: `aio_manage_settings`. Handled by `Admin_Menu::handle_save_industry_profile()`; uses `Industry_Profile_Repository::merge_profile()` and `Industry_Profile_Validator`. Section and page template directories may show industry-aware filters and badges per industry-admin-screen-contract.

### 2.2 Form provider seed (Settings; form-provider-integration-contract)

The **Settings** screen (`Settings_Screen` / `aio-page-builder-settings`) hosts a **Seed form section and request page template** action that writes the form section template (`form_section_ndr`) and request page template (`pt_request_form`) to the section and page template registries. Action: `admin_post_aio_seed_form_templates`. Nonce: `aio_seed_form_templates`. Capability: `manage_options`. Handled by `Admin_Menu::handle_seed_form_templates()`; uses `Form_Template_Seeder::run()`. Form-bearing section and page templates appear in the section and page template directories like any other template; no separate form-provider admin screen.

### 2.3 Page template directory and taxonomy (spec §49.3, §62.10; Prompt 133)

When admin screens list or browse **page templates** (e.g. registry, template picker, or Build Plan template selection), **directory and browse grouping** must follow **page-template-category-taxonomy-contract.md**. Grouping and filtering use stable registry metadata: `template_category_class` (top_level, hub, nested_hub, child_detail), `template_family` (e.g. home, services, locations), and `hierarchy_role`. Preview grouping aligns with the same taxonomy so that “Services”, “Locations”, etc. map to contract-defined family slugs. Category labels are server-authoritative, not ad-hoc UI strings.

### 2.4 Page template directory IA (spec §49.7; Prompt 141)

The **page template directory** is a dedicated browse experience for page templates. Its **information architecture** (hierarchical tree, breadcrumbs, category/family filters, list/detail, one-pager/preview links) is defined in **page-template-directory-ia-extension.md**. Tree structure: **Page Templates** (root) → **Category class** (Top Level, Hub, Nested Hub, Child/Detail) → **Family** (e.g. Home Page Templates, Services Page Templates) → **Template option list** → **Template detail**. Screen slug: `aio-page-builder-page-templates` (or as specified in that contract). Directory is capability-gated; preview uses preview-safe data only. Build Plan template selection may deep-link into the directory; directory does not replace Build Plan or composition workflows. State is built by `Page_Template_Directory_State_Builder`.

**Example page-template directory state payload** (list view, one row):

```json
{
  "view": "list",
  "breadcrumbs": [
    { "label": "Page Templates", "url": "https://example.com/wp-admin/admin.php?page=aio-page-builder-page-templates" },
    { "label": "Top Level Page Templates", "url": "...&category_class=top_level" },
    { "label": "Home Page Templates", "url": "" }
  ],
  "tree": [
    { "slug": "top_level", "label": "Top Level Page Templates", "count": 12, "url": "..." },
    { "slug": "hub", "label": "Hub Page Templates", "count": 8, "url": "..." },
    { "slug": "nested_hub", "label": "Nested Hub Page Templates", "count": 6, "url": "..." },
    { "slug": "child_detail", "label": "Child/Detail Page Templates", "count": 20, "url": "..." }
  ],
  "families": [],
  "list_result": {
    "rows": [
      {
        "internal_key": "pt_home_landing",
        "name": "Home Landing",
        "status": "stable",
        "template_category_class": "top_level",
        "template_family": "home",
        "section_count": 7,
        "version": "1",
        "preview_available": false
      }
    ],
    "pagination": { "page": 1, "per_page": 25, "total": 1, "total_pages": 1, "offset": 0 },
    "total_matching": 1
  },
  "filters": { "category_class": "top_level", "family": "home", "status": "", "search": "", "paged": 1, "per_page": 25 },
  "base_url": "https://example.com/wp-admin/admin.php?page=aio-page-builder-page-templates",
  "can_manage_templates": true,
  "category_labels": {
    "top_level": "Top Level Page Templates",
    "hub": "Hub Page Templates",
    "nested_hub": "Nested Hub Page Templates",
    "child_detail": "Child/Detail Page Templates"
  }
}
```

When admin screens list or browse **section templates**, **directory and browse grouping** must follow **section-template-category-taxonomy-contract.md**. Grouping and filtering use stable registry metadata: `section_purpose_family` (e.g. hero, proof, cta, legal), `placement_tendency` (opener, mid_page, cta_ending, legal_footer_adjacent), and `cta_classification`. Section preview grouping aligns with the same taxonomy. All taxonomy values are validated and deterministic.

### 2.5 Compositions screen (spec §14, §49.6; Prompt 177)

The **Compositions** screen lists governed custom compositions and provides a **composition builder** view for large-library assembly. Views: **list** (compositions table; Build composition link) and **build** (filtered section library, current ordered sections, CTA count/proximity warnings, insertion hint, preview and one-pager readiness). State is built by `Composition_Builder_State_Builder`; filter state by `Composition_Filter_State`. Screen slug: `aio-page-builder-compositions`. Capability: `aio_view_build_plans`. No freeform drag-and-drop; section ordering and CTA rules remain governed. Mutation (create/update composition) is server-validated and out of scope for this screen’s initial implementation; builder shows state and guidance only.

**Example composition-builder state payload** (build view, one composition with two sections):

```json
{
  "filter_state": { "purpose_family": "hero", "category": "", "cta_classification": "", "variation_family_key": "", "search": "", "status": "", "paged": 1, "per_page": 25 },
  "section_result": { "rows": [ { "internal_key": "st_hero", "name": "Section st_hero", "category": "hero_intro", "section_purpose_family": "hero", "cta_classification": "", "status": "active" } ], "pagination": { "page": 1, "per_page": 25, "total": 2, "total_pages": 1, "offset": 0 }, "total_matching": 2 },
  "ordered_sections_display": [ { "section_key": "st_hero", "name": "Section st_hero", "position": 0, "cta_classification": "none", "is_cta": false }, { "section_key": "st_cta", "name": "Section st_cta", "position": 1, "cta_classification": "primary_cta", "is_cta": true } ],
  "cta_warnings": [],
  "insertion_hint": "Next section should be non-CTA to avoid adjacent CTAs. Add content (proof, feature, FAQ, etc.) then another CTA later.",
  "validation_status": "valid",
  "validation_codes": [],
  "preview_readiness": true,
  "one_pager_ready": true,
  "base_url": "https://example.com/wp-admin/admin.php?page=aio-page-builder-compositions",
  "category_labels": { "hero_intro": "Hero / Intro", "trust_proof": "Trust / Proof", "cta_conversion": "CTA", "faq": "FAQ", "legal_disclaimer": "Legal" },
  "cta_labels": { "primary_cta": "Primary CTA", "contact_cta": "Contact CTA", "navigation_cta": "Navigation CTA", "none": "None" }
}
```

### 2.5 Section template directory IA (spec §49.6; Prompt 142)

The **section template directory** is a dedicated browse experience for section templates. Its **information architecture** (hierarchical tree by purpose family, CTA/variant grouping, breadcrumbs, list/detail, helper-doc and field blueprint links, preview) is defined in **section-template-directory-ia-extension.md**. Tree structure: **Section Templates** (root) → **Purpose family** (Hero, Proof, CTA, FAQ, etc.) → **CTA classification** (for cta/contact) or **Variant family** (e.g. Hero primary) → **Section option list** → **Section detail**. Screen slug: `aio-page-builder-section-templates`. Directory is capability-gated; preview uses preview-safe data only. Build Plan and composition section pickers may deep-link into the directory. Section directory does not replace Build Plan or composition workflows and emphasizes purpose-family, CTA classification, and variant families for section reuse. State is built by `Section_Template_Directory_State_Builder`.

**Example section-template directory state payload** (list view, one row):

```json
{
  "view": "list",
  "breadcrumbs": [
    { "label": "Section Templates", "url": "https://example.com/wp-admin/admin.php?page=aio-page-builder-section-templates" },
    { "label": "Hero", "url": "...&purpose_family=hero" },
    { "label": "Hero primary", "url": "" }
  ],
  "tree": [
    { "slug": "hero", "label": "Hero", "count": 24, "url": "..." },
    { "slug": "proof", "label": "Proof", "count": 18, "url": "..." },
    { "slug": "cta", "label": "Cta", "count": 32, "url": "..." }
  ],
  "l3_nodes": [],
  "list_result": {
    "rows": [
      {
        "internal_key": "st_hero_01",
        "name": "Hero with CTA",
        "status": "active",
        "category": "hero_intro",
        "section_purpose_family": "hero",
        "cta_classification": "",
        "variation_family_key": "hero_primary",
        "placement_tendency": "opener",
        "helper_ref": "hero_helper",
        "field_blueprint_ref": "acf_hero",
        "preview_available": true,
        "version": "1",
        "variant_count": 2
      }
    ],
    "pagination": { "page": 1, "per_page": 25, "total": 1, "total_pages": 1, "offset": 0 },
    "total_matching": 1
  },
  "filters": { "purpose_family": "hero", "cta_classification": "", "variation_family_key": "hero_primary", "all": false, "status": "", "search": "", "paged": 1, "per_page": 25 },
  "base_url": "https://example.com/wp-admin/admin.php?page=aio-page-builder-section-templates",
  "can_manage_templates": true,
  "purpose_labels": { "hero": "Hero", "proof": "Proof", "cta": "Cta" },
  "cta_labels": { "primary_cta": "Primary CTA", "contact_cta": "Contact CTA", "navigation_cta": "Navigation CTA", "none": "None" }
}
```

### 2.6.1 Section and page template detail screens — per-entity styling (Prompt 253)

The **Section Template Detail** screen (`Section_Template_Detail_Screen` / `aio-page-builder-section-template-detail`) and **Page Template Detail** screen (`Page_Template_Detail_Screen` / `aio-page-builder-page-template-detail`) each include a **Per-entity styling** panel when viewing a single section or page template. The panel is built by `Entity_Style_UI_State_Builder` and `Entity_Style_Form_Builder`; save is capability- and nonce-gated and passes through the styles_json sanitization pipeline. No freeform CSS; only approved token and component override fields from the style specs. Validation errors are shown when save fails.

---

## 2.6 Form Provider Health screen (Prompt 239, spec §0.10.11, §49.11, §59.12)

- **Slug:** `aio-page-builder-form-provider-health`
- **Capability:** `aio_view_logs`
- **Purpose:** Internal provider dependency health: provider availability, section/page template counts using provider-backed forms, provider-related attention items, links to Section/Page Template directories. Observational only; no secrets; for operators and support.
- **State:** Built by `Form_Provider_Health_Summary_Service` (provider_availability, registered_provider_ids, section_templates_with_forms_count, page_templates_using_forms_count, recent_failures_summary, built_at). Support bundles may include `form_provider_health_summary` via Template_Library_Support_Summary_Builder when the service is available.
- **Redaction:** Bounded; no secrets or raw provider config.

---

## 3. Support Triage screen (spec §49.11, §59.12)

- **Slug:** `aio-page-builder-support-triage`
- **Capability:** `aio_view_logs`
- **Purpose:** Aggregate support view: critical issues, degraded systems, recent failed workflows, plans needing attention, rollback candidates, import/export context, recommended links. No mutation; deep links to Queue & Logs, Build Plans, AI Runs, Import/Export.
- **State:** Built by `Support_Triage_State_Builder` (critical_issues, degraded_systems, recent_failed_workflows, rollback_candidates, import_export_failures, recommended_links, stale_plans). Optional filter by `domain` or `severity` (query args).
- **Redaction:** All data from existing redacted summaries; no secrets.

---

## 4. Post-Release Health screen (spec §45, §49.11, §59.15, §60.8; Prompt 131)

- **Slug:** `aio-page-builder-post-release-health`
- **Capability:** `aio_view_logs`
- **Purpose:** Internal post-release operational review: reporting health, queue backlog/failures, Build Plan approval/denial trends, AI run validity rates, rollback usage, import/export and support-package context. Observational only; no automatic product changes. Date-range filter; optional export of summary to JSON (support-safe). Deep links to Queue & Logs, Build Plan Analytics, Support Triage, AI Runs, Import/Export.
- **State:** Built by `Post_Release_Health_State_Builder` (post_release_health_summary, domain_health_scores, recommended_investigation_items). Uses existing structured records only.
- **Redaction:** No secrets or prohibited data in summaries; same rules as existing monitoring screens.

---

## 5. Build Plan Analytics screen (spec §30, §45, §49.11, §59.12; Prompt 129)

- **Slug:** `aio-page-builder-build-plan-analytics`
- **Capability:** `aio_view_build_plans`
- **Purpose:** Observational analytics over Build Plans: plan review trends (approval/denial rates), common blockers (rejected/failed by category), execution failure trends, rollback frequency summary. Read-only; no mutation. Date-range and plan-type filtering. Links back to Build Plans.
- **State:** Built by `Build_Plan_Analytics_Service` (plan_review_trends, common_blockers, execution_failure_trends, rollback_frequency_summary). Uses existing plan history as authority.
- **Redaction:** Summaries only; no raw secrets or sensitive payloads.

---

## 6. Queue & Logs screen (spec §49.11)

- **Slug:** `aio-page-builder-queue-logs`
- **Capability:** `aio_view_logs`
- **Tabs:** Queue, Execution Logs, AI Runs, Reporting Logs, Import/Export Logs, Critical Errors
- **State:** Built by `Logs_Monitoring_State_Builder` (queue, execution_logs, ai_runs, reporting_logs, import_export_logs, critical_errors)
- **Reporting health:** Built by `Reporting_Health_Summary_Builder` (recent_failures_count, last_heartbeat_month, reporting_degraded, summary_message)
- **Row-to-object:** Queue/Execution rows link to Build Plan when `related_plan_id` present; AI Runs tab links to AI Run detail; Reporting/Critical are read-only
- **Redaction:** All displayed data is from already-redacted or non-secret sources; no raw payloads or secrets in tables

---

## 7. Example payloads (monitoring state)

**Queue tab (one row):**
```json
{
  "job_ref": "job-uuid-1",
  "job_type": "replace_page",
  "queue_status": "completed",
  "created_at": "2025-03-15 10:00:00",
  "completed_at": "2025-03-15 10:01:00",
  "failure_reason": "",
  "related_plan_id": "plan-abc-123"
}
```

**Reporting health summary:**
```json
{
  "recent_failures_count": 0,
  "last_heartbeat_month": "2025-03",
  "reporting_degraded": false,
  "summary_message": "Reporting current. Heartbeat sent this month.",
  "failed_events_by_type": {}
}
```

**Support triage payload (excerpt):**
```json
{
  "critical_issues": [
    {
      "severity": "critical",
      "domain": "reporting",
      "title": "Critical error delivery failures",
      "message": "2 developer error report(s) failed to deliver.",
      "link_url": ".../admin.php?page=aio-page-builder-queue-logs&tab=critical",
      "link_label": "View critical errors"
    }
  ],
  "degraded_systems": [],
  "recent_failed_workflows": [],
  "rollback_candidates": [],
  "import_export_failures": [],
  "recommended_links": [
    { "label": "Queue & Logs", "url": "...", "description": "Queue health, execution logs, reporting logs." }
  ],
  "stale_plans": []
}
```

**Post-release health summary (excerpt):**
```json
{
  "post_release_health_summary": {
    "period_start": "2025-02-15",
    "period_end": "2025-03-15",
    "overall_status": "ok",
    "summary_message": "Operational health good across domains for the selected period."
  },
  "domain_health_scores": {
    "reporting": { "status": "ok", "score_label": "OK", "message": "Reporting current. Heartbeat sent this month.", "link_url": "...", "link_label": "Queue & Logs → Reporting" },
    "queue": { "status": "ok", "score_label": "OK", "message": "...", "link_url": "...", "link_label": "Queue & Logs" }
  },
  "recommended_investigation_items": []
}
```
