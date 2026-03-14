# Error Email Templates Appendix

**Spec**: §62.7 Error Email Templates Appendix; §45.7–45.9, §46.6–46.12; §4.16 Telemetry and Reporting.

## Subject formats

Exact subject is transport-defined; severity and category may appear. Dedupe by log_id.

## Body block order

1. Severity and category  
2. Sanitized error summary (redacted)  
3. Expected/actual behavior (redacted, length-limited)  
4. Site and environment (website_address, plugin_version, wordpress_version, php_version, admin_contact_email, server_ip, timestamp)  
5. Log reference (log_id, log_category, log_severity)  
6. Related IDs (related_plan_id, related_job_id, related_run_id)

## Optional payload field (Prompt 214)

- **template_library_report_summary** — When the error is template-operation relevant, reports may include bounded template-library context: `section_template_count`, `page_template_count`, `composition_count`, `library_version_marker`, `plugin_version_marker`, `appendices_available`, `compliance_summary`. No secrets; no raw registry or preview content.

## Severity variants

As per Reporting_Eligibility_Evaluator and severity-based reporting rules (spec §46.7).

## Included field list

severity, category, sanitized_error_summary, expected_behavior, actual_behavior, website_address, plugin_version, wordpress_version, php_version, admin_contact_email, server_ip, timestamp, log_reference, related_plan_id, related_job_id, related_run_id, and optionally template_library_report_summary.

## Excluded field list

All prohibited keys per Reporting_Payload_Schema (e.g. password, api_key, bearer_token, nonce, raw_ai_payload, secret). No stack traces or raw payloads beyond sanitized summary.
