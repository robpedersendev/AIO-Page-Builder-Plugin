# Styling Subsystem Contract (Option A)

**Spec**: §17.10 Rendered Content Independence from Plugin; §18 CSS, ID, Class, and Attribute Contract; §18.11 Tokenized Styling Model  
**Upstream**: [css-selector-contract.md](css-selector-contract.md), [rendering-contract.md](rendering-contract.md)  
**Standards**: [PORTABILITY_AND_UNINSTALL.md](../standards/PORTABILITY_AND_UNINSTALL.md)  
**Status**: Storage, registries, admin UI (global tokens, component overrides), global token emission (Prompts 246–250), per-entity payload schema (Prompt 251), and styles_json normalization/sanitization pipeline (Prompt 252) implemented.

---

## 1. Purpose

This contract defines the **styling subsystem** as a formal retrofit into the AIO Page Builder plugin. The subsystem is plugin-owned, additive, and optional. It establishes styling layers, root scope, relationship to the existing selector and token contract, global vs per-entity styling, sanitization and portability expectations, and plugin removal behavior—without implementing runtime styling, options storage, UI screens, or emitters.

---

## 2. Terminology (Single Source)

| Term | Definition |
|------|-------------|
| **Styling subsystem** | Plugin-owned layer that manages design-token values, optional global styling settings, and per-entity style payloads; emits CSS or inline styles only through approved pipelines. |
| **Selector contract** | Fixed `aio-*` class/ID and `data-aio-*` attribute rules from [css-selector-contract.md](css-selector-contract.md). Selector **names** are immutable. |
| **Token contract** | Fixed `--aio-*` CSS custom property **names**; only **values** may vary. Defined in css-selector-contract.md §7. |
| **Global styling settings** | Site-wide styling configuration (e.g. applied token set, theme mode) stored in options or equivalent; applies to all surfaces that consume the styling subsystem. |
| **Per-entity style payload** | Styling data scoped to a single entity (e.g. page, composition, or section instance) such as overrides or variant-specific token values; stored separately from post_content. |
| **styles_json** | Future machine-readable structure holding style payload (global and/or per-entity); meaning, schema, and sanitization are defined by this contract and spec layer. |
| **Root scope** | The DOM root(s) at which plugin-injected CSS variables or stylesheet rules apply (e.g. `:root`, `.aio-page`, or section wrapper). No injection into saved post_content structure. |
| **Emitter** | Component that produces CSS (stylesheet link, inline block, or scoped variables) from approved token/spec data; never emits arbitrary CSS or new selectors. |

---

## 3. Relationship to Existing Contracts

### 3.1 Selector and Token Contract Preservation

| Rule | Requirement |
|------|-------------|
| **aio-* selectors** | All structural class and ID names remain as defined in css-selector-contract.md. The styling subsystem **must not** introduce new structural selectors or rename existing ones. |
| **--aio-* token names** | All design-token **variable names** (e.g. `--aio-color-primary`, `--aio-space-md`) remain fixed per css-selector-contract.md §7. Only **values** may be set by the subsystem. |
| **data-aio-*** | No new structural data attributes for styling; only approved attributes from the CSS contract. |
| **No content injection** | The subsystem **must not** modify saved post_content, inject styles into content blocks, or add structural markup. Styling is applied via external stylesheet or scoped inline output (e.g. on wrapper or `:root`), not by editing stored block HTML. |

### 3.2 Existing Design Token Usage

The plugin already uses:

- **Option key**: `aio_applied_design_tokens` (Token_Set_Job_Service::OPTION_APPLIED_TOKENS) — stored as `[ group => [ name => value ] ]` for color, typography, spacing, radius, shadow, component.
- **Build plan**: Design token step and recommendations (Build_Plan_Draft_Schema::KEY_DESIGN_TOKEN_RECOMMENDATIONS, ITEM_TYPE_DESIGN_TOKEN).
- **Rollback/snapshots**: Token set in operational snapshots and diff payloads.

The styling subsystem **extends** this model under a single contract: same token **names** (--aio-*), same naming policy; additional layers (global settings, per-entity payloads, specs, cache/version markers) are additive and documented.

---

## 4. Styling Layers (Conceptual)

| Layer | Owner | Description |
|-------|--------|-------------|
| **Structural selectors** | CSS contract | Fixed aio-* classes/IDs; unchanged by styling subsystem. |
| **Token names** | CSS contract | Fixed --aio-* names; unchanged. |
| **Token values** | Styling subsystem | From global settings and/or per-entity payloads; whitelist-sanitized. |
| **Machine-readable specs** | Plugin specs | Core tokens, component overrides, render surfaces; see style-registry-contract and pb-style-*-spec.json. |
| **Emitter output** | Styling subsystem | Stylesheet or inline variables only; no new selectors; no injection into post_content. |

---

## 5. Root Scope Model

- Styling output (CSS variables or rules) applies at **root scope** defined by the plugin: e.g. `:root`, `.aio-page`, or section-level wrapper consistent with the CSS contract.
- **No** insertion of `<style>` or class/ID attributes inside saved block markup or post_content.
- Built pages remain **meaningful** after plugin deactivation: content and structure survive; plugin-owned styling may disappear (per §17.10 and PORTABILITY_AND_UNINSTALL).

---

## 6. Global vs Per-Entity Styling

| Scope | Purpose | Storage | Notes |
|-------|---------|---------|-------|
| **Global** | Site-wide token values and global component overrides | Option `aio_global_style_settings` (version, global_tokens, global_component_overrides). See [global-styling-settings-contract.md](global-styling-settings-contract.md). | Separate from `aio_applied_design_tokens` (build plan/rollback). |
| **Per-entity** | Section template / page template style overrides | Option `aio_entity_style_payloads` (version, payloads keyed by entity_type and section_key/template_key). See [per-entity-style-payload-contract.md](per-entity-style-payload-contract.md). | Schema and repository implemented (Prompt 251); sanitization and emission in later prompts. No structural change to content. |

---

## 7. styles_json Meaning and Sanitization

- **styles_json** (or equivalent) is a **machine-readable** structure for global and/or per-entity style payloads.
- **Normalization**: Raw input is normalized via **Styles_JSON_Normalizer** into deterministic shapes (global tokens, global component overrides, entity payload). See [styling-sanitization-rules.md](../security/styling-sanitization-rules.md).
- **Sanitization**: Whitelist-based only via **Styles_JSON_Sanitizer**. Allowed keys and value types come from the style specs (core token spec, component spec); no arbitrary CSS text storage and no arbitrary selector injection. Prohibited value patterns (e.g. `url(`, `expression(`, `javascript:`, `<`, `>`, `{`, `}`) are rejected. **Style_Validation_Result** carries valid flag, bounded errors, and sanitized payload.
- **Integration**: All style editing and emission flows must pass input through the normalizer and sanitizer before persistence. Repositories expose `persist_*_result(Style_Validation_Result)` to persist only when valid.
- **Invalid input**: Safe failure: reject or strip invalid entries; never emit unsanitized data to the front end.
- **Secrets**: No secret-bearing data in styling storage.

---

## 8. Portability and Plugin Removal

| Scenario | Styling behavior |
|----------|------------------|
| **Plugin deactivation** | Token-driven styles may no longer load; page structure and content remain. Built page is still meaningful (spec §17.10). |
| **Plugin uninstall** | Per PORTABILITY_AND_UNINSTALL: plugin-owned options/transients removed; built post_content and user content preserved. Styling options are plugin-owned and may be removed. |
| **Export/restore** | Styling metadata (if any) is exportable as documented; restore may reapply global/per-entity payloads within sanitization rules. |

---

## 9. Security and Permissions

| Requirement | Description |
|-------------|-------------|
| **Whitelist sanitization** | All style input (token values, overrides) must be validated against the machine-readable spec whitelist. |
| **No arbitrary CSS** | Storage and emission of raw CSS text or user-supplied selectors is **forbidden**. |
| **No arbitrary selectors** | Only contract-defined selectors and token names; no injection of new structural selectors. |
| **Safe failure** | Invalid or malformed style input must not be emitted; reject or use safe defaults. |
| **No secrets** | Styling storage and payloads must not contain API keys, tokens, or other secrets. |

---

## 10. Machine-Readable Specs and Style Registry

The styling subsystem uses versioned machine-readable specs and a read-only style registry:

- **Core token spec**: [pb-style-core-spec.json](../specs/pb-style-core-spec.json) — token groups (color, typography, spacing, radius, shadow, component), allowed token names, sanitization metadata. Token names follow css-selector-contract.md §7 (--aio-*).
- **Component override spec**: [pb-style-components-spec.json](../specs/pb-style-components-spec.json) — component ids, element roles, selector patterns, allowed_token_overrides; aligns with css-selector-contract §3.4.
- **Render surfaces spec**: [pb-style-render-surfaces-spec.json](../specs/pb-style-render-surfaces-spec.json) — allowed surfaces (:root, .aio-page, section wrapper) for emitting CSS variables; no new selectors.
- **Style registry contract**: [style-registry-contract.md](style-registry-contract.md) — registry responsibilities, read-only lookup, spec versioning, loading and security rules.

No new structural selectors or token names are introduced by the specs; they document and constrain the existing contract.

**Global token emission (Prompt 249):** `Global_Token_Variable_Emitter` reads validated global token values from `aio_global_style_settings`, confirms names against the token registry, and emits only approved `--aio-*` custom properties. Emission is scoped to `:root` per render-surfaces spec. Invalid token names or values are omitted (fail closed). `Frontend_Style_Enqueue_Service` appends the emitted `:root { ... }` block as inline style when the base stylesheet is enqueued.

**Global component override emission (Prompt 250):** `Global_Component_Override_Emitter` reads validated global component overrides from the same option, confirms component ids and token names against the component spec, and emits scoped CSS rules using only spec-derived selectors (e.g. `[class*="aio-s-"][class*="__card"]` for element role `card`). No new structural selectors or arbitrary declarations; invalid override data is omitted. Output is appended by `Frontend_Style_Enqueue_Service` together with the token block. Usable on front end and in preview contexts.

---

## 11. Extension Boundaries for Later Prompts

Later work may add, in order, without breaking this contract:

- **Storage**: Global options and optional per-entity style payload schema; version/cache markers.
- **Registry implementation**: Runtime loader that reads the three spec files and exposes lookup per style-registry-contract.md.
- **Sanitizers**: **Styles_JSON_Normalizer**, **Styles_JSON_Sanitizer**, and **Style_Validation_Result** (Prompt 252). Whitelist-based validation and normalization against the machine-readable specs; see styling-sanitization-rules.md.
- **Emitters**: Stylesheet or scoped inline output from approved token/spec data only.
- **Admin UI**: Screens to view/edit global styling and optionally per-entity overrides; no editing of selector/token **names**.
- **Export/restore and diagnostics**: Inclusion of styling metadata in support bundles and export manifests; bounded and redacted.

No later prompt may:

- Change saved post_content or inject styles into content.
- Introduce new structural selectors or token **names** outside the CSS contract.
- Store or emit arbitrary CSS or user-defined selectors.

---

## 12. Cross-References

- **Spec**: §17.10, §18, §18.11; [aio-page-builder-master-spec.md](../specs/aio-page-builder-master-spec.md).
- **CSS/selector contract**: [css-selector-contract.md](css-selector-contract.md).
- **Rendering**: [rendering-contract.md](rendering-contract.md).
- **Portability**: [PORTABILITY_AND_UNINSTALL.md](../standards/PORTABILITY_AND_UNINSTALL.md).
- **Retrofit impact**: [styling-retrofit-impact-analysis.md](../qa/styling-retrofit-impact-analysis.md).
- **Style specs and registry**: [style-registry-contract.md](style-registry-contract.md); pb-style-core-spec.json, pb-style-components-spec.json, pb-style-render-surfaces-spec.json.

---

## 13. Revision History

| Version | Date | Change |
|---------|------|--------|
| 1 | Prompt 242 | Initial styling subsystem contract (Option A). |
| 2 | Prompt 252 | §7: normalizer, sanitizer, Style_Validation_Result; persist_*_result; link to styling-sanitization-rules.md. §11: sanitizers extension. |
