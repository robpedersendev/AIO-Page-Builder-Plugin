# Additional Form Provider Onboarding Contract

**Governs:** Adding provider #2, #3, and beyond (Prompt 240; spec §0.10.12, §5.12, §57.4–57.5, §57.9, §58.4, §58.8).  
**Purpose:** Formal contract so future providers (e.g. WPForms, Contact Form 7) integrate through the same registry, picker adapter, availability/cache, storage, rendering, diagnostics, export/restore, and security layers. Extensibility without fragility; retrofit-first.

---

## 1. Canonical storage (unchanged)

- **Form section field values:** `form_provider` (provider identifier), `form_id` (form slug/key), `headline` (optional). Stored per section instance; provider-agnostic.
- **Validation:** form_provider must be in Form_Provider_Registry; form_id must match allowed pattern (alphanumeric, underscore, hyphen). No provider-specific fields in canonical storage unless explicitly added via a versioned schema change and documented here.
- **Additive metadata:** Availability state, picker cache, and diagnostics use additive payloads only; they do not change the canonical form_provider/form_id schema.

---

## 2. Registry registration (required)

- **Form_Provider_Registry::register( provider_id, shortcode_tag, id_attr ):**
  - `provider_id`: Stable slug (e.g. ndr_forms, wpforms, cf7). Lowercase, alphanumeric, underscore. Must be unique.
  - `shortcode_tag`: WordPress shortcode tag (e.g. ndr_forms, wpforms, contact-form-7).
  - `id_attr`: Attribute name for form identifier (e.g. id, form_id).
- **Registration point:** Provider registration must occur at plugin/bootstrap time (e.g. provider plugin or main plugin registration hook). No runtime discovery of unregistered providers for canonical storage.
- **Validation:** Form_Provider_Registry is the single source of truth for “is this provider valid.” All save paths and Build Plan/execution flows must validate against the registry.

---

## 3. Picker adapter (required for form-list UX; optional fallback)

- **Contract:** [form-provider-picker-adapter-contract.md](form-provider-picker-adapter-contract.md) (or equivalent in repo). Adapter implements: get_provider_key(), get_display_label(), is_available(), supports_form_list(), get_form_list(), is_item_stale(form_id), get_fallback_entry_label().
- **Discovery:** Form_Provider_Picker_Discovery_Service holds adapters per provider_key. New provider: implement adapter, register with discovery (container or explicit register_adapter).
- **Fallback behavior:** If a provider does not support form list (supports_form_list() false), manual form_id entry is the only path; UI must show fallback label and validate form_id format. No blind trust of provider APIs; labels/ids from provider are sanitized (e.g. esc_html, item_id pattern).
- **Retrofit-first:** New provider must not change existing adapter interfaces or break existing providers. Additive registration only.

---

## 4. Availability and caching (required when adapter supports form list)

- **Form_Provider_Availability_Service** and **Form_Provider_Picker_Cache_Service** (see [form-provider-availability-state-contract.md](form-provider-availability-state-contract.md)) must be used for any provider that implements get_form_list(). States: available, unavailable, no_forms, provider_error, cached_fallback. Bounded TTL and entry cap; no secrets in cache.
- **Stale-binding:** Adapter::is_item_stale(form_id) must reflect provider reality where possible (e.g. form deleted). Dashboard and state builder surface stale_binding for operator clarity.

---

## 5. Rendering and shortcode assembly

- **Single path:** Native_Block_Assembly_Pipeline (or current pipeline) uses Form_Provider_Registry::build_shortcode( provider_id, form_id ) for sections with form_provider and form_id. No provider-specific branches in core rendering; registry returns shortcode string or null.
- **Escaping:** form_id is sanitized per registry rules before inclusion in shortcode attribute. No raw user input in output.

---

## 6. Diagnostics, survivability, and reporting

- **Form Provider Health:** Form_Provider_Health_Summary_Service and Form Provider Health screen must include the new provider in availability and counts when registered. No code change required if registration and adapter are in place; summary is registry- and availability-driven.
- **Support bundle:** template_library_support_summary includes form_provider_availability and form_provider_health_summary; new provider appears in those payloads when registered. Bounded; no secrets.
- **Survivability:** Export/restore and migration must preserve form_provider and form_id; no provider-specific restore logic unless documented and versioned.

---

## 7. Export / restore expectations

- **Canonical fields:** form_provider and form_id are part of section/page template definitions in export. Restore validates provider_id against registry at restore time; invalid provider is a validation error, not a silent drop.
- **No provider secrets:** Export and support bundles must never include provider API keys, tokens, or internal config. Only provider_id and form_id (and optional headline) are stored and exported.

---

## 8. Security requirements (mandatory)

- **Capability:** All admin and AJAX/REST paths that read or write form provider state must enforce capability (e.g. aio_view_logs for diagnostics, manage_options or aio_manage_section_templates for save). No blind trust of client.
- **Nonce:** State-changing admin actions (e.g. save section, seed templates) require nonce verification and intent validation.
- **Input:** Sanitize form_provider and form_id on input; validate against registry and pattern on save. Escape on output.
- **Redaction:** Logs, diagnostics, and support payloads must not contain secrets or raw provider config. Use bounded status and message strings only.
- **Route authorization:** REST and AJAX routes must have explicit permission_callback; reject unauthorized requests with 403.

---

## 9. QA and release-gate obligations

- **Regression:** Provider-backed regression harness (form-provider-integration fixtures and FormProviderIntegrationRegressionHarness) must pass. New provider should add fixtures for valid/missing-provider/invalid-form_id as needed.
- **Acceptance:** End-to-end and security checklists (form-provider-end-to-end-acceptance-report, form-provider-security-checklist) apply to every provider; no waiver for “add-on” providers.
- **Release gate:** Form provider extension backlog and release checklist must reference the new provider and any new risks. Known-risk register updated if the new provider introduces new failure modes.

---

## 10. Bucket and prompt mapping

- **Hardening/integrations:** Registry + adapter + availability + diagnostics integration.
- **Reporting:** Health dashboard and support summary inclusion (automatic when registered).
- **QA/hardening:** Regression harness, acceptance, security checklist.

Future prompts that add a provider must cite this contract and the [form-provider-onboarding-checklist.md](../operations/form-provider-onboarding-checklist.md). No provider is “special-case”; all go through the same layers.
