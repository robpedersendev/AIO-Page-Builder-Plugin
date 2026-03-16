# Industry Subsystem — CLI and Scripted Inspection Guide (Prompt 398)

**Spec**: industry-lifecycle-hardening-contract §3; diagnostics/support contracts.  
**Purpose:** Internal, read-only inspection of industry profile, health, diagnostics, and recommendation preview from CLI or scripted contexts. No mutation; no public routes.

---

## 1. Scope and constraints

- **Read-only:** All inspection methods return data only; they do not change profile, preset, or registries.
- **Internal use:** For developers and support. Not a public API. No secrets or sensitive payloads in output.
- **Bounded output:** Summaries and previews are capped (e.g. top-N keys, sample errors) so output stays readable in terminals and logs.

---

## 2. Service and entry point

- **Service:** `plugin/src/Domain/Industry/Reporting/Industry_Inspection_Command_Service.php`
- **Usage:** Instantiate with profile repository, health service, diagnostics service, and optional registries/resolvers. When the plugin container is available, resolve the service from the container if registered; otherwise build with minimal dependencies for the calls you need.

---

## 3. Inspection methods

| Method | Purpose | Output shape |
|--------|---------|--------------|
| **get_profile_summary()** | Current industry profile summary | primary_industry_key, secondary_industry_keys, selected_starter_bundle_key, readiness, available |
| **get_health_summary()** | Health check result summary | errors_count, warnings_count, sample_errors (up to 5), sample_warnings (up to 5), available |
| **get_diagnostics_snapshot()** | Full diagnostics snapshot | Same as Industry_Diagnostics_Service::get_snapshot() (primary_industry, active_pack_refs, applied_preset_ref, overlay counts, warnings, etc.) |
| **get_recommendation_preview( $industry_key, $top_templates, $top_sections )** | Top template/section keys for a given industry | industry_key, top_template_keys, top_section_keys, template_count, section_count, pack_found |
| **get_starter_bundles_for_industry( $industry_key )** | Starter bundle keys for an industry | list of bundle keys |

---

## 4. Using from WP-CLI or scripts

- **With container:** If the plugin registers `Industry_Inspection_Command_Service` in the container, obtain it and call the methods above. Output can be printed as JSON for scripting: `echo wp_json_encode( $service->get_profile_summary(), JSON_PRETTY_PRINT );`
- **Without container:** Construct the service with the dependencies you have (e.g. only profile repo and diagnostics service). Omitted dependencies yield empty or “available: false” where applicable.
- **Multisite:** When running in a site context (e.g. `wp --url=example.com/site2` or after `switch_to_blog()`), inspection reflects that site’s profile and state.

---

## 5. Example: profile and diagnostics

```php
// Pseudocode: get profile summary and diagnostics (when service is available)
$inspection = $container->get( 'industry_inspection_command_service' );
$profile = $inspection->get_profile_summary();
$diagnostics = $inspection->get_diagnostics_snapshot();
// Log or print $profile, $diagnostics (e.g. JSON).
```

---

## 6. Example: recommendation preview for an industry

```php
// Pseudocode: top 5 page template keys for industry 'realtor'
$preview = $inspection->get_recommendation_preview( 'realtor', 5, 0 );
// $preview['top_template_keys'], $preview['pack_found'], $preview['template_count']
```

---

## 7. Support and runbook

- **Diagnostics:** Use get_diagnostics_snapshot() when gathering industry state for support triage. See [industry-subsystem-diagnostics-checklist.md](../qa/industry-subsystem-diagnostics-checklist.md).
- **Health:** Use get_health_summary() to quickly see pack ref resolution and profile issues (errors_count, sample_errors).
- **No mutation:** This service does not set profile, apply preset, or change overrides. Mutations remain through admin UI or future, explicitly authorized CLI commands with capability checks.

---

## 8. Cross-references

- [industry-lifecycle-hardening-contract.md](../contracts/industry-lifecycle-hardening-contract.md) §3 — CLI and scripted behavior.
- [industry-lifecycle-regression-guard.md](../qa/industry-lifecycle-regression-guard.md) — CLI read and fallback verification.
- [industry-subsystem-diagnostics-checklist.md](../qa/industry-subsystem-diagnostics-checklist.md) — diagnostics and support.
