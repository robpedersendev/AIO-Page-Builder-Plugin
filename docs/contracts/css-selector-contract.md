# CSS, ID, Class, and Attribute Manifest Contract

**Spec**: §7.7 CSS and Asset Delivery Strategy; §12.11 Section CSS Contract Manifest; §17 Rendering Architecture; §18 CSS, ID, Class, and Attribute Contract; §57.6 CSS Naming Conventions; §59.5 Rendering and ACF Phase

**Related**: rendering-contract.md (markup output); section-registry-schema.md (§6 CSS contract manifest reference); PORTABILITY_AND_UNINSTALL.md; **semantic-seo-accessibility-extension-contract.md** (semantic HTML and accessibility rules that apply to the same markup; selectors and element roles here align with semantic requirements there); **animation-support-and-fallback-contract.md** (animation state classes and optional animation hooks must follow this contract’s state/modifier patterns; no new structural selectors for animation).

**Status**: Contract definition only; no CSS files, asset loaders, or renderer implementation.

---

## 1. Purpose

This contract defines the **fixed selector and attribute rules** for section and page output. Class names, ID patterns, and data attributes are **system-owned and stable**. AI and section authors may not rename or redefine them. Visual values (colors, spacing, typography) may vary via design tokens; selector names may not. The contract ensures portability, documentation, deterministic rendering, and survivability.

---

## 2. Global Selector Prefix

| Rule | Value | Notes |
|------|--------|--------|
| Plugin prefix | `aio-` | All structural classes and IDs that belong to the plugin **must** start with this prefix. |
| Scope | Global | Applies to section-level and page-level output. |
| Immutability | Prefix is fixed | Changed only by formal versioned contract revision. |

**Machine-readable pattern**: Any plugin structural class matches `^aio-[a-z0-9_-]+$` (after prefix, alphanumeric, underscore, hyphen only). No spaces; single class per token.

---

## 3. Section-Level Conventions

### 3.1 Section Wrapper (Outer)

| Concept | Pattern | Example |
|---------|---------|---------|
| Base section class | `aio-s-{section_key}` | `aio-s-st01_hero` |
| Rule | `section_key` = section template `internal_key` (Section_Schema). Lowercase; underscores allowed. | One wrapper class per section instance. |

**No** generic wrapper without section identity (e.g. `aio-section` alone is **prohibited** as the only section identifier; use `aio-s-{section_key}`).

### 3.2 Inner Wrapper

| Concept | Pattern | Example |
|---------|---------|---------|
| Inner container | `aio-s-{section_key}__inner` | `aio-s-st01_hero__inner` |
| Rule | Single inner wrapper per section instance when structural blueprint defines it. | Optional per manifest. |

### 3.3 Major Structural Child Classes (Elements)

| Concept | Pattern | Example |
|---------|---------|---------|
| Element | `aio-s-{section_key}__{element}` | `aio-s-st01_hero__headline` |
| `element` | One of the approved element roles (see §3.4). Lowercase; hyphen for multi-word. | Role-oriented; consistent across sections where role applies. |

### 3.4 Approved Element Roles (Child Selectors)

These are the **allowed** `{element}` values for `aio-s-{section_key}__{element}`. Section manifests **must** use only these or a subset; no ad-hoc element names.

| Element role | Use |
|--------------|-----|
| `inner` | Inner container (see §3.2). |
| `eyebrow` | Eyebrow / kicker text. |
| `headline` | Primary heading. |
| `subheadline` | Subhead or secondary heading. |
| `intro` | Introductory paragraph or block. |
| `media` | Media wrapper (image, video). |
| `media-caption` | Caption for media. |
| `content` | General content region. |
| `cards` | Cards container. |
| `card` | Single card (child of cards). |
| `cta` | Call-to-action block. |
| `cta-group` | Group of CTAs. |
| `list` | List container. |
| `list-item` | List item. |
| `faq-item` | FAQ item (question/answer pair). |
| `disclosure` | Disclosure/accordion wrapper. |
| `badge` | Badge or label. |
| `note` | Note or aside. |
| `footer` | Section footer area. |

New roles require a contract revision; section authors **must not** invent new element tokens.

### 3.5 Modifier Classes

| Concept | Pattern | Example |
|---------|---------|---------|
| Variant modifier | `aio-s-{section_key}--variant-{variant_key}` | `aio-s-st01_hero--variant-compact` |
| Optional modifier | `aio-s-{section_key}--{modifier}` | `aio-s-st01_hero--theme-dark` |
| Rule | Modifiers are **suffixes** after `--`. One variant modifier per instance; optional modifiers from manifest allowlist. | `variant_key` from section `variants` keys. |

### 3.6 State Classes

| Concept | Pattern | Example |
|---------|---------|---------|
| State | `aio-s-{section_key}--is-{state}` | `aio-s-st05_faq--is-expanded` |
| Rule | State reflects runtime or interactive state (e.g. expanded, collapsed, active). | Only states declared in section manifest. |

---

## 4. Page-Level Conventions

| Concept | Pattern | Example |
|---------|---------|---------|
| Page wrapper | `aio-page` | Applied to page root wrapper when used. |
| Template identifier | `aio-page--{template_key}` | `aio-page--pt_landing` |
| Composition identifier | `aio-page--comp-{composition_id}` | `aio-page--comp-landing_001` |
| Rule | `template_key` = page template `internal_key`; `composition_id` = composition id. One of template or composition class when applicable. | Page-level scope; does not replace section-level classes. |

---

## 5. ID Strategy

### 5.1 When IDs Are Allowed

| Use | Allowed | Pattern | Example |
|-----|---------|---------|---------|
| Section anchor | Yes | `aio-section-{section_key}-{position}` | `aio-section-st01_hero-0` |
| Page anchor | Yes (optional) | `aio-page-{page_slug_or_id}` | `aio-page-about-us` |
| Skip link target | Yes | Per accessibility contract; stable ID. | — |
| ARIA relationships | Yes | Same pattern; unique per page. | — |

### 5.2 When IDs Are Prohibited or Optional

| Case | Rule |
|------|------|
| Styling only | Use classes; IDs are **prohibited** for pure styling. |
| Non-unique elements | Use classes. IDs must be unique per page. |
| Random or opaque IDs | **Prohibited.** IDs must follow the patterns above and be traceable. |
| User-supplied IDs | **Prohibited** as part of structural contract. No arbitrary ID injection. |

### 5.3 ID Pattern Summary

**Machine-readable**: Section ID `^aio-section-[a-z0-9_]+-\d+$` (section_key + position). Page ID `^aio-page-[a-z0-9_-]+$`. No spaces or special characters beyond hyphen and underscore.

---

## 6. Data Attribute Rules

### 6.1 Approved Data Attribute Prefix

All plugin structural data attributes **must** use the prefix `data-aio-`.

### 6.2 Approved Data Attributes

| Attribute | Values / purpose | Safe for front-end |
|-----------|------------------|--------------------|
| `data-aio-section` | Section `internal_key` | Yes; identity only. |
| `data-aio-variant` | Variant key | Yes. |
| `data-aio-position` | Zero-based position on page | Yes. |
| `data-aio-template` | Page template `internal_key` (page wrapper) | Yes. |
| `data-aio-composition` | Composition id (page wrapper) | Yes. |
| `data-aio-role` | Element role (e.g. headline, cta) | Yes; when needed for JS or a11y. |

### 6.3 Prohibited Data Attributes

| Prohibition | Reason |
|-------------|--------|
| Privileged or secret state | Must not expose tokens, keys, or internal refs that are sensitive. |
| Arbitrary `data-*` | Only approved `data-aio-*` list above. No user-supplied attribute names on structural elements. |
| Unprefixed plugin attributes | All plugin structural attributes must be `data-aio-*`. |

---

## 7. Token Hook Points

Design tokens supply **values**; selectors and hook **names** are fixed.

### 7.1 CSS Custom Property Prefix

| Rule | Value |
|------|--------|
| Token variable prefix | `--aio-` |
| Usage | Values (colors, spacing, typography) are applied via custom properties; **property names** are part of the contract. |

### 7.2 Token Hook Point Pattern

| Category | Pattern | Example (name only; value is variable) |
|----------|---------|--------------------------------------|
| Color | `--aio-color-{name}` | `--aio-color-primary`, `--aio-color-surface` |
| Typography | `--aio-font-{name}` | `--aio-font-heading`, `--aio-font-body` |
| Spacing | `--aio-space-{name}` | `--aio-space-md`, `--aio-space-section` |
| Radius | `--aio-radius-{name}` | `--aio-radius-card` |
| Shadow | `--aio-shadow-{name}` | `--aio-shadow-card` |

**Rule**: AI and themes may **change the value** of these variables. They may **not** rename or remove the variable names defined in the contract. Section manifests declare which token hooks a section uses; values are supplied by the design-token engine or theme.

### 7.3 Separation of Structural Selectors from Variable Values

| Layer | Stable (contract) | Variable (allowed) |
|-------|-------------------|---------------------|
| Class/ID names | Yes | No |
| Data attribute names | Yes | No |
| Token **names** (e.g. `--aio-color-primary`) | Yes | No |
| Token **values** (e.g. `#333`) | No | Yes |
| Inline style values | N/A | Yes, when driven by tokens. |

---

## 8. Prohibited Selector and Attribute Patterns

### 8.1 Prohibited Class Patterns

| Pattern | Prohibition |
|---------|-------------|
| Unprefixed generic names | No `section`, `container`, `wrapper`, `content` as standalone structural classes from the plugin. Use `aio-s-{section_key}`, `aio-s-{section_key}__inner`, etc. |
| Ad-hoc element names | No element role that is not in the approved list (§3.4). |
| User-supplied class injection | Structural wrappers and children must not carry user-editable or AI-invented class names as **structural** identifiers. (Content inside blocks may have editor-added classes per block editor rules.) |
| Multiple section identities on one element | One section wrapper has one `aio-s-{section_key}`; no mixing. |
| Spaces or special chars in segment | Only `a-z`, `0-9`, `_`, `-` in class segments. |

### 8.2 Prohibited ID Patterns

| Pattern | Prohibition |
|---------|-------------|
| Random or UUID-style IDs | No `id="aio-abc123"` or opaque strings. |
| IDs for styling only | Use classes. |
| Non-unique IDs | IDs must be unique within the page. |
| User-supplied IDs on structural nodes | No arbitrary ID injection. |

### 8.3 Prohibited Data Attribute Patterns

| Pattern | Prohibition |
|---------|-------------|
| Unprefixed plugin attributes | No `data-section`, `data-variant`; use `data-aio-section`, `data-aio-variant`. |
| Sensitive data | No `data-aio-token`, `data-aio-apikey`, or any secret. |
| Arbitrary names | Only the approved list in §6.2. |

### 8.4 Prohibited in Section Manifests

| Prohibition | Reason |
|-------------|--------|
| Redefining base section class pattern | Must use `aio-s-{section_key}`. |
| Introducing new element roles without contract change | Use only approved roles from §3.4. |
| Overly generic modifiers | Modifiers must be explicit and documented in manifest. |
| Selectors that conflict with global contract | Section manifest must align (see §10). |

---

## 9. Section-Level CSS Manifest Alignment with Global Contract

Each section template references a **CSS contract manifest** via `css_contract_ref` (Section_Schema, §12.11). The manifest is a **structural contract** for that section and **must** align with this global contract.

### 9.1 Required Manifest Entries

| Entry | Must align with |
|-------|------------------|
| Base section class | §3.1: `aio-s-{section_key}` where `section_key` is that section’s `internal_key`. |
| Section ID strategy | §5: pattern `aio-section-{section_key}-{position}` if IDs used. |
| Inner wrapper class | §3.2: `aio-s-{section_key}__inner` if used. |
| Major child classes | §3.3, §3.4: only approved element roles; pattern `aio-s-{section_key}__{element}`. |
| Modifier classes | §3.5: variant `aio-s-{section_key}--variant-{variant_key}`; other modifiers from allowlist. |
| State classes | §3.6: `aio-s-{section_key}--is-{state}`. |
| Approved data attributes | §6.2: only `data-aio-*` from approved list. |
| Token hook points | §7: only `--aio-*` names; values are variable. |
| Prohibited patterns | §8: none of the prohibited patterns. |

### 9.2 Manifest as Contract (No Override)

The section manifest **documents** the section’s selector structure; it does **not** override the global contract. If a manifest specified a class that violated §3–§8, the **global contract wins** and the manifest would be non-compliant.

---

## 10. Example Section Manifests

### 10.1 Example: Hero Section (st01_hero)

| Concept | Value |
|---------|--------|
| Base section class | `aio-s-st01_hero` |
| Section ID | `aio-section-st01_hero-{position}` (optional; for anchor) |
| Inner wrapper | `aio-s-st01_hero__inner` |
| Child classes | `aio-s-st01_hero__eyebrow`, `aio-s-st01_hero__headline`, `aio-s-st01_hero__subheadline`, `aio-s-st01_hero__media`, `aio-s-st01_hero__cta` |
| Variant modifier | `aio-s-st01_hero--variant-compact`, `aio-s-st01_hero--variant-media-left` |
| Data attributes | `data-aio-section="st01_hero"`, `data-aio-variant="{variant_key}"`, `data-aio-position="{position}"` |
| Token hooks | `--aio-color-primary`, `--aio-space-section`, `--aio-font-heading` |

### 10.2 Example: FAQ Section (st05_faq)

| Concept | Value |
|---------|--------|
| Base section class | `aio-s-st05_faq` |
| Inner wrapper | `aio-s-st05_faq__inner` |
| Child classes | `aio-s-st05_faq__headline`, `aio-s-st05_faq__list`, `aio-s-st05_faq__faq-item`, `aio-s-st05_faq__disclosure` |
| State class | `aio-s-st05_faq--is-expanded` (on item or disclosure as per structural blueprint) |
| Data attributes | `data-aio-section="st05_faq"`, `data-aio-variant`, `data-aio-role="faq-item"` where needed |

### 10.3 Example: Page Wrapper

| Concept | Value |
|---------|--------|
| Page wrapper class | `aio-page` |
| Template class | `aio-page--pt_landing` |
| Data attributes | `data-aio-template="pt_landing"` (or `data-aio-composition="comp_landing_001"`) |

---

## 11. Compliance Checklist for Section-Level CSS Manifests

Use this checklist to verify a section’s CSS contract manifest complies with the global contract.

- [ ] **Prefix**: All section classes use `aio-` prefix.
- [ ] **Base class**: Base section class is exactly `aio-s-{section_key}` with that section’s `internal_key`.
- [ ] **Inner wrapper**: If used, inner wrapper class is `aio-s-{section_key}__inner`.
- [ ] **Elements**: Every child class uses pattern `aio-s-{section_key}__{element}` and `{element}` is from the approved list (§3.4).
- [ ] **Modifiers**: Variant modifier is `aio-s-{section_key}--variant-{variant_key}`; other modifiers follow `aio-s-{section_key}--{modifier}` and are documented.
- [ ] **States**: State classes follow `aio-s-{section_key}--is-{state}`.
- [ ] **IDs**: If IDs are used, pattern is `aio-section-{section_key}-{position}` or other approved pattern; no random or styling-only IDs.
- [ ] **Data attributes**: Only `data-aio-*` from approved list (§6.2); no sensitive or arbitrary attributes.
- [ ] **Token hooks**: Only `--aio-*` variable names; no new structural selector names for tokenization.
- [ ] **Prohibited**: No unprefixed generic classes, no ad-hoc element names, no user-supplied structural class/ID/attribute injection, no prohibited patterns from §8.

---

## 12. Version Stability

| Aspect | Rule |
|--------|------|
| Contract version | Document version (e.g. 1) should be referenced in schema or implementation where manifest validation occurs. |
| Adding element roles | New roles require a contract revision and addition to §3.4. |
| Adding data attributes | New approved attributes require a contract revision and addition to §6.2. |
| Changing prefix | Would break all selectors; requires formal major contract revision. |
| Token names | New token hook names require contract revision; value changes do not. |

---

## 13. Cross-References

- **Styling subsystem**: The formal styling subsystem (Option A) extends token **values** and optional styling metadata only; selector and token **names** remain defined by this contract. See [styling-subsystem-contract.md](styling-subsystem-contract.md) and [styling-retrofit-impact-analysis.md](../qa/styling-retrofit-impact-analysis.md).
- **Section_Schema** (`css_contract_ref`): `plugin/src/Domain/Registries/Section/Section_Schema.php`
- **section-registry-schema.md** §6: CSS contract manifest reference block; section manifests must conform to this contract.
- **rendering-contract.md**: Markup output and wrapper_attrs; class and ID assignment follow this contract.
- **Spec §18.10**: AI may influence values, not structure-defining identifiers; this contract defines those identifiers.
- **semantic-seo-accessibility-extension-contract.md**: Semantic HTML patterns by section purpose family, heading/landmark/CTA/image/list/form rules; element roles in §3.4 align with semantic structure required there.
- **animation-support-and-fallback-contract.md**: Animation tiers and families; animation state uses §3.6 state-class pattern; no new structural selectors introduced by animation.

---

## 14. Revision History

| Version | Date | Change |
|---------|------|--------|
| 1 | Prompt 042 | Initial selector and attribute contract. |
| 2 | Prompt 137 | Cross-reference to semantic-seo-accessibility-extension-contract. |
| 3 | Prompt 138 | Cross-reference to animation-support-and-fallback-contract. |
