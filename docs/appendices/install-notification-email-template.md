# Install Notification Email Template Appendix

**Spec**: §62.9 Install Notification Email Template Appendix; §46.2–46.3; §4.16 Telemetry and Reporting.

## Subject

Exact subject format is transport-defined (e.g. `[AIO Page Builder] Install notification – {site_reference}`). Dedupe key is included in envelope.

## Required body fields (payload)

- `website_address`
- `plugin_version`
- `wordpress_version`
- `php_version`
- `admin_contact_email`
- `timestamp`
- `dependency_readiness_summary`

## Optional payload field (Prompt 214)

- **template_library_report_summary** — When present, bounded template-library health context for support visibility. Subfields: `section_template_count`, `page_template_count`, `composition_count`, `library_version_marker`, `plugin_version_marker`, `appendices_available`, `compliance_summary`. No secrets; no raw registry or preview content.

## Example payload (with template summary)

```json
{
  "website_address": "https://example.com",
  "plugin_version": "1.0.0",
  "wordpress_version": "6.6",
  "php_version": "8.2",
  "admin_contact_email": "admin@example.com",
  "timestamp": "2025-03-14T12:00:00Z",
  "dependency_readiness_summary": "all ready",
  "template_library_report_summary": {
    "section_template_count": 250,
    "page_template_count": 500,
    "composition_count": 120,
    "library_version_marker": "1",
    "plugin_version_marker": "1.0.0",
    "appendices_available": true,
    "compliance_summary": "unknown"
  }
}
```

## Duplicate suppression

One install notification per site (site_reference). Re-send only on reinstall or domain change.
