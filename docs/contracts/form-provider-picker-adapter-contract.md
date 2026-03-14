# Form Provider Picker Adapter Contract

**Governs:** Provider-backed form selection in admin UI (Prompt 236).  
**Spec:** §0.10.10, §0.10.11, §0.10.12, §5.12, §44, §49.6, §50.1, §57.4, §57.9.  
**Purpose:** Extensibility without fragility; provider discovery, normalized picker items, empty-state and fallback behavior. Canonical storage remains form_provider and form_id.

---

## 1. Scope

- **Picker adapter:** Per-provider contract for display label, availability, optional form-list API, normalized picker items, stale-item reporting, and fallback when no list API.
- **Discovery service:** Resolves which providers support the picker contract and returns normalized, sanitized state for UI (dropdown vs manual entry).
- **Canonical storage:** Unchanged. Persisted values remain **form_provider** and **form_id**. Picker result is used only to set those; no new canonical fields.

---

## 2. Form_Provider_Picker_Adapter_Interface

Each provider that participates in picker UI implements:

| Method | Return | Description |
|--------|--------|-------------|
| `get_provider_key()` | string | Provider key (e.g. ndr_forms); matches Form_Provider_Registry. |
| `get_display_label()` | string | Human-readable label for UI. |
| `is_available()` | bool | Provider available (e.g. plugin active). |
| `supports_form_list()` | bool | Whether provider exposes a form-list API for dropdown. |
| `get_form_list()` | list of picker items | Only when supports_form_list; see §3. |
| `is_item_stale(form_id)` | bool | True if form no longer exists or inaccessible in provider. |
| `get_fallback_entry_label()` | string | Label for manual form_id entry when no list. |

**Security:** Provider-returned item_id and item_label are untrusted; discovery service sanitizes before exposing to UI.

---

## 3. Normalized picker item

When a provider exposes a form list, each item has:

| Field | Type | Description |
|-------|------|-------------|
| provider_key | string | Same as adapter. |
| item_id | string | Form identifier (validated against form_id pattern). |
| item_label | string | Display label (escaped for output). |
| status_hint | string \| null | Optional provider-specific hint (e.g. draft, archived). |

Discovery service filters out items with invalid item_id and escapes item_label.

---

## 4. Form_Provider_Picker_Discovery_Service

- **Inputs:** Form_Provider_Registry and a map of provider_key → Form_Provider_Picker_Adapter_Interface.
- **get_providers_with_picker_support():** Provider keys that are registered, have an adapter, and adapter is_available().
- **get_picker_state_for_provider(provider_key):** Normalized state: display_label, available, supports_form_list, picker_items (sanitized), fallback_entry_label, empty_state_message.
- **has_adapter(provider_key):** Whether an adapter is registered for that key.
- **is_item_stale(provider_key, form_id):** Delegates to adapter when present.

**Capability:** Discovery does not enforce capability; callers (admin screens) must check capability before using. Responses are sanitized and bounded.

---

## 5. Current provider (NDR) conformance

- **Ndr_Form_Provider_Picker_Adapter:** Implements the interface; is_available() from registry has_provider(ndr_forms); supports_form_list() false; get_form_list() empty; is_item_stale() false; get_fallback_entry_label() for manual entry.
- **Runtime submission/shortcode:** Unchanged; Form_Provider_Registry and Native_Block_Assembly_Pipeline unchanged. Adapter is for UI/picker only.

---

## 6. Future providers

- Register provider in Form_Provider_Registry (shortcode, id_attr).
- Implement Form_Provider_Picker_Adapter_Interface (optionally supports_form_list true and get_form_list() from provider API).
- Register adapter with Form_Provider_Picker_Discovery_Service (e.g. via container).
- UI uses discovery get_picker_state_for_provider() to show dropdown or fallback text field. No switch-case on provider_id in UI; adapter-driven.

---

## 7. State builder integration

Form_Section_Field_State_Builder may accept an optional Form_Provider_Picker_Discovery_Service. When present, state can include picker_states (per-provider picker state) so the admin UI can render provider dropdown and form selector (list or manual) without coupling to a single provider.

---

## 8. Cross-references

- **Registry and shortcode:** [form-provider-integration-contract.md](form-provider-integration-contract.md).
- **Schema:** form_provider, form_id in [data-schema-appendix.md](../appendices/data-schema-appendix.md).
- **Extension backlog:** [form-provider-extension-backlog.md](../release/form-provider-extension-backlog.md) (form-list API, additional providers).
