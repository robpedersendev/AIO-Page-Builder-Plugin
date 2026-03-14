# Form Provider Integration — Security Checklist

**Document type:** Internal security checklist for provider-backed form integration (Prompt 233).  
**Spec refs:** §0.10.9 Security Is Mandatory; §44 Capability Model; §59.14 Hardening and QA Phase.  
**Purpose:** Ensure validation, sanitization, capability, nonce, and route hardening are applied at every integration surface.

---

## 1. Provider ID and form ID handling

| # | Check | Status | Notes |
|---|--------|--------|--------|
| 1.1 | Provider IDs are validated against the registry only (no arbitrary shortcode tags from user input). | ✓ | Form_Provider_Registry::build_shortcode returns null for unregistered provider; shortcode tag comes from registry. |
| 1.2 | Form ID is sanitized/validated with a strict pattern (alphanumeric, underscore, hyphen) before construction or persistence. | ✓ | FORM_ID_PATTERN in registry; sanitize_form_id private; build_shortcode uses it; public validate_provider_and_form / is_valid_form_id_format added. |
| 1.3 | Malformed or malicious provider_id/form_id cannot produce arbitrary shortcode output. | ✓ | build_shortcode returns null for invalid; output uses esc_attr for form_id. |
| 1.4 | Public validation helpers exist for save paths: is_valid_provider_id, is_valid_form_id_format, validate_provider_and_form. | ✓ | Form_Provider_Registry (Prompt 233). |

---

## 2. Admin and mutating actions

| # | Check | Status | Notes |
|---|--------|--------|--------|
| 2.1 | Template seed (form section + request page template): capability and nonce. | ✓ | Admin_Menu::handle_seed_form_templates — wp_verify_nonce('aio_seed_form_templates'), MANAGE_SECTION_TEMPLATES + MANAGE_PAGE_TEMPLATES. |
| 2.2 | Section template edit/save: screen capability. | ✓ | Section_Template_Detail_Screen get_capability(); ACF saves follow WordPress/ACF; no plugin-specific save endpoint without nonce. |
| 2.3 | Page template edit/save: screen capability. | ✓ | Page_Template_Detail_Screen get_capability(). |
| 2.4 | Relink/recovery or picker endpoints for form provider/form: permission_callback and nonce if added. | N/A | No dedicated form picker REST/AJAX in current scope; state builders are read-only. |

---

## 3. REST / AJAX routes

| # | Check | Status | Notes |
|---|--------|--------|--------|
| 3.1 | Any REST route that accepts or returns provider/form data has permission_callback. | ✓ | NamespaceController uses check_permission (Capabilities::MANAGE). |
| 3.2 | AJAX actions that mutate form references are nonce-protected and capability-checked. | N/A | No form-specific AJAX mutating endpoints. |

---

## 4. Rendering and output

| # | Check | Status | Notes |
|---|--------|--------|--------|
| 4.1 | Shortcode is built only via Form_Provider_Registry::build_shortcode (registry + sanitized form_id; esc_attr on attribute). | ✓ | Native_Block_Assembly_Pipeline uses build_shortcode only. |
| 4.2 | form_provider and form_id are not echoed as raw text in block markup. | ✓ | skip_keys in field_values_to_inner_html; only shortcode or nothing emitted. |
| 4.3 | Preview and diagnostics do not leak unsafe provider data (secrets, tokens, raw config). | ✓ | State builders and diagnostics use provider IDs and validation flags; no secrets in definitions. |

---

## 5. Import / restore validation

| # | Check | Status | Notes |
|---|--------|--------|--------|
| 5.1 | Import/restore validates provider-backed references where applicable (Prompts 231–232). | Deferred | Export/restore validation of form_provider/form_id is in scope for Prompt 232; document in review. |
| 5.2 | Restore does not trust provider/form_id from package without validation. | Deferred | Per Prompt 232. |

---

## 6. Diagnostics and reporting

| # | Check | Status | Notes |
|---|--------|--------|--------|
| 6.1 | Diagnostics and support bundles classify provider dependency; no raw provider internals or secrets. | Per 231 | Prompt 231 scope. |
| 6.2 | Reporting payloads do not include raw form_provider/form_id beyond identifiers; redaction rules apply. | ✓ | Reporting uses summary/classification; no expansion of provider config. |

---

## 7. Sign-off

- [ ] All applicable rows verified (or N/A / deferred with rationale).
- [ ] No high-severity gap remaining for provider-backed form surfaces.
- [ ] Findings recorded in [form-provider-security-review.md](form-provider-security-review.md).

*Update this checklist when new form-provider surfaces are added.*
