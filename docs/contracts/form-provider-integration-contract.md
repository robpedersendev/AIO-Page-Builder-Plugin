# Form Provider Integration Contract

**Document type:** Canonical internal contract for provider-backed form sections and request-form page templates (Prompt 226).  
**Governs:** Field names, category semantics, rendering, provider registry, fallback behavior, diagnostics, export/restore, and security.  
**Spec refs:** §0.4, §0.10.8–0.10.12, §20.1–20.3, §59.5, §60.4, §60.6.  
**See also:** [form-provider-retrofit-impact-analysis.md](form-provider-retrofit-impact-analysis.md).

---

## 1. Scope

This contract defines how the AIO Page Builder integrates with external form providers (e.g. NDR Form Manager). Provider-backed form sections use a **section-level field group** with two stable fields; the **page template** aggregates these sections like any other. The plugin remains **registry-first**: section and page template structure is defined in the plugin; the external form provider is not the source of truth for page-template structure. Field visibility is **page-relevant** and governed by ACF assignment. Extensibility (adding providers) must not introduce fragility or break the storage contract.

---

## 2. Stable field names and storage contract

| Field name (storage / ACF) | Meaning | Type / constraints |
|----------------------------|--------|---------------------|
| `form_provider` | Provider identifier (e.g. `ndr_forms`). Must be registered in Form_Provider_Registry. | Non-empty string; sanitized per Form_Provider_Registry::sanitize_provider_id(). |
| `form_id` | Form identifier within the provider (e.g. form post ID or slug). | Non-empty string; sanitized per Form_Provider_Registry::sanitize_form_id(); format is provider-defined. |

These two fields are the **canonical storage contract** for “which form to render” in a provider-backed form section. No other field names are used for this purpose in the form-provider integration. Future providers are added by registering in Form_Provider_Registry; the storage contract does not change.

**Blueprint alignment:** Section definitions that represent provider-backed forms must use the same names in their embedded field_blueprint (e.g. `field_form_provider`, `field_form_id` mapping to these keys). See Section_Field_Blueprint_Service and Field_Key_Generator for deterministic key derivation.

---

## 3. Category: form_embed

The section template category **`form_embed`** (Section_Schema) denotes sections whose primary content is an embedded form. The **provider-backed** variant is defined by this contract: such sections use `form_provider` and `form_id` and render via Form_Provider_Registry shortcode. Other uses of `form_embed` (e.g. free-text shortcode slot in Legal Policy Utility batches) may have different field semantics (e.g. `form_embed_slot`); they are not governed by the provider registry and are out of scope for this contract.

---

## 4. Rendering behavior

- **Input:** Section render result with `field_values` containing `form_provider` and `form_id`.
- **Pipeline:** Native_Block_Assembly_Pipeline (with Form_Provider_Registry set) calls Form_Provider_Registry::build_shortcode(provider_id, form_id). The resulting shortcode string is emitted in block markup; `form_provider` and `form_id` are **not** emitted as headline/paragraph text.
- **When provider is missing or form_id invalid:** Form_Provider_Registry::build_shortcode() returns `null`. The pipeline does not emit a form shortcode; it does not throw. No fallback markup is required; optional placeholder or message may be added in a later prompt.
- **Output escaping:** Shortcode and attributes are escaped for safe output (e.g. esc_attr where appropriate). No raw user or provider content is output unescaped.

---

## 5. Provider registry responsibilities

- **Registration:** Form_Provider_Registry::register(provider_id, shortcode_tag, id_attr). Provider_id is the value stored in `form_provider`; shortcode_tag and id_attr define how build_shortcode() builds the shortcode (e.g. `[ndr_forms id="123"]`).
- **Validation:** Before rendering or saving, the plugin validates that provider_id is registered and that form_id is non-empty and passes provider-specific validation if any. Validation failure results in no shortcode (render) or safe rejection (save) without breaking the page.
- **Build shortcode:** build_shortcode(provider_id, form_id) returns the shortcode string or null if provider unknown or form_id invalid. The registry does not call the provider plugin to verify form existence at render time unless explicitly extended; missing forms are a content/configuration concern.

---

## 6. Missing-provider, missing-form, and invalid-form-reference states

| State | Meaning | Render behavior | Save / persistence |
|-------|---------|-----------------|--------------------|
| Provider not registered | form_provider value not in Form_Provider_Registry | No shortcode emitted; no throw. | Values may still be stored; validation may reject or warn in admin context if implemented. |
| form_id empty or invalid | form_id fails sanitization or provider validation | build_shortcode returns null; no shortcode. | Stored value is sanitized; invalid values may be rejected by validation. |
| Provider plugin deactivated | Provider was registered but plugin no longer active | Same as “provider not registered” at render time. | Stored form_provider/form_id remain; restore does not re-validate provider availability. |

The contract does not require the plugin to block save or to migrate content when a provider is missing; diagnostics and UI may surface “missing form” or “provider unavailable” for support.

---

## 7. Page-template aggregation and request-form template

The **request-form page template** (e.g. `pt_request_form`) is a page template that includes one or more provider-backed form sections in its ordered_section_keys. It is defined in the page template registry like any other template. Page-template structure (section order, optional sections) is defined by the plugin registries; the form provider does not define page structure. Template library coverage and compliance count form-bearing section and page templates per [template-library-coverage-matrix.md](template-library-coverage-matrix.md).

---

## 8. Diagnostics requirements

- **ACF diagnostics:** Provider-backed form sections use the same ACF blueprint and registration path as other sections; they appear in blueprint health, registration status, and assignment checks.
- **Third-party form dependencies (optional):** Diagnostics may classify sections/pages that reference form_provider/form_id so support can identify external dependencies. This is not required for MVP; the contract allows future addition of such classification without changing the storage contract.
- **No secrets:** Diagnostics and reports must not expose provider API keys, form content, or other secrets. Provider and form IDs are non-secret identifiers and may appear in diagnostic summaries.

---

## 9. Export / import persistence

- **Registry export:** Section and page template definitions (including form section and request-form page template with embedded field_blueprint) are part of the registry export. Form_provider and form_id are not a separate export entity; they are part of section definitions and of ACF field values on content.
- **Restore:** Restored registries and content restore form section and request-form template definitions and ACF field values. The plugin does not re-validate provider or form existence on restore; restored form references remain valid storage and will render if the provider is available at render time.

---

## 10. Survivability and repair

- **ACF registration and repair:** Form section blueprint is registered and repaired like any other section-owned blueprint. Field keys are deterministic and stable; regeneration and repair do not special-case form sections.
- **Migration:** Schema and versioning apply to section/page definitions; form_provider and form_id are part of the section field set and are not migrated separately unless a future schema change explicitly defines such migration.

---

## 11. Security and permissions

- **Capability:** Admin actions that mutate form template data (e.g. “Seed form templates”) must be gated by an appropriate capability (e.g. manage section/page templates or manage_options until capability mapping is finalized). See [admin-screen-inventory.md](admin-screen-inventory.md).
- **Nonce:** State-changing admin requests (e.g. seed form templates) must use a nonce and verify intent.
- **Validation and sanitization:** Provider ID and form ID are sanitized before use; output is escaped. AIO validates its own provider IDs and form IDs before rendering or saving; it does not trust raw input from the provider plugin for security boundaries.
- **External dependency:** Form provider plugins are external. The contract does not require the provider to be installed for the plugin to store or export form references; only rendering depends on provider availability at runtime.

---

## 12. Adding future providers

New providers are added by registering with Form_Provider_Registry (e.g. at bootstrap or via a dedicated registration hook). No change to the storage contract (form_provider, form_id) or to section/page template schema is required. New provider_id values become valid for form_provider; build_shortcode and validation logic use the registry. The contract remains stable and extensible without fragility.
