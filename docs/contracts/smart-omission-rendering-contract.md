# Smart Omission Rendering Contract

**Spec**: §20.4–20.6 Field Requirement Rules; §20.8 Repeater and Flexible Field Usage; §17.1–17.3 Rendering Inputs/Outputs/Durable Content; §21.9 Validation and Fallback Rules (token-driven content, emptiness)

**Upstream**: rendering-contract.md, css-selector-contract.md, semantic-seo-accessibility-extension-contract.md; Field_Blueprint_Schema / field blueprint definitions (required vs optional)

**Status**: Contract definition only. No mass implementation in all renderers; no arbitrary runtime DOM stripping. Smart omission is **field-driven and schema-aware**, not heuristic DOM cleanup. Required structural anchors and CTA sequencing remain intact. Omission logic is **server-side and renderer-owned**; no unsafe front-end script-based omission; no bypass of escaping/sanitization.

---

## 1. Purpose and scope

This contract formalizes **smart omission** rules: when section and page-template renderers may **suppress empty optional elements** so that “if no content exists, do not render the element” is applied only where **reliable and contract-safe**. It defines omission eligibility by field type and render-node type, required versus optional nodes, field-driven omission conditions, accessibility implications, and **failure-safe fallback behavior**. When omission cannot be guaranteed safely, the feature **must be disabled** for that node or context; the contract states those cases explicitly.

**Out of scope**: Mass implementation across all renderers; arbitrary runtime DOM stripping; weakening of required content anchors; silent omission of mandatory accessibility content (e.g. headings that participate in outline, CTA structure).

---

## 2. Omission eligibility by field type and render-node type

### 2.1 “Empty” definition

For omission purposes, a value is **empty** when:

| Field / value type | Empty means |
|--------------------|-------------|
| Text (text, textarea, WYSIWYG) | Absent, or string after trim is empty. |
| Image | No attachment ID or URL; or attachment not available. |
| Link/URL | No URL; or URL after trim is empty. |
| Repeater / group | Zero rows; or all subfields for every row are empty per their types. |
| Select / relationship | No selection (null, empty array, or “none” equivalent). |
| Tokenized value | Token unresolved and no fallback; or fallback is empty per type above. |

**Rule**: Emptiness is evaluated **after** token replacement and fallback application (per §21.9). If a token is missing and no safe fallback exists, the field is treated as empty for omission only when the **blueprint** marks that field as optional and the render node is omission-eligible.

### 2.2 Omission eligibility by render-node type (element role)

Eligibility is **per render node** (one output element or block corresponding to one field or one logical unit). Alignment with css-selector-contract §3.4 element roles.

| Render-node / element role | Omission eligible when empty? | Condition |
|----------------------------|-------------------------------|-----------|
| `eyebrow` | Yes | Field optional per blueprint; value empty. |
| `headline` | **No** (see §3) | Required for outline and section identity; never omit. |
| `subheadline` | Yes | Field optional; value empty. |
| `intro` | Yes | Field optional; value empty. |
| `media` | Yes | Field optional; no image/URL. |
| `media-caption` | Yes | Field optional; value empty. |
| `content` | Yes (with wrapper-collapse rules) | Field optional; see §5. |
| `cards` (container) | Yes when zero cards | Repeater has 0 rows; omit entire cards wrapper. |
| `card` (single) | N/A | Cards container controls; individual card omitted only by not rendering a row. |
| `cta` | **No** when structural CTA required (see §3) | CTA structure preserved; label may be fallback text. |
| `cta-group` | Yes when zero CTAs | All CTA fields empty and optional; omit group wrapper. |
| `list` (container) | Yes when zero items | Repeater/list has 0 rows; omit list wrapper. |
| `list-item` | N/A | Omitted by not rendering a row. |
| `faq-item` | Omit row when Q&A both empty | Per repeater row; both question and answer empty → omit that item. |
| `disclosure` | Yes when no content | No question/answer content; omit disclosure block. |
| `badge` | Yes | Field optional; value empty. |
| `note` | Yes | Field optional; value empty. |
| `footer` | Yes | Field optional; value empty. |

### 2.3 Omission eligibility by field type (blueprint)

| Field type (ACF / blueprint) | Omission eligible when optional? | Notes |
|-------------------------------|----------------------------------|--------|
| text, textarea | Yes | Empty string after trim. |
| WYSIWYG | Yes | Empty or only whitespace/formatting. |
| image | Yes | No image selected or available. |
| link | Yes | No URL or empty URL. |
| repeater | Yes (container) | Zero rows → omit container and all children. |
| group | Yes (wrapper) | When all subfields are empty and group wrapper is optional; see §5. |
| select, relationship | Yes | No selection. |
| true_false, checkbox | No omission of element | Render as present or use default; boolean is never “missing” for structure. |
| file, gallery | Yes | No items when optional. |

**Required fields**: If the blueprint marks a field as **required**, the renderer must **not** omit its node based on emptiness alone. Required fields may still have fallback or placeholder output per schema/validation rules; omission applies only to **optional** fields.

---

## 3. Required versus optional node rules

### 3.1 Nodes that must never be omitted (structural / accessibility)

| Node / role | Reason |
|-------------|--------|
| Section outer wrapper | Structural anchor; always present. |
| `headline` (when it supplies h1 or section h2) | semantic-seo-accessibility-extension-contract: single h1, no heading skip; outline integrity. |
| Primary CTA structure when section is CTA-classified | CTA sequencing and placement; section purpose requires at least one CTA anchor (may render with fallback label or placeholder link, but node must exist). |
| Main landmark / section semantics | Accessibility; section must remain identifiable. |
| Form controls (when form is present) | Labels and structure per §51.9; do not omit required form structure. |

### 3.2 Optional nodes (omission allowed when empty)

All other render nodes tied to **optional** fields may be omitted when the field value is empty, provided:

- The node is in the eligibility table (§2.2, §2.3).
- Omission does not leave a broken layout (e.g. orphaned wrapper; see §5).
- Omission is not disabled for safety in that context (§6).

### 3.3 Mandatory-heading refusal

If a section is designated to supply the **page h1** (e.g. hero opener), the **headline** node for that section **must not** be omitted even if the headline field is empty. The renderer must emit a heading (e.g. h1) with fallback text (e.g. page title or placeholder from schema) rather than omit the node. Same for a section that is the only supplier of an h2 for a region—do not omit that heading; use fallback.

**Invalid**: Omitting the hero headline because the headline field is empty, resulting in no h1 on the page.

**Valid**: Rendering the hero headline with a fallback (e.g. page title) when the field is empty.

---

## 4. Omission behavior by node family (summary)

| Node family | When empty | Action |
|-------------|------------|--------|
| **Images** | Optional image field; no image | Omit the media wrapper and any caption that is only for that image. |
| **Pills/badges** | Optional badge field; empty | Omit the badge element. |
| **Headings** | Subheadline, eyebrow optional; empty | Omit subheadline/eyebrow only. Never omit primary headline when it is the section title or page h1. |
| **Subheadings** | Optional; empty | Omit. |
| **Captions** | Optional media-caption; empty | Omit caption element. |
| **Proof items** | Repeater with 0 rows | Omit list/cards container and all children. |
| **Buttons / CTAs** | Single optional CTA in a multi-CTA group | Omit that CTA only. Do not omit the entire cta-group if at least one CTA is present. When section is CTA-classified and has one primary CTA, do not omit that CTA node (render with fallback if needed). |
| **Grouped wrappers** | Group with all subfields empty; wrapper optional | See §5 (wrapper collapse). |

---

## 5. Nested omission rules and wrapper-collapse rules

### 5.1 Repeater: zero rows

When a repeater has **zero rows**:

- Omit the **container** node (e.g. list, cards, faq list).
- Do not emit wrapper elements for that repeater (no empty `<ul>`, no empty cards div).
- If the repeater is the **only** content inside a parent wrapper (e.g. section inner), the parent may be collapsed per §5.3 only when the parent is not a required structural anchor.

### 5.2 Repeater: some rows empty

When a repeater has one or more rows but **some rows** have all subfields empty:

- **Omit those rows** (do not render list-item, card, or faq-item for that row).
- Render only rows that have at least one non-empty subfield (or per-section policy for “all empty = omit row”).
- Container is still rendered (list/cards) with only the non-empty items.

### 5.3 Wrapper collapse

When **all children** of a wrapper are omitted (e.g. all list items omitted, or all optional content blocks omitted):

| Wrapper type | Collapse allowed? | Condition |
|--------------|-------------------|-----------|
| Section outer | No | Always keep section. |
| Inner (e.g. `__inner`) | No | Structural; keep for layout. |
| List container | Yes | Zero items → omit list wrapper. |
| Cards container | Yes | Zero cards → omit cards wrapper. |
| CTA group | Yes | Zero CTAs and section not CTA-classified → omit cta-group. |
| Content block (single optional content) | Yes | Content field empty → omit content wrapper. |
| Group (ACF group) | Yes | All subfields empty and group is optional → omit group wrapper; do not leave empty div. |

**Rule**: Collapse only when the wrapper has **no remaining visible children** after omission. Do not collapse if the wrapper is required for layout (e.g. inner) or for accessibility (e.g. section, main).

### 5.4 Invalid nested omission examples

- **Invalid**: Omitting the section wrapper because “all content is empty.” Section wrapper must remain.
- **Invalid**: Omitting the primary headline and leaving no h1/h2 for the section.
- **Invalid**: Collapsing the inner wrapper and leaving section with no inner structure when the layout expects inner (e.g. contract requires `__inner`).
- **Valid**: Repeater has 0 rows → omit list/cards wrapper only; section and inner remain.
- **Valid**: All optional children of a content block omitted → omit that content block wrapper; section and inner remain.

---

## 6. Cases where omission must be disabled for safety

Omission **must not** be applied (or must be overridden) in these cases:

| Case | Reason |
|------|--------|
| Required field per blueprint | Field is required; render with value or schema fallback; do not omit node. |
| Node supplies required heading (h1 or section h2) | Outline and accessibility; see §3.3. |
| Section is CTA-classified and node is the primary CTA | CTA structure must remain; render with fallback label/link if needed. |
| Omission would leave no visible content in section and section is required | At least one visible node or placeholder so section is not “blank” when section is required by page template. |
| Tokenized value with unresolved token and no safe fallback | Do not omit on “empty” if the empty is due to missing token; treat per §21.9 (failure state or explicit fallback). |
| Uncertainty about optional vs required | If blueprint or context does not clearly mark optional, **do not omit**; prefer rendering with fallback. |
| Repeater min items enforced | If schema enforces minimum number of items, do not omit below that minimum; render placeholders or fallback rows if defined. |

**Deterministic rule**: When in doubt, **do not omit**. Omission is allowed only when eligibility is clear and structural/accessibility requirements are preserved.

---

## 7. Interaction with tokenized content and missing token data

Per Spec §21.9:

| Situation | Omission behavior |
|-----------|--------------------|
| Field has token; token resolves to value | Value empty → apply omission rules if field optional and node eligible. |
| Field has token; token **fails** or missing; **no** fallback | Do not treat as “empty” for omission if that would silently drop content that should have been filled. Either render explicit fallback (e.g. placeholder text) or treat as failure state; do not omit without documented fallback. |
| Field has token; token fails; **fallback** defined and empty | Fallback empty → eligible for omission if field optional and node eligible. |
| Field has token; token fails; fallback defined and non-empty | Render fallback; no omission. |

**Rule**: Omission must not hide **token failure**. If the only reason content is “empty” is an unresolved token with no fallback, the renderer should not omit the node unless the contract explicitly allows “omit when token missing and no fallback” for that field (and that must not be used for required fields or required nodes).

---

## 8. Fallback behavior when omission is not applied

When a node is **not** omitted (required or ineligible) but the value is empty:

| Node type | Fallback behavior |
|-----------|--------------------|
| Headline (required) | Use schema/default fallback (e.g. page title, “Untitled”), or placeholder from blueprint. |
| CTA (primary, required) | Use fallback label (e.g. “Learn more”) and/or link (e.g. # or page URL from context); do not leave blank link or button. |
| Other required text | Use blueprint placeholder or empty string only if schema allows; prefer visible placeholder. |
| Image (when required) | Use placeholder image if defined; otherwise retain wrapper with alt or aria so structure is not broken. |

Failure-safe: **Never** emit invalid or misleading markup (e.g. empty `<a href="">`, button with no accessible name). If fallback is not defined, use a conservative default (e.g. “—” or schema-defined placeholder) rather than silent omit when the node is required.

---

## 9. Omission matrix (field / render-node family)

| Field type | Render node (element) | Required? | Empty → omit? | Notes |
|------------|------------------------|-----------|--------------|--------|
| text/textarea | eyebrow, subheadline, intro, note, badge, footer, media-caption | Optional | Yes | Trim empty. |
| text/textarea | headline (section title / h1) | Required or structural | **No** | Use fallback. |
| image | media | Optional | Yes | No image ID/URL. |
| image | media | Required | No | Use placeholder or keep wrapper. |
| link | cta | Optional (in group) | Yes | No URL. |
| link | cta (primary in CTA section) | Structural | **No** | Fallback label/URL. |
| repeater | list, cards, faq | Optional | Yes when 0 rows | Omit container. |
| repeater | list-item, card, faq-item | Per row | Omit row when row empty | Keep container. |
| group | content, etc. | Optional | Yes when all subfields empty | Collapse wrapper. |
| group | (any) | Required | No | Render with fallbacks. |

---

## 10. Accessibility implications

| Concern | Rule |
|---------|------|
| Heading outline | Never omit required heading nodes; use fallback text so outline remains valid (semantic-seo-accessibility-extension-contract). |
| CTA clarity | Do not omit primary CTA node in CTA sections; render with accessible name (fallback if needed). |
| Empty elements | Omission avoids **empty** interactive or semantic elements (e.g. empty link, empty button), which is an accessibility improvement. |
| Landmarks | Section and main structure are never omitted; landmarks remain. |
| Lists | Empty list containers are omitted (no empty `<ul>`); list semantics apply only when list has items. |

---

## 11. Valid and invalid omission examples (test requirements)

### 11.1 Valid omission

- Optional eyebrow empty → omit eyebrow element.
- Optional subheadline empty → omit subheadline.
- Optional image empty → omit media wrapper and optional caption.
- Repeater 0 rows → omit list/cards wrapper; section and inner remain.
- Optional cta-group with all CTAs empty and section not CTA-classified → omit cta-group.
- Optional note/badge/footer empty → omit that element.
- Group with all subfields empty and optional → omit group wrapper (collapse).

### 11.2 Invalid omission (must not do)

- **Mandatory-heading refusal**: Hero headline empty → must not omit; render h1 with fallback (e.g. page title).
- **CTA-structure preservation**: Section cta_classification = primary_cta and single CTA field empty → must not omit CTA node; render with fallback label/link.
- **Nested group collapse**: Section outer or section inner → must not omit even if “all content” omitted; section must have at least inner or placeholder.
- **Required field**: Field marked required in blueprint and value empty → must not omit; render with fallback or validation message.
- **Token failure with no fallback**: Unresolved token, no fallback → do not silently omit; use failure state or explicit fallback.

### 11.3 Repeater edge cases

- Repeater min 1 item; 0 rows → do not omit container if schema requires at least one item; render placeholder row or fallback per schema.
- Repeater 3 rows; rows 1 and 3 empty, row 2 has content → omit rows 1 and 3; render list with one item (row 2).

---

## 12. Cross-references

- **rendering-contract.md**: Section renderer “Omission logic” (§6.1); optional field omission per section rules; this contract defines those rules.
- **css-selector-contract.md**: Element roles (§3.4); omission applies to nodes that map to these roles; no new structural selectors for omission.
- **semantic-seo-accessibility-extension-contract.md**: Heading and CTA requirements; omission must not violate single h1, outline, or CTA clarity.
- **Field_Blueprint_Schema** (plugin): Required vs optional field definitions; validation and requiredness model (§20.4–20.6). When a docs/schemas/acf-field-blueprint-schema.md or equivalent exists, it should reference this contract for omission eligibility.

---

## 13. Revision history

| Version | Date | Change |
|---------|------|--------|
| 1 | Prompt 139 | Initial smart omission rendering contract. |
