# Data Schema Appendix

**Spec**: §62.3 Data Schema Appendix; §57.9 Documentation Standards; Prompt 215.

This appendix contains schema summaries for CPTs, key option structures, token set, Build Plan storage, crawl snapshot, and the payload families introduced by the template expansion. All shapes align with implemented code; no invented behavior.

---

## 1. CPT schema summaries

### Section template (section template registry)

Stored as custom post type. Key fields (Section_Schema): internal_key, name, purpose_summary, section_purpose_family, variation_family_key, category, status, version (version block), deprecation (deprecation block), field blueprints, helper_doc_ref, compatibility_notes. See [Section Template Inventory](section-template-inventory.md) and Section_Schema.

### Page template (page template registry)

Stored as custom post type. Key fields (Page_Template_Schema): internal_key, name, purpose_summary, template_family, variation_family, template_category_class, ordered_section_keys, optional_section_keys, status, version (version block), deprecation (deprecation block), one_pager_ref, metadata. See [Page Template Inventory](page-template-inventory.md) and Page_Template_Schema.

### Composition

Stored in composition registry (custom storage). Key fields (Composition_Schema): composition_id, name, ordered_section_list (array of section_key, position, variant), status, validation_status, source_template_ref. Used for preview assembly and export validation.

---

## 2. Key option structures

- **Reporting log**: array of entries (event_type, dedupe_key, attempted_at, delivery_status, log_reference, failure_reason). Option_Names::REPORTING_LOG.
- **Install notice state**: dedupe_key, sent_at, site_reference, log_reference. Option_Names::INSTALL_NOTICE_STATE.
- **Heartbeat state**: last_successful_month, site_reference, log_reference; or for retry: for_month, attempt_count, last_attempt_at. Option_Names::HEARTBEAT_STATE.
- **Error report state**: sent_dedupe_keys (array), retry_for_dedupe_key, retry_attempt_count, retry_last_attempt_at. Option_Names::ERROR_REPORT_STATE.
- **Template preference profile**: stored under Profile_Store root key template_preference_profile. Fields: page_emphasis, conversion_posture, proof_style, content_density, animation_preference, cta_intensity_preference, reduced_motion_preference. See Template_Preference_Profile::to_array().
- **Compare list**: user meta; list of section or page template internal_keys per type ('section' | 'page'). Capped at Template_Compare_State_Builder::MAX_COMPARE_ITEMS (10).

---

## 3. Token set schema

Export token set: key-value pairs for placeholders (e.g. site name, contact). Read via Export_Token_Set_Reader; used in export manifest and restore. See export manifest appendix and global-options-schema where applicable.

---

## 4. Build Plan storage schema

Build plans are stored as defined in spec §30 and execution contracts. Envelope and step structures include optional template keys (template_key, template_family, template_category_class), new-page and existing-page step payloads, and execution artifacts (template_build_execution_result, template_replacement_execution_result, replacement_trace_record, finalization_summary, template_execution_closure_record). See prompt-schema and ai-output-schema appendices for planning output shape.

---

## 5. Crawl snapshot schema

Crawl snapshots store run metadata, page list, and classification results. **hierarchy_clues** (or equivalent) may contain **crawl_template_family_hint**: suggested_page_class, suggested_families, confidence. Consumed by Template_Recommendation_Context_Builder when crawl_run_id is set. See crawler contracts.

---

## 6. Preview cache record (preview payload)

Single cache entry (Preview_Cache_Record). Keys:

| Field          | Type    | Description |
|----------------|---------|-------------|
| cache_key      | string  | Unique key (hash of context + version). |
| type           | string  | `section` \| `page`. |
| template_key   | string  | Section or page template internal_key. |
| version_hash   | string  | Hash for invalidation on template/version change. |
| html           | string  | Rendered preview HTML (synthetic data only). |
| created_at     | int     | Unix timestamp. |
| reduced_motion | bool    | Whether reduced-motion was applied. |
| animation_tier | string  | none \| subtle \| enhanced \| premium. |

---

## 7. Synthetic preview context

Input to synthetic preview data generation (Synthetic_Preview_Context). Not stored; passed at render time. Type (section|page), key, purpose_family, template_category_class, variant, reduced_motion, animation_tier, omission_case. See template-preview-and-dummy-data-contract.

---

## 8. Template compare state payload

Output of Template_Compare_State_Builder::build_state(type, compare_list_keys):

| Field                 | Type   | Description |
|-----------------------|--------|-------------|
| type                  | string | `section` \| `page`. |
| compare_list_keys     | list   | Ordered list of template keys (capped). |
| section_compare_matrix| array  | Rows when type=section; else []. |
| page_compare_matrix   | array  | Rows when type=page; else []. |
| template_compare_rows | array  | Alias for the active matrix. |
| base_url_sections     | string | Admin URL for section templates. |
| base_url_pages        | string | Admin URL for page templates. |
| compare_screen_url    | string | URL of Template Compare screen. |
| empty_message         | string | Message when compare list is empty. |

Each **template_compare_row** contains definition metadata (key, name, purpose, fields/sections summary, etc.) from the registry.

---

## 9. Version block (section and page template)

Applied to definition[Section_Schema::FIELD_VERSION] or Page_Template_Schema::FIELD_VERSION. Built by Template_Versioning_Service.

| Field               | Type   | Description |
|---------------------|--------|-------------|
| version             | string | Version string (e.g. "1", "2"). |
| stable_key_retained | bool   | Whether internal_key is unchanged. |
| changelog_ref       | string | Optional changelog reference. |
| breaking            | bool   | Optional; true if revision is breaking. |

---

## 10. Deprecation block (section and page template)

Merged into definition when status => 'deprecated'. Built by Template_Deprecation_Service. Contains status, deprecation (reason, replacement refs), replacement_section_suggestions (section) or replacement_template_refs (page). See Template_Deprecation_Service::get_section_deprecation_block, get_page_template_deprecation_block.

---

## 11. Template library compliance result payload

Output of Template_Library_Compliance_Service::run() → Template_Library_Compliance_Result::to_array():

| Field                    | Type  | Description |
|--------------------------|-------|-------------|
| count_summary            | array | section_total, page_total, section_target, page_target, by_section_purpose_family, by_page_category_class, by_page_family. |
| category_coverage_summary| array | section_family_minimums, page_class_minimums, max_share_violations. |
| cta_rule_violations      | array | List of { template_key, code, message }. |
| preview_readiness        | array | sections_missing_preview, pages_missing_one_pager. |
| metadata_checks          | array | sections_missing_accessibility, sections_invalid_animation. |
| export_viability         | array | viable (bool), errors (list). |
| passed                   | bool  | True when no hard-fail violations. |

---

## 12. Template diff summary (template_diff_summary)

Output of Template_Diff_Summary_Builder::build(pre_snapshot, post_snapshot). Uses **intended_template_key** from pre_snapshot state when present to set template_key_before:

| Field                     | Type   | Description |
|---------------------------|--------|-------------|
| template_key_before       | string | Template key before change (from intended_template_key when applicable). |
| template_key_after        | string | Template key after change. |
| template_family_after     | string | Template family after change. |
| section_count_after      | int    | Section count after change. |
| template_structural_change | bool | True if template_key changed. |
| rollback_template_context| array  | See §13. |

---

## 13. Rollback template context (rollback_template_context)

Template_Diff_Context::to_array(); embedded in template_diff_summary and diff/rollback payloads:

| Field              | Type   | Description |
|--------------------|--------|-------------|
| template_key       | string | Template internal_key. |
| template_family    | string | Template family. |
| template_variation | string | Variation. |
| cta_pattern_shift  | bool   | Whether CTA pattern changed. |
| version_context    | string | Version context (e.g. stable_key_retained). |
| deprecation_context| string | Deprecation context if any. |

---

## 14. Template build execution result (template_build_execution_result)

Template_Page_Build_Result::to_array(); attached to new-page creation artifacts:

| Field                    | Type   | Description |
|--------------------------|--------|-------------|
| success                  | bool   | Whether build succeeded. |
| post_id                  | int    | Created post ID. |
| template_key             | string | Page template internal_key. |
| template_family           | string | Template family. |
| template_category_class  | string | Category class. |
| hierarchy_applied        | bool   | Whether hierarchy was applied. |
| parent_post_id           | int    | Parent post ID when hierarchy applied. |
| one_pager_available     | bool   | One-pager present. |
| one_pager_metadata      | array  | One-pager ref/metadata. |
| section_count            | int    | Number of sections. |
| field_assignment_count   | int    | Field assignments. |
| warnings                 | array  | Warning messages. |
| errors                   | array  | Error messages. |
| log_ref                  | string | Log reference. |
| message                  | string | Human-readable message. |

---

## 15. Template replacement execution result (template_replacement_execution_result)

Template_Page_Replacement_Result::to_array(); attached to replace-page artifacts. Fields include success, target_post_id, superseded_post_id, snapshot_ref, template_key, template_family, **replacement_trace_record** (object: target_post_id, superseded_post_id, snapshot_ref, template_key), assignment_count, errors.

---

## 16. Replacement trace record (replacement_trace_record)

Sub-object in template_replacement_execution_result: target_post_id, superseded_post_id, snapshot_ref, template_key. Used for snapshot-backed audit and rollback.

---

## 17. Finalization summary and template execution closure record

Template_Finalization_Result::to_array(): **finalization_summary** (counts and completion state), **template_execution_closure_record** (closure record for the run), **run_completion_state**, **one_pager_retention_summary**. Consumed by Finalization_Job_Service and stored in job/operational artifacts.

---

## 18. Template library report summary (template_library_report_summary)

Optional payload in install, heartbeat, and error report payloads (Template_Library_Report_Summary_Builder). Keys: section_template_count, page_template_count, composition_count, library_version_marker, plugin_version_marker, appendices_available, compliance_summary. See §62.7–62.9 and install-notification-email-template, heartbeat-email-templates, error-email-templates appendices.

---

## 19. Template library lifecycle summary (template_library_lifecycle_summary)

Template_Library_Lifecycle_Summary_Builder::build(). Keys: built_pages_survive, built_pages_description, template_registry_exportable, template_registry_description, one_pagers_description, appendices_description, previews_description, restore_guidance, deactivation_message; optional section_template_count, page_template_count, composition_count. Used on Privacy and Import/Export screens (Prompt 213).

---

## 20. Template preference profile (template_preference_profile)

Template_Preference_Profile::to_array(). Stored in Profile_Store. Fields: page_emphasis, conversion_posture, proof_style, content_density, animation_preference, cta_intensity_preference, reduced_motion_preference. All optional; advisory for planning. See [Glossary](glossary.md#template_preference_profile).

---

## 21. Form provider integration (form_provider, form_id)

Provider-backed form sections store which form to render using two stable ACF field names. Schema is defined in [form-provider-integration-contract.md](../contracts/form-provider-integration-contract.md).

| Field / concept | Type / location | Description |
|-----------------|-----------------|-------------|
| form_provider | string (ACF field) | Provider identifier (e.g. `ndr_forms`). Must be registered in Form_Provider_Registry. Sanitized per registry. |
| form_id | string (ACF field) | Form identifier within the provider. Sanitized per registry; format provider-defined. |
| form_embed | string (section category) | Section template category for sections whose primary content is an embedded form. Provider-backed variant uses form_provider + form_id. |
| Page-template aggregation | ordered_section_keys | Request-form page template (e.g. `pt_request_form`) includes provider-backed form section(s) in ordered_section_keys like any other section. |
| Export/import | Registry + content | Form references persist via section/page definitions and ACF field values; no separate form-reference export schema. |
| Picker adapter (additive) | Non-canonical | Form_Provider_Picker_Discovery_Service and Form_Provider_Picker_Adapter_Interface expose provider display label, availability, optional form list, and fallback entry label for UI. Canonical storage remains form_provider and form_id. See [form-provider-picker-adapter-contract.md](../contracts/form-provider-picker-adapter-contract.md). |
| Form provider health summary (additive) | Non-canonical | Form_Provider_Health_Summary_Service builds a bounded payload for diagnostics and support bundles: provider_availability, registered_provider_ids, section_templates_with_forms_count, page_templates_using_forms_count, recent_failures_summary, built_at. No secrets. See [form-provider-availability-state-contract.md](../contracts/form-provider-availability-state-contract.md). |
| Additional provider onboarding | Contract | New providers (e.g. WPForms, CF7) follow [additional-form-provider-onboarding-contract.md](../contracts/additional-form-provider-onboarding-contract.md) and [form-provider-onboarding-checklist.md](../operations/form-provider-onboarding-checklist.md). Canonical storage (form_provider, form_id) and schema versioning remain unchanged; only registry and adapter are additive. |

---

## 22. Styling subsystem (specs and registry)

Machine-readable style specs and style registry are defined by contract; no runtime schema in this appendix until storage is implemented.

| Artifact | Location | Description |
|----------|----------|-------------|
| Core token spec | [pb-style-core-spec.json](../specs/pb-style-core-spec.json) | spec_version, token_groups (color, typography, spacing, radius, shadow, component), allowed_names, sanitization metadata. Token names are --aio-* per css-selector-contract. |
| Component override spec | [pb-style-components-spec.json](../specs/pb-style-components-spec.json) | spec_version, components (id, element_role, selector_pattern, allowed_token_overrides). Aligns with css-selector-contract §3.4. |
| Render surfaces spec | [pb-style-render-surfaces-spec.json](../specs/pb-style-render-surfaces-spec.json) | spec_version, render_surfaces (id, selector, scope, allowed_output). Surfaces: :root, .aio-page, section wrapper. |
| Style registry | [style-registry-contract.md](../contracts/style-registry-contract.md) | Read-only lookup over the three specs; spec versioning; plugin-local loading. No new selectors or token names. |
| Applied design tokens (existing) | Option `aio_applied_design_tokens` | [ group => [ name => value ] ]; see Token_Set_Job_Service. Styling subsystem extends value source only; names remain from contract. |

---

## Cross-references

- Section/page template field details: [Section Template Inventory](section-template-inventory.md), [Page Template Inventory](page-template-inventory.md).
- Form provider: [form-provider-integration-contract.md](../contracts/form-provider-integration-contract.md), [form-provider-retrofit-impact-analysis.md](../contracts/form-provider-retrofit-impact-analysis.md).
- Styling subsystem: [styling-subsystem-contract.md](../contracts/styling-subsystem-contract.md), [style-registry-contract.md](../contracts/style-registry-contract.md), [styling-retrofit-impact-analysis.md](../qa/styling-retrofit-impact-analysis.md).
- Planning output and AI schema: [Prompt Schema Appendix](prompt-schema-appendix.md), [AI Output Schema Appendix](ai-output-schema-appendix.md).
- Reporting payloads: [Error Email Templates](error-email-templates.md), [Heartbeat Email Templates](heartbeat-email-templates.md), [Install Notification Email Template](install-notification-email-template.md).
- Terms: [Glossary](glossary.md).
