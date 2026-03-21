# Form Provider — Operator Guide

**Audience:** Administrators and operators using provider-backed form sections and the request-form page template.  
**Spec:** §0.10.7, §0.10.10, §49, §50, §57.9, §60.6.  
**Purpose:** Current behavior, failure states, and operator-facing guidance. Product-accurate; no aspirational behavior.  
**Knowledge base:** [KB index](../kb/index.md); [FILE_MAP.md](../kb/FILE_MAP.md) §3 (Form Provider Health) and §2 (Settings seed).

---

## 1. What the form provider feature is

- **Provider-backed form section:** A section template (category **form_embed**, e.g. Form section) that embeds a single form from an external form plugin (e.g. NDR Form Manager). The page builder stores **form_provider** (e.g. `ndr_forms`) and **form_id** (e.g. `contact`) and renders the form via the provider’s shortcode.
- **Request-form page template:** A page template (`pt_request_form`) that includes the form section. Used for contact, request, or lead-capture pages.
- **Canonical storage:** Only **form_provider** and **form_id** are stored (in section field values / page content). The provider plugin owns the form definition and submissions; the page builder only references them.

---

## 2. What you need before using it

- **Provider plugin active:** The form provider (e.g. NDR Form Manager) must be installed and active. The page builder registers known providers; if the provider is not registered, build and replace actions that use the request-form template will **block** with a clear error.
- **Seed form templates (once):** Under **AIO Page Builder → Settings**, use **Seed form section and request page template** to add the form section and request-form page template to the registries. This requires **Manage section templates** and **Manage page templates**. The action is nonce-protected.
- **Capabilities:** Section and page template directories/detail require the same capabilities as the rest of the template library. Build Plan execution requires execution capability; dependency validation runs before create/replace.

---

## 3. Where form provider appears in the UI

- **Section Templates directory:** Form section (e.g. `form_section_ndr`) appears like any other section; category is **form_embed**.
- **Section Template Detail:** For a form section, a **Form binding** panel shows the current form provider, form identifier, validation state (provider valid, form_id valid), and shortcode preview when both are valid. This is observational; editing the stored values happens when editing a **page** that uses the form section (ACF fields on the page).
- **Page Templates directory:** Request-form page template (`pt_request_form`) is listed when seeded.
- **Build Plan (new pages):** If a new-page item uses the request-form template and the form provider is **not** registered, the row shows **dependency warnings** and the build will **fail** at execution with a message that the form provider is not registered.
- **Build Plan (replace):** Same dependency check; replace is blocked if the provider is missing.
- **Finalization:** After a run, the execution closure record may include a **form_dependency** flag for items that used the request-form template or a template with form_embed sections (for support/traceability).

---

## 4. Failure states and what to do

| Situation | What happens | What to do |
|-----------|----------------|------------|
| Form provider plugin deactivated | Build and replace for request-form (or any template with form_embed sections) **block** with an error that the provider is not registered. | Activate the provider plugin, or choose a different template that does not use form sections. |
| Invalid or empty form_id | Section may show a validation message; shortcode is not emitted on render (no form displayed). | Set a valid form_id from your form manager (e.g. form slug or ID). Form ID may only contain letters, numbers, hyphens, and underscores. |
| Form section not seeded | Form section and request-form page template do not appear in directories. | Run **Seed form section and request page template** from Settings (requires section + page template manage capabilities). |
| Build Plan row shows dependency warnings | New-page or replace item uses a template that requires a form provider that is not registered. | Resolve by activating the provider or changing the template; otherwise execution will fail. |

---

## 5. Export, restore, and survivability

- **Export:** Provider-backed references (form_provider, form_id) are part of section/page content. Export packages include these references; the plugin does not export the provider’s own form definitions or submissions.
- **Restore:** When restore validation is implemented (Prompt 232), missing provider or missing form may be reported as conflicts; follow relink or environment guidance.
- **Survivability:** Built pages are native WordPress content; they **survive** plugin uninstall. **Form functionality** on those pages depends on the form provider plugin being active; if the provider is removed, the shortcode will not expand and the form will not display. This is documented in uninstall/export messaging (Prompt 231 when complete).

---

## 6. Security and validation

- Provider IDs are validated against the **registry only**; arbitrary shortcode tags cannot be injected from user input.
- Form ID is validated with a strict pattern (alphanumeric, underscore, hyphen) before shortcode construction.
- Seed action requires nonce and both **Manage section templates** and **Manage page templates**.
- For more detail, see [form-provider-security-checklist.md](../qa/form-provider-security-checklist.md) and [form-provider-security-review.md](../qa/form-provider-security-review.md).

---

## 7. Cross-references

| Need | Doc |
|------|-----|
| Template library (directories, compare, compositions) | [template-library-operator-guide.md](template-library-operator-guide.md) |
| Build Plan and execution | [admin-operator-guide.md](admin-operator-guide.md) |
| Integration contract (technical) | [form-provider-integration-contract.md](../contracts/form-provider-integration-contract.md) |
| Support and diagnostics | [template-library-support-guide.md](template-library-support-guide.md) |
| Known risks | [known-risk-register.md](../release/known-risk-register.md) |
| Extension backlog (future work) | [form-provider-extension-backlog.md](../release/form-provider-extension-backlog.md) |

---

*This guide reflects the current implementation. When Prompts 231–232 (diagnostics, export/restore) and 236 (picker adapter) are complete, operator and support docs may be updated.*
