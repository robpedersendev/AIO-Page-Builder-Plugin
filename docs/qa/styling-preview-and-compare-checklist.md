# Styling Preview and Compare QA Checklist

**Purpose**: Verify the styling subsystem is correctly applied in template previews, compare screen, and detail screens; preview output is aligned with frontend styling; cache invalidation and scoping are correct.

**Contract refs**: [styling-subsystem-contract.md](../contracts/styling-subsystem-contract.md), [rendering-contract.md](../contracts/rendering-contract.md), [css-selector-contract.md](../contracts/css-selector-contract.md).

**Prompt**: 255 (Preview, compare, and detail-screen styling integration).

---

## 1. Detail screen preview styling

- [ ] **Section template detail**: Opening a section template detail screen shows the preview panel with base stylesheet and global styling (tokens + component overrides) applied. Section-level inline styles and component override blocks are present in the rendered preview HTML (from Section_Style_Emitter).
- [ ] **Page template detail**: Opening a page template detail screen shows the preview panel with base stylesheet, global styling, and per-page styling (when the page template has saved per-entity style payload) applied. Preview matches intended token/override values.
- [ ] **No styling**: When no global or per-entity styling is configured, preview still loads the base stylesheet and renders without errors; no broken layout due to missing CSS.
- [ ] **Safe output**: Preview panel uses only sanitized styling data; no raw user input or arbitrary selectors/declarations in the emitted `<style>` or `<link>`.

---

## 2. Compare screen styling

- [ ] **Compare screen loads styles**: On the Template Compare screen (section or page type), the compare matrix receives the base stylesheet and global inline CSS so any preview content or future HTML in cells would be styled consistently.
- [ ] **No per-entity in compare**: Compare screen uses global styling only (no per-template page/section CSS in the shared block); per-entity styles are visible on the respective detail screens.

---

## 3. Cache invalidation

- [ ] **Global style change**: After updating global style settings (tokens or component overrides) and saving, previously cached section/page previews are invalidated; next view of a detail screen or compare uses fresh preview content that reflects the new global styling.
- [ ] **Per-entity style change**: After saving per-entity styling on a section or page template detail screen, the preview cache for that template (and globally, per current implementation) is invalidated; preview reflects the new per-entity payload on reload.
- [ ] **No stale styling**: No observable stale token or override values in preview after style data has been changed and cache has been cleared (or invalidated by the above hooks).

---

## 4. Scoping and no leakage

- [ ] **Preview scope**: Styling injected for preview (base stylesheet link, inline style block) is limited to the preview section or compare matrix; it does not alter other admin UI or frontend outside the intended preview container.
- [ ] **No new structural selectors**: Preview styling uses only approved selectors (e.g. `.aio-page`, `:root`, section wrapper patterns); no new structural classes or IDs introduced for preview.
- [ ] **Errors**: Invalid or missing styling data does not produce unsafe output; preview fails safely (e.g. no inline CSS or empty block) and does not leak internals.

---

## 5. Regression

- [ ] **Detail screens**: Metadata, breadcrumbs, entity style form, and other detail screen behavior unchanged; only the preview panel gains the style context (link + optional style block).
- [ ] **Compare screen**: Compare list, add/remove, type switcher, and table layout unchanged; only the matrix receives the shared style context.
- [ ] **Frontend**: Live frontend rendering and enqueue behavior unchanged; preview styling is additive in admin only.

---

*Run this checklist after changes to Preview_Style_Context_Builder, detail/compare screens, or Preview_Cache_Service invalidation hooks.*
