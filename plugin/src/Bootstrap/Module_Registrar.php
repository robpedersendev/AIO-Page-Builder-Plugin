<?php
/**
 * Registers bootstrap-level service providers in a stable order.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Bootstrap;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Providers\ACF_Blueprints_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\ACF_Assignment_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Crawler_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\ACF_Compatibility_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\ACF_Debug_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\ACF_Diagnostics_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\ACF_Registration_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\ACF_Repair_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Rendering_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Admin_Router_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Capability_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Config_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Dashboard_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Execution_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\ExportRestore_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Diagnostics_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Object_Registration_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\AI_Provider_Base_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\AI_Failover_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\AI_Provider_Drivers_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\AI_Prompt_Pack_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\AI_Experiments_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\AI_Regression_Harness_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\AI_Runs_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\AI_Validation_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Onboarding_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Registries_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Rollback_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Reporting_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Build_Plan_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Repositories_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Storage_Services_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Styling_Provider;
use AIOPageBuilder\Bootstrap\Industry_Packs_Module;

/**
 * Loads and runs only bootstrap-level providers. Domain providers are registered during the full module registration phase.
 * Registration order is explicit and stable.
 */
final class Module_Registrar {

	/** @var Service_Container */
	private Service_Container $container;

	public function __construct( Service_Container $container ) {
		$this->container = $container;
	}

	/**
	 * Registers all bootstrap providers in order. Call once from Plugin::run().
	 * Config_Provider registers config and settings (see global-options-schema.md).
	 * Diagnostics_Provider registers logger and diagnostics helper (see diagnostics-contract.md).
	 * Crawler_Provider registers snapshot, crawl_profile_service, discovery, fetch, classification, extraction, and recrawl comparison (spec §24, §24.12–24.17, §59.7). Crawler-to-template family matching (Prompt 209): Crawl_Template_Family_Matcher and Crawl_Template_Match_Result classify pages against template families and hierarchy classes; hints are persisted in crawl snapshots (hierarchy_clues) and exposed to planning via Template_Recommendation_Context_Builder when options['crawl_run_id'] is set. Crawl profiles are bounded presets (quick_context_refresh, full_public_baseline, support_triage_crawl). Crawler admin screens (Sessions, Comparison) are registered in Admin_Menu and documented in crawler-admin-screen-contract.md.
	 * Queue & Logs screen (Queue_Logs_Screen) and reporting monitoring are registered in Admin_Menu; state from Logs_Monitoring_State_Builder (optionally includes acf_diagnostics_summary for diagnostics bundles) and Reporting_Health_Summary_Builder (spec §49.11). ACF Field Architecture diagnostics screen (ACF_Architecture_Diagnostics_Screen, ACF_Diagnostics_State_Builder) is registered in Admin_Menu; state builder provides field_architecture_health_card, assignment_mismatch_groups, lpagery_field_support_summary, regeneration_plan_summary (spec §20, §59.12; Prompt 223). Queue health and recovery (Queue_Health_Summary_Builder, Queue_Recovery_Service) are registered in Execution_Provider (spec §42, §49.11, §59.12). Template-aware menu apply (Template_Menu_Apply_Service, Template_Menu_Apply_Result) is registered in Execution_Provider; Apply_Menu_Change_Handler uses it when envelope has page_class or template_aware_menu (Prompt 207). Menu apply execution result and navigation_hierarchy_summary are captured in operational snapshots via Post_Change_Result_Builder. Template-aware finalization (Template_Finalization_Service, Template_Finalization_Result; Prompt 208) is registered in Execution_Provider; Finalization_Job_Service builds finalization_summary, template_execution_closure_record, run_completion_state, and one_pager_retention_summary. Finalization_Step_UI_Service and Logs_Monitoring_State_Builder expose run_completion_state for completed plans and finalize_plan rows. Template_Page_Build_Service and Template_Page_Build_Result (Prompt 194) harden the new-page executor for the expanded template library: hierarchy assignment, one-pager metadata, template_build_execution_result in artifacts, queue-safe and deterministic. Bulk_Template_Page_Build_Service and Bulk_Template_Page_Build_Result (Prompt 195) harden bulk new-page orchestration: dependency ordering (parent-first), slug collision validation, per-item status, retry-safe batch_id, partial-failure reporting. Template_Page_Replacement_Service and Template_Page_Replacement_Result (Prompt 196) harden existing-page replacement: snapshot-backed traceability, replacement_trace_record, template_replacement_execution_result, archive/private handling. Support Triage (Support_Triage_Dashboard_Screen, Support_Triage_State_Builder) is registered in Admin_Menu (spec §49.11, §59.12, §60.7). Post-Release Health (Post_Release_Health_Screen, Post_Release_Health_State_Builder) is registered in Admin_Menu (spec §45, §49.11, §59.15, §60.8). Build Plan Analytics (Build_Plan_Analytics_Screen, Build_Plan_Analytics_Service) is registered in Build_Plan_Provider and Admin_Menu (spec §30, §45, §49.11, §59.12). Template Analytics (Template_Analytics_Screen, Template_Analytics_Service) is registered in Build_Plan_Provider and Admin_Menu (spec §49.11, §56.1, §59.12, §59.15; Prompt 199). Template capability matrix and role-specific UI restriction (Prompt 200): Section/Page Templates and detail screens use MANAGE_SECTION_TEMPLATES / MANAGE_PAGE_TEMPLATES; Template Compare uses MANAGE_PAGE_TEMPLATES; Compositions uses MANAGE_COMPOSITIONS; Template Analytics uses VIEW_LOGS; template seed handlers gated by corresponding MANAGE_* caps; see docs/qa/template-capability-audit-report.md and capability-matrix §4. LPagery token compatibility (LPagery_Token_Compatibility_Service) and library-wide LPagery compatibility (Library_LPagery_Compatibility_Service, LPagery_Compatibility_Result) are registered in Rendering_Provider (spec §7.4, §20.7, §35, Prompt 179); section and page template detail state builders expose lpagery_compatibility_state when the service is available.
	 * ExportRestore_Provider registers export_generator (optional acf_local_json_mirror_service for acf_field_groups_mirror category, Prompt 224), support_package_generator (spec §59.15), template_library_support_summary_builder (Prompt 198), import validator, restore pipeline, template_library_export_validator and template_library_restore_validator (Prompt 185), template_library_lifecycle_summary_builder (Prompt 213), and import/export state builder.
	 * ACF_Debug_Provider registers acf_local_json_mirror_service, acf_field_group_debug_exporter, and acf_migration_verification_service for debug export, environment comparison (Prompt 224), and ACF upgrade/migration verification (Prompt 225); registry remains source of truth.
	 * Template ecosystem lifecycle (Prompt 213): Template_Library_Lifecycle_Summary_Builder (Domain\Lifecycle) produces template_library_lifecycle_summary for deactivation, uninstall, export, and restore UX; Privacy and Import/Export screens surface built-pages survivability, registry export, appendices/previews regenerable, and restore guidance; Lifecycle_Manager docblock references same.
	 * Registries_Provider registers large_library_query_service for filtered, paginated section/page template directory queries (spec §55.8; Prompt 188 template-admin performance hardening: MAX_PER_PAGE cap, template-admin-performance-hardening-report), template_library_compliance_service for the automated library-wide compliance pass (Prompt 176, template-library-compliance-matrix), template_accessibility_audit_service for the semantic/accessibility/CTA audit (Prompt 186, template-library-accessibility-audit-report), and animation_qa_service for cross-browser animation/fallback and reduced-motion QA (Prompt 187, template-library-animation-fallback-report). Page Templates directory (Page_Templates_Directory_Screen, Page_Template_Directory_State_Builder) and Section Templates directory (Section_Templates_Directory_Screen, Section_Template_Directory_State_Builder) use this service (spec §49.6, §49.7). Compositions screen (Compositions_Screen, Composition_Builder_State_Builder, Composition_Filter_State) is registered in Admin_Menu for large-library composition assembly (Prompt 177, spec §14, §49.6). Large-library composition validation (Large_Composition_Validator, Composition_Validation_Result) enforces CTA rules, compatibility, and preview/one-pager readiness (Prompt 178, spec §14.3, §14.4); wired into composition_registry_service and Composition_Builder_State_Builder. Page template detail (Page_Template_Detail_Screen, Page_Template_Detail_State_Builder) provides metadata, used-section list, one-pager link, and rendered preview via the real pipeline and synthetic data (spec §49.7, §17). Section template detail (Section_Template_Detail_Screen, Section_Template_Detail_State_Builder) provides metadata, field summary, helper-doc ref, compatibility notes, and rendered section preview (spec §49.6, §17). Template Compare workspace (Template_Compare_Screen, Template_Compare_State_Builder) provides side-by-side comparison of section or page templates from a user compare list (user meta); observational only (Prompt 180, spec §49.6, §49.7). Section and Page Template Inventory Appendix generators (Section_Inventory_Appendix_Generator, Page_Template_Inventory_Appendix_Generator) produce docs/appendices markdown from live registries per spec §62.11, §62.12 (Prompt 181). Template versioning and deprecation workflow (Template_Versioning_Service, Template_Deprecation_Service, template-library-decision-log, Prompt 189) are registered in Registries_Provider; detail state builders expose version_summary and deprecation_summary. Template-aware snapshot, diff, and rollback context (Prompt 197) are provided by Post_Change_Result_Builder template_context, Pre_Change_Snapshot_Builder intended_template_key, Template_Diff_Summary_Builder, Diff_Summarizer_Service enrichment, and Rollback_State_Builder (Execution_Provider, Rollback_Provider). Planner-facing template recommendation context (Template_Recommendation_Context_Builder, Prompt 190) is registered in Registries_Provider and injected into onboarding input artifact; Build_Plan_Template_Explanation_Builder and New_Page_Creation_Detail_Builder add template rationale to Build Plan new-pages step. New_Page_Template_Recommendation_Builder (Prompt 192) adds family/hierarchy grouping, proposed_template_summary, and template detail/compare links to Step 2 rows; registered in Build_Plan_Provider. Existing_Page_Template_Change_Builder (Prompt 193) adds existing_page_template_change_summary and replacement_reason_summary to Step 1 rows and detail panel; template detail/compare links in Build_Plan_Workspace_Screen. Synthetic preview data (Domain\Preview): Synthetic_Preview_Data_Generator produces deterministic, family-aware synthetic ACF payloads for section and page previews; Synthetic_Preview_Context carries type, key, family, variant, reduced_motion, animation_tier, omission_case; Preview_Side_Panel_Builder builds side-panel metadata for detail screens (template-preview-and-dummy-data-contract §4, §7).
	 * Reporting_Provider registers log_export_service (spec §48.10, §59.12) for structured log export from Queue & Logs screen; log export includes template_family and template_operation categories (Prompt 198). Template-aware reporting (Prompt 214): Template_Library_Report_Summary_Builder enriches install, heartbeat, and error report payloads with bounded template_library_report_summary (counts, version markers, appendices_available, compliance_summary); Install_Notification_Service, Heartbeat_Service, Developer_Error_Reporting_Service accept optional summary builder; heartbeat_service is registered and exposed via filter for cron; appendices §62.7–62.9 document optional field.
	 * Rendering_Provider registers smart_omission_service, animation_tier_resolver, and wires both into section_renderer_base (smart-omission-rendering-contract; animation-support-and-fallback-contract).
	 * Styling_Provider registers style specs, registries, global_style_settings_repository, global_token_variable_emitter, global_component_override_emitter (Prompt 250), style_cache_service (Prompt 256), and frontend_style_enqueue_service; base stylesheet plus inline :root and component-scoped CSS variables when enqueued; style cache versioning and invalidation on global/per-entity style changes.
	 * ACF_Blueprints_Provider and ACF_Registration_Provider: blueprint_family_registry, blueprint_family_resolver, preview_family_mapping; ACF registration centralized via ACF_Registration_Bootstrap_Controller (acf/init priority 5; timing see docs/qa/acf-registration-hook-timing-report.md); deterministic registration per large-scale-acf-lpagery-binding-contract and acf-conditional-registration-contract.
	 * Admin menu and screen routing are registered separately in Plugin::register_admin_menu().
	 * AI_Provider_Drivers_Provider registers openai and anthropic provider drivers (spec §25, §49.9). Prompt packs (Prompt 210): Prompt_Pack_Registry_Service exposes get_planning_guidance_content() for template-family taxonomy, CTA-law rules, and hierarchy-role guidance; planning_guidance placeholders and optional segments (template_family_guidance, cta_law_guidance, hierarchy_role_guidance) inject this into planning prompts per template-family-planning-prompt-pack-addendum; prompt-schema and ai-output-schema appendices document structure and versioning.
	 * Template-recommendation regression (Prompt 211): Template_Recommendation_Regression_Harness and Template_Recommendation_Regression_Result under Domain\AI\Regression provide golden-case regression for template-family recommendations (class/family fit, CTA-law alignment, explanation); fixtures under tests/fixtures/template-recommendations; see docs/qa/prompt-pack-regression-guide.md.
	 * Template preference profile (Prompt 212): Template_Preference_Profile (Domain\Profile) and bounded onboarding fields (page emphasis, conversion posture, proof style, content density, animation, CTA intensity, reduced motion) are stored via Profile_Store (template_preference_profile root); Onboarding_Screen step template_preferences collects and persists them; Template_Recommendation_Context_Builder and onboarding artifact include template_preference_profile for advisory planning and recommendation context.
	 *
	 * @return void
	 */
	public function register_bootstrap(): void {
		$providers = array(
			new Config_Provider(),
			new Dashboard_Provider(),
			new Diagnostics_Provider(),
			new Crawler_Provider(),
			new Admin_Router_Provider(),
			new Capability_Provider(),
			new Object_Registration_Provider(),
			new Repositories_Provider(),
			new Build_Plan_Provider(),
			new Execution_Provider(),
			new Rollback_Provider(),
			new Reporting_Provider(),
			new ACF_Blueprints_Provider(),
			new ACF_Registration_Provider(),
			new ACF_Assignment_Provider(),
			new ACF_Compatibility_Provider(),
			new ACF_Diagnostics_Provider(),
			new ACF_Repair_Provider(),
			new ACF_Debug_Provider(),
			new Rendering_Provider(),
			new Registries_Provider(),
			new AI_Validation_Provider(),
			new AI_Provider_Base_Provider(),
			new AI_Failover_Provider(),
			new AI_Provider_Drivers_Provider(),
			new AI_Prompt_Pack_Provider(),
			new AI_Regression_Harness_Provider(),
			new AI_Experiments_Provider(),
			new AI_Runs_Provider(),
			new Storage_Services_Provider(),
			new ExportRestore_Provider(),
			new Onboarding_Provider(),
			new Styling_Provider(),
			new Industry_Packs_Module(),
		);
		foreach ( $providers as $provider ) {
			$provider->register( $this->container );
		}
	}

	/** @return Service_Container */
	public function container(): Service_Container {
		return $this->container;
	}
}
