# Style Registry Contract

**Upstream**: [styling-subsystem-contract.md](styling-subsystem-contract.md), [css-selector-contract.md](css-selector-contract.md)  
**Specs**: [pb-style-core-spec.json](../specs/pb-style-core-spec.json), [pb-style-components-spec.json](../specs/pb-style-components-spec.json), [pb-style-render-surfaces-spec.json](../specs/pb-style-render-surfaces-spec.json)  
**Status**: Contract and runtime implementation. Loader: `Style_Spec_Loader`; registries: `Style_Token_Registry`, `Component_Override_Registry`, `Render_Surface_Style_Registry`. Wired via `Styling_Provider`; specs loaded from plugin-owned path (plugin_dir() . 'specs/' with fallback to repo docs/specs/ when present).

---

## 1. Purpose

This contract defines the **style registry**: a read-only lookup layer that loads and exposes the machine-readable style specs (core tokens, component overrides, render surfaces) for use by sanitizers, emitters, and optional UI. The registry is plugin-local and independent from any external plugin.

---

## 2. Registry Responsibilities

| Responsibility | Description |
|----------------|-------------|
| **Load specs** | Read the three spec files (pb-style-core-spec.json, pb-style-components-spec.json, pb-style-render-surfaces-spec.json) from plugin-owned paths only. No runtime path loading from arbitrary or user-supplied paths. |
| **Expose token metadata** | Provide lookup by token group and token name: allowed names, value type, sanitization rules (from core spec). |
| **Expose component overrides** | Provide lookup by component id: element_role, selector_pattern, allowed_token_overrides (from components spec). |
| **Expose render surfaces** | Provide list of allowed render surfaces: id, selector, scope, allowed_output (from render-surfaces spec). |
| **Read-only** | Registry does not mutate specs or accept user input to add/remove token names or selectors. |
| **Versioning** | Expose spec_version (and optionally spec_schema) from each loaded spec for compatibility and cache invalidation. |

---

## 3. Spec File Purposes

| File | Purpose |
|------|---------|
| **pb-style-core-spec.json** | Defines core token groups (color, typography, spacing, radius, shadow, component), token name patterns (--aio-*), allowed_names per group, and sanitization metadata (value_type, allowed_formats, max_length). |
| **pb-style-components-spec.json** | Defines component override rules: component id, element_role (from css-selector-contract §3.4), selector_pattern (aio-s-{section_key}__{element}), allowed_token_overrides list, sanitization rule. |
| **pb-style-render-surfaces-spec.json** | Defines allowed render surfaces: id, selector (:root, .aio-page, section wrapper pattern), scope, allowed_output. |

---

## 4. Spec Versioning and Compatibility

| Rule | Description |
|------|-------------|
| **spec_version** | Each spec file includes a spec_version field (e.g. "1"). Registry must expose it; consumers may use it for cache keys and compatibility checks. |
| **Compatibility** | Specs may include a compatibility block (min_plugin_version, css_contract_ref). Registry does not enforce compatibility; that is the responsibility of the loader or caller. |
| **Schema evolution** | New token names or new components require a spec revision and contract alignment; no new structural selectors or token name patterns outside the CSS contract. |

---

## 5. Sanitization Metadata Usage

- **Core spec**: token_groups[*].sanitization defines value_type, allowed_formats, max_length (and optionally whitelist_from for component group).
- **Components spec**: allowed_token_overrides is the whitelist of token names that may be set for that component; sanitization_metadata.rule is whitelist-only; unknown_component or unknown_token_name → reject.
- **Registry**: Exposes this metadata for sanitizer implementations; registry does not perform sanitization itself.

---

## 6. Security and Loading Rules

| Rule | Description |
|------|-------------|
| **Internal artifacts** | Spec files are internal plugin artifacts. Not editable by untrusted users in this contract; loading from plugin directory only. |
| **No arbitrary paths** | Runtime must not load specs from user input, theme, or other plugins. |
| **Whitelist-driven** | All sanitization metadata is whitelist-driven; no arbitrary CSS or selector injection. |

---

## 7. Cross-References

- [styling-subsystem-contract.md](styling-subsystem-contract.md)
- [css-selector-contract.md](css-selector-contract.md)
- [pb-style-core-spec.json](../specs/pb-style-core-spec.json)
- [pb-style-components-spec.json](../specs/pb-style-components-spec.json)
- [pb-style-render-surfaces-spec.json](../specs/pb-style-render-surfaces-spec.json)

---

## 8. Revision History

| Version | Date | Change |
|---------|------|--------|
| 1 | Prompt 243 | Initial style registry contract. |
