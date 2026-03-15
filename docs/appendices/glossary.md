# Glossary Appendix

**Spec**: §62.1 Glossary; §0.6 Definitions and Terminology; §57.9 Documentation Standards; Prompt 215.

This appendix defines key product and technical terms used in the specification, architecture, planning, execution, reporting, storage, and privacy sections. It reduces ambiguity and supports future implementers and reviewers. Terms align with real screen labels and code-facing concepts.

---

## Core template terms

**section template**  
A reusable content block definition stored in the section template registry (CPT). Each section has an `internal_key`, purpose, field blueprints, optional helper docs, and belongs to a **section_purpose_family**. Used as ordered building blocks inside **page templates** and **compositions**. See [Section Template Inventory](section-template-inventory.md).

**page template**  
A reusable full-page definition stored in the page template registry (CPT). Defines ordered and optional section references, **template_family**, **template_category_class**, hierarchy role, and optional one-pager. Used by the Build Plan and execution engine to create or replace pages. See [Page Template Inventory](page-template-inventory.md).

**composition**  
A named assembly of section instances (key, position, variant) tied to a source page template. Stored in the composition registry; used for preview, validation, and export. Schema: `Composition_Schema` (composition_id, name, ordered_section_list, status, validation_status, source_template_ref). See data-schema appendix.

**template family**  
A taxonomy slug grouping templates by purpose or role. For section templates: **section_purpose_family** (e.g. hero, cta, faq, comparison). For page templates: **template_family** (e.g. home, services, about, contact) and optionally **variation_family**. Used for planning guidance, recommendations, and compliance coverage.

**template_category_class**  
Page-template classification for hierarchy and planning: e.g. `top_level`, `hub`, `child_detail`, `nested_hub`. Informs parent-child relationships and **hierarchy_class** in execution.

**section_purpose_family**  
The family slug of a section template (e.g. hero, conversion, faq, comparison). Used in compliance **count_summary** (`by_section_purpose_family`) and planning.

**hierarchy_class**  
The hierarchy role applied when a page is created from a page template (e.g. top-level, hub, child). Stored in **template_build_execution_result** and operational snapshots for rollback and diff.

**variation_family**  
Optional sub-family for page template variants (e.g. conversion_01, conversion_02) within a **template_family**. Used in directory filtering and compare.

**form provider**  
An external plugin or service that provides forms (e.g. NDR Form Manager). Registered in Form_Provider_Registry with a provider_id, shortcode_tag, and id_attr. The AIO Page Builder stores **form_provider** and **form_id** in section field values and renders the form via the provider’s shortcode. See [form-provider-integration-contract.md](../contracts/form-provider-integration-contract.md).

**form_provider**  
Stable ACF field name: the provider identifier (e.g. `ndr_forms`) for a provider-backed form section. Must be registered in Form_Provider_Registry. Stored with **form_id** as the canonical storage contract for “which form to render.”

**form_id**  
Stable ACF field name: the form identifier within the provider (e.g. form post ID or slug). Stored with **form_provider**; format is provider-defined. Sanitized and validated before render or save.

**form_embed**  
Section template category (Section_Schema) for sections whose primary content is an embedded form. The **provider-backed** variant uses form_provider and form_id and renders via Form_Provider_Registry; other uses (e.g. free-text shortcode slot) may have different field semantics.

**provider-backed form section**  
A section template that uses **form_provider** and **form_id** to reference an external form and renders it via Form_Provider_Registry::build_shortcode(). Example: `form_section_ndr`. Governed by form-provider-integration-contract.

**request-form page template**  
A page template that includes one or more provider-backed form sections in its ordered_section_keys (e.g. `pt_request_form`). Structure is defined in the page template registry; the form provider does not define page structure.

**form provider picker adapter**  
Per-provider contract (Form_Provider_Picker_Adapter_Interface) for admin UI: display label, availability, optional form-list API, normalized picker items, stale-item reporting, and fallback when no list. See [form-provider-picker-adapter-contract.md](../contracts/form-provider-picker-adapter-contract.md).

**form provider discovery**  
Form_Provider_Picker_Discovery_Service: resolves which providers support the picker contract and returns normalized, sanitized state (dropdown vs manual form_id entry). Capability checks are the caller’s responsibility.

**form provider onboarding**  
Formal process for adding a second or subsequent form provider (e.g. WPForms, Contact Form 7). Governed by [additional-form-provider-onboarding-contract.md](../contracts/additional-form-provider-onboarding-contract.md) and [form-provider-onboarding-checklist.md](../operations/form-provider-onboarding-checklist.md). Requires registry registration, picker adapter, availability/caching integration, and compliance with security, export/restore, and QA obligations. Retrofit-first; no change to canonical storage.

---

## CTA-law and compliance terms

**CTA law**  
The set of structural and editorial rules governing call-to-action placement, density, and direction across sections and pages. Encoded in prompt-pack **cta_law_rules** and enforced by the template library compliance pass. Recommendations and Build Plan outputs should align with CTA law; **template_preference_profile** is advisory and does not override it.

**CTA rule**  
A single rule within CTA law (e.g. max CTAs per section, CTA direction consistency). Violations are recorded in **Template_Library_Compliance_Result** as **cta_rule_violations** (template_key, code, message).

**max-share violation**  
A compliance violation where a category or family exceeds its allowed share of the library (recorded in **category_coverage_summary.max_share_violations**). Used by **Template_Library_Compliance_Service**.

**cta_pattern_shift**  
Boolean in **rollback_template_context** and **Template_Diff_Context** indicating whether a template change involved a meaningful CTA pattern change; used for diff/rollback explainability.

**template library compliance**  
Automated library-wide pass (Template_Library_Compliance_Service) that produces **Template_Library_Compliance_Result**: count summary, category coverage, CTA rule violations, preview/one-pager readiness, metadata checks, export viability, and a single **passed** flag.

**compliance result**  
The machine-readable result of a template library compliance run: **count_summary**, **category_coverage_summary**, **cta_rule_violations**, **preview_readiness**, **metadata_checks**, **export_viability**, **passed**. See data-schema appendix.

---

## Preview and compare terms

**preview cache**  
Bounded storage for rendered template preview HTML (Preview_Cache_Service). Keys are derived from template key and **version_hash**; invalidated on template/version change. No production content; **synthetic preview data** only.

**preview payload**  
The payload sent to or stored for a single preview: **Preview_Cache_Record** (cache_key, type, template_key, version_hash, html, created_at, reduced_motion, animation_tier). See data-schema appendix.

**synthetic preview context**  
Input to synthetic preview data generation (Synthetic_Preview_Context): type (section|page), key, purpose_family, template_category_class, variant, reduced_motion, animation_tier, omission_case. Deterministic; no secrets or production data.

**animation tier**  
Preview and rendering tier for motion: `none`, `subtle`, `enhanced`, `premium`. Respects **reduced_motion** (when true, effectively `none`). See animation-support-and-fallback-contract.

**compare list**  
User-maintained list of section or page template keys for side-by-side comparison. Stored in user meta; capped at **MAX_COMPARE_ITEMS** (10). Retrieved via Template_Compare_Screen::get_compare_list(type).

**template compare state**  
State for the Template Compare workspace: **type** (section|page), **compare_list_keys**, **section_compare_matrix** or **page_compare_matrix**, **template_compare_rows**, base URLs, **empty_message**. Built by Template_Compare_State_Builder from registry data; observational only.

**template_compare_row**  
One row in the compare matrix: definition metadata for a single section or page template (key, name, purpose, fields/sections summary, etc.) for display in the compare screen.

---

## Versioning and deprecation terms

**version block**  
Metadata block on a section or page template definition: version string, stable_key_retained, optional changelog_ref, optional breaking flag. Built by Template_Versioning_Service (build_section_version_block, build_page_template_version_block). See Section_Schema::FIELD_VERSION, Page_Template_Schema::FIELD_VERSION.

**deprecation block**  
Metadata applied when a template is deprecated: status => 'deprecated', deprecation (reason, replacement refs), replacement_section_suggestions or replacement_template_refs. Built by Template_Deprecation_Service; validated before apply.

**replacement_key**  
The internal_key of a suggested replacement template when deprecating a section or page template. Stored in deprecation block and used for migration guidance.

**template versioning**  
Workflow for advancing template version (Template_Versioning_Service): version block construction, suggest_next_version, version summary for display. Does not mutate registries; callers apply and persist.

**template deprecation**  
Workflow for marking templates deprecated (Template_Deprecation_Service): validation, deprecation block building, replacement references, decision-log/changelog support. Status becomes 'deprecated'; definition remains in registry.

---

## Execution and diff artifacts

**template_build_execution_result**  
Stable payload produced when creating a new page from a page template (Template_Page_Build_Service): success, post_id, template_key, template_family, template_category_class, hierarchy_applied, parent_post_id, one_pager_available, one_pager_metadata, section_count, field_assignment_count, warnings, errors, log_ref, message. Stored in job artifacts and operational snapshots.

**template_replacement_execution_result**  
Stable payload produced when replacing an existing page with a template (Template_Page_Replacement_Service): success, target_post_id, superseded_post_id, snapshot_ref, template_key, template_family, replacement_trace_record, assignment_count, errors. Stored in job artifacts.

**replacement_trace_record**  
Audit record for a page replacement: target_post_id, superseded_post_id, snapshot_ref, template_key. Enables snapshot-backed rollback and traceability.

**finalization_summary**  
Summary produced at run finalization (Template_Finalization_Service): counts and high-level completion state. Part of Template_Finalization_Result with **template_execution_closure_record** and **run_completion_state**.

**template_execution_closure_record**  
Stable record closing a template-aware run: finalization summary, run completion state, one_pager_retention_summary. Used for operator-facing completion and rollback/diff review.

**rollback_template_context**  
Template context embedded in diff/rollback payloads (Template_Diff_Context): template_key, template_family, template_variation, cta_pattern_shift, version_context, deprecation_context. Permission-safe; no secret or raw artifact data.

**template_diff_summary**  
Payload built from pre/post snapshots (Template_Diff_Summary_Builder): template_key_before, template_key_after, template_family_after, section_count_after, template_structural_change, rollback_template_context. Used in diff UI and rollback context.

---

## Planning, reporting, and other terms

**Build Plan**  
The structured plan produced by the AI planning phase: steps (e.g. new pages, existing page updates), template recommendations, and execution envelopes. Stored and executed by the execution engine. See spec §30, §59.12.

**AI run**  
A single invocation of the AI planning or execution pipeline; may produce artifacts, snapshots, and log entries. Tracked for reporting and diagnostics.

**artifact**  
A structured payload attached to a job, step, or run (e.g. template_build_execution_result, finalization_summary). Used for logging, rollback, and UI state.

**snapshot**  
A point-in-time capture of state (e.g. pre-change, post-change). Operational snapshots include result_snapshot, state_snapshot, and optional template_context. See Operational_Snapshot_Schema, Pre_Change_Snapshot_Builder.

**intended_template_key**  
Pre-change state field (in state_snapshot) holding the page template internal_key targeted for a replacement. Used by Template_Diff_Summary_Builder to populate template_key_before in **template_diff_summary** when comparing pre/post snapshots.

**rollback**  
The process of reverting a change using stored snapshots and diff context. Template-aware rollback uses **rollback_template_context** and **template_diff_summary**.

**token set**  
Stored set of placeholder tokens (e.g. site name, contact) used for export/import and content generation. Schema and usage in Export_Token_Set_Reader and option storage.

**reporting event**  
A single outbound reporting occurrence: install notification, heartbeat, or developer error report. Typed by Reporting_Event_Types; payloads follow Reporting_Payload_Schema. See §46, §62.7–62.9.

**template_library_report_summary**  
Optional payload in install, heartbeat, and error reports (Prompt 214): section_template_count, page_template_count, composition_count, library_version_marker, plugin_version_marker, appendices_available, compliance_summary. Support-safe; no secrets.

**template_preference_profile**  
User preferences for planning and recommendations (Template_Preference_Profile): page_emphasis, conversion_posture, proof_style, content_density, animation_preference, cta_intensity_preference, reduced_motion_preference. Advisory only; does not override CTA law or planner judgment. Stored in Profile_Store under template_preference_profile.

**crawl_template_family_hint**  
Suggestion from crawl classification (Crawl_Template_Family_Matcher): suggested_page_class, suggested_families, confidence. Persisted in crawl snapshots as **hierarchy_clues** and exposed to planning via Template_Recommendation_Context_Builder when crawl_run_id is set.

**one-pager**  
Short documentation reference for a page template (or section), stored with the template definition. **one_pager_available** and **one_pager_metadata** appear in template_build_execution_result and finalization. Appendices (section/page inventory) are generated from registries; one-pagers are template-attached.

**template_library_lifecycle_summary**  
Payload for deactivation, uninstall, and restore UX (Template_Library_Lifecycle_Summary_Builder): built_pages_survive, template_registry_exportable, descriptions for one-pagers, appendices, previews, restore_guidance, deactivation_message, optional counts. See Prompt 213; Privacy and Import/Export screens.
