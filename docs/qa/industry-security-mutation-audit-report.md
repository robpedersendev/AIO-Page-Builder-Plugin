# Industry Capabilities, Nonces, and Unsafe-Mutation Security Audit Report (Prompt 608)

**Spec:** Security and privacy docs; admin mutation docs; export/restore docs; Build Plan approval/execution docs.  
**Purpose:** Security-focused audit of all admin mutations, repair flows, exports, imports, approvals, settings saves, and review actions in the industry subsystem.

---

## 1. Scope audited

- **Industry profile save:** Admin_Menu (aio_save_industry_profile): wp_verify_nonce(..., 'aio_save_industry_profile'); capability not re-checked in handler (menu registration is capability-gated; handler runs in admin context). Profile validation and normalize before save. Industry_Profile_Settings_Screen::render() uses current_user_can(get_capability()).
- **Pack toggle:** Admin_Menu (aio_toggle_industry_pack): wp_verify_nonce(..., 'aio_toggle_industry_pack'); container Industry_Pack_Toggle_Controller used for mutation.
- **Style preset apply:** Admin_Menu (aio_apply_industry_style_preset): wp_verify_nonce(..., 'aio_apply_industry_style_preset').
- **Override save/remove:** Save_Industry_Section_Override_Action, Save_Industry_Page_Template_Override_Action, Save_Industry_Build_Plan_Override_Action: wp_verify_nonce(..., self::NONCE_ACTION); current_user_can(Capabilities::MANAGE_SECTION_TEMPLATES | MANAGE_PAGE_TEMPLATES | APPROVE_BUILD_PLANS) as appropriate. Remove_Industry_Override_Action: nonce + capability check per target_type (section/page_template/build_plan_item).
- **Guided repair:** Admin_Menu: aio_guided_repair_migrate_nonce, aio_guided_repair_apply_ref_nonce, aio_guided_repair_activate_nonce — each wp_verify_nonce with screen NONCE_ACTION_*; handlers perform repair actions after check.
- **Bundle preview:** aio_industry_bundle_preview_nonce (POST). Preview only; apply not implemented; no confirm-import action.
- **Export/restore:** Import_Export_Screen: NONCE_CREATE_EXPORT, NONCE_VALIDATE, NONCE_RESTORE, NONCE_DOWNLOAD — wp_verify_nonce on each. Restore validates payload and schema version; no silent overwrite. Admin-only screen.
- **Build Plan approval/deny:** Build_Plan_Workspace_Screen: NONCE_ACTION_STEP1_REVIEW, STEP2_REVIEW, NAVIGATION_REVIEW, ROLLBACK — wp_verify_nonce before approve_item/deny_item/rollback. Capability implied by menu/screen access.
- **Create plan from starter bundle:** Create_Plan_From_Starter_Bundle_Action: nonce check (NONCE_ACTION).
- **All Industry screens:** Industry_Author_Dashboard_Screen, Industry_Health_Report_Screen, Industry_Profile_Settings_Screen, Industry_Guided_Repair_Screen, Industry_Drift_Report_Screen, Future_Industry_Readiness_Screen, Future_Subtype_Readiness_Screen, Industry_Scaffold_Promotion_Readiness_Report_Screen, Industry_Override_Management_Screen, Industry_Bundle_Import_Preview_Screen, etc. — current_user_can(get_capability()) at render; wp_die or redirect on failure.
- **Preview/comparison/simulation:** Industry preview and what-if simulation paths are read-only (audited in 595, 598); no mutation. Comparison screens are read-only.

---

## 2. Findings summary

| Area | Result | Notes |
|------|--------|--------|
| **Nonce on state-changing actions** | Verified | Profile save, pack toggle, style preset, override save/remove, guided repair (migrate/apply_ref/activate), bundle preview, export/restore/download, Build Plan approve/deny/rollback, create plan from bundle — all use wp_verify_nonce with named action. |
| **Capability on state-changing actions** | Verified | Override actions check MANAGE_SECTION_TEMPLATES, MANAGE_PAGE_TEMPLATES, APPROVE_BUILD_PLANS. Screens check get_capability() (VIEW_LOGS or equivalent). Menu registration restricts industry pages to capable users. |
| **No hidden mutation in preview/comparison/simulation** | Verified | Preview resolvers and What-If simulation return computed data; no write to profile, overrides, or build plan. Guided repair and bundle import are explicit actions with nonce. |
| **Export/restore security** | Verified | Export/restore/download behind nonce; restore validates schema version and payload; admin-only. No public export/restore endpoints. |
| **Failure paths safe** | Verified | Nonce/capability failure leads to redirect or wp_die; no partial mutation. Restore with invalid payload does not apply; pipeline exits without overwriting. |

---

## 3. Recommendations

- **No code changes required.** State-changing industry paths are protected by nonce and capability; preview/simulation remain non-mutating; export/restore boundaries are enforced.
- **Tests:** Add nonce/capability failure-path tests for representative mutation actions and regression tests for preview/comparison/simulation non-mutation per prompt 608.

---

## 4. References

- [industry-implementation-audit-service-map.md](../operations/industry-implementation-audit-service-map.md)
- [industry-audit-remediation-ledger.md](../operations/industry-audit-remediation-ledger.md)
