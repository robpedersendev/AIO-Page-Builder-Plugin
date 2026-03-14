# Form Provider Integration — Security Review

**Document type:** Security retrofit findings and surface map (Prompt 233).  
**Spec refs:** §0.10.9, §44, §59.14, §60.5, §60.8.  
**Purpose:** Document where the implementation is already safe, where it was implicitly safe, and what was added or remains deferred.

---

## 1. Impact analysis

- **Scope:** Provider-backed form sections and request-form page template: registry, admin editing, rendering, execution (build/replace), dependency validation, finalization, diagnostics/reporting (Prompts 231–232), export/restore (Prompt 232).
- **Risk:** User or package-supplied provider_id/form_id could become shortcode-injection vectors if accepted without validation. Admin actions could be abused without capability/nonce.
- **Approach:** Retrofit only; preserve registry as single source of allowed provider IDs; add explicit validation helpers and checklist; document all surfaces.

---

## 2. Security surface map

| Surface | Input source | Validation / authority | Output / side effect |
|--------|--------------|------------------------|----------------------|
| **Form_Provider_Registry** | register(): code/config only. build_shortcode(provider_id, form_id): callers pass values. | provider_id sanitized (alphanumeric+underscore); form_id via FORM_ID_PATTERN; has_provider check before shortcode. | Shortcode string or null; esc_attr(form_id) in attribute. |
| **Form_Section_Field_State_Builder** | field_values (display/editor). | sanitize_text_field on form_provider, form_id, headline; has_provider + regex form_id for validation flags. | Read-only state; no persist. |
| **Native_Block_Assembly_Pipeline** | field_values from section render context (DB/content). | Only calls build_shortcode(provider, form_id); registry rejects invalid. | Block inner HTML; form_provider/form_id not echoed as text. |
| **Form_Template_Seeder** | Static definitions (Form_Integration_Definitions). | No user input. | Writes section + page template definitions to repos. |
| **Admin_Menu::handle_seed_form_templates** | POST with nonce. | wp_verify_nonce; MANAGE_SECTION_TEMPLATES + MANAGE_PAGE_TEMPLATES. | Redirect; calls Section_Registry_Service::ensure_bundled_form_templates. |
| **Form_Provider_Dependency_Validator** | template_key (from plan/envelope). | Validates template’s form_embed sections against registry; no user-supplied provider_id in this path. | Blocks build/replace when provider missing. |
| **New_Page_Template_Recommendation_Builder** | Plan item payload (template_key). | Uses same validator for dependency_warnings; read-only. | UI state. |
| **Template_Finalization_Service** | Plan definition (internal). | Adds form_dependency flag from template_key; no user input. | Closure record. |
| **Import/restore** | Package manifest/content. | Prompt 232: validate provider-backed references on restore. | Deferred to 232. |
| **Diagnostics/reporting** | Internal state. | Prompt 231: classify provider dependency; redaction. | Bounded payloads. |

---

## 3. Already safe (explicit or implicit)

- **Shortcode construction:** Only via registry; unregistered provider or invalid form_id yields null; no arbitrary tag or attribute from input.
- **Output escaping:** form_id in shortcode attribute uses esc_attr; form_provider/form_id not echoed as content.
- **Seed action:** Nonce and dual capability (section + page template manage) enforced before any write.
- **Rendering:** Assembly pipeline does not use raw request or unsanitized user input; field_values come from stored content, and build_shortcode sanitizes.

---

## 4. Changes made (Prompt 233)

- **Form_Provider_Registry:** Added `is_valid_provider_id()`, `is_valid_form_id_format()`, `validate_provider_and_form()` for use by save paths or REST handlers that accept provider/form input. No change to build_shortcode behavior.
- **Documentation:** Added [form-provider-security-checklist.md](form-provider-security-checklist.md) and this review; updated [known-risk-register.md](../release/known-risk-register.md) with provider-backed form risk row.
- **Tests:** Unit tests for malicious or malformed provider_id/form_id (registry validation and build_shortcode returning null).

---

## 5. Deferred / dependency

- **ACF save validation:** If a single save path for section/page form fields is introduced (e.g. ACF validate_value or pre-save filter), it should call `validate_provider_and_form()` and block invalid values. Current flow relies on output-side safety (build_shortcode) and display-time validation in Form_Section_Field_State_Builder.
- **Import/restore:** Validate provider-backed references on restore (Prompt 232); document in checklist.
- **Diagnostics/reporting:** Bounded provider dependency classification (Prompt 231).

---

## 6. Remaining risks

| ID | Severity | Description | Mitigation |
|----|----------|-------------|------------|
| FPR-1 | Low | Stored content could contain non-registry provider_id or malformed form_id; render path still safe (null shortcode). | Validation helpers available for future save paths; optional ACF validate_value in follow-up. |
| FPR-2 | Low | Export/restore of provider refs not yet validated (Prompt 232). | Add restore validation in 232; checklist updated. |

No critical or high-severity open issues for provider-backed form integration. Sign-off per hardening matrix when checklist is verified and tests pass.
