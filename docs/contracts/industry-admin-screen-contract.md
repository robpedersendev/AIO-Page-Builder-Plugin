# Industry Admin Screen Contract

**Spec**: aio-page-builder-master-spec.md (industry pack extension); industry-pack-extension-contract; industry-profile-schema.

**Status**: Contract. Defines the admin settings/profile management surface for Industry Profile and industry-aware directory filtering.

---

## 1. Purpose and scope

- **Industry Profile Settings screen**: Admin-only screen (or settings tab) to view and edit the site Industry Profile: primary/secondary industry selection, readiness/completeness status, active pack references and warnings. Saves via Industry_Profile_Repository and Industry_Profile_Validator. Does not replace or overload the existing onboarding flow.
- **Section library industry filtering**: Admin UI for the section template directory to filter by recommendation view (recommended_only, recommended_plus_weak_fit, full_library) and to show recommendation badges and explanation snippets. Full section library remains accessible.
- **Page template directory industry filtering**: Admin UI for the page template directory to filter by industry fit and to show hierarchy-fit and LPagery-fit indicators with explanation snippets. Full template library remains accessible.

**Out of scope for this contract**: Full library filtering UI redesign, Build Plan integration, whole admin navigation redesign. Section/page template definitions and rendering are unchanged.

---

## 2. Industry Profile Settings screen

### 2.1 Routing and capability

- **Slug**: `aio-page-builder-industry-profile` (or as defined by `Industry_Profile_Settings_Screen::SLUG`).
- **Parent**: Settings submenu (parent slug `aio-page-builder-settings`).
- **Capability**: `aio_manage_settings` (Capabilities::MANAGE_SETTINGS). Enforced at menu registration and again in screen render and save handler.

### 2.2 Nonce and save action

- **Save action**: `admin_post_aio_save_industry_profile`.
- **Nonce field**: `aio_industry_profile_nonce`; action: `aio_save_industry_profile`.
- **Method**: POST. Handler verifies nonce and capability, then validates payload with Industry_Profile_Validator and persists via Industry_Profile_Repository (merge or set). Invalid updates must fail safely with clear redirect message (e.g. `?page=...&aio_industry_result=error` or `saved`).

### 2.3 Data and behavior

- **Source of truth**: Industry_Profile_Repository (site-level Industry Profile). Schema and normalization: Industry_Profile_Schema; validation and readiness: Industry_Profile_Validator and Industry_Profile_Readiness_Result.
- **Display**: Current profile (primary_industry_key, secondary_industry_keys, optional subtype/service_model/geo_model as desired), readiness state/score, validation errors/warnings, active pack label and status (from Industry_Pack_Registry when primary is set).
- **Form**: Primary industry (dropdown from pack registry active packs), secondary industries (multi-select from same). Optional fields may be shown; question_pack_answers are not required to be edited on this screen (onboarding remains the primary path). Form built by Industry_Profile_Form_Builder; screen does not perform direct save—POST is handled by Admin_Menu (or dedicated handler) with nonce and capability checks.

### 2.4 Guidance links

- Screen may expose links to industry-pack extension docs or onboarding for question-pack completion when readiness is partial.

---

## 3. Section library industry filtering (admin UI)

- **Filter control**: View state `industry_view` (e.g. `recommended_only` | `recommended_plus_weak_fit` | `full_library`). Default or missing profile → full_library/neutral.
- **Data source**: Industry_Section_Library_Read_Model_Builder (and existing section directory state). Filter controller (e.g. Industry_Section_Library_Filter_Controller) applies read model to directory list when industry_view is set; otherwise full list is shown.
- **Badges**: Per-section recommendation badge (recommended, weak_fit, discouraged) and optional explanation snippet/tooltip from read model item view. Rendered via a dedicated partial (e.g. `industry-section-badges.php`).
- **Access**: Full section library always reachable (e.g. “Show all” or `industry_view=full_library`). No permanent hiding of sections.

---

## 4. Page template directory industry filtering (admin UI)

- **Filter control**: View state for industry fit (e.g. recommended_only, recommended_plus_weak_fit, full_library). Missing/invalid profile → full_library/neutral.
- **Badges**: Recommended, weak fit, discouraged, hierarchy fit, LPagery fit; explanation snippets from page template read model. Rendered via a dedicated partial (e.g. `industry-template-badges.php`).
- **Data source**: Industry_Page_Template_Directory_Read_Model_Builder and existing page template directory state. Filter controller (e.g. Industry_Page_Template_Filter_Controller) enriches or filters directory state when industry view is set.
- **Access**: Full template library always reachable. No permanent hiding of templates.

---

## 5. Security and failure behavior

- All industry admin surfaces are admin-only (capability as above or `aio_view_build_plans` for directory screens).
- Invalid or missing Industry Profile must result in safe fallback: neutral/full-library behavior in directories; clear error message on profile save.
- No client-side mutation of recommendation or readiness state that bypasses server validation.

---

## 6. Files and inventory

- **Screen**: `plugin/src/Admin/Screens/Industry/Industry_Profile_Settings_Screen.php`
- **Form**: `plugin/src/Admin/Forms/Industry_Profile_Form_Builder.php`
- **Section filter**: `plugin/src/Admin/Screens/Sections/Industry_Section_Library_Filter_Controller.php`; view: `plugin/src/Admin/Views/sections/industry-section-badges.php`
- **Page template filter**: `plugin/src/Admin/Screens/PageTemplates/Industry_Page_Template_Filter_Controller.php`; view: `plugin/src/Admin/Views/page-templates/industry-template-badges.php`
- **Inventory**: admin-screen-inventory.md lists Industry Profile screen and notes industry filter/badge behavior for section and page template directories.
