# Heartbeat Email Templates Appendix

**Spec**: §62.8 Heartbeat Email Templates Appendix; §46.4–46.5; §4.16 Telemetry and Reporting.

## Subject

Exact subject format is transport-defined (e.g. `[AIO Page Builder] Heartbeat – {site_reference} – {year_month}`). One successful heartbeat per site per calendar month.

## Body sections (payload)

- `website_address`, `plugin_version`, `wordpress_version`, `php_version`, `admin_contact_email`, `server_ip`
- `last_successful_ai_run_at`, `last_successful_build_plan_execution_at`
- `current_health_summary`, `current_queue_warning_count`, `current_unresolved_critical_error_count`
- `timestamp`

## Optional payload field (Prompt 214)

- **template_library_report_summary** — Bounded template-library health context. Subfields: `section_template_count`, `page_template_count`, `composition_count`, `library_version_marker`, `plugin_version_marker`, `appendices_available`, `compliance_summary`. No secrets; support-safe.

## Status enum values (current_health_summary)

- `healthy`
- `warning`
- `degraded`
- `critical`

## Example healthy message

Payload includes `current_health_summary: "healthy"`, optional `template_library_report_summary` with counts and version markers when the template library is present.
