# Animation Support and Progressive Fallback Contract

**Spec**: §7.7 CSS and Asset Delivery Strategy; §17 Rendering Architecture; §18 Native Block Assembly; §51.10 Modal / Popup Accessibility Rules (where motion affects focus context); §55.5 Asset Loading Rules; §59.5 Rendering and ACF Phase; §59.14 Hardening and QA Phase

**Upstream**: rendering-contract.md, css-selector-contract.md, semantic-seo-accessibility-extension-contract.md

**Status**: Contract definition only. No mass animation implementation; no custom JS animation engine. This contract formalizes **progressive enhancement**: templates must render correctly without animation. The system does **not** promise identical effects in every browser; it promises **controlled progressive enhancement with safe, non-broken fallbacks**. Performance budgets are not weakened.

---

## 1. Purpose and scope

This contract defines the **animation system** for the expanded template library: animation tiers, animation families, browser-support posture, **reduced-motion** behavior, no-support fallback behavior, and how animation metadata is attached to section and page templates. Animation is **optional progressive enhancement**; structural selector contracts (css-selector-contract.md) remain unchanged by animation behavior.

**Out of scope**: Mass implementation of animations; custom JavaScript animation engine; promise of identical effects in all browsers; weakening of performance budgets. Animation settings are governed by **template metadata**, not arbitrary front-end user input. No unsafe script injection pathways.

---

## 2. Animation tiers (enhancement levels)

Animation is delivered in **tiers**. Higher tiers add motion on top of a stable base; the base (tier none) must always render correctly.

| Tier slug | Description | Use |
|-----------|-------------|-----|
| `none` | No animation. Static layout and content only. | Default safe baseline; required fallback. |
| `subtle` | Minimal motion: opacity, short duration, no large movement. Suitable for reduced-motion fallback when user allows “subtle” only. | Entry-level enhancement; low risk. |
| `enhanced` | Moderate motion: transitions, entrance/exit, scroll or hover effects. | Standard enhancement tier. |
| `premium` | Rich motion: multi-step sequences, parallax-like or coordinated effects. May require JS or advanced CSS. | Optional; only where supported and where reduced-motion is respected. |

**Rules:**

- Every section and page template that declares animation **must** have a defined **fallback** for tier `none` (content and layout visible and correct).
- Tiers are **additive**: enhanced includes subtle behavior where applicable; premium includes enhanced where applicable. Fallback always goes down to `none` when support or preference is missing.
- Tier selection may be **global** (site/theme preference) or **per-section** via section metadata; see §7.

---

## 3. Animation families and where they are appropriate

**Animation family** is a stable slug that groups similar motion treatments. Section and page templates declare which families they use (if any). Asset loading and fallback are family-aware.

| Family slug | Description | Typical section purpose families | Tier typically used | Fallback |
|-------------|-------------|----------------------------------|---------------------|----------|
| `entrance` | Elements animate in on load or into view (fade, slide, scale). | hero, proof, offer, explainer, listing, cta | subtle, enhanced | Static visibility (no animation). |
| `hover` | Hover-state transitions (underline, lift, color). | cards, cta, list items | subtle, enhanced | No hover effect; state still visible. |
| `scroll` | Motion tied to scroll (e.g. parallax, reveal). | hero, media | enhanced, premium | Static layout; no scroll-driven motion. |
| `focus` | Focus-visible emphasis (outline, scale). | cta, form, interactive | subtle | Native focus outline only. |
| `disclosure` | Expand/collapse, accordion open/close. | faq, utility | subtle, enhanced | Instant show/hide; no transition. |
| `stagger` | Sequential delay for list/card items. | proof, listing, cards | enhanced | All items visible; no stagger. |
| `micro` | Small feedback (button press, badge pulse). | cta, badge | subtle | No micro motion. |

**Appropriateness:**

- **Hero**: entrance, scroll (optional), hover on CTA. Avoid heavy continuous motion.
- **Proof / listing / cards**: entrance, stagger, hover. Disclosure if accordion.
- **CTA**: hover, focus, micro. No distracting continuous motion.
- **FAQ / utility**: disclosure only. No decorative motion.
- **Legal**: typically none; optional subtle entrance only.

New families require a contract revision. Section metadata (see §7) references **allowed** family slugs; no ad-hoc family names.

---

## 4. Supported browser strategy and fallback rules

### 4.1 Browser-support posture

| Principle | Requirement |
|-----------|-------------|
| No identical promise | The contract does **not** promise identical animation effects in every browser. It promises **correct layout and content** in all supported environments and **progressive enhancement** where support exists. |
| Graceful degradation | When a technique is unsupported (e.g. `@scroll-timeline`, certain `@keyframes` usage), the page must render as if tier `none` or the next lower supported tier for that family. |
| No broken layout | Animation must never cause overflow, overlap, or invisible critical content. If an effect would hide or misplace content in a given browser, that effect must be disabled and fallback applied. |

### 4.2 Fallback matrix (family × support)

| Family | Full support | Partial support | No support / disabled |
|--------|--------------|-----------------|------------------------|
| entrance | CSS transition/opacity/transform + optional IntersectionObserver | Transitions only; no IO | All elements visible immediately; no motion. |
| hover | CSS :hover transitions | Same | Hover state still available; no transition. |
| scroll | Scroll-driven APIs or JS scroll listeners where implemented | Static or simple fade | Static layout; no scroll-based motion. |
| focus | :focus-visible + optional transition | :focus-visible only | Native focus ring. |
| disclosure | transition on height/opacity or preferred method | Instant toggle | Content toggles; no animation. |
| stagger | CSS animation-delay or JS | All visible at once | No stagger; all visible. |
| micro | Short transition or keyframes | None | No micro effect. |

**Deterministic fallback:** For each family and tier, the **fallback behavior** is defined: either “static equivalent” (content visible, no motion) or “next lower tier” for that family. There is no undefined or “best effort” state that leaves content broken.

### 4.3 No-support fallback behavior (summary)

| When | Behavior |
|------|----------|
| CSS animations/transitions not supported | Tier `none`; layout and content unchanged. |
| JS required for family (e.g. scroll) and JS disabled or failed | That family disabled; static fallback. |
| Feature detection fails for a given family | Treat as no support for that family; apply fallback. |
| Performance or capability hint (e.g. reduced data) | Tier `none` or `subtle` per policy. |

---

## 5. Reduced-motion rules and user-preference overrides

### 5.1 prefers-reduced-motion

| Rule | Requirement |
|------|-------------|
| Honor preference | When the user has set `prefers-reduced-motion: reduce` (OS or browser), the system **must** respect it. No exception for “subtle” or “brand” motion unless the user has explicitly opted in (e.g. site setting). |
| Default behavior | If no site-level override exists: `prefers-reduced-motion: reduce` → treat effective tier as `none` (or at most `subtle` for essential UI only, e.g. focus indicator transition). |
| CSS media query | Animation CSS **must** be written so that `@media (prefers-reduced-motion: reduce)` (or equivalent) disables or greatly reduces motion. Options: disable animation inside the media block, or use a class applied when reduced motion is preferred. |
| No override of OS preference by default | Template metadata may not force “enhanced” or “premium” when the user has expressed reduced motion. Site-level “ignore reduced motion” is out of scope for this contract unless explicitly added by revision; assume honor by default. |

### 5.2 Reduced-motion fallback behavior

| Tier when reduced-motion active | Behavior |
|---------------------------------|----------|
| none | No change. |
| subtle | Only essential feedback (e.g. focus) may retain very short, minimal motion; all decorative motion off. |
| enhanced / premium | Treated as `none` for decorative and layout motion; disclosure may remain instant show/hide with no transition. |

### 5.3 QA obligation

QA must verify that with `prefers-reduced-motion: reduce` (browser or devtools), no decorative or non-essential animation runs, and content/layout remain correct. See §9.

---

## 6. Progressive enhancement rules

| Rule | Description |
|------|-------------|
| Content first | All critical content and structure are present and correct with tier `none`. Animation only adds polish. |
| No dependency for meaning | Meaning, readability, and CTAs do not depend on animation. If animation is off, the page is still complete. |
| Selector stability | Animation may add classes or data attributes for state (e.g. `--is-animated`); these must follow css-selector-contract (e.g. state class pattern `aio-s-{section_key}--is-{state}`). No new structural selectors invented for animation. |
| Asset loading | Animation assets (CSS, optional JS) are loaded only where needed (sections using that family/tier). Per §55.5: front-end assets only where needed; versioned; modular. See §8. |
| Modals and focus | Where animation affects modals or focus context (Spec §51.10), motion must not break focus trap, focus return on close, or keyboard use. If an animation would move focus or obscure focus target, it must be disabled or adjusted in that context. |

---

## 7. Metadata fields for section and page animation capability

### 7.1 Section template metadata (recommended shape)

Animation capability is declared in **template metadata**, not in user-editable content. The following are **contract-level** definitions; schema implementation lives in registry/schema.

| Field | Type | Description |
|-------|------|-------------|
| `animation_tier` | enum | `none` \| `subtle` \| `enhanced` \| `premium`. Default: `none`. Section’s intended tier when used. |
| `animation_families` | string[] | Allowed family slugs from §3 (e.g. `['entrance', 'hover']`). Empty or omitted = no animation. |
| `animation_fallback_tier` | enum | Tier when motion is disabled or unsupported. Must be `none` or same as tier; typically `none`. |
| `reduced_motion_behavior` | string | `honor` (default): respect prefers-reduced-motion. `essential_only`: only essential feedback (e.g. focus) may animate when reduced. |

**Validation:** `animation_families` must only contain slugs defined in this contract (§3). `animation_tier` must be one of the four tiers. Section templates that omit these fields are treated as tier `none`, no families.

### 7.2 Page template metadata (optional)

| Field | Type | Description |
|-------|------|-------------|
| `animation_tier_cap` | enum | Optional cap for the page (e.g. `enhanced`). Sections may not exceed this tier when composed on this template. Omitted = no cap. |
| `animation_families_allowed` | string[] | Optional allowlist of families for this page. Omitted = all families allowed per section. |

Page-level metadata does not enable animation by itself; it constrains or allows what sections may use. Section-level metadata declares actual usage.

### 7.3 Security and governance

| Constraint | Requirement |
|------------|-------------|
| Template metadata only | Animation tier and families are set in **section/page template definitions** (registry), not from arbitrary front-end user input. |
| No script injection | Animation must not introduce unsafe script injection. Any JS used for animation (e.g. scroll) must be under plugin/theme control and not derived from user-supplied strings. |

---

## 8. Asset-loading implications

Per Spec §7.7 and §55.5:

| Rule | Implication |
|------|-------------|
| Front-end assets only where needed | Animation CSS (and optional JS) should be loaded only on pages that include sections using the corresponding animation family/tier. |
| Versioned assets | Animation stylesheets/scripts must be versioned for cache behavior. |
| Modular structure | Prefer per-family or per-tier assets so that “no animation” pages do not load animation code. |
| No global bloat | Avoid loading all animation code on every page. Conditional enqueue based on section set or page template animation metadata. |

Implementation details (enqueue hooks, file names) are outside this contract; the contract requires that **policy** is conditional, versioned, and modular.

---

## 9. QA obligations for cross-browser rendering integrity

### 9.1 Browser-support / fallback QA checklist

Batch prompts and template QA must satisfy:

- [ ] **Tier none:** With animation disabled or tier forced to `none`, every section and page renders with correct layout and all content visible.
- [ ] **Fallback per family:** For each animation family in use, verify fallback behavior when the family is unsupported or disabled (see §4.2).
- [ ] **No broken layout:** In at least one “low support” scenario (e.g. older browser or animation off), confirm no overflow, overlap, or invisible critical content.
- [ ] **Progressive enhancement:** In a “full support” scenario, confirm enhanced/premium tiers add motion without removing or hiding content.

### 9.2 Reduced-motion QA checklist

- [ ] **prefers-reduced-motion: reduce:** With the preference set (browser or devtools), no decorative or non-essential animation runs.
- [ ] **Content unchanged:** With reduced motion, all content and CTAs remain visible and usable.
- [ ] **Focus and modals:** If modal or focus-related animation exists, focus trap and focus return still work when reduced motion is on (Spec §51.10).

### 9.3 Hardening alignment (§59.14)

Accessibility fixes in the hardening phase include **reduced-motion** behavior. The accessibility-remediation-checklist.md references this contract for front-end animation and reduced-motion checks.

---

## 10. Summary: what must hold

| Requirement | Contract commitment |
|-------------|---------------------|
| Templates render without animation | Yes; tier `none` is the required baseline. |
| Reduced motion honored | Yes; `prefers-reduced-motion: reduce` disables or minimizes non-essential motion. |
| Deterministic fallbacks | Yes; each family and tier has a defined fallback (static or lower tier). |
| No identical effects everywhere | Correct; progressive enhancement with safe fallbacks only. |
| Selector contract unchanged | Yes; animation uses existing state/class patterns or approved additions only. |
| Performance discipline | Yes; asset loading conditional and modular; no weakening of budgets. |
| No unsafe script injection | Yes; animation settings from template metadata only; no user-driven script. |

---

## 11. Cross-references

- **rendering-contract.md**: Survivability (animation is optional; content survives without it); §8.3 “assets may degrade gracefully.”
- **css-selector-contract.md**: State classes (§3.6); no new structural selectors for animation; data attributes per §6.
- **semantic-seo-accessibility-extension-contract.md**: Accessibility of generated output; animation must not violate focus or landmark rules.
- **accessibility-remediation-checklist.md**: Reduced-motion and animation QA (§9.2; §59.14).

---

## 12. Revision history

| Version | Date | Change |
|---------|------|--------|
| 1 | Prompt 138 | Initial animation support and progressive fallback contract. |
