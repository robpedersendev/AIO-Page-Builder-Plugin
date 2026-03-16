# Industry Pack Activation Contract

**Spec**: industry-pack-extension-contract.md; industry-pack-extension-contract (additive, optional). **Prompt**: 389.

This contract defines admin controls for enabling or disabling industry packs without removing pack definitions or profile data. Disabled packs are excluded from recommendations and overlays; profile references are preserved and a warning is shown.

---

## 1. Purpose

- Allow admins to **turn off** an industry pack (or pack-driven surfaces) explicitly.
- **Preserve** industry profile data (primary_industry_key, selected_starter_bundle_key, etc.); do not delete or corrupt when a pack is disabled.
- **Warn** when the profile's primary industry references a disabled pack.
- **Fall back** to generic recommendations and docs when a pack is disabled (treat as no pack for scoring, overlays, and guidance).
- Keep pack toggling **reversible** and **explicit**; expose active/inactive state to diagnostics and admin.

---

## 2. State

- **Disabled pack keys**: A stored list of industry_key values that the admin has disabled. Stored in a single option (e.g. `aio_page_builder_disabled_industry_packs`). Shape: list of non-empty strings; versioned and exportable. No secrets; no execution instructions.
- **Pack definition**: Unchanged. Pack definition files and registry are not modified by activation state. The registry remains the source of truth for pack *definition*; the toggle state only affects whether a pack is *applied*.

---

## 3. Behavior

- **Active pack**: industry_key is in the registry and **not** in the disabled list. Recommendations, overlays, and guidance use the pack.
- **Inactive/disabled pack**: industry_key is in the disabled list. Recommendations and overlays **do not** use the pack; behavior is the same as "no pack" or generic. Profile may still reference the key; no automatic profile change.
- **Admin toggle**: Nonce and capability check required. Toggling adds or removes the industry_key from the disabled list. Reversible.
- **Profile + disabled primary**: When profile.primary_industry_key is set and that pack is disabled, show a warning on Industry Profile (and optionally onboarding/diagnostics). Do not clear the profile; let the admin re-enable the pack or change primary industry.

---

## 4. Fallback

- Any consumer that uses the "primary pack" for recommendations, scoring, overlays, or guidance must consider activation state: if the primary pack is disabled, treat as **no primary pack** (generic fallback). Examples: Build Plan scoring, section/page recommendation resolvers, style preset application, LPagery posture. Diagnostics and admin surfaces may still show "primary industry: X (disabled)" for clarity.

---

## 5. Security and permissions

- Toggle action: admin-only; capability `aio_manage_settings` (or equivalent per admin-screen-contract). Nonce for toggle request.
- Invalid or missing industry_key in toggle request: no-op or safe reject; do not modify other keys in the disabled list.

---

## 6. Implementation reference

- **Industry_Pack_Toggle_Controller** (plugin/src/Admin/Screens/Industry/Industry_Pack_Toggle_Controller.php): Reads/writes disabled list; `is_pack_active(industry_key)`, `get_disabled_pack_keys()`, `set_pack_disabled(industry_key, bool)`. Handles admin_post toggle with nonce and capability.
- **Option**: `Option_Names::DISABLED_INDUSTRY_PACKS` (list of industry_key). Export/restore may include this option; restore re-validates keys against registry.
- **Industry Profile screen**: Shows toggle for primary pack (or all packs); shows warning when primary is disabled. No automatic profile clear.
