# Form Provider Health Dashboard Verification

**Governs:** Prompt 239, spec §0.10.11, §49.11, §59.12.  
**Purpose:** QA checklist for the Form Provider Health screen and support-bundle inclusion of provider health data.

---

## 1. Scope

- **In scope:** Form Provider Health screen (slug `aio-page-builder-form-provider-health`), Form_Provider_Health_Summary_Service, support bundle payload `form_provider_health_summary`, permission gating, bounded data.
- **Out of scope:** Provider management UI, configuration editing, public dashboard, secrets or raw provider config.

---

## 2. Verification checklist

### 2.1 Screen access and capability

- [ ] Menu shows **Form Provider Health** under AIO Page Builder (after ACF Field Architecture).
- [ ] Slug is `aio-page-builder-form-provider-health`; capability is `aio_view_logs`.
- [ ] Users without `aio_view_logs` do not see the submenu item and receive 403 when opening the URL directly.

### 2.2 Provider availability display

- [ ] **Provider availability** table shows one row per registered provider (e.g. ndr_forms) with columns: Provider, Status, Message.
- [ ] Status values shown are among: available, unavailable, no_forms, provider_error, cached_fallback.
- [ ] When availability service is not configured, a short message indicates no provider availability data.

### 2.3 Registered providers and usage counts

- [ ] **Registered providers** lists current provider IDs from the registry (e.g. ndr_forms).
- [ ] **Section templates (form_embed)** count is bounded and matches section templates with category form_embed (or 0 when repos unavailable).
- [ ] **Page templates using form sections** count is bounded and matches page templates that include at least one form_embed section (or 0 when validator/repos unavailable).

### 2.4 Provider-related attention and links

- [ ] When any provider has status provider_error or unavailable, **Provider-related attention** section appears with count and label.
- [ ] **Related screens** links point to Section Templates and Page Templates directory; links are valid and capability-appropriate.

### 2.5 Support bundle inclusion

- [ ] When generating a support bundle (export/support package), `template_library_support_summary.json` (or equivalent) includes key `form_provider_health_summary` when Form_Provider_Health_Summary_Service is available.
- [ ] `form_provider_health_summary` payload contains: provider_availability, registered_provider_ids, section_templates_with_forms_count, page_templates_using_forms_count, recent_failures_summary, built_at. No secrets.

### 2.6 Performance and boundedness

- [ ] Summary build uses capped repository reads (SECTION_CAP 500, PAGE_CAP 500); no unbounded queries.
- [ ] Screen renders without timeouts under normal template library size.

---

## 3. Risk notes

- Counts depend on Section_Template_Repository and Page_Template_Repository (and Form_Provider_Dependency_Validator for page count). If those are not in the container, counts are 0.
- Recent-failures summary is derived from provider availability status only; no dedicated failure log is ingested in this prompt.
