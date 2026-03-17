# Industry Style Preset Application Contract

**Spec**: industry-style-preset-schema.md; styling subsystem contracts (prompts 242–260); industry-admin-screen-contract.md.

**Status**: Admin workflow for selecting, previewing, and applying an industry style preset through the existing styling subsystem. Optional and reversible; no new styling engine or arbitrary CSS.

---

## 1. Purpose

- **Surface** available style presets for the active industry (from Industry_Style_Preset_Registry and Industry_Profile primary).
- **Preview** label, description, and preview metadata.
- **Apply** the selected preset through **existing** global styling settings storage and sanitizer (Global_Style_Settings_Repository, Style_Token_Registry). No arbitrary CSS or new token names.
- **Record** which preset is active (e.g. optional metadata or separate option) for revert and explanation.

---

## 2. Application path

- **Industry_Style_Preset_Application_Service**: Validates preset by key against Industry_Style_Preset_Registry; maps preset `token_values` (--aio-* => value) to the structure expected by Global_Style_Settings_Repository (group => [ name => value ]) using Style_Token_Registry to resolve allowed token names; merges with current global tokens and calls repository set_global_tokens. Optional: merge component overrides when preset defines component_override_refs and repository supports it. Records applied preset key (and optional label) for revert/display.
- **Sanitization**: All values pass through existing styling sanitization; invalid token names or prohibited value patterns are stripped before write. No raw CSS.
- **Revert**: Admin may clear applied preset (restore tokens to state before preset or to defaults per product); implementation may store previous snapshot or rely on “revert to defaults” plus manual re-apply.

---

## 3. Admin workflow

- **Industry_Style_Preset_Screen**: Under Settings (sibling to Industry Profile or under same parent). Lists presets for active industry (list_by_industry(primary)); shows label, description, preview_metadata; “Apply” action with nonce and capability check. POST to admin_post_aio_apply_industry_style_preset (or equivalent). Success/error redirect with query message.
- **Capability**: Same as global style tokens (e.g. aio_manage_settings). Nonce and capability enforced on apply and on screen render.

---

## 4. Data and storage

- Use **existing** global style option (aio_global_style_settings) for token values. Additive: optional **applied_industry_preset_key** (or separate option aio_applied_industry_preset) to record which preset is active; structure { preset_key, label?, applied_at } for audit and revert.
- No schema change to core styling blob beyond optional metadata key if desired; otherwise separate option.

---

## 5. Security

- Admin-only; nonce and capability checks on apply.
- Presets validated against Industry_Style_Preset_Registry; token names validated against Style_Token_Registry before merge. Prohibited value patterns (styling-sanitization-rules) applied.
- Invalid preset ref or malformed preset data: fail safely (no apply, redirect with error).

---

## 6. Conversion-goal overlay extension (Prompt 511)

- **Goal style preset overlays** layer on top of the applied industry (and subtype) preset per [conversion-goal-style-preset-contract.md](conversion-goal-style-preset-contract.md). When conversion_goal_key is set, application may merge goal overlay token_values and component_override_refs for the target preset before sanitization. When no goal or invalid goal, only industry/subtype preset apply.

---

## 7. Files

- **Screen**: plugin/src/Admin/Screens/Industry/Industry_Style_Preset_Screen.php
- **Application service**: plugin/src/Domain/Industry/Registry/Industry_Style_Preset_Application_Service.php
- **Contract**: docs/contracts/industry-style-preset-application-contract.md
