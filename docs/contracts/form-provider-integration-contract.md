# Form Provider Integration Contract

**Document type:** Integration specification and contract for third-party form providers.  
**Audience:** Form plugin developers (Contact Form 7, WPForms, custom form plugins, or any form management solution).  
**Purpose:** Defines what a form plugin MUST and SHOULD provide so that the AIO Page Builder plugin can offer section templates and page templates that embed forms from any compatible provider.  
**Downstream use:** AIO Page Builder will use this contract to implement form-capable section templates, block assembly behavior, and page templates (e.g. contact, request) that consume form references.  
**Status:** Authoritative for integration; form plugins that satisfy this contract are considered compatible for use in AIO Page Builder form sections.

---

## Table of contents

1. [Purpose and scope](#1-purpose-and-scope)
2. [Definitions and glossary](#2-definitions-and-glossary)
3. [AIO Page Builder context](#3-aio-page-builder-context)
4. [Contract overview](#4-contract-overview)
5. [Required: Shortcode embedding](#5-required-shortcode-embedding)
6. [Required: Form identifier](#6-required-form-identifier)
7. [Required: Output behavior and security](#7-required-output-behavior-and-security)
8. [Recommended: Block embedding](#8-recommended-block-embedding)
9. [Recommended: Form list API](#9-recommended-form-list-api)
10. [Recommended: Form picker integration](#10-recommended-form-picker-integration)
11. [Security and sanitization](#11-security-and-sanitization)
12. [Examples by provider](#12-examples-by-provider)
13. [What AIO Page Builder will do with this](#13-what-aio-page-builder-will-do-with-this)
14. [Checklist for form plugin developers](#14-checklist-for-form-plugin-developers)
15. [Revision history](#15-revision-history)

---

## 1. Purpose and scope

### 1.1 Purpose

The AIO Page Builder plugin builds pages from **section templates** and **page templates**. Section content is driven by ACF (Advanced Custom Fields) field values. To support “form sections”—sections whose primary content is a form from an external provider—the page builder must be able to:

- Store a **reference** to a form (which provider, which form) in a section’s field data.
- **Emit** that reference as embeddable content (shortcode or block) when assembling the page’s saved content.
- Rely on the form plugin to **render** the form when the page is viewed, without the page builder implementing form logic itself.

This document specifies what any form plugin (Contact Form 7, WPForms, a custom Cursor-built form plugin, or another form manager) must provide so that it can be used inside AIO Page Builder’s form sections. It is written for the **form plugin developer**. Satisfying this contract makes a form provider “compatible” with AIO Page Builder’s form section and page template system.

### 1.2 Scope

- **In scope:** Shortcode and (optionally) block embedding, form identifier format, output and security expectations, optional APIs for listing forms. Everything the form plugin must or should expose for embedding and (optionally) for picker UIs.
- **Out of scope:** How the form plugin handles submissions, email, storage, or validation internally. How the page builder’s UI looks. Specific ACF field keys or section template internal_keys (those are defined by the page builder implementation).

### 1.3 Non-goals

- This contract does **not** require the form plugin to be aware of AIO Page Builder. The form plugin only needs to expose a shortcode (and optionally a block and list API) that any consumer can use.
- This contract does **not** define a single “form provider API” that all plugins must implement in PHP. It defines **behavior and syntax** (shortcode format, block format, safe output) that the page builder will rely on.

---

## 2. Definitions and glossary

| Term | Definition |
|------|------------|
| **Form provider** | A WordPress plugin (or theme) that creates and manages forms and exposes at least one shortcode to embed a form by identifier. |
| **Form identifier** | A stable, unique value that selects one form within a provider (e.g. numeric ID, post ID, or slug). |
| **Form section** | A section template in AIO Page Builder whose purpose is to display a single form from a form provider. |
| **Form reference** | The pair (provider identifier, form identifier) stored in section field data and used to generate the shortcode or block when assembling the page. |
| **Provider identifier** | A stable string that identifies the form plugin (e.g. `wpforms`, `contact-form-7`, `my_form_plugin`). Used by the page builder to know which shortcode/block to emit. |
| **Embed string** | The exact shortcode or block markup that, when placed in `post_content`, causes WordPress to display the form (e.g. `[wpforms id="123"]`). |
| **Durable content** | Content saved in `post_content` that displays correctly when the page is viewed; for forms, this typically means the shortcode (or block) is stored in the page and expanded at view time by the form plugin. |
| **Form list API** | Optional programmatic way to retrieve the set of forms (id and label) for a provider, so the page builder or ACF can populate a dropdown. |

---

## 3. AIO Page Builder context

### 3.1 How section content becomes page content

- A **section template** defines structure and a **field blueprint** (ACF fields). When a page is built from a page template, each section instance has field values stored in ACF post meta (e.g. `group_aio_{section_key}`).
- The page builder’s **block assembly pipeline** reads those field values and turns them into block markup (e.g. `core/html` or GenerateBlocks blocks). That markup is saved into the page’s `post_content`.
- Currently, all field values are escaped as plain text. To support forms, the pipeline will be extended so that a designated “form reference” field (e.g. provider + form id) is **not** escaped; instead, the pipeline will **generate** the appropriate shortcode (or block) string and inject it into the section’s output. WordPress will then expand that shortcode when the page is viewed (assuming the form plugin is active).

### 3.2 What the page builder needs from the form plugin

- A **deterministic shortcode syntax** so that, given a provider id and a form id, the page builder can construct the embed string (e.g. `[wpforms id="123"]`) and save it into `post_content`.
- **Stable form identifiers** so that the same form can be referenced reliably across pages and over time.
- **Safe output**: the form plugin must not require the page builder to output unescaped user input; the page builder will only output a shortcode it constructed from validated provider + form id.
- Optionally: a **block** and/or a **form list API** for better UX (dropdown of forms, block-based embedding).

### 3.3 Survivability and dependencies

- Content in `post_content` that contains a form shortcode **depends on the form plugin being active** at view time. The page builder may document this as “form section requires [Provider X]” and may classify such content as “survivable only when form plugin is present” for diagnostics. The form plugin does not need to change; the page builder will handle this classification.

---

## 4. Contract overview

| Requirement | Level | Summary |
|-------------|--------|---------|
| Shortcode that accepts a form identifier | **MUST** | Embedding a form by id (or slug) via shortcode is required. |
| Stable, documented shortcode name and attributes | **MUST** | Page builder must be able to construct the shortcode from (provider, form_id). |
| Safe rendering (no raw unsanitized input in embed) | **MUST** | Form plugin must render safely when shortcode is built from trusted form id. |
| Form identifier format documented | **MUST** | Numeric id, slug, or other; must be documented and stable. |
| Block embedding | **SHOULD** | Recommended for block editor parity and clarity. |
| Form list API (PHP and/or REST) | **SHOULD** | Recommended so the page builder or ACF can show a form picker. |
| Provider identifier | **MUST** | A stable slug for the provider (e.g. `wpforms`) so the page builder can map to the correct shortcode. |

---

## 5. Required: Shortcode embedding

### 5.1 Shortcode requirement

The form plugin **MUST** register at least one WordPress shortcode that embeds a single form. The shortcode **MUST** accept a form identifier (id or slug) so that the page builder can embed a specific form by storing only that identifier.

### 5.2 Shortcode name

- The shortcode **tag** (e.g. `wpforms`, `contact-form-7`) must be **documented** and **stable**.
- The page builder will maintain a mapping: `provider_id` → shortcode tag. For example:
  - `wpforms` → `wpforms`
  - `contact-form-7` → `contact-form-7`
  - Custom plugin → whatever tag the plugin uses (e.g. `my_forms`)

The form plugin must document the exact tag it registers.

### 5.3 Shortcode attributes (form identifier)

- The shortcode **MUST** accept at least one attribute that uniquely identifies the form. Common patterns:
  - **ID-based:** `id="123"` (numeric post ID or form ID).
  - **Slug-based:** `slug="contact"` or `title="Contact"` if the plugin supports it.
- The attribute name(s) (e.g. `id`, `slug`) must be **documented** so the page builder knows how to build the shortcode.
- Example formats the page builder will generate:
  - `[wpforms id="123"]`
  - `[contact-form-7 id="456" title="Contact"]`
  - `[my_form_plugin form_id="789"]`

The form plugin must document which attribute(s) are supported and what format the value must have (integer, string, etc.).

### 5.4 Shortcode syntax (canonical form)

The form plugin must document the **canonical** shortcode form that will be used when the page builder has only:
- Provider identifier (maps to shortcode tag).
- Form identifier (one value: id or slug).

If the shortcode supports multiple attributes (e.g. `id` and `title`), document whether `id` alone is sufficient and whether `title` is optional or required. The page builder will prefer the minimal form that uniquely identifies the form (e.g. `[wpforms id="123"]`).

### 5.5 Behavior when form is missing

If the form identifier refers to a form that no longer exists (deleted, trashed), the shortcode handler **SHOULD**:
- Output nothing, or
- Output a minimal fallback (e.g. “Form not found.”) that is safe (escaped, no script execution).

It **MUST NOT** throw a fatal error or output unescaped user data. The page builder will not pass user input into the shortcode; it will only pass a stored form id that was previously chosen in the admin.

---

## 6. Required: Form identifier

### 6.1 Stability

The form identifier (id or slug) used in the shortcode **MUST** be stable: the same value must continue to refer to the same form until the form is deleted or the plugin’s data model changes in a documented way. Prefer numeric IDs or immutable slugs.

### 6.2 Uniqueness

Within a single form provider, the identifier **MUST** uniquely identify one form. The page builder will store one form identifier per form section instance.

### 6.3 Documentation

The form plugin must document:
- Whether the identifier is numeric (e.g. post ID), a string slug, or something else.
- How an implementer or admin can discover the identifier (e.g. in the form list UI, in the shortcode snippet the plugin shows when editing a form).

---

## 7. Required: Output behavior and security

### 7.1 No unsanitized input from page builder

The page builder will **only** inject a shortcode string it built from:
- A **provider identifier** (from its own config or section field schema).
- A **form identifier** (from ACF field value, which will be validated/sanitized by the page builder before use).

The page builder will **not** pass arbitrary user-submitted HTML or URL parameters into the shortcode. The form plugin’s shortcode callback will receive attributes set by the page builder (e.g. `id => "123"`). The form plugin **MUST**:
- Validate/sanitize the form identifier (e.g. ensure it is a valid form id in the database).
- Not output raw unsanitized data from the identifier.
- Render the form markup (and any scripts/styles) in a way that is safe for front-end display (escaping, nonces for form submission, etc., per WordPress and the plugin’s own security practices).

### 7.2 Safe fallback

If the form cannot be found or cannot be rendered, output must be safe (no PHP errors, no unescaped user content). Prefer empty output or a short, escaped message.

### 7.3 Scripts and styles

The form plugin may enqueue scripts and styles when the shortcode is rendered. This is expected. The page builder does not control enqueue behavior; the form plugin remains responsible for only loading assets when the form is present (e.g. on the page) and for not breaking the rest of the page.

---

## 8. Recommended: Block embedding

### 8.1 Block support

If the form plugin registers a **block** that embeds a form (e.g. “WPForms” block, “Contact Form 7” block), that block **SHOULD**:
- Accept the same logical “form identifier” (e.g. stored in block attributes as `formId` or `id`).
- Produce the same form on the front as the shortcode when given the same form identifier.

This allows the page builder to optionally emit block markup instead of a shortcode, for better block-editor parity and clarity in `post_content`.

### 8.2 Block format (if implemented)

The form plugin **SHOULD** document:
- Block name (e.g. `wpforms/form-selector`, `contact-form-7/contact-form-selector`).
- Block attributes that carry the form identifier (e.g. `formId: "123"`).
- Whether the block saves as static markup or uses a shortcode internally; if it saves a shortcode, the page builder can either emit the shortcode in a `core/html` block or emit the form block if the schema is known.

If the page builder is to emit the block by name and attributes, the form plugin must document the attribute names and value types so the page builder can construct the block markup (or use the block parser) when assembling the page.

### 8.3 Parity with shortcode

When the same form is embedded via shortcode and via block, the visible form and behavior **SHOULD** be the same (same fields, same submit behavior, same styling). The block is an alternative **embedding mechanism**, not a different form type.

---

## 9. Recommended: Form list API

### 9.1 Purpose

So that the page builder (or ACF) can show a **form picker** (dropdown or list) in the admin, the form plugin **SHOULD** expose a way to retrieve the list of available forms. Each item should include at least:
- **Form identifier** (the value that will be used in the shortcode/block).
- **Label** (title or name of the form) for display in the UI.

### 9.2 PHP API

A PHP function or method that returns an array of forms is sufficient. For example:

```php
/**
 * Returns an array of forms for use in selectors/dropdowns.
 *
 * @return array<int, array{id: string|int, label: string}>
 */
function my_form_plugin_get_forms_for_picker() {
    // Return e.g. [ ['id' => 123, 'label' => 'Contact'], ['id' => 456, 'label' => 'Newsletter'] ]
}
```

The form plugin should document the function name and the shape of the returned array (id/key name, label key name, and whether id is string or int).

### 9.3 REST API (optional)

If the form plugin exposes a REST endpoint that returns the same list (id + label), that endpoint **SHOULD** be protected (e.g. only for users with permission to edit posts or manage options) and **SHOULD** return a simple structure, e.g.:

```json
{
  "forms": [
    { "id": 123, "label": "Contact" },
    { "id": 456, "label": "Newsletter" }
  ]
}
```

The page builder can use this to populate a dynamic dropdown via JavaScript if it chooses. Document the route and response shape.

### 9.4 Caching

The list of forms may be cached by the page builder or by ACF for performance. The form plugin does not need to implement cache invalidation; the page builder will decide when to refetch (e.g. on load of the form picker field).

---

## 10. Recommended: Form picker integration

### 10.1 ACF field

The page builder will add at least one ACF field to form sections: a field that stores the **form reference**. That reference will consist of:
- **Provider** (e.g. `wpforms`, `contact-form-7`, `my_form_plugin`). May be a separate select field or fixed per section template.
- **Form identifier** (the id or slug of the form). Ideally this is a **select** or **dropdown** populated with the form list from the provider.

If the form plugin provides a PHP (or REST) form list API, the page builder (or an ACF field type / helper) can use it to build the choices for the form-id field. The form plugin only needs to expose the list; it does not need to implement an ACF field type.

### 10.2 Manual entry fallback

If no form list API is available, the page builder may provide a **text** or **number** field where the editor types the form id. The form plugin must then accept that id in the shortcode (and optionally in the block) as documented.

---

## 11. Security and sanitization

### 11.1 Form plugin responsibilities

- **Shortcode / block handler:** Validate the form identifier (type, range, existence). Use prepared statements or equivalent when querying by id. Escape all output intended for HTML. Use nonces and capability checks for form submission handling (unchanged from the form plugin’s normal behavior).
- **Form list API:** Restrict to users who are allowed to edit content or manage options (as appropriate). Do not expose sensitive form data (e.g. field configurations or submissions) in the list; id and label are sufficient.
- **No execution of user-supplied code:** The page builder will not send code or markup in the form reference; the form plugin must not interpret the form identifier as code or allow injection.

### 11.2 Page builder responsibilities

- Store only **provider id** and **form id** (or slug) in section field data. Validate and sanitize these before constructing the shortcode/block (e.g. form id as integer or alphanumeric slug).
- Construct the shortcode string server-side from trusted values only. Do not allow the editor to type raw shortcode strings that could include arbitrary shortcodes; use a structured form reference (provider + form id) and then build the embed string in code.
- When populating a form picker from an API, ensure the request is authenticated and authorized; do not expose the form list endpoint to unauthenticated users if the form plugin restricts it.

---

## 12. Examples by provider

### 12.1 Contact Form 7 (CF7)

- **Provider identifier:** `contact-form-7` (or `cf7` if the page builder maps it).
- **Shortcode tag:** `contact-form-7`.
- **Canonical shortcode:** `[contact-form-7 id="123" title="Contact"]` — document whether `id` alone is sufficient and what `title` does.
- **Form identifier:** Typically the post ID of the form (integer). Document how to obtain it (e.g. form list in admin).
- **Block:** If CF7 registers a block, document block name and attributes (e.g. `formId`).
- **Form list:** CF7 forms are stored as a custom post type; a PHP function that returns `[ ['id' => post_id, 'label' => post_title], ... ]` would satisfy the recommended API.

### 12.2 WPForms

- **Provider identifier:** `wpforms`.
- **Shortcode tag:** `wpforms`.
- **Canonical shortcode:** `[wpforms id="123"]` — document that `id` is the form id.
- **Form identifier:** Form ID (integer). Document where it appears in the WPForms UI.
- **Block:** Document the WPForms block name and attribute for form id.
- **Form list:** WPForms likely has an internal API to list forms; document or expose a simple `id` + `label` list for the picker.

### 12.3 Custom form plugin (e.g. Cursor-built)

- **Provider identifier:** Choose a stable slug (e.g. `my_company_forms`). Document it.
- **Shortcode tag:** Register a shortcode (e.g. `[my_company_forms id="456"]`). Document tag and attribute name and value format.
- **Form identifier:** Define whether forms are identified by numeric id, slug, or both. Ensure the same identifier is used in the shortcode and (if applicable) in the block and in the form list API.
- **Block:** If the plugin supports the block editor, register a block that accepts the same form identifier and document block name and attributes.
- **Form list:** Implement a PHP function (and optionally a REST endpoint) that returns an array of `id` and `label` for each form. Document the function name and return shape.

---

## 13. What AIO Page Builder will do with this

The following is a commitment of the AIO Page Builder side once the form provider satisfies this contract. It is for the form plugin developer’s awareness; implementation details may change, but the intent is stable.

### 13.1 Section templates

- The page builder will provide one or more **section templates** whose purpose is to display a single form (e.g. category `cta_conversion` or a dedicated form category). Each such section will have a **field blueprint** that includes:
  - A field for **form provider** (select: e.g. WPForms, Contact Form 7, Custom) and/or a fixed provider per section template.
  - A field for **form identifier** (select populated from the provider’s form list if available, or text/number for manual id).
- The section may also include optional fields (e.g. heading above the form, intro text) that are rendered as normal ACF content; only the form reference field will be turned into an embed.

### 13.2 Block assembly pipeline

- When assembling the page’s block content, the pipeline will detect section instances that include a form reference (provider + form id). For those sections, it will:
  - Build the canonical shortcode string (or block markup) from the stored provider and form id, using the provider’s documented shortcode tag and attribute.
  - Inject that shortcode (or block) into the section’s output instead of escaping the form id as plain text.
- The rest of the section (wrapper, optional heading, intro) will follow the existing rendering rules. The form shortcode/block will appear inside the section’s wrapper in `post_content`.

### 13.3 Page templates

- The page builder will provide **page templates** (e.g. contact page, request page) that include a form section in `ordered_sections`. Editors will choose a form (via the form picker or manual id) when editing the page or when the page is built from the template.

### 13.4 Provider registry (page builder side)

- The page builder will maintain a **provider registry** (config or code) that maps provider identifiers to:
  - Shortcode tag.
  - Attribute name(s) for form identifier.
  - Optional: block name and attribute map; PHP function or REST endpoint for form list.
- Form plugins do not need to register with the page builder; the page builder will add entries for each supported provider (CF7, WPForms, custom) according to the provider’s documented shortcode/block/API.

### 13.5 Survivability and diagnostics

- Content that contains a form shortcode will be considered to have a **runtime dependency** on the corresponding form plugin. The page builder may document this and may report it in diagnostics (e.g. “Form section requires WPForms”). The form plugin does not need to implement anything specific for this.

---

## 14. Checklist for form plugin developers

Use this checklist to confirm that your form plugin meets the contract. When all “MUST” items are satisfied, the plugin is **compatible** for use in AIO Page Builder form sections. “SHOULD” items improve UX and are recommended.

### Required (MUST)

- [ ] **Shortcode:** The plugin registers a shortcode that embeds a single form.
- [ ] **Form identifier:** The shortcode accepts at least one attribute (e.g. `id`) that uniquely identifies the form. The attribute name and value format are documented.
- [ ] **Documentation:** The canonical shortcode syntax (tag + attribute) is documented so the page builder can construct the shortcode from (provider_id, form_id).
- [ ] **Stable identifier:** The same form identifier continues to refer to the same form until the form is deleted or the data model changes (documented).
- [ ] **Safe output:** When the shortcode is called with a valid form id (and no user-supplied HTML), the plugin outputs safe markup. When the form is missing, it does not fatal or output unescaped user data.
- [ ] **Provider identifier:** A stable provider slug (e.g. `wpforms`, `contact-form-7`, `my_forms`) is documented for the page builder’s provider registry.

### Recommended (SHOULD)

- [ ] **Block:** A block is registered that embeds the same form by the same identifier; block name and attributes are documented.
- [ ] **Form list (PHP):** A PHP function (or method) returns a list of forms with `id` and `label`; function name and return shape are documented.
- [ ] **Form list (REST):** Optionally, a REST endpoint returns the same list; route and response shape are documented and the endpoint is protected by capability.
- [ ] **Fallback:** If the form is not found, the shortcode (and block) outputs nothing or a short, escaped message.

### Documentation to provide

- [ ] Shortcode tag and canonical example (e.g. `[wpforms id="123"]`).
- [ ] Attribute name(s) and value type for the form identifier.
- [ ] How to obtain the form identifier (e.g. form list in admin, shortcode snippet).
- [ ] Provider identifier (slug) to use in the page builder’s provider registry.
- [ ] (If block) Block name and attribute(s) for form id.
- [ ] (If form list API) Function name and return array shape, and optionally REST route and response.

---

## 15. Revision history

| Version | Date | Author | Summary |
|---------|------|--------|---------|
| 1.0 | 2025-03-12 | AIO Page Builder | Initial form provider integration contract for section and page templates. |

---

*End of Form Provider Integration Contract.*
