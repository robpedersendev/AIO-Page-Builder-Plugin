# Form Provider Onboarding Checklist

**Use when:** Adding provider #2, #3, or any new form provider (Prompt 240).  
**Contract:** [additional-form-provider-onboarding-contract.md](../contracts/additional-form-provider-onboarding-contract.md).  
**Purpose:** Ensure no step is skipped and architecture remains consistent.

---

## 1. Registry and metadata

- [ ] **Provider ID** chosen (lowercase, alphanumeric, underscore; unique). Example: wpforms, cf7.
- [ ] **Shortcode tag** and **id attribute** identified (e.g. wpforms / form_id).
- [ ] **Form_Provider_Registry::register( provider_id, shortcode_tag, id_attr )** called at bootstrap (provider plugin or main plugin). No canonical storage change.

---

## 2. Picker adapter

- [ ] **Form_Provider_Picker_Adapter_Interface** implemented (get_provider_key, get_display_label, is_available, supports_form_list, get_form_list, is_item_stale, get_fallback_entry_label).
- [ ] Adapter **registered** with Form_Provider_Picker_Discovery_Service (container or register_adapter). Same discovery instance used by state builder and availability service.
- [ ] **Normalization:** get_form_list() returns items with provider_key, item_id, item_label (and optional status_hint). Discovery normalizes/sanitizes; no raw provider output in UI.
- [ ] **Fallback:** If supports_form_list() is false, get_fallback_entry_label() provides label for manual form_id entry; validation and format rules documented.

---

## 3. Availability and caching

- [ ] When adapter **supports form list:** Form_Provider_Availability_Service and Form_Provider_Picker_Cache_Service are used (already in container). No new cache implementation; adapter is called via discovery.
- [ ] **Stale detection:** is_item_stale(form_id) implemented to reflect provider state where possible (e.g. form removed). Dashboard and state builder show stale_binding when relevant.

---

## 4. Rendering and shortcode

- [ ] **No change** to Native_Block_Assembly_Pipeline or shortcode assembly for the new provider; Form_Provider_Registry::build_shortcode( provider_id, form_id ) is provider-agnostic. Confirm shortcode_tag and id_attr produce the correct embed string for the provider.

---

## 5. Diagnostics and support

- [ ] **Form Provider Health** screen and support bundle automatically include the new provider once registered (availability and counts). Verify after registration.
- [ ] **No secrets** in availability state, health summary, or support payloads. Only status, message, counts, and built_at.

---

## 6. Export / restore

- [ ] **Canonical fields** form_provider and form_id are already part of export/restore. Restore validates provider_id against registry; document any restore-time behavior for missing provider (e.g. validation error vs. best-effort).
- [ ] **No provider-specific export fields** unless approved and documented in data-schema-appendix and this contract.

---

## 7. Security

- [ ] **Capability** enforced on all admin/diagnostics and save paths that touch form provider state.
- [ ] **Nonce** and intent verification on state-changing actions.
- [ ] **Sanitize** form_provider and form_id on input; **validate** against registry and pattern; **escape** on output.
- [ ] **Redaction** applied to any log or support payload; no API keys or tokens.

---

## 8. QA and release

- [ ] **Regression:** Add or extend fixtures in plugin/tests/fixtures/form-provider-integration/ if provider-specific cases are needed; FormProviderIntegrationRegressionHarness runs for all fixtures.
- [ ] **Acceptance:** Run form-provider E2E and security checklists with the new provider; record in acceptance report.
- [ ] **Backlog:** Update [form-provider-extension-backlog.md](../release/form-provider-extension-backlog.md) and known-risk register if the new provider introduces new risks or deferred decisions.

---

## 9. Documentation

- [ ] **Glossary** and **data-schema-appendix** updated if new terms or schema fields are introduced (per contract §12).
- [ ] **Operator/support guides** updated if the new provider requires special steps (e.g. plugin activation, form_id format).

---

*Complete this checklist for each new provider. Sign off in PR or release notes.*
