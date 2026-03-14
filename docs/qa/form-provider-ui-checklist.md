# Form Provider UI Checklist

**Document type:** QA checklist for form provider and form ID editing UI (Prompt 228).  
**Purpose:** Verify that form_provider, form_id, and headline are exposed and editable in a governed way; provider list and validation states are clear.  
**Spec refs:** §20.1–20.4, §20.6, §49.6, §49.7, §50.1–50.2, §51.9.

---

## 1. Scope

- **In scope:** Section template detail screen form binding panel; ACF-backed fields (form_provider, form_id, headline) when editing pages that use form sections; labels, helper text, empty/stale/missing states; Form_Section_Field_State_Builder and its integration.
- **Out of scope:** New providers; provider-side form creation; raw shortcode text entry; replacing existing screen architecture.

---

## 2. Checklist

### 2.1 Section template detail (form_embed)

- [ ] Opening the section template detail for `form_section_ndr` (or any section with category `form_embed`) shows a **Form binding** subsection in the metadata panel when Form_Provider_Registry is available.
- [ ] Form binding shows: **Form provider** (value and hint with registered provider IDs), **Form identifier**, and **Shortcode** (when provider and form_id are valid).
- [ ] Labels use the strings from Form_Section_Field_State_Builder (Form provider, Form identifier, Heading (optional)).
- [ ] When provider is missing or form_id invalid, **messages** list is shown (e.g. "Form provider is not set", "Selected form provider is not registered", "Form ID is not set").

### 2.2 ACF fields (page/section editing)

- [ ] When editing a page that contains a form section, ACF shows fields for form_provider, form_id, and headline (per section blueprint).
- [ ] Field labels and instructions match the blueprint (form-provider-integration-contract); no raw shortcode entry.
- [ ] Saved values for form_provider and form_id load correctly and persist on save.

### 2.3 Provider list and fallback

- [ ] Form binding state shows **registered_provider_ids** (e.g. ndr_forms) so operators know which providers are available.
- [ ] If no provider picker/select is implemented, manual entry for form_provider and form_id is possible with validation (instructions in blueprint).

### 2.4 Missing / stale states

- [ ] **Missing provider:** When form_provider value is not in the registry, a warning/message is shown; shortcode preview is not shown.
- [ ] **Missing form:** When form_id is empty, message indicates form ID is required.
- [ ] **Stale form:** (Optional) If a provider form list API is added later, stale-form state can be surfaced; not required for current scope.

### 2.5 Permission and safety

- [ ] Detail screen and any edit flows remain capability-gated; no provider/system metadata exposed to unauthorized roles.
- [ ] Provider IDs and form IDs are sanitized before display and before save.

### 2.6 Save/reload integrity

- [ ] Saving form_provider and form_id on a page that uses the form section persists correctly; reload shows the same values and shortcode preview when valid.

---

## 3. Risk notes

- **ACF field type:** If form_provider is implemented as a text field, operators must type the provider slug; a select populated from Form_Provider_Registry improves UX but requires blueprint or group-builder augmentation. Checklist item 2.2 is satisfied with text + clear instructions.
- **Detail screen:** Form binding panel depends on `form_section_field_state` in state; if Form_Provider_Registry is not in the container, the panel is omitted and no error is shown.
