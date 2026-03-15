# Global Styling Settings Contract

**Spec**: [styling-subsystem-contract.md](styling-subsystem-contract.md), css-selector-contract.md  
**Status**: Schema and repository implemented (Prompt 246). Admin UI for global tokens (Prompt 247).

---

## 1. Purpose

This contract defines the **global styling settings** storage: a versioned, plugin-owned option that holds global token values and global component overrides for the styling subsystem. It is **separate** from the existing `aio_applied_design_tokens` option used by the build plan and rollback flow.

---

## 2. Option and Schema

| Key | Type | Description |
|-----|------|-------------|
| **Option key** | `aio_global_style_settings` | Single option holding the full blob. |
| `version` | string | Schema version for migration (e.g. `"1"`). |
| `global_tokens` | object | `[ group => [ name => value ] ]` — same shape as token groups in the core spec; only allowed group/name pairs and scalar string values. |
| `global_component_overrides` | object | `[ component_id => [ token_var_name => value ] ]` — only allowed component ids and token variable names per component spec. |

- **No raw CSS**: Values are token values (color strings, lengths, font stacks, etc.), not arbitrary CSS rules or selectors.
- **No secrets**: Styling storage must not contain API keys or sensitive data.
- **Invalid keys/values**: Repository strips disallowed keys and caps value length; invalid input fails closed.

---

## 3. Relationship to aio_applied_design_tokens

| Aspect | `aio_applied_design_tokens` | `aio_global_style_settings` |
|--------|-----------------------------|------------------------------|
| **Purpose** | Build plan token apply step; rollback/snapshot; execution artifact. | Runtime global styling for the styling subsystem; consumed by emitters and UI. |
| **Written by** | Token_Set_Job_Service (plan execution). | Global_Style_Settings_Repository (settings UI, future migration). |
| **Read by** | Rollback, snapshots, build plan display. | Style emitter, admin token settings screen. |
| **Shape** | `[ group => [ name => value ] ]` (same group/name semantics). | `version` + `global_tokens` (same shape for tokens) + `global_component_overrides`. |

**Separation**: The two options are **not** merged or repurposed. Build plan and rollback continue to use `aio_applied_design_tokens`. The styling subsystem uses `aio_global_style_settings` as the source of truth for global runtime styling. A future prompt may add an optional one-way copy (e.g. “Apply plan tokens to global settings”) as a documented action; this contract does not require it.

---

## 4. Repository and Validation

- **Global_Style_Settings_Repository**: Read/write; `get_full()`, `get_version()`, `get_global_tokens()`, `set_global_tokens()`, `get_global_component_overrides()`, `set_global_component_overrides()`, `reset_to_defaults()`.
- **Validation**: All writes are filtered through Style_Token_Registry and Component_Override_Registry when available; only allowed group/name or component_id/token_name pairs are persisted; value length is capped per spec or a safe default.
- **Missing/corrupt option**: `get_full()` and getters return defaults or empty structures; no fatal.

---

## 5. Security

- Invalid keys and values must not be stored (fail closed).
- No raw CSS text or user-supplied selectors.
- Safe behavior for corrupt or missing option (return defaults, empty arrays).

---

## 6. Cross-References

- [styling-subsystem-contract.md](styling-subsystem-contract.md)
- [style-registry-contract.md](style-registry-contract.md)
- Token_Set_Job_Service (aio_applied_design_tokens)
- Global_Style_Settings_Schema, Global_Style_Settings_Repository

---

## 7. Revision History

| Version | Date | Change |
|---------|------|--------|
| 1 | Prompt 246 | Initial global styling settings contract. |
