# Industry Onboarding, Settings, and Admin Save-Flow Audit Report (Prompt 590)

**Spec:** Admin-screen contracts; Industry Profile docs; goal and bundle docs; capability/security docs.  
**Purpose:** Audit of onboarding and industry settings flows so profile selections, bundle choices, goal selections, and admin mutations save correctly, validate correctly, display clear errors, and honor nonce/capability checks.

---

## 1. Scope audited

- **Industry Profile save:** `Admin_Menu::handle_save_industry_profile()` (admin_post_aio_save_industry_profile); `Industry_Profile_Settings_Screen`; `Industry_Profile_Form_Builder`; `Industry_Starter_Bundle_Assistant` (bundle field).
- **Toggle industry pack:** `Admin_Menu::handle_toggle_industry_pack()`; `Industry_Pack_Toggle_Controller`; nonce/capability in Admin_Menu.
- **Industry style preset apply:** `Admin_Menu::handle_apply_industry_style_preset()`.
- **Guided repair actions:** `Industry_Guided_Repair_Screen` (migrate, apply_ref, activate nonces).
- **Bundle import confirm:** `Industry_Bundle_Import_Preview_Screen` (nonce on confirm).
- **Override remove:** `Remove_Industry_Override_Action` (nonce; capability per target type in Override Management).
- **Screen capability:** All Industry screens checked use `get_capability()` and `current_user_can()` before render.

---

## 2. Findings summary

| Area | Result | Notes |
|------|--------|--------|
| **Profile save nonce** | Verified | `handle_save_industry_profile` checks `aio_industry_profile_nonce` and `wp_verify_nonce(..., 'aio_save_industry_profile')`; redirects with error on failure. |
| **Profile save capability** | Verified | `current_user_can( Capabilities::MANAGE_SETTINGS )` before any profile mutation; redirect on failure. |
| **Profile validation before save** | Verified | `Industry_Profile_Validator::validate( $merged, ... )` run before `merge_profile()`; redirect with error if validation fails. Invalid refs/combinations do not persist. |
| **Profile fields saved** | Verified | Primary industry, secondary industries, selected starter bundle (validated against registry and primary), industry_subtype_key (validated against subtype registry and parent). Subtype cleared when primary changes. |
| **Conversion goal on Profile screen** | Observation | `conversion_goal_key` and `secondary_conversion_goal_key` are in `Industry_Profile_Schema` but are **not** in `Industry_Profile_Form_Builder::get_field_config()` and are **not** included in `$partial` in `handle_save_industry_profile()`. So goals are not saved from the Industry Profile Settings screen. If product intent is to edit goals from this screen, a follow-up change would add fields and handler support; otherwise they may be set only via onboarding or another flow. |
| **Bundle selection save** | Verified | `Industry_Starter_Bundle_Assistant::FIELD_NAME` read from POST; value validated against bundle registry and primary industry; only valid bundle key for current primary is persisted. |
| **Toggle pack nonce/capability** | Verified | `handle_toggle_industry_pack` verifies nonce and `MANAGE_SETTINGS`; delegates to `Industry_Pack_Toggle_Controller`. |
| **Error feedback** | Verified | Failed nonce/capability/validation redirect to profile screen with `aio_industry_result=error`; success uses `aio_industry_result=saved`. Screen can display result message. |
| **Cache invalidation** | Verified | After successful profile merge, industry read-model cache is invalidated. |
| **Guided repair / Override / Bundle import** | Verified | Nonces and capability checks present on state-changing actions as noted above. |

---

## 3. Recommendations

- **No code changes required** for security or validation correctness; nonce, capability, and validation are in place for the profile save flow.
- **Optional follow-up:** If conversion goal and secondary goal should be editable from Industry Profile Settings, add form fields and extend `handle_save_industry_profile` to read, validate, and merge those keys (out of scope for this audit per "Do not add new setting dimensions" unless required for correctness).

---

## 4. References

- [industry-implementation-audit-service-map.md](../operations/industry-implementation-audit-service-map.md)
- [industry-audit-remediation-ledger.md](../operations/industry-audit-remediation-ledger.md)
