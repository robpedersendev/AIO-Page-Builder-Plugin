# GenerateBlocks Compatibility Contract

**Spec**: §7.2 GeneratePress/GenerateBlocks Assumptions; §7.5 Native Blocks Strategy; §17 Rendering Architecture; §17.2 GenerateBlocks Integration Strategy; §18 Native Block Assembly; §54.2 GenerateBlocks Compatibility Rules; §59.5 Rendering and ACF Phase

**Related**: rendering-contract.md (durable output, section rendering); css-selector-contract.md (section classes, IDs, data attributes)

**Status**: Contract definition; implementation in `src/Domain/Rendering/GenerateBlocks/`.

---

## 1. Purpose

This contract defines where and how the plugin uses GenerateBlocks-compatible block output within the rendering pipeline. GenerateBlocks compatibility is **controlled and bounded**. Native blocks remain the primary durable output model; GenerateBlocks is an allowed compatibility layer that improves alignment with the product’s preferred block composition when available. Built pages must remain meaningful after plugin deactivation or uninstall.

---

## 2. Where GenerateBlocks Is Used

| Use | Scope | Condition |
|-----|--------|-----------|
| Section wrapper | One `generateblocks/container` per section when mapping applies | GenerateBlocks available and section eligible per mapping rules |
| Section heading | `generateblocks/headline` with element `h2` for field keys `headline`, `title` | Same as above |
| Section body text | `generateblocks/headline` with element `p` for other scalar text fields | Same as above |
| Fallback | `core/html` section wrapper + semantic HTML (h2, p) | When GB unavailable or section not eligible |

No other GenerateBlocks block types are emitted by the compatibility layer. No render callbacks are used for section content.

---

## 3. Compatibility Detection

| Mechanism | Description |
|-----------|-------------|
| Availability check | The pipeline uses an injectable callable (e.g. plugin active, block type registered). When the callable returns false, all sections use native output. |
| Eligibility | Per-section: `GenerateBlocks_Mapping_Rules::is_eligible_for_gb()` must be true (valid section_key, wrapper_attrs with class, scalar-only field values). |
| Determinism | Detection and mapping are server-side and deterministic; no client-side or runtime shortcuts. |

---

## 4. Allowed GenerateBlocks Constructs

| Block | Purpose | Attributes / content |
|-------|---------|----------------------|
| `generateblocks/container` | Section wrapper | `className` = contract wrapper classes; `anchor` = section id (e.g. `aio-section-{key}-{position}`). Inner HTML: div with contract inner class and data-aio-* attributes, then GB Headline blocks. |
| `generateblocks/headline` | Heading or paragraph | `element`: `h2` for headline/title fields, `p` for other text. Inner content: escaped text. |

Selector contract (css-selector-contract.md) is preserved: wrapper class `aio-s-{section_key}`, variant modifier, inner class `aio-s-{section_key}__inner`, id `aio-section-{section_key}-{position}`, data attributes `data-aio-section`, `data-aio-variant`, `data-aio-position`.

---

## 5. Fallback When GenerateBlocks Is Unavailable or Limited

| Situation | Behavior |
|-----------|----------|
| GenerateBlocks not available | Every section is rendered with native block output (`core/html` + semantic HTML). Page ordering and selector contract unchanged. |
| Section not eligible (e.g. repeater fields) | That section uses native block output; other sections may still use GB when eligible. |
| Mapping returns null | Pipeline uses native `section_to_block_markup()` for that section. |

Fallback is structural: the same section order and selector contract apply; only the block type (GB vs core/html) may differ.

---

## 6. Unsupported Patterns and Required Fallback

| Pattern | Handling |
|---------|----------|
| Repeater or group field values (array) | Not mapped to GenerateBlocks; section is rendered with native output. |
| Section without valid wrapper_attrs (class list) | Not mapped; native fallback. |
| Custom or third-party block types | Not emitted by this layer; only `generateblocks/container` and `generateblocks/headline` are used. |
| Render-callback blocks for section content | Not used; content is static and durable. |

Unsupported patterns are documented in `GenerateBlocks_Mapping_Rules::unsupported_patterns()` and in this contract.

---

## 7. Page Ordering and Selector Contract Integrity

- Section order is determined by the page template or composition; the compatibility layer does not change order.
- Each section output (GB or native) preserves the same wrapper classes, id, data attributes, and inner structure per css-selector-contract.
- Survivability: output is save-ready block markup; no plugin runtime is required for display. If GenerateBlocks is later deactivated, existing GB blocks may show as unrecognized but content remains in post_content.

---

## 8. Security and Permissions

- No dynamic execution shortcuts; mapping is deterministic.
- No privileged or secret data in compatibility metadata.
- All compatibility logic is server-side; content is escaped per WordPress standards.

---

## 9. Examples

### 9.1 Supported mapping example

Section payload: `section_key` = `st01_hero`, `wrapper_attrs.class` = `['aio-s-st01_hero', 'aio-s-st01_hero--variant-default']`, `field_values` = `{ "headline": "Welcome", "subheadline": "Intro text" }` (scalar only). GenerateBlocks available and eligible → output includes one `generateblocks/container` block with contract classes and id, inner div with `aio-s-st01_hero__inner` and data-aio-* attributes, and two `generateblocks/headline` blocks (element h2 for "Welcome", element p for "Intro text"). Section order and selector contract unchanged.

### 9.2 Unsupported-pattern behavior example

Section payload: `field_values` includes a key `items` whose value is an array (repeater). `is_eligible_for_gb()` returns false. The compatibility layer returns null for that section; the pipeline falls back to native `core/html` output for that section only. Other sections in the same page may still use GenerateBlocks when eligible. No custom or undocumented block types are emitted.

---

## 10. Revision History

| Version | Date | Change |
|---------|------|--------|
| 1 | Prompt 045 | Initial contract; supported mapping and unsupported-pattern examples |
