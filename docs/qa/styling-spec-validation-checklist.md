# Styling Spec Validation Checklist

**Purpose**: Verify the three style spec files (pb-style-core-spec.json, pb-style-components-spec.json, pb-style-render-surfaces-spec.json) are coherent, versioned, and aligned with the CSS contract. No new structural selectors or token names.

**Contract refs**: [css-selector-contract.md](../contracts/css-selector-contract.md) §3.4, §7; [styling-subsystem-contract.md](../contracts/styling-subsystem-contract.md); [style-registry-contract.md](../contracts/style-registry-contract.md).

---

## 1. Spec coherence and versioning

- [ ] **pb-style-core-spec.json** contains `spec_version` and `spec_schema` (e.g. "pb-style-core"); all token_groups have `pattern`, `allowed_names`, and `sanitization`.
- [ ] **pb-style-components-spec.json** contains `spec_version` and `spec_schema`; each component has `id`, `element_role`, `selector_pattern`, `allowed_token_overrides`.
- [ ] **pb-style-render-surfaces-spec.json** contains `spec_version` and `spec_schema`; each render_surface has `id`, `selector`, `scope`, `allowed_output`.
- [ ] All three specs reference the same contract (css-selector-contract.md) and do not introduce a new token name pattern (must use --aio-* only).

---

## 2. Token names vs CSS contract

- [ ] Every token name in core spec follows pattern `--aio-{category}-{name}` where category is one of color, font, space, radius, shadow (per css-selector-contract §7.2).
- [ ] Core spec `allowed_names` and `pattern` use only --aio-*; no new variable name prefix.
- [ ] Component spec `allowed_token_overrides` list only token names that exist in core spec (e.g. --aio-color-primary, --aio-radius-card).

---

## 3. Selectors vs CSS contract

- [ ] Component spec `selector_pattern` uses only `aio-s-{section_key}__{element}` with `element` from the approved list (§3.4): inner, eyebrow, headline, subheadline, intro, media, media-caption, content, cards, card, cta, cta-group, list, list-item, faq-item, disclosure, badge, note, footer.
- [ ] Render-surfaces spec `selector` values are `:root`, `.aio-page`, or the section wrapper pattern `[class^="aio-s-"]`; no new structural selectors.
- [ ] No new class names, ID patterns, or data-aio-* attributes introduced in any spec.

---

## 4. No new structural selectors or token names

- [ ] No token name in any spec uses a prefix other than `--aio-`.
- [ ] No selector in any spec uses a class or ID outside the patterns defined in css-selector-contract.md (§3–§5, §7).

---

## 5. Cross-reference

- [ ] style-registry-contract.md correctly names the three spec files and describes read-only lookup and versioning.
- [ ] styling-subsystem-contract.md §10 references all three specs and the style registry contract.

---

*Run this checklist when adding or changing pb-style-*-spec.json or the style registry contract.*
