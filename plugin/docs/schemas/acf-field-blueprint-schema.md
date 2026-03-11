# ACF Field Blueprint Schema

**Document type:** Implementation-grade schema contract for section-owned ACF field blueprints (spec §7.3, §20, §21, §59.5).  
**Governs:** Field blueprint structure, supported field types, validation metadata, nested/repeatable rules, LPagery compatibility annotations.  
**Related:** section-registry-schema.md (`field_blueprint_ref`), master spec §12.8 Section Blueprint Structure, §20 ACF Architecture, §21 LPagery Token Compatibility, §22.10 Asset Intake Rules. Key generation follows **acf-key-naming-contract.md**.

---

## 1. Purpose and scope

A **field blueprint** is the formal definition of the ACF field group owned by a section template. It specifies:

- Root blueprint structure and required properties
- Per-field definitions with type, label, requiredness, validation, and editorial metadata
- Supported ACF field types and type-specific constraints
- Nested and repeatable structures (repeater rules; flexible used cautiously)
- LPagery/token compatibility annotations per field where applicable
- Incompleteness and ineligibility rules for blueprints
- Linkage to section template (section key, version)

Blueprints **must not** carry executable logic, secrets, or arbitrary code. Blueprint mutation is admin-governed and not exposed to front-end users. Server-side validation remains authoritative.

---

## 2. Root blueprint structure

Each section template owns **one** field-group blueprint. The root blueprint object has this structure:

| Field name | Type | Required | Validation | Notes |
|------------|------|----------|------------|--------|
| `blueprint_id` | string | Yes | Non-empty; pattern `^[a-z0-9_]+$`; max 64 chars; e.g. `acf_blueprint_st01` | Stable blueprint identifier. Must align with `field_blueprint_ref` in section template. |
| `section_key` | string | Yes | Non-empty; matches section `internal_key` | Links blueprint to owning section. |
| `section_version` | string | Yes | Non-empty; e.g. `1`, `1.0`; max 32 chars | Version marker for compatibility. |
| `label` | string | Yes | Non-empty; max 255 chars | Human-readable field group label. |
| `description` | string | No | Max 512 chars | Editorial guidance for the field group. |
| `fields` | array | Yes | Non-empty; each element per §4 Field definition | Ordered list of field definitions. |
| `location_rules_hint` | string | No | Max 256 chars | Hint for programmatic visibility; e.g. `page_template`, `composition`. Not executed directly. |
| `variant_overrides` | object | No | Keys = variant keys; values = field override sets | Per-variant field visibility or overrides. |

---

## 3. Required blueprint properties checklist

A blueprint is **complete** only when:

- [ ] `blueprint_id` — stable identifier
- [ ] `section_key` — owning section internal_key
- [ ] `section_version` — version marker
- [ ] `label` — field group label
- [ ] `fields` — at least one field defined
- [ ] Each field has `key`, `type`, `label` per §4

A blueprint missing any required property is **incomplete** and **ineligible** for ACF registration.

---

## 4. Per-field definition schema

Each element in `fields` has this shape:

| Field | Type | Required | Validation | Notes |
|-------|------|----------|------------|--------|
| `key` | string | Yes | Pattern `^field_[a-z0-9_]+$`; max 64 chars; unique within blueprint | Deterministic field key per **acf-key-naming-contract.md**. |
| `name` | string | Yes | Non-empty; pattern `^[a-z0-9_]+$`; max 64 chars | ACF field name (often same as key stem). |
| `label` | string | Yes | Non-empty; max 255 chars | Display label. |
| `type` | string | Yes | One of supported types (§5) | ACF field type. |
| `required` | boolean | No | Default false | Required for section validity when true. |
| `instructions` | string | No | Max 512 chars | Editorial instructions for the editor. |
| `default_value` | mixed | No | Per-type validation | Default or placeholder; clearly distinguishable from user content. |
| `placeholder` | string | No | Max 255 chars | Placeholder text where applicable. |
| `validation` | object | No | Per §6 Validation metadata | Validation rules. |
| `conditional_logic` | array | No | ACF-style conditional logic | When field is shown. |
| `lpagery` | object | No | Per §8 LPagery annotations | Token compatibility notes. |
| `wrapper` | object | No | `width`, `class`, etc. | Layout hints. |
| `sub_fields` | array | Conditional | Required when `type` is `repeater` or `group` | Nested field definitions. |
| `layout` | string | No | For repeater/group: `block`, `table`, `row` | Layout preference. |
| `min` / `max` | number | No | For repeater | Min/max rows. |
| `button_label` | string | No | For repeater | Add row button label. |

**Distinction:** `key` is the full deterministic ACF field key; `name` is the shorter identifier. Labels are human-readable and may change for display; keys are stable.

---

## 5. Supported field types and type matrix

The blueprint **shall** use only these field types. Undocumented or arbitrary ACF structures are **prohibited**.

| Type | ACF type | Required metadata | Optional metadata | LPagery support | Notes |
|------|----------|-------------------|-------------------|-----------------|--------|
| `text` | text | key, name, label, type | required, instructions, default_value, placeholder, maxlength | Supported (§21.3) | Single-line text. |
| `textarea` | textarea | key, name, label, type | required, instructions, default_value, placeholder, rows | Supported | Multi-line text. |
| `number` | number | key, name, label, type | required, instructions, default_value, min, max, step | Partial (where numeric token) | Numeric value. |
| `url` | url | key, name, label, type | required, instructions, default_value, placeholder | Supported | URL field. |
| `email` | email | key, name, label, type | required, instructions, default_value | Supported | Email address. |
| `wysiwyg` | wysiwyg | key, name, label, type | required, instructions, default_value, toolbar, media_upload | Supported (with constraints §20.10) | Rich text. Use only where richer formatting is genuinely useful. |
| `image` | image | key, name, label, type | required, instructions, return_format, preview_size, library | Unsupported for attachment ref (§21.4) | Single image. Alt-text responsibility; see §20.9. |
| `gallery` | gallery | key, name, label, type | required, instructions, return_format, preview_size, min, max | Unsupported | Multi-image. Avoid when single image suffices. |
| `link` | link | key, name, label, type | required, instructions, return_format | Supported (URL, title) | Link object. |
| `select` | select | key, name, label, type, choices | required, instructions, default_value, allow_null, multiple | Supported for simple choice mapping | Controlled choices. |
| `true_false` | true_false | key, name, label, type | required, instructions, default_value, ui | Partial (where boolean token) | Boolean. |
| `relationship` | relationship | key, name, label, type | required, instructions, post_type, filters, return_format | Unsupported (§21.4) | Entity linking. |
| `repeater` | repeater | key, name, label, type, sub_fields | required, instructions, layout, min, max, button_label | Partial (subfield token map required) | Repeated structured content. Stable subfield schema. |
| `group` | group | key, name, label, type, sub_fields | required, instructions, layout | Partial | Nested group; no repetition. |
| `color_picker` | color_picker | key, name, label, type | required, instructions, default_value | Partial (design token ref) | Color value. |

**Prohibited or avoid:** `flexible_content` (overly open-ended; use repeaters with stable schemas). `file` without explicit asset-intake alignment. `post_object`, `page_link`, `taxonomy` unless explicitly governed. Arbitrary `message` or `accordion` as structural fields without schema.

---

## 6. Validation and requiredness model (spec §20.4, §20.6, §20.12)

### 6.1 Validation metadata object

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| `required` | boolean | No | Field must have value for section validity. |
| `url` | boolean | No | Value must be valid URL. |
| `number` | object | No | `min`, `max`, `step` constraints. |
| `pattern` | string | No | Regex for text validation. |
| `maxlength` | number | No | Max character count. |
| `min_rows` | number | No | Repeater minimum (when type is repeater). |
| `max_rows` | number | No | Repeater maximum. |
| `warning_if_empty` | boolean | No | Non-blocking editorial warning. |
| `variant_dependent` | string | No | Validation applies only for listed variants. |

### 6.2 Requiredness rules

- **Required fields:** Must have a value for the section to be considered valid. Block save or block render when empty where product logic requires it.
- **Optional fields:** Enhance but do not define the section. May trigger `warning_if_empty` for best practice.
- **Conditional fields:** Shown only when `conditional_logic` matches. Requiredness applies when visible.
- **Page-template awareness:** A field may be critical in one page-template context and less important in another; document via `instructions` or `location_rules_hint`.

---

## 7. Nested and repeatable structures (spec §20.5, §20.8)

### 7.1 Repeater rules

- Use repeaters when repeated items share a **stable subfield schema**.
- Each repeater **must** define `sub_fields` with same per-field schema as §4.
- **Avoid** unnecessary nesting depth (prefer one level of repeater).
- Supported patterns: cards, bullets, stats, FAQs, process steps.
- `min` and `max` recommended for controlled row counts.
- **Prohibited:** Recursive repeaters (repeater inside repeater without explicit approval). Unbounded repeaters without max.

### 7.2 Group rules

- Use `group` for nested structures that are **not** repeated.
- `sub_fields` required. Same schema as §4.
- Prefer flat structures where clarity is preserved.

### 7.3 Flexible content

- **Avoid** flexible content by default. Favor repeaters with stable schemas.
- If used: each layout must be fully defined; no undocumented layouts.
- Product favors controlled structure over freeform complexity.

---

## 8. LPagery compatibility annotations (spec §21)

### 8.1 Per-field `lpagery` object

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| `token_compatible` | boolean | No | True when field accepts tokenized values. |
| `token_name` | string | No | Expected token reference name (e.g. `{{location_name}}`). |
| `injection_notes` | string | No | Max 256 chars. How token is injected. |
| `fallback_behavior` | string | No | `fail`, `warn`, `use_default` when token missing. |
| `unsupported_reason` | string | No | When token_compatible false; explains why. |

### 8.2 LPagery supported types (summary)

| Field type | Token support |
|------------|---------------|
| text, textarea, url, email | Supported |
| wysiwyg | Supported with structural constraints |
| link (URL, title) | Supported |
| select (simple mapping) | Supported |
| image (URL only; not attachment ref) | Partial |
| repeater (with token map per subfield) | Partial |
| relationship, gallery (attachment ref) | Unsupported |
| highly nested structures | Unsupported |

### 8.3 Unsupported / partially supported

- Highly nested repeaters without clear token-map logic
- Relationship fields (require local object refs)
- Media-library attachment selection
- Variant selectors controlled by template logic
- Fields where token injection could destabilize markup

---

## 9. Defaults and placeholders (spec §20.7)

- Defaults and placeholders **guide** expected content shape.
- Must remain **clearly distinguishable** from user-entered content.
- Avoid encouraging lazy production content.
- Support onboarding and build efficiency.
- Do not become accidental live copy unless intentionally accepted.
- Document in `instructions` when default is example-only.

---

## 10. Asset intake alignment (§22.10)

When a blueprint includes image or file fields:

- **Validation:** File type restrictions where files are accepted.
- **Required vs optional:** Distinguish required and optional asset fields.
- **Safe handling:** Stored asset refs (attachment_id, path, or URL) must be validated.
- **No secrets:** No API keys, passwords, or tokens in asset refs.
- **Alt-text:** Image fields should document alt-text responsibility per §20.9.

---

## 11. Blueprint incompleteness and ineligibility rules

A blueprint is **incomplete** and **ineligible** for ACF registration when:

1. Any required root property (§2) is missing or empty.
2. `fields` is empty.
3. Any field lacks `key`, `name`, `label`, or `type`.
4. Any field `type` is not in the supported type list (§5).
5. A repeater/group lacks `sub_fields` or has invalid sub_fields.
6. `blueprint_id` does not match `field_blueprint_ref` of the owning section.
7. `section_key` does not match section `internal_key`.
8. Recursive or prohibited nested structures (§7) are used without explicit approval.

---

## 12. Relationship to section templates

| Section field | Blueprint link |
|---------------|----------------|
| `internal_key` | Maps to blueprint `section_key` |
| `field_blueprint_ref` | Must equal blueprint `blueprint_id` |
| `version.version` | Maps to blueprint `section_version` |

The section template references the blueprint by `field_blueprint_ref`. The blueprint references the section by `section_key` and `section_version`. This bidirectional link ensures deterministic registration and migration.

---

## 13. Example: valid minimal blueprint

```json
{
  "blueprint_id": "acf_blueprint_st01",
  "section_key": "st01_hero",
  "section_version": "1",
  "label": "Hero Section Fields",
  "description": "Headline, subhead, and optional CTA for hero section.",
  "fields": [
    {
      "key": "field_st01_headline",
      "name": "headline",
      "label": "Headline",
      "type": "text",
      "required": true,
      "instructions": "Primary hero headline. Keep concise.",
      "validation": { "required": true, "maxlength": 120 },
      "lpagery": { "token_compatible": true, "token_name": "{{headline}}" }
    },
    {
      "key": "field_st01_subheadline",
      "name": "subheadline",
      "label": "Subheadline",
      "type": "textarea",
      "required": false,
      "instructions": "Supporting text below the headline."
    },
    {
      "key": "field_st01_cta",
      "name": "cta",
      "label": "CTA Link",
      "type": "link",
      "required": false,
      "instructions": "Optional call-to-action button.",
      "lpagery": { "token_compatible": true }
    }
  ]
}
```

---

## 14. Example: valid blueprint with repeater

```json
{
  "blueprint_id": "acf_blueprint_st05_faq",
  "section_key": "st05_faq",
  "section_version": "1",
  "label": "FAQ Section Fields",
  "fields": [
    {
      "key": "field_st05_faq_section_title",
      "name": "section_title",
      "label": "Section Title",
      "type": "text",
      "required": true
    },
    {
      "key": "field_st05_faq_items",
      "name": "faq_items",
      "label": "FAQ Items",
      "type": "repeater",
      "required": true,
      "layout": "block",
      "min": 1,
      "max": 20,
      "button_label": "Add FAQ",
      "sub_fields": [
        {
          "key": "field_st05_faq_question",
          "name": "question",
          "label": "Question",
          "type": "text",
          "required": true
        },
        {
          "key": "field_st05_faq_answer",
          "name": "answer",
          "label": "Answer",
          "type": "wysiwyg",
          "required": true
        }
      ]
    }
  ]
}
```

---

## 15. Example: invalid blueprint (missing required)

```json
{
  "blueprint_id": "acf_blueprint_bad",
  "section_key": "st01_hero",
  "section_version": "1",
  "label": "Bad Blueprint",
  "fields": [
    {
      "key": "field_st01_headline",
      "name": "headline",
      "label": "Headline",
      "type": "text"
    },
    {
      "key": "",
      "name": "subhead",
      "label": "Subhead",
      "type": "textarea"
    }
  ]
}
```

→ **Ineligible:** Second field has empty `key`. Per §4, `key` is required and non-empty.

---

## 16. Example: invalid blueprint (unsupported type)

```json
{
  "blueprint_id": "acf_blueprint_bad2",
  "section_key": "st99_bad",
  "section_version": "1",
  "label": "Bad Type",
  "fields": [
    {
      "key": "field_st99_custom",
      "name": "custom",
      "label": "Custom",
      "type": "flexible_content"
    }
  ]
}
```

→ **Ineligible:** `flexible_content` is not in the supported type list (§5). Use repeater with stable schema instead.

---

## 17. Supported field type matrix (quick reference)

| Type | Supported | Repeater sub | LPagery |
|------|-----------|--------------|---------|
| text | ✓ | ✓ | ✓ |
| textarea | ✓ | ✓ | ✓ |
| number | ✓ | ✓ | Partial |
| url | ✓ | ✓ | ✓ |
| email | ✓ | ✓ | ✓ |
| wysiwyg | ✓ | ✓ | ✓ (constrained) |
| image | ✓ | — | Unsupported (attachment) |
| gallery | ✓ | — | Unsupported |
| link | ✓ | ✓ | ✓ |
| select | ✓ | ✓ | ✓ (simple) |
| true_false | ✓ | ✓ | Partial |
| relationship | ✓ | — | Unsupported |
| repeater | ✓ | N/A | Partial |
| group | ✓ | N/A | Partial |
| color_picker | ✓ | ✓ | Partial |
| flexible_content | Avoid | — | — |

---

## 18. Security and governance

- Blueprints **must not** carry executable logic, secrets, or arbitrary code.
- Validation rules remain server-authoritative; client-side validation is supplementary.
- Blueprint mutation is admin-governed. Do not expose blueprint definition or editing to unauthenticated or non-capable users.
- Asset references: validate file types, safe handling per §22.10. No user-controlled paths without validation.
- Export: Blueprint definitions are exportable; no secrets in blueprints.
