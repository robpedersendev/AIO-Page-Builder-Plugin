# Styling Sanitization Rules (Prompt 252)

**Upstream**: [styling-subsystem-contract.md](../contracts/styling-subsystem-contract.md), [pb-style-core-spec.json](../../plugin/specs/pb-style-core-spec.json), [pb-style-components-spec.json](../../plugin/specs/pb-style-components-spec.json)

---

## 1. Purpose

All styling payloads (global tokens, global component overrides, per-entity style payloads) must pass through a **whitelist-based** normalization and sanitization pipeline before persistence or emission. This document defines prohibited patterns, whitelist rules, and allowed value types/units.

---

## 2. Pipeline Components

| Component | Responsibility |
|-----------|----------------|
| **Styles_JSON_Normalizer** | Converts raw input into deterministic internal shapes (global tokens, global component overrides, entity payload). Drops non-string keys/values. |
| **Styles_JSON_Sanitizer** | Validates keys against token and component registries; validates values for prohibited patterns and length; returns **Style_Validation_Result**. |
| **Style_Validation_Result** | Immutable result: `valid`, bounded list of `errors`, and `sanitized` payload when valid. Safe for admin display and logging. |

---

## 3. Whitelist Rules

- **Token keys (global or per-entity token_overrides)**: Group and name must exist in the core style spec (`token_groups`). The `component` group is excluded from global token editing; component-level overrides use the component override spec.
- **Component override keys**: Component id must exist in the component spec; token variable names (e.g. `--aio-color-primary`) must be listed in that component’s `allowed_token_overrides`.
- **Value types / units**: Constrained by the spec’s `sanitization` (e.g. `value_type`, `allowed_formats`, `max_length`). Length is enforced; format checks may be extended in a later prompt.

---

## 4. Prohibited Patterns (Values)

Any style **value** containing the following is **rejected** (case-insensitive where relevant):

| Pattern | Reason |
|---------|--------|
| `url(` | Prevents script or resource injection via `url()`. |
| `expression(` | Blocks legacy IE expression() injection. |
| `javascript:` | Script protocol. |
| `vbscript:` | Script protocol. |
| `data:` | Can carry script or executable content. |
| `<` | Prevents HTML/script injection. |
| `>` | Prevents HTML/script injection. |
| `{` | Prevents injection of CSS blocks or JSON. |
| `}` | Prevents injection of CSS blocks or JSON. |

No arbitrary selectors, raw CSS text, or `<style>` content may be stored or emitted.

---

## 5. Allowed Value Types (from spec)

From `pb-style-core-spec.json` per token group:

- **color**: hex, rgb, rgba, hsl, hsla, css_named (max_length 128).
- **typography**: font_family_string, css_font_stack (max_length 256).
- **spacing / radius**: length with px, rem, em, ch, percent (max_length 24–32).
- **shadow**: css_box_shadow (max_length 256).
- **component**: any_css_value whitelisted from component spec (max_length 256).

---

## 6. Fail Closed

- Invalid payloads must **not** be persisted. Use **Style_Validation_Result**: when `valid` is false, the repository must not write; callers may show bounded errors to the user.
- Validation results are bounded (max error count and max length per error message) so they are safe for admin UI and logs.

---

## 7. Integration

- **Global_Style_Settings_Repository**: Prefer persisting via a **Style_Validation_Result** (e.g. `persist_global_tokens_result` / `persist_global_component_overrides_result`) so only sanitizer-approved payloads are written.
- **Entity_Style_Payload_Repository**: Prefer `persist_entity_payload_result(entity_type, entity_key, result)` so only sanitizer-approved entity payloads are written.
- All style editing and emission flows must use the normalizer and sanitizer; no path may bypass them.
