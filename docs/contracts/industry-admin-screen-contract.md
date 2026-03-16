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

## 4.1 Multi-industry conflict and warning surfacing (Prompt 372)

- **Section library**: When the read model is built with weighted resolution (primary + secondary industries), conflict/warning badges and short explanation snippets are shown per item where multi-industry resolution produced warning_worthy conflicts. Rendered via `industry-conflict-badges.php`; data from `industry_weighted_by_key` and item-level `conflict_results` / `explanation_summary`.
- **Page template directory**: Same pattern—weighted read model may attach conflict results and explanation summary to items; conflict badges partial is included when present.
- **Build Plan review UI**: Industry plan explanations view (industry-plan-explanations.php) shows conflict badges and explanation summary when item payload contains industry conflict results or explanation summary (from Industry_Build_Plan_Explanation_View_Model). Recommendation logic remains centralized in resolvers; views only surface metadata.

---

## 4.1.1 Compliance and caution surfacing (Prompt 407)

- **Advisory only**: Structured compliance/caution rules (Industry_Compliance_Rule_Registry, Industry_Compliance_Warning_Resolver) are surfaced as **advisory** hints only. They do not block content or claim legal compliance.
- **Helper docs**: Composed_Helper_Doc_Result may include `compliance_warnings` (rule_key, severity, caution_summary). Consumers (e.g. section detail) may display these alongside composed_helper content.
- **One-pagers**: Composed_Page_OnePager_Result may include `compliance_warnings` for the page template + industry context.
- **Section detail preview**: Industry_Section_Preview_View_Model includes `compliance_warnings`; the section detail screen may show them in the industry fit block when present.
- **Page template detail preview**: Industry_Page_Template_Preview_View_Model includes `compliance_warnings`; the template detail screen may show them in the industry fit block when present.
- **Build Plan review**: New-page item detail panel "Industry context" section (industry-plan-explanations.php) shows an "Advisory" list of compliance cautions when primary industry is set and Industry_Compliance_Warning_Resolver is available. Warnings remain scoped and concise; no automatic blocking.

---

## 4.2 Industry-aware page template detail preview (Prompt 383)

- **Industry_Page_Template_Preview_Resolver**: Resolves industry-aware preview context for a single page template (recommendation fit, composed one-pager, hierarchy fit, LPagery posture, substitute suggestions). Read-only; safe when no industry profile.
- **Industry_Page_Template_Preview_View_Model**: DTO for the detail screen: has_industry, recommendation_fit, hierarchy_fit, lpagery_posture, composed_one_pager (allowed regions), substitute_suggestions, warning_flags, explanation_reasons, compliance_warnings (advisory; Prompt 407).
- **Page template detail screen**: When the industry preview resolver is available (container key `industry_page_template_preview_resolver`), the screen merges `industry_preview` (view model to_array()) into state and renders an industry fit block in the metadata panel: industry label, fit badge, hierarchy/LPagery notes, one-pager excerpt (hierarchy_hints, cta_strategy), warnings, and substitute suggestion links. Substitute suggestions are populated only when the resolver is called with a full template list; otherwise the list is empty. Generic fallback: when no industry profile or resolver unavailable, no industry block is shown.

---

## 4.3 Industry create-page-from-template assistant (Prompt 376)

- **Industry_Create_Page_Assistant**: Provides industry-aware guidance for the create-page-from-template flow. Call `build_state( $page_templates )` with the current template list, then use `has_industry_guidance()`, `get_recommended_template_keys()`, `get_fit_for_template( $key )`, `get_warning_flags_for_template( $key )`, and `get_substitute_template_keys( $key )` to show recommended templates first, weak-fit/discouraged warnings before page creation, and substitute suggestions. Full template library access and explicit override selection remain; actual template application logic is unchanged. Safe fallback when no industry profile.

---

## 4.4 Industry composition builder assistant (Prompt 377)

- **Industry_Composition_Assistant**: Provides industry-aware section guidance in the composition builder. Call `build_state( $sections )` with the section list, then use `has_industry_guidance()`, `get_recommended_section_keys()`, `get_fit_for_section( $key )`, `get_warning_flags_for_section( $key )`, and `get_substitute_section_keys( $key )` to surface recommended sections, warnings for weak-fit/discouraged choices, and substitute suggestions. CTA/purpose guidance continues to use existing composition builder validation and insertion hints. Manual selection control preserved; no auto-swap. Safe fallback when no industry profile.

---

## 4.5 Industry-aware section detail preview (Prompt 384)

- **Industry_Section_Preview_Resolver**: Resolves industry-aware preview context for a single section (recommendation fit, composed helper, warnings, substitute suggestions). Read-only; safe when no industry profile.
- **Industry_Section_Preview_View_Model**: DTO for the section detail screen: has_industry, recommendation_fit, composed_helper (allowed regions), substitute_suggestions, warning_flags, explanation_reasons, compliance_warnings (advisory; Prompt 407).
- **Section template detail screen**: When the industry section preview resolver is available (container key `industry_section_preview_resolver`), the screen merges `industry_preview` (view model to_array()) into state and renders an industry fit block in the metadata panel: industry label, fit badge, composed helper excerpt (tone_notes, cta_usage_notes), warnings, and substitute suggestion links. Substitute suggestions are populated only when the resolver is called with a full section list; otherwise the list is empty. Generic fallback: when no industry profile or resolver unavailable, no industry block is shown.

---

## 5. Security and failure behavior

- All industry admin surfaces are admin-only (capability as above or `aio_view_build_plans` for directory screens).
- Invalid or missing Industry Profile must result in safe fallback: neutral/full-library behavior in directories; clear error message on profile save.
- No client-side mutation of recommendation or readiness state that bypasses server validation.

---

## 5.1 Industry dashboard summary widget (Prompt 410)

- **Industry_Status_Summary_Widget**: Compact card on the main plugin Dashboard (overview) summarizing industry state. Shown only when the industry subsystem is loaded (container has `industry_profile_store`).
- **Content**: Primary and secondary industry labels, active pack or no-pack state, selected starter bundle label, profile readiness summary, and top health warning/error count. Links to Industry Profile settings and (when issues exist) to the Industry Health Report screen.
- **Safety**: Admin-only (same capability as dashboard). No secrets or raw internals. When no industry is configured, the card shows "Not configured" and a link to set up Industry Profile. Detailed diagnostics remain on the dedicated Industry Health Report screen.

---

## 6. Files and inventory

- **Screen**: `plugin/src/Admin/Screens/Industry/Industry_Profile_Settings_Screen.php`
- **Form**: `plugin/src/Admin/Forms/Industry_Profile_Form_Builder.php`
- **Section filter**: `plugin/src/Admin/Screens/Sections/Industry_Section_Library_Filter_Controller.php`; view: `plugin/src/Admin/Views/sections/industry-section-badges.php`
- **Page template filter**: `plugin/src/Admin/Screens/PageTemplates/Industry_Page_Template_Filter_Controller.php`; view: `plugin/src/Admin/Views/page-templates/industry-template-badges.php`
- **Page template preview resolver**: `plugin/src/Domain/Industry/Registry/Industry_Page_Template_Preview_Resolver.php`; view model: `plugin/src/Admin/ViewModels/PageTemplates/Industry_Page_Template_Preview_View_Model.php` (Prompt 383).
- **Section preview resolver**: `plugin/src/Domain/Industry/Registry/Industry_Section_Preview_Resolver.php`; view model: `plugin/src/Admin/ViewModels/Sections/Industry_Section_Preview_View_Model.php` (Prompt 384).
- **Create-page assistant**: `plugin/src/Admin/Screens/PageTemplates/Industry_Create_Page_Assistant.php` (Prompt 376).
- **Composition assistant**: `plugin/src/Admin/Screens/Compositions/Industry_Composition_Assistant.php` (Prompt 377).
- **Industry status summary widget**: `plugin/src/Admin/Widgets/Industry_Status_Summary_Widget.php` (Prompt 410). Rendered from Dashboard_Screen when industry profile store is present.
- **Inventory**: admin-screen-inventory.md lists Industry Profile screen and notes industry filter/badge behavior for section and page template directories.
