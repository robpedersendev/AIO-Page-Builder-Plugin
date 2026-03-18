<?php
/**
 * Registers the single top-level plugin menu and submenu pages.
 * Routes each page to a dedicated screen class (see admin-screen-inventory.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Screens\AI\AI_Providers_Screen;
use AIOPageBuilder\Admin\Screens\AI\AI_Runs_Screen;
use AIOPageBuilder\Admin\Screens\AI\Onboarding_Screen;
use AIOPageBuilder\Admin\Screens\AI\Prompt_Experiments_Screen;
use AIOPageBuilder\Admin\Screens\Analytics\Template_Analytics_Screen;
use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plan_Analytics_Screen;
use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plans_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Compositions_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Page_Template_Detail_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Page_Templates_Directory_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Section_Template_Detail_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Section_Templates_Directory_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Template_Compare_Screen;
use AIOPageBuilder\Admin\Screens\Crawler\Crawler_Comparison_Screen;
use AIOPageBuilder\Admin\Screens\Crawler\Crawler_Sessions_Screen;
use AIOPageBuilder\Admin\Screens\Dashboard\Dashboard_Screen;
use AIOPageBuilder\Admin\Screens\Diagnostics\ACF_Architecture_Diagnostics_Screen;
use AIOPageBuilder\Admin\Screens\Diagnostics\Form_Provider_Health_Screen;
use AIOPageBuilder\Admin\Screens\Diagnostics_Screen;
use AIOPageBuilder\Admin\Screens\Logs\Queue_Logs_Screen;
use AIOPageBuilder\Admin\Screens\Operations\Post_Release_Health_Screen;
use AIOPageBuilder\Admin\Screens\Support\Support_Triage_Dashboard_Screen;
use AIOPageBuilder\Admin\Screens\ImportExport\Import_Export_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Bundle_Import_Preview_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Author_Dashboard_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Guided_Repair_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Health_Report_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Drift_Report_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Maturity_Delta_Report_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Scaffold_Promotion_Readiness_Report_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Pack_Family_Comparison_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Stale_Content_Report_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Future_Industry_Readiness_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Future_Subtype_Readiness_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Override_Management_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Profile_Settings_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Pack_Toggle_Controller;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Starter_Bundle_Assistant;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Style_Preset_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Style_Layer_Comparison_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Conversion_Goal_Comparison_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Starter_Bundle_Comparison_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Subtype_Comparison_Screen;
use AIOPageBuilder\Admin\Screens\Settings\Global_Component_Override_Settings_Screen;
use AIOPageBuilder\Admin\Screens\Settings\Global_Style_Token_Settings_Screen;
use AIOPageBuilder\Admin\Screens\Settings\Privacy_Reporting_Settings_Screen;
use AIOPageBuilder\Admin\Screens\Settings_Screen;
use AIOPageBuilder\Domain\Registries\PageTemplate\ExpansionPack\Page_Template_And_Composition_Expansion_Pack_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\TopLevelBatch\Top_Level_Marketing_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\TopLevelLegalUtilityBatch\Top_Level_Legal_Utility_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\TopLevelEducationalResourceAuthorityBatch\Top_Level_Educational_Resource_Authority_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\TopLevelVariantExpansionBatch\Top_Level_Variant_Expansion_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\HubBatch\Hub_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\GeographicHubBatch\Geographic_Hub_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\NestedHubBatch\Nested_Hub_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\HubNestedHubVariantExpansionBatch\Hub_Nested_Hub_Variant_Expansion_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\ChildDetailBatch\Child_Detail_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\ChildDetailProductBatch\Child_Detail_Product_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\ChildDetailProfileEntityBatch\Child_Detail_Profile_Entity_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\ChildDetailVariantExpansionBatch\Child_Detail_Variant_Expansion_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\Section\ExpansionPack\Section_Expansion_Pack_Seeder;
use AIOPageBuilder\Domain\Registries\Section\HeroBatch\Hero_Intro_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\FeatureBenefitBatch\Feature_Benefit_Value_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\CtaSuperLibraryBatch\CTA_Super_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\LegalPolicyUtilityBatch\Legal_Policy_Utility_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\MediaListingProfileBatch\Media_Listing_Profile_Detail_Library_Batch_Seeder;
use AIOPageBuilder\Bootstrap\Industry_Packs_Module;
use AIOPageBuilder\Domain\Industry\Export\Industry_Bundle_Upload_Validator;
use AIOPageBuilder\Domain\Industry\Export\Industry_Pack_Bundle_Service;
use AIOPageBuilder\Domain\Industry\Export\Industry_Pack_Import_Conflict_Service;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Validator;
use AIOPageBuilder\Domain\Registries\Section\ProcessTimelineFaqBatch\Process_Timeline_FAQ_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\TrustProofBatch\Trust_Proof_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Registers admin menu and submenus. Screen rendering is delegated to screen classes.
 */
final class Admin_Menu {

	private const PARENT_SLUG = 'aio-page-builder';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	/**
	 * Registers the top-level menu and Dashboard, Settings, Diagnostics, Crawler submenus.
	 * Call from admin_menu action. Capability-aware; no mutation actions.
	 *
	 * @return void
	 */
	public function register(): void {
		\add_action( 'admin_post_aio_seed_form_templates', array( $this, 'handle_seed_form_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_section_expansion_pack', array( $this, 'handle_seed_section_expansion_pack' ), 10 );
		\add_action( 'admin_post_aio_seed_hero_intro_library_batch', array( $this, 'handle_seed_hero_intro_library_batch' ), 10 );
		\add_action( 'admin_post_aio_seed_trust_proof_library_batch', array( $this, 'handle_seed_trust_proof_library_batch' ), 10 );
		\add_action( 'admin_post_aio_seed_feature_benefit_value_batch', array( $this, 'handle_seed_feature_benefit_value_batch' ), 10 );
		\add_action( 'admin_post_aio_seed_process_timeline_faq_batch', array( $this, 'handle_seed_process_timeline_faq_batch' ), 10 );
		\add_action( 'admin_post_aio_seed_media_listing_profile_batch', array( $this, 'handle_seed_media_listing_profile_batch' ), 10 );
		\add_action( 'admin_post_aio_seed_legal_policy_utility_batch', array( $this, 'handle_seed_legal_policy_utility_batch' ), 10 );
		\add_action( 'admin_post_aio_seed_cta_super_library_batch', array( $this, 'handle_seed_cta_super_library_batch' ), 10 );
		\add_action( 'admin_post_aio_seed_page_composition_expansion_pack', array( $this, 'handle_seed_page_composition_expansion_pack' ), 10 );
		\add_action( 'admin_post_aio_seed_top_level_marketing_templates', array( $this, 'handle_seed_top_level_marketing_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_top_level_legal_utility_templates', array( $this, 'handle_seed_top_level_legal_utility_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_top_level_educational_resource_authority_templates', array( $this, 'handle_seed_top_level_educational_resource_authority_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_top_level_variant_expansion_templates', array( $this, 'handle_seed_top_level_variant_expansion_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_hub_page_templates', array( $this, 'handle_seed_hub_page_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_geographic_hub_templates', array( $this, 'handle_seed_geographic_hub_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_nested_hub_templates', array( $this, 'handle_seed_nested_hub_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_hub_nested_hub_variant_expansion_templates', array( $this, 'handle_seed_hub_nested_hub_variant_expansion_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_child_detail_templates', array( $this, 'handle_seed_child_detail_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_child_detail_product_templates', array( $this, 'handle_seed_child_detail_product_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_child_detail_profile_entity_templates', array( $this, 'handle_seed_child_detail_profile_entity_templates' ), 10 );
		\add_action( 'admin_post_aio_seed_child_detail_variant_expansion_templates', array( $this, 'handle_seed_child_detail_variant_expansion_templates' ), 10 );
		\add_action( 'admin_post_aio_save_industry_profile', array( $this, 'handle_save_industry_profile' ), 10 );
		\add_action( 'admin_post_aio_toggle_industry_pack', array( $this, 'handle_toggle_industry_pack' ), 10 );
		\add_action( 'admin_post_aio_apply_industry_style_preset', array( $this, 'handle_apply_industry_style_preset' ), 10 );
		\add_action( 'admin_post_aio_save_industry_section_override', array( $this, 'handle_save_industry_section_override' ), 10 );
		\add_action( 'admin_post_aio_save_industry_page_template_override', array( $this, 'handle_save_industry_page_template_override' ), 10 );
		\add_action( 'admin_post_aio_save_industry_build_plan_override', array( $this, 'handle_save_industry_build_plan_override' ), 10 );
		\add_action( 'admin_post_aio_remove_industry_override', array( $this, 'handle_remove_industry_override' ), 10 );
		\add_action( 'admin_post_aio_guided_repair_migrate', array( $this, 'handle_guided_repair_migrate' ), 10 );
		\add_action( 'admin_post_aio_guided_repair_apply_ref', array( $this, 'handle_guided_repair_apply_ref' ), 10 );
		\add_action( 'admin_post_aio_guided_repair_activate', array( $this, 'handle_guided_repair_activate' ), 10 );
		\add_action( 'admin_post_aio_create_plan_from_bundle', array( $this, 'handle_create_plan_from_bundle' ), 10 );
		\add_action( 'admin_post_aio_industry_bundle_preview', array( $this, 'handle_industry_bundle_preview' ), 10 );

		$dashboard   = new Dashboard_Screen( $this->container );
		$settings    = new Settings_Screen();
		$diagnostics = new Diagnostics_Screen();
		$acf_diagnostics = new ACF_Architecture_Diagnostics_Screen( $this->container );
		$onboarding  = new Onboarding_Screen( $this->container );
		$crawler_sessions  = new Crawler_Sessions_Screen( $this->container );
		$crawler_comparison = new Crawler_Comparison_Screen( $this->container );
		$ai_runs            = new AI_Runs_Screen( $this->container );
		$ai_providers       = new AI_Providers_Screen( $this->container );
		$prompt_experiments = new Prompt_Experiments_Screen( $this->container );
		$build_plans        = new Build_Plans_Screen( $this->container );
		$page_templates_dir   = new Page_Templates_Directory_Screen( $this->container );
		$page_template_detail   = new Page_Template_Detail_Screen( $this->container );
		$section_templates_dir  = new Section_Templates_Directory_Screen( $this->container );
		$section_template_detail = new Section_Template_Detail_Screen( $this->container );
		$template_compare_screen  = new Template_Compare_Screen( $this->container );
		$compositions_screen     = new Compositions_Screen( $this->container );
		$build_plan_analytics = new Build_Plan_Analytics_Screen( $this->container );
		$template_analytics   = new Template_Analytics_Screen( $this->container );
		$queue_logs           = new Queue_Logs_Screen( $this->container );
		$support_triage     = new Support_Triage_Dashboard_Screen( $this->container );
		$post_release_health = new Post_Release_Health_Screen( $this->container );
		$privacy_reporting   = new Privacy_Reporting_Settings_Screen( $this->container );
		$industry_profile     = new Industry_Profile_Settings_Screen( $this->container );
		$industry_author_dashboard = new Industry_Author_Dashboard_Screen( $this->container );
		$industry_health_report = new Industry_Health_Report_Screen( $this->container );
		$industry_stale_content_report = new Industry_Stale_Content_Report_Screen( $this->container );
		$industry_pack_family_comparison = new Industry_Pack_Family_Comparison_Screen( $this->container );
		$industry_future_readiness = new Future_Industry_Readiness_Screen( $this->container );
		$industry_future_subtype_readiness = new Future_Subtype_Readiness_Screen( $this->container );
		$industry_maturity_delta_report = new Industry_Maturity_Delta_Report_Screen( $this->container );
		$industry_drift_report = new Industry_Drift_Report_Screen( $this->container );
		$industry_scaffold_promotion_readiness = new Industry_Scaffold_Promotion_Readiness_Report_Screen( $this->container );
		$industry_guided_repair = new Industry_Guided_Repair_Screen( $this->container );
		$industry_subtype_comparison = new Industry_Subtype_Comparison_Screen( $this->container );
		$industry_bundle_comparison   = new Industry_Starter_Bundle_Comparison_Screen( $this->container );
		$industry_goal_comparison    = new Conversion_Goal_Comparison_Screen( $this->container );
		$industry_bundle_import_preview = new Industry_Bundle_Import_Preview_Screen( $this->container );
		$industry_style_preset = new Industry_Style_Preset_Screen( $this->container );
		$global_style_tokens = new Global_Style_Token_Settings_Screen( $this->container );
		$global_component_overrides = new Global_Component_Override_Settings_Screen( $this->container );
		$import_export       = new Import_Export_Screen( $this->container );

		add_menu_page(
			__( 'AIO Page Builder', 'aio-page-builder' ),
			__( 'AIO Page Builder', 'aio-page-builder' ),
			$dashboard->get_capability(),
			self::PARENT_SLUG,
			array( $dashboard, 'render' ),
			'dashicons-admin-generic',
			59
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$dashboard->get_title(),
			__( 'Dashboard', 'aio-page-builder' ),
			$dashboard->get_capability(),
			Dashboard_Screen::SLUG,
			array( $dashboard, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$settings->get_title(),
			__( 'Settings', 'aio-page-builder' ),
			$settings->get_capability(),
			Settings_Screen::SLUG,
			array( $settings, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$diagnostics->get_title(),
			__( 'Diagnostics', 'aio-page-builder' ),
			$diagnostics->get_capability(),
			Diagnostics_Screen::SLUG,
			array( $diagnostics, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$acf_diagnostics->get_title(),
			__( 'ACF Field Architecture', 'aio-page-builder' ),
			$acf_diagnostics->get_capability(),
			ACF_Architecture_Diagnostics_Screen::SLUG,
			array( $acf_diagnostics, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$form_provider_health->get_title(),
			__( 'Form Provider Health', 'aio-page-builder' ),
			$form_provider_health->get_capability(),
			Form_Provider_Health_Screen::SLUG,
			array( $form_provider_health, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$onboarding->get_title(),
			__( 'Onboarding & Profile', 'aio-page-builder' ),
			$onboarding->get_capability(),
			Onboarding_Screen::SLUG,
			array( $onboarding, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$crawler_sessions->get_title(),
			__( 'Crawl Sessions', 'aio-page-builder' ),
			$crawler_sessions->get_capability(),
			Crawler_Sessions_Screen::SLUG,
			array( $crawler_sessions, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$crawler_comparison->get_title(),
			__( 'Crawl Comparison', 'aio-page-builder' ),
			$crawler_comparison->get_capability(),
			Crawler_Comparison_Screen::SLUG,
			array( $crawler_comparison, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$ai_runs->get_title(),
			__( 'AI Runs', 'aio-page-builder' ),
			$ai_runs->get_capability(),
			AI_Runs_Screen::SLUG,
			array( $ai_runs, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$ai_providers->get_title(),
			__( 'AI Providers', 'aio-page-builder' ),
			$ai_providers->get_capability(),
			AI_Providers_Screen::SLUG,
			array( $ai_providers, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$prompt_experiments->get_title(),
			__( 'Prompt Experiments', 'aio-page-builder' ),
			$prompt_experiments->get_capability(),
			Prompt_Experiments_Screen::SLUG,
			array( $prompt_experiments, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$build_plans->get_title(),
			__( 'Build Plans', 'aio-page-builder' ),
			$build_plans->get_capability(),
			Build_Plans_Screen::SLUG,
			array( $build_plans, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$page_templates_dir->get_title(),
			__( 'Page Templates', 'aio-page-builder' ),
			$page_templates_dir->get_capability(),
			Page_Templates_Directory_Screen::SLUG,
			array( $page_templates_dir, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$page_template_detail->get_title(),
			'', // * Hidden from menu; reachable via View link from directory.
			$page_template_detail->get_capability(),
			Page_Template_Detail_Screen::SLUG,
			array( $page_template_detail, 'render' )
		);
		\remove_submenu_page( self::PARENT_SLUG, Page_Template_Detail_Screen::SLUG );

		add_submenu_page(
			self::PARENT_SLUG,
			$section_templates_dir->get_title(),
			__( 'Section Templates', 'aio-page-builder' ),
			$section_templates_dir->get_capability(),
			Section_Templates_Directory_Screen::SLUG,
			array( $section_templates_dir, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$section_template_detail->get_title(),
			'', // * Hidden from menu; reachable via View link from section directory.
			$section_template_detail->get_capability(),
			Section_Template_Detail_Screen::SLUG,
			array( $section_template_detail, 'render' )
		);
		\remove_submenu_page( self::PARENT_SLUG, Section_Template_Detail_Screen::SLUG );

		add_submenu_page(
			self::PARENT_SLUG,
			$template_compare_screen->get_title(),
			__( 'Template Compare', 'aio-page-builder' ),
			$template_compare_screen->get_capability(),
			Template_Compare_Screen::SLUG,
			array( $template_compare_screen, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$compositions_screen->get_title(),
			__( 'Compositions', 'aio-page-builder' ),
			$compositions_screen->get_capability(),
			Compositions_Screen::SLUG,
			array( $compositions_screen, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$build_plan_analytics->get_title(),
			__( 'Build Plan Analytics', 'aio-page-builder' ),
			$build_plan_analytics->get_capability(),
			Build_Plan_Analytics_Screen::SLUG,
			array( $build_plan_analytics, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$template_analytics->get_title(),
			__( 'Template Analytics', 'aio-page-builder' ),
			$template_analytics->get_capability(),
			Template_Analytics_Screen::SLUG,
			array( $template_analytics, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$queue_logs->get_title(),
			__( 'Queue & Logs', 'aio-page-builder' ),
			$queue_logs->get_capability(),
			Queue_Logs_Screen::SLUG,
			array( $queue_logs, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$support_triage->get_title(),
			__( 'Support Triage', 'aio-page-builder' ),
			$support_triage->get_capability(),
			Support_Triage_Dashboard_Screen::SLUG,
			array( $support_triage, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$post_release_health->get_title(),
			__( 'Post-Release Health', 'aio-page-builder' ),
			$post_release_health->get_capability(),
			Post_Release_Health_Screen::SLUG,
			array( $post_release_health, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$privacy_reporting->get_title(),
			__( 'Privacy, Reporting & Settings', 'aio-page-builder' ),
			$privacy_reporting->get_capability(),
			Privacy_Reporting_Settings_Screen::SLUG,
			array( $privacy_reporting, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$industry_profile->get_title(),
			__( 'Industry Profile', 'aio-page-builder' ),
			$industry_profile->get_capability(),
			Industry_Profile_Settings_Screen::SLUG,
			array( $industry_profile, 'render' )
		);

		$industry_override_management = new Industry_Override_Management_Screen();
		add_submenu_page(
			self::PARENT_SLUG,
			$industry_override_management->get_title(),
			__( 'Industry Overrides', 'aio-page-builder' ),
			$industry_override_management->get_capability(),
			Industry_Override_Management_Screen::SLUG,
			array( $industry_override_management, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$industry_author_dashboard->get_title(),
			__( 'Industry Author Dashboard', 'aio-page-builder' ),
			$industry_author_dashboard->get_capability(),
			Industry_Author_Dashboard_Screen::SLUG,
			array( $industry_author_dashboard, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$industry_health_report->get_title(),
			__( 'Industry Health Report', 'aio-page-builder' ),
			$industry_health_report->get_capability(),
			Industry_Health_Report_Screen::SLUG,
			array( $industry_health_report, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$industry_stale_content_report->get_title(),
			__( 'Stale content report', 'aio-page-builder' ),
			$industry_stale_content_report->get_capability(),
			Industry_Stale_Content_Report_Screen::SLUG,
			array( $industry_stale_content_report, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$industry_pack_family_comparison->get_title(),
			__( 'Pack family comparison', 'aio-page-builder' ),
			$industry_pack_family_comparison->get_capability(),
			Industry_Pack_Family_Comparison_Screen::SLUG,
			array( $industry_pack_family_comparison, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$industry_future_readiness->get_title(),
			__( 'Future industry readiness', 'aio-page-builder' ),
			$industry_future_readiness->get_capability(),
			Future_Industry_Readiness_Screen::SLUG,
			array( $industry_future_readiness, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$industry_future_subtype_readiness->get_title(),
			__( 'Future subtype readiness', 'aio-page-builder' ),
			$industry_future_subtype_readiness->get_capability(),
			Future_Subtype_Readiness_Screen::SLUG,
			array( $industry_future_subtype_readiness, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$industry_maturity_delta_report->get_title(),
			__( 'Maturity delta report', 'aio-page-builder' ),
			$industry_maturity_delta_report->get_capability(),
			Industry_Maturity_Delta_Report_Screen::SLUG,
			array( $industry_maturity_delta_report, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$industry_drift_report->get_title(),
			__( 'Drift report', 'aio-page-builder' ),
			$industry_drift_report->get_capability(),
			Industry_Drift_Report_Screen::SLUG,
			array( $industry_drift_report, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$industry_scaffold_promotion_readiness->get_title(),
			__( 'Scaffold promotion readiness', 'aio-page-builder' ),
			$industry_scaffold_promotion_readiness->get_capability(),
			Industry_Scaffold_Promotion_Readiness_Report_Screen::SLUG,
			array( $industry_scaffold_promotion_readiness, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$industry_guided_repair->get_title(),
			__( 'Guided Repair', 'aio-page-builder' ),
			$industry_guided_repair->get_capability(),
			Industry_Guided_Repair_Screen::SLUG,
			array( $industry_guided_repair, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$industry_subtype_comparison->get_title(),
			__( 'Subtype comparison', 'aio-page-builder' ),
			$industry_subtype_comparison->get_capability(),
			Industry_Subtype_Comparison_Screen::SLUG,
			array( $industry_subtype_comparison, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$industry_bundle_comparison->get_title(),
			__( 'Bundle comparison', 'aio-page-builder' ),
			$industry_bundle_comparison->get_capability(),
			Industry_Starter_Bundle_Comparison_Screen::SLUG,
			array( $industry_bundle_comparison, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$industry_goal_comparison->get_title(),
			__( 'Conversion goal comparison', 'aio-page-builder' ),
			$industry_goal_comparison->get_capability(),
			Conversion_Goal_Comparison_Screen::SLUG,
			array( $industry_goal_comparison, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$industry_bundle_import_preview->get_title(),
			__( 'Industry Bundle Import', 'aio-page-builder' ),
			$industry_bundle_import_preview->get_capability(),
			Industry_Bundle_Import_Preview_Screen::SLUG,
			array( $industry_bundle_import_preview, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$industry_style_preset->get_title(),
			__( 'Industry Style Preset', 'aio-page-builder' ),
			$industry_style_preset->get_capability(),
			Industry_Style_Preset_Screen::SLUG,
			array( $industry_style_preset, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$industry_style_layer_comparison->get_title(),
			__( 'Style layer comparison', 'aio-page-builder' ),
			$industry_style_layer_comparison->get_capability(),
			Industry_Style_Layer_Comparison_Screen::SLUG,
			array( $industry_style_layer_comparison, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$global_style_tokens->get_title(),
			__( 'Global Style Tokens', 'aio-page-builder' ),
			$global_style_tokens->get_capability(),
			Global_Style_Token_Settings_Screen::SLUG,
			array( $global_style_tokens, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$global_component_overrides->get_title(),
			__( 'Global Component Overrides', 'aio-page-builder' ),
			$global_component_overrides->get_capability(),
			Global_Component_Override_Settings_Screen::SLUG,
			array( $global_component_overrides, 'render' )
		);

		add_submenu_page(
			self::PARENT_SLUG,
			$import_export->get_title(),
			__( 'Import / Export', 'aio-page-builder' ),
			$import_export->get_capability(),
			Import_Export_Screen::SLUG,
			array( $import_export, 'render' )
		);
	}

	/**
	 * Handles admin-post save of Industry Profile (industry-admin-screen-contract).
	 * Verifies nonce and capability; validates then merges profile via Industry_Profile_Repository; redirects back to Industry Profile screen.
	 *
	 * @return void
	 */
	public function handle_save_industry_profile(): void {
		$redirect_url = \admin_url( 'admin.php?page=' . Industry_Profile_Settings_Screen::SLUG );
		if ( ! isset( $_POST['aio_industry_profile_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_industry_profile_nonce'] ) ), 'aio_save_industry_profile' ) ) {
			\wp_safe_redirect( $redirect_url . '&aio_industry_result=error' );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_SETTINGS ) ) {
			\wp_safe_redirect( $redirect_url . '&aio_industry_result=error' );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( $redirect_url . '&aio_industry_result=error' );
			exit;
		}
		$repo = null;
		if ( $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ) {
			$store = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
			if ( $store instanceof Industry_Profile_Repository ) {
				$repo = $store;
			}
		}
		if ( $repo === null ) {
			\wp_safe_redirect( $redirect_url . '&aio_industry_result=error' );
			exit;
		}
		$primary = isset( $_POST[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $_POST[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? trim( \sanitize_text_field( \wp_unslash( $_POST[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) ) )
			: '';
		$secondary_raw = isset( $_POST[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] )
			? $_POST[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ]
			: ( isset( $_POST[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS . '[]' ] ) ? $_POST[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS . '[]' ] : array() );
		$secondary = array();
		if ( is_array( $secondary_raw ) ) {
			foreach ( $secondary_raw as $v ) {
				if ( is_string( $v ) ) {
					$k = trim( \sanitize_text_field( \wp_unslash( $v ) ) );
					if ( $k !== '' ) {
						$secondary[] = $k;
					}
				}
			}
			$secondary = array_values( array_unique( $secondary ) );
		}
		$selected_bundle_raw = isset( $_POST[ Industry_Starter_Bundle_Assistant::FIELD_NAME ] ) && \is_string( $_POST[ Industry_Starter_Bundle_Assistant::FIELD_NAME ] )
			? \trim( \sanitize_text_field( \wp_unslash( $_POST[ Industry_Starter_Bundle_Assistant::FIELD_NAME ] ) ) )
			: '';
		$selected_bundle = '';
		if ( $selected_bundle_raw !== '' && \strlen( $selected_bundle_raw ) <= 64 ) {
			$bundle_registry = $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY )
				? $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY )
				: null;
			if ( $bundle_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry ) {
				$bundle_def = $bundle_registry->get( $selected_bundle_raw );
				if ( $bundle_def !== null && isset( $bundle_def[ \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY ] ) && (string) $bundle_def[ \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY ] === $primary ) {
					$selected_bundle = $selected_bundle_raw;
				}
			}
		}

		$subtype_raw = isset( $_POST[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] ) && is_string( $_POST[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] )
			? trim( sanitize_text_field( wp_unslash( $_POST[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] ) ) )
			: '';
		$current = $repo->get_profile();
		$previous_primary = isset( $current[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $current[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? trim( $current[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			: '';
		$subtype_registry = $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_SUBTYPE_REGISTRY )
			? $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_SUBTYPE_REGISTRY )
			: null;
		$subtype = '';
		if ( $subtype_raw !== '' && $primary !== '' && strlen( $subtype_raw ) <= 64 && $subtype_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry ) {
			$def = $subtype_registry->get( $subtype_raw );
			if ( $def !== null && isset( $def[ \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] ) && trim( (string) $def[ \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] ) === $primary ) {
				$subtype = $subtype_raw;
			}
		}
		if ( $previous_primary !== '' && $previous_primary !== $primary ) {
			$subtype = '';
		}
		$partial = array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY   => $primary,
			Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS => $secondary,
			Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY => $selected_bundle,
			Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY   => $subtype,
		);
		$merged = array_merge( $current, $partial );
		$merged[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] = $secondary;
		$pack_registry = null;
		if ( $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) ) {
			$r = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY );
			if ( $r instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry ) {
				$pack_registry = $r;
			}
		}
		$qp_registry = $this->container->has( 'industry_question_pack_registry' ) ? $this->container->get( 'industry_question_pack_registry' ) : null;
		$validator = new Industry_Profile_Validator();
		if ( ! $validator->validate( $merged, $pack_registry, $qp_registry instanceof \AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Registry ? $qp_registry : null, $subtype_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry ? $subtype_registry : null ) ) {
			\wp_safe_redirect( $redirect_url . '&aio_industry_result=error' );
			exit;
		}
		$repo->merge_profile( $partial );
		if ( $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE ) ) {
			$cache = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE );
			if ( $cache instanceof \AIOPageBuilder\Domain\Industry\Cache\Industry_Read_Model_Cache_Service ) {
				$cache->invalidate_all_industry_read_models();
			}
		}
		\wp_safe_redirect( $redirect_url . '&aio_industry_result=saved' );
		exit;
	}

	/**
	 * Handles admin-post toggle of industry pack (industry-pack-activation-contract). Nonce and capability required.
	 *
	 * @return void
	 */
	public function handle_toggle_industry_pack(): void {
		$redirect_url = \admin_url( 'admin.php?page=' . Industry_Profile_Settings_Screen::SLUG );
		if ( ! isset( $_POST['aio_toggle_industry_pack_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_toggle_industry_pack_nonce'] ) ), 'aio_toggle_industry_pack' ) ) {
			\wp_safe_redirect( $redirect_url . '&aio_industry_result=toggle_error' );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_SETTINGS ) ) {
			\wp_safe_redirect( $redirect_url . '&aio_industry_result=toggle_error' );
			exit;
		}
		$industry_key = isset( $_POST['aio_industry_pack_key'] ) && \is_string( $_POST['aio_industry_pack_key'] )
			? \trim( \sanitize_text_field( \wp_unslash( $_POST['aio_industry_pack_key'] ) ) )
			: '';
		$disable = isset( $_POST['aio_industry_pack_disable'] ) && (string) $_POST['aio_industry_pack_disable'] === '1';
		if ( $industry_key === '' ) {
			\wp_safe_redirect( $redirect_url . '&aio_industry_result=toggle_error' );
			exit;
		}
		if ( ! $this->container instanceof Service_Container || ! $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_TOGGLE_CONTROLLER ) ) {
			\wp_safe_redirect( $redirect_url . '&aio_industry_result=toggle_error' );
			exit;
		}
		$controller = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_TOGGLE_CONTROLLER );
		if ( ! $controller instanceof Industry_Pack_Toggle_Controller ) {
			\wp_safe_redirect( $redirect_url . '&aio_industry_result=toggle_error' );
			exit;
		}
		$controller->set_pack_disabled( $industry_key, $disable );
		if ( $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE ) ) {
			$cache = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE );
			if ( $cache instanceof \AIOPageBuilder\Domain\Industry\Cache\Industry_Read_Model_Cache_Service ) {
				$cache->invalidate_all_industry_read_models();
			}
		}
		\wp_safe_redirect( $redirect_url . '&aio_industry_result=toggled' );
		exit;
	}

	/**
	 * Handles admin-post apply of Industry Style Preset (industry-style-preset-application-contract).
	 * Verifies nonce and capability; applies preset via Industry_Style_Preset_Application_Service; redirects back to Industry Style Preset screen.
	 *
	 * @return void
	 */
	public function handle_apply_industry_style_preset(): void {
		$redirect_url = \admin_url( 'admin.php?page=' . Industry_Style_Preset_Screen::SLUG );
		if ( ! isset( $_POST['aio_industry_style_preset_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_industry_style_preset_nonce'] ) ), 'aio_apply_industry_style_preset' ) ) {
			\wp_safe_redirect( $redirect_url . '&aio_style_preset_msg=error' );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_SETTINGS ) ) {
			\wp_safe_redirect( $redirect_url . '&aio_style_preset_msg=error' );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( $redirect_url . '&aio_style_preset_msg=error' );
			exit;
		}
		$preset_key = isset( $_POST['preset_key'] ) && is_string( $_POST['preset_key'] )
			? trim( \sanitize_text_field( \wp_unslash( $_POST['preset_key'] ) ) )
			: '';
		if ( $preset_key === '' ) {
			\wp_safe_redirect( $redirect_url . '&aio_style_preset_msg=error' );
			exit;
		}
		if ( ! $this->container->has( 'industry_style_preset_application_service' ) ) {
			\wp_safe_redirect( $redirect_url . '&aio_style_preset_msg=error' );
			exit;
		}
		$service = $this->container->get( 'industry_style_preset_application_service' );
		if ( ! $service instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Application_Service ) {
			\wp_safe_redirect( $redirect_url . '&aio_style_preset_msg=error' );
			exit;
		}
		$applied = $service->apply_preset( $preset_key );
		\wp_safe_redirect( $redirect_url . ( $applied ? '&aio_style_preset_msg=applied' : '&aio_style_preset_msg=error' ) );
		exit;
	}

	/**
	 * Handles admin-post save of industry section override (Prompt 367). Delegates to Save_Industry_Section_Override_Action.
	 *
	 * @return void
	 */
	public function handle_save_industry_section_override(): void {
		\AIOPageBuilder\Admin\Actions\Save_Industry_Section_Override_Action::handle();
	}

	/**
	 * Handles admin-post save of industry page template override (Prompt 368).
	 *
	 * @return void
	 */
	public function handle_save_industry_page_template_override(): void {
		\AIOPageBuilder\Admin\Actions\Save_Industry_Page_Template_Override_Action::handle();
	}

	/**
	 * Handles admin-post save of industry Build Plan item override (Prompt 369).
	 *
	 * @return void
	 */
	public function handle_save_industry_build_plan_override(): void {
		\AIOPageBuilder\Admin\Actions\Save_Industry_Build_Plan_Override_Action::handle();
	}

	/**
	 * Handles admin-post request to remove a single industry override (Prompt 436).
	 *
	 * @return void
	 */
	public function handle_remove_industry_override(): void {
		\AIOPageBuilder\Admin\Actions\Remove_Industry_Override_Action::handle();
	}

	/**
	 * Handles guided repair: migrate deprecated pack to replacement (Prompt 527). Nonce and capability required.
	 *
	 * @return void
	 */
	public function handle_guided_repair_migrate(): void {
		$redirect = \admin_url( 'admin.php?page=' . Industry_Guided_Repair_Screen::SLUG );
		if ( ! isset( $_POST['aio_guided_repair_migrate_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_guided_repair_migrate_nonce'] ) ), Industry_Guided_Repair_Screen::NONCE_ACTION_MIGRATE ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_SETTINGS ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		$deprecated_key = isset( $_POST['deprecated_pack_key'] ) && \is_string( $_POST['deprecated_pack_key'] )
			? \trim( \sanitize_text_field( \wp_unslash( $_POST['deprecated_pack_key'] ) ) )
			: '';
		if ( $deprecated_key === '' || ! $this->container instanceof Service_Container || ! $this->container->has( 'industry_pack_migration_executor' ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		$executor = $this->container->get( 'industry_pack_migration_executor' );
		if ( ! $executor instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Pack_Migration_Executor ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		$result = $executor->run_migration_to_replacement( $deprecated_key );
		\wp_safe_redirect( \add_query_arg( 'aio_repair_result', $result->is_success() ? 'migrated' : 'error', $redirect ) );
		exit;
	}

	/**
	 * Handles guided repair: apply suggested profile ref (e.g. selected_starter_bundle_key). Nonce and capability required.
	 *
	 * @return void
	 */
	public function handle_guided_repair_apply_ref(): void {
		$redirect = \admin_url( 'admin.php?page=' . Industry_Guided_Repair_Screen::SLUG );
		if ( ! isset( $_POST['aio_guided_repair_apply_ref_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_guided_repair_apply_ref_nonce'] ) ), Industry_Guided_Repair_Screen::NONCE_ACTION_APPLY_REF ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_SETTINGS ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		$field = isset( $_POST['profile_field'] ) && \is_string( $_POST['profile_field'] )
			? \trim( \sanitize_text_field( \wp_unslash( $_POST['profile_field'] ) ) )
			: '';
		$value = isset( $_POST['profile_value'] ) && \is_string( $_POST['profile_value'] )
			? \trim( \sanitize_text_field( \wp_unslash( $_POST['profile_value'] ) ) )
			: '';
		$allowed = array( \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY );
		if ( $field === '' || ! \in_array( $field, $allowed, true ) || ! $this->container instanceof Service_Container || ! $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		$profile_repo = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
		if ( ! $profile_repo instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		$profile_repo->merge_profile( array( $field => $value ) );
		\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'applied', $redirect ) );
		exit;
	}

	/**
	 * Handles guided repair: enable (activate) industry pack. Nonce and capability required.
	 *
	 * @return void
	 */
	public function handle_guided_repair_activate(): void {
		$redirect = \admin_url( 'admin.php?page=' . Industry_Guided_Repair_Screen::SLUG );
		if ( ! isset( $_POST['aio_guided_repair_activate_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_guided_repair_activate_nonce'] ) ), Industry_Guided_Repair_Screen::NONCE_ACTION_ACTIVATE ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_SETTINGS ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		$industry_key = isset( $_POST['industry_pack_key'] ) && \is_string( $_POST['industry_pack_key'] )
			? \trim( \sanitize_text_field( \wp_unslash( $_POST['industry_pack_key'] ) ) )
			: '';
		if ( $industry_key === '' || ! $this->container instanceof Service_Container || ! $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_TOGGLE_CONTROLLER ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		$controller = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_TOGGLE_CONTROLLER );
		if ( ! $controller instanceof Industry_Pack_Toggle_Controller ) {
			\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'error', $redirect ) );
			exit;
		}
		$controller->set_pack_disabled( $industry_key, false );
		if ( $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE ) ) {
			$cache = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE );
			if ( $cache instanceof \AIOPageBuilder\Domain\Industry\Cache\Industry_Read_Model_Cache_Service ) {
				$cache->invalidate_all_industry_read_models();
			}
		}
		\wp_safe_redirect( \add_query_arg( 'aio_repair_result', 'activated', $redirect ) );
		exit;
	}

	/**
	 * Handles admin-post create Build Plan from starter bundle (Prompt 409).
	 *
	 * @return void
	 */
	public function handle_create_plan_from_bundle(): void {
		\AIOPageBuilder\Admin\Actions\Create_Plan_From_Starter_Bundle_Action::handle( $this->container );
	}

	/**
	 * Handles industry bundle preview: validate upload, analyze conflicts, store preview in transient, redirect.
	 *
	 * @return void
	 */
	public function handle_industry_bundle_preview(): void {
		$redirect = \admin_url( 'admin.php?page=' . Industry_Bundle_Import_Preview_Screen::SLUG );
		if ( ! isset( $_POST['aio_industry_bundle_preview_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_industry_bundle_preview_nonce'] ) ), 'aio_industry_bundle_preview' ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_bundle_preview_error', 'Invalid request.', $redirect ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_SETTINGS ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_bundle_preview_error', 'Permission denied.', $redirect ) );
			exit;
		}
		$file = isset( $_FILES['aio_industry_bundle_file'] ) && is_array( $_FILES['aio_industry_bundle_file'] ) ? $_FILES['aio_industry_bundle_file'] : array();
		$upload_result = Industry_Bundle_Upload_Validator::validate_upload( $file );
		if ( ! $upload_result['ok'] ) {
			if ( $upload_result['log_reason'] !== '' ) {
				\error_log( '[AIO Page Builder] Industry bundle upload rejected: ' . $upload_result['log_reason'] );
			}
			\wp_safe_redirect( \add_query_arg( 'aio_bundle_preview_error', \rawurlencode( $upload_result['user_message'] ), $redirect ) );
			exit;
		}
		$parse_result = Industry_Bundle_Upload_Validator::read_parse_validate_bundle(
			$upload_result['tmp_path'],
			Industry_Bundle_Upload_Validator::MAX_BYTES
		);
		if ( $parse_result['bundle'] === null ) {
			if ( $parse_result['log_reason'] !== '' ) {
				\error_log( '[AIO Page Builder] Industry bundle upload rejected: ' . $parse_result['log_reason'] );
			}
			\wp_safe_redirect( \add_query_arg( 'aio_bundle_preview_error', \rawurlencode( $parse_result['user_message'] ), $redirect ) );
			exit;
		}
		$bundle = $parse_result['bundle'];
		$screen = new Industry_Bundle_Import_Preview_Screen( $this->container );
		$local_state = $screen->get_local_state_for_conflict();
		$conflict_service = new Industry_Pack_Import_Conflict_Service();
		$conflicts = $conflict_service->analyze( $bundle, $local_state );
		$included = isset( $bundle[ Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES ] ) && \is_array( $bundle[ Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES ] )
			? $bundle[ Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES ]
			: array();
		$summary = array();
		foreach ( $included as $cat ) {
			if ( \is_string( $cat ) && isset( $bundle[ $cat ] ) && \is_array( $bundle[ $cat ] ) ) {
				$summary[ $cat ] = \count( $bundle[ $cat ] );
			}
		}
		$transient_key = \sprintf( 'aio_industry_bundle_preview_%d', \get_current_user_id() );
		\set_transient( $transient_key, array(
			'bundle'    => $bundle,
			'conflicts' => $conflicts,
			'included'  => $included,
			'summary'   => $summary,
		), 900 );
		\wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Handles admin-post request to seed form section and request page template (form-provider-integration-contract).
	 * Verifies nonce and capability; redirects back to Settings with result.
	 *
	 * @return void
	 */
	public function handle_seed_form_templates(): void {
		if ( ! isset( $_POST['aio_seed_form_templates_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_form_templates_nonce'] ) ), 'aio_seed_form_templates' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_seed_result=error' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_SECTION_TEMPLATES ) || ! \current_user_can( Capabilities::MANAGE_PAGE_TEMPLATES ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_seed_result=error' ) );
			exit;
		}
		$section_registry = $this->container->get( 'section_registry_service' );
		$page_repo        = $this->container->get( 'page_template_repository' );
		if ( ! $section_registry instanceof \AIOPageBuilder\Domain\Registries\Section\Section_Registry_Service || ! $page_repo instanceof Page_Template_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_seed_result=error' ) );
			exit;
		}
		$result = $section_registry->ensure_bundled_form_templates( $page_repo );
		$query  = $result['success'] ? 'aio_seed_result=success' : 'aio_seed_result=error';
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&' . $query ) );
		exit;
	}

	/**
	 * Handles admin-post request to seed the section expansion pack (Prompt 122).
	 *
	 * @return void
	 */
	public function handle_seed_section_expansion_pack(): void {
		if ( ! isset( $_POST['aio_seed_expansion_pack_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_expansion_pack_nonce'] ) ), 'aio_seed_section_expansion_pack' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_expansion_seed_result=error' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_SECTION_TEMPLATES ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_expansion_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_expansion_seed_result=error' ) );
			exit;
		}
		$section_repo = $this->container->get( 'section_template_repository' );
		if ( ! $section_repo instanceof Section_Template_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_expansion_seed_result=error' ) );
			exit;
		}
		$result = Section_Expansion_Pack_Seeder::run( $section_repo );
		$query  = $result['success'] ? 'aio_expansion_seed_result=success' : 'aio_expansion_seed_result=error';
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&' . $query ) );
		exit;
	}

	/**
	 * Handles admin-post request to seed the hero/intro library batch (SEC-01, Prompt 147).
	 *
	 * @return void
	 */
	public function handle_seed_hero_intro_library_batch(): void {
		if ( ! isset( $_POST['aio_seed_hero_intro_batch_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_hero_intro_batch_nonce'] ) ), 'aio_seed_hero_intro_library_batch' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_hero_intro_batch_seed_result=error' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_SECTION_TEMPLATES ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_hero_intro_batch_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_hero_intro_batch_seed_result=error' ) );
			exit;
		}
		$section_repo = $this->container->get( 'section_template_repository' );
		if ( ! $section_repo instanceof Section_Template_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_hero_intro_batch_seed_result=error' ) );
			exit;
		}
		$result = Hero_Intro_Library_Batch_Seeder::run( $section_repo );
		$query  = $result['success'] ? 'aio_hero_intro_batch_seed_result=success' : 'aio_hero_intro_batch_seed_result=error';
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&' . $query ) );
		exit;
	}

	/**
	 * Handles admin-post request to seed the trust/proof library batch (SEC-02, Prompt 148).
	 *
	 * @return void
	 */
	public function handle_seed_trust_proof_library_batch(): void {
		if ( ! isset( $_POST['aio_seed_trust_proof_batch_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_trust_proof_batch_nonce'] ) ), 'aio_seed_trust_proof_library_batch' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_trust_proof_batch_seed_result=error' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_SECTION_TEMPLATES ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_trust_proof_batch_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_trust_proof_batch_seed_result=error' ) );
			exit;
		}
		$section_repo = $this->container->get( 'section_template_repository' );
		if ( ! $section_repo instanceof Section_Template_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_trust_proof_batch_seed_result=error' ) );
			exit;
		}
		$result = Trust_Proof_Library_Batch_Seeder::run( $section_repo );
		$query  = $result['success'] ? 'aio_trust_proof_batch_seed_result=success' : 'aio_trust_proof_batch_seed_result=error';
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&' . $query ) );
		exit;
	}

	/**
	 * Handles admin-post request to seed the feature/benefit/value library batch (SEC-03, Prompt 149).
	 *
	 * @return void
	 */
	public function handle_seed_feature_benefit_value_batch(): void {
		if ( ! isset( $_POST['aio_seed_fb_value_batch_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_fb_value_batch_nonce'] ) ), 'aio_seed_feature_benefit_value_batch' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_fb_value_batch_seed_result=error' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_SECTION_TEMPLATES ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_fb_value_batch_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_fb_value_batch_seed_result=error' ) );
			exit;
		}
		$section_repo = $this->container->get( 'section_template_repository' );
		if ( ! $section_repo instanceof Section_Template_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_fb_value_batch_seed_result=error' ) );
			exit;
		}
		$result = Feature_Benefit_Value_Library_Batch_Seeder::run( $section_repo );
		$query  = $result['success'] ? 'aio_fb_value_batch_seed_result=success' : 'aio_fb_value_batch_seed_result=error';
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&' . $query ) );
		exit;
	}

	/**
	 * Handles admin-post request to seed the process/timeline/FAQ library batch (SEC-05, Prompt 150).
	 *
	 * @return void
	 */
	public function handle_seed_process_timeline_faq_batch(): void {
		if ( ! isset( $_POST['aio_seed_ptf_batch_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_ptf_batch_nonce'] ) ), 'aio_seed_process_timeline_faq_batch' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_ptf_batch_seed_result=error' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_SECTION_TEMPLATES ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_ptf_batch_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_ptf_batch_seed_result=error' ) );
			exit;
		}
		$section_repo = $this->container->get( 'section_template_repository' );
		if ( ! $section_repo instanceof Section_Template_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_ptf_batch_seed_result=error' ) );
			exit;
		}
		$result = Process_Timeline_FAQ_Library_Batch_Seeder::run( $section_repo );
		$query  = $result['success'] ? 'aio_ptf_batch_seed_result=success' : 'aio_ptf_batch_seed_result=error';
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&' . $query ) );
		exit;
	}

	/**
	 * Handles admin-post request to seed the media/listing/profile/detail library batch (SEC-06, Prompt 151).
	 *
	 * @return void
	 */
	public function handle_seed_media_listing_profile_batch(): void {
		if ( ! isset( $_POST['aio_seed_mlp_batch_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_mlp_batch_nonce'] ) ), 'aio_seed_media_listing_profile_batch' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_mlp_batch_seed_result=error' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_SECTION_TEMPLATES ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_mlp_batch_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_mlp_batch_seed_result=error' ) );
			exit;
		}
		$section_repo = $this->container->get( 'section_template_repository' );
		if ( ! $section_repo instanceof Section_Template_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_mlp_batch_seed_result=error' ) );
			exit;
		}
		$result = Media_Listing_Profile_Detail_Library_Batch_Seeder::run( $section_repo );
		$query  = $result['success'] ? 'aio_mlp_batch_seed_result=success' : 'aio_mlp_batch_seed_result=error';
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&' . $query ) );
		exit;
	}

	/**
	 * Handles admin-post request to seed the legal/policy/utility library batch (SEC-07, Prompt 152).
	 *
	 * @return void
	 */
	public function handle_seed_legal_policy_utility_batch(): void {
		if ( ! isset( $_POST['aio_seed_lpu_batch_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_lpu_batch_nonce'] ) ), 'aio_seed_legal_policy_utility_batch' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_lpu_batch_seed_result=error' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_SECTION_TEMPLATES ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_lpu_batch_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_lpu_batch_seed_result=error' ) );
			exit;
		}
		$section_repo = $this->container->get( 'section_template_repository' );
		if ( ! $section_repo instanceof Section_Template_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_lpu_batch_seed_result=error' ) );
			exit;
		}
		$result = Legal_Policy_Utility_Library_Batch_Seeder::run( $section_repo );
		$query  = $result['success'] ? 'aio_lpu_batch_seed_result=success' : 'aio_lpu_batch_seed_result=error';
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&' . $query ) );
		exit;
	}

	/**
	 * Handles admin-post request to seed the CTA super-library batch (SEC-08, Prompt 153).
	 *
	 * @return void
	 */
	public function handle_seed_cta_super_library_batch(): void {
		if ( ! isset( $_POST['aio_seed_cta_super_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_cta_super_nonce'] ) ), 'aio_seed_cta_super_library_batch' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_cta_super_seed_result=error' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_SECTION_TEMPLATES ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_cta_super_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_cta_super_seed_result=error' ) );
			exit;
		}
		$section_repo = $this->container->get( 'section_template_repository' );
		if ( ! $section_repo instanceof Section_Template_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_cta_super_seed_result=error' ) );
			exit;
		}
		$result = CTA_Super_Library_Batch_Seeder::run( $section_repo );
		$query  = $result['success'] ? 'aio_cta_super_seed_result=success' : 'aio_cta_super_seed_result=error';
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&' . $query ) );
		exit;
	}

	/**
	 * Handles admin-post request to seed the page template and composition expansion pack (Prompt 123).
	 *
	 * @return void
	 */
	public function handle_seed_page_composition_expansion_pack(): void {
		if ( ! isset( $_POST['aio_seed_pt_comp_expansion_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_pt_comp_expansion_nonce'] ) ), 'aio_seed_page_composition_expansion_pack' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_pt_comp_expansion_seed_result=error' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_PAGE_TEMPLATES ) || ! \current_user_can( Capabilities::MANAGE_COMPOSITIONS ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_pt_comp_expansion_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_pt_comp_expansion_seed_result=error' ) );
			exit;
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		$comp_repo = $this->container->get( 'composition_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository || ! $comp_repo instanceof Composition_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_pt_comp_expansion_seed_result=error' ) );
			exit;
		}
		$result = Page_Template_And_Composition_Expansion_Pack_Seeder::run( $page_repo, $comp_repo );
		$query  = $result['success'] ? 'aio_pt_comp_expansion_seed_result=success' : 'aio_pt_comp_expansion_seed_result=error';
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&' . $query ) );
		exit;
	}

	/**
	 * Handles admin-post request to seed the top-level marketing page template batch (Prompt 155).
	 *
	 * @return void
	 */
	public function handle_seed_top_level_marketing_templates(): void {
		if ( ! isset( $_POST['aio_seed_top_level_marketing_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_top_level_marketing_nonce'] ) ), 'aio_seed_top_level_marketing_templates' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_top_level_marketing_seed_result=error' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_PAGE_TEMPLATES ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_top_level_marketing_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_top_level_marketing_seed_result=error' ) );
			exit;
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_top_level_marketing_seed_result=error' ) );
			exit;
		}
		$result = Top_Level_Marketing_Page_Template_Seeder::run( $page_repo );
		$query  = $result['success'] ? 'aio_top_level_marketing_seed_result=success' : 'aio_top_level_marketing_seed_result=error';
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&' . $query ) );
		exit;
	}

	/**
	 * Handles admin-post request to seed the top-level legal/utility page template batch (Prompt 156).
	 *
	 * @return void
	 */
	public function handle_seed_top_level_legal_utility_templates(): void {
		if ( ! isset( $_POST['aio_seed_top_level_legal_utility_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_top_level_legal_utility_nonce'] ) ), 'aio_seed_top_level_legal_utility_templates' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_top_level_legal_utility_seed_result=error' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_PAGE_TEMPLATES ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_top_level_legal_utility_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_top_level_legal_utility_seed_result=error' ) );
			exit;
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_top_level_legal_utility_seed_result=error' ) );
			exit;
		}
		$result = Top_Level_Legal_Utility_Page_Template_Seeder::run( $page_repo );
		$query  = $result['success'] ? 'aio_top_level_legal_utility_seed_result=success' : 'aio_top_level_legal_utility_seed_result=error';
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&' . $query ) );
		exit;
	}

	/**
	 * Handles admin-post request to seed the top-level educational/resource/authority page template batch (Prompt 163).
	 *
	 * @return void
	 */
	public function handle_seed_top_level_educational_resource_authority_templates(): void {
		if ( ! isset( $_POST['aio_seed_top_level_edu_resource_authority_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_top_level_edu_resource_authority_nonce'] ) ), 'aio_seed_top_level_educational_resource_authority_templates' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_top_level_edu_resource_authority_seed_result=error' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_PAGE_TEMPLATES ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_top_level_edu_resource_authority_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_top_level_edu_resource_authority_seed_result=error' ) );
			exit;
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_top_level_edu_resource_authority_seed_result=error' ) );
			exit;
		}
		$result = Top_Level_Educational_Resource_Authority_Page_Template_Seeder::run( $page_repo );
		$query  = $result['success'] ? 'aio_top_level_edu_resource_authority_seed_result=success' : 'aio_top_level_edu_resource_authority_seed_result=error';
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&' . $query ) );
		exit;
	}

	/**
	 * Handles admin-post request to seed the top-level variant expansion super-batch (Prompt 164).
	 *
	 * @return void
	 */
	public function handle_seed_top_level_variant_expansion_templates(): void {
		if ( ! isset( $_POST['aio_seed_top_level_variant_expansion_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_top_level_variant_expansion_nonce'] ) ), 'aio_seed_top_level_variant_expansion_templates' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_top_level_variant_expansion_seed_result=error' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_PAGE_TEMPLATES ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_top_level_variant_expansion_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_top_level_variant_expansion_seed_result=error' ) );
			exit;
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_top_level_variant_expansion_seed_result=error' ) );
			exit;
		}
		$result = Top_Level_Variant_Expansion_Page_Template_Seeder::run( $page_repo );
		$query  = $result['success'] ? 'aio_top_level_variant_expansion_seed_result=success' : 'aio_top_level_variant_expansion_seed_result=error';
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&' . $query ) );
		exit;
	}

	/**
	 * Handles admin-post request to seed the hub page template batch (Prompt 157).
	 *
	 * @return void
	 */
	public function handle_seed_hub_page_templates(): void {
		if ( ! isset( $_POST['aio_seed_hub_page_templates_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_hub_page_templates_nonce'] ) ), 'aio_seed_hub_page_templates' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_hub_page_seed_result=error' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_PAGE_TEMPLATES ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_hub_page_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_hub_page_seed_result=error' ) );
			exit;
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_hub_page_seed_result=error' ) );
			exit;
		}
		$result = Hub_Page_Template_Seeder::run( $page_repo );
		$query  = $result['success'] ? 'aio_hub_page_seed_result=success' : 'aio_hub_page_seed_result=error';
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&' . $query ) );
		exit;
	}

	/**
	 * Handles admin-post request to seed the geographic hub page template batch (Prompt 158).
	 *
	 * @return void
	 */
	public function handle_seed_geographic_hub_templates(): void {
		if ( ! isset( $_POST['aio_seed_geographic_hub_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_geographic_hub_nonce'] ) ), 'aio_seed_geographic_hub_templates' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_geographic_hub_seed_result=error' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_PAGE_TEMPLATES ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_geographic_hub_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_geographic_hub_seed_result=error' ) );
			exit;
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_geographic_hub_seed_result=error' ) );
			exit;
		}
		$result = Geographic_Hub_Page_Template_Seeder::run( $page_repo );
		$query  = $result['success'] ? 'aio_geographic_hub_seed_result=success' : 'aio_geographic_hub_seed_result=error';
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&' . $query ) );
		exit;
	}

	/**
	 * Handles seed request for nested hub page template batch (PT-06). Capability and nonce checked.
	 *
	 * @return void
	 */
	public function handle_seed_nested_hub_templates(): void {
		if ( ! isset( $_POST['aio_seed_nested_hub_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_nested_hub_nonce'] ) ), 'aio_seed_nested_hub_templates' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_nested_hub_seed_result=error' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_PAGE_TEMPLATES ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_nested_hub_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_nested_hub_seed_result=error' ) );
			exit;
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_nested_hub_seed_result=error' ) );
			exit;
		}
		$result = Nested_Hub_Page_Template_Seeder::run( $page_repo );
		$query  = $result['success'] ? 'aio_nested_hub_seed_result=success' : 'aio_nested_hub_seed_result=error';
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&' . $query ) );
		exit;
	}

	/**
	 * Handles seed request for hub and nested hub variant expansion super-batch (PT-12). Capability and nonce checked.
	 *
	 * @return void
	 */
	public function handle_seed_hub_nested_hub_variant_expansion_templates(): void {
		if ( ! isset( $_POST['aio_seed_hub_nested_hub_variant_expansion_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_hub_nested_hub_variant_expansion_nonce'] ) ), 'aio_seed_hub_nested_hub_variant_expansion_templates' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_hub_nested_hub_variant_expansion_seed_result=error' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_PAGE_TEMPLATES ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_hub_nested_hub_variant_expansion_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_hub_nested_hub_variant_expansion_seed_result=error' ) );
			exit;
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_hub_nested_hub_variant_expansion_seed_result=error' ) );
			exit;
		}
		$result = Hub_Nested_Hub_Variant_Expansion_Page_Template_Seeder::run( $page_repo );
		$query  = $result['success'] ? 'aio_hub_nested_hub_variant_expansion_seed_result=success' : 'aio_hub_nested_hub_variant_expansion_seed_result=error';
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&' . $query ) );
		exit;
	}

	/**
	 * Handles seed request for child/detail page template batch (PT-07). Capability and nonce checked.
	 *
	 * @return void
	 */
	public function handle_seed_child_detail_templates(): void {
		if ( ! isset( $_POST['aio_seed_child_detail_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_child_detail_nonce'] ) ), 'aio_seed_child_detail_templates' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_child_detail_seed_result=error' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_PAGE_TEMPLATES ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_child_detail_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_child_detail_seed_result=error' ) );
			exit;
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_child_detail_seed_result=error' ) );
			exit;
		}
		$result = Child_Detail_Page_Template_Seeder::run( $page_repo );
		$query  = $result['success'] ? 'aio_child_detail_seed_result=success' : 'aio_child_detail_seed_result=error';
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&' . $query ) );
		exit;
	}

	/**
	 * Handles seed request for product/catalog child/detail page template batch (PT-08). Capability and nonce checked.
	 *
	 * @return void
	 */
	public function handle_seed_child_detail_product_templates(): void {
		if ( ! isset( $_POST['aio_seed_child_detail_product_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_child_detail_product_nonce'] ) ), 'aio_seed_child_detail_product_templates' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_child_detail_product_seed_result=error' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_PAGE_TEMPLATES ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_child_detail_product_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_child_detail_product_seed_result=error' ) );
			exit;
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_child_detail_product_seed_result=error' ) );
			exit;
		}
		$result = Child_Detail_Product_Page_Template_Seeder::run( $page_repo );
		$query  = $result['success'] ? 'aio_child_detail_product_seed_result=success' : 'aio_child_detail_product_seed_result=error';
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&' . $query ) );
		exit;
	}

	/**
	 * Handles seed request for directory/profile/entity/resource child/detail page template batch (PT-09). Capability and nonce checked.
	 *
	 * @return void
	 */
	public function handle_seed_child_detail_profile_entity_templates(): void {
		if ( ! isset( $_POST['aio_seed_child_detail_profile_entity_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_child_detail_profile_entity_nonce'] ) ), 'aio_seed_child_detail_profile_entity_templates' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_child_detail_profile_entity_seed_result=error' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_PAGE_TEMPLATES ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_child_detail_profile_entity_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_child_detail_profile_entity_seed_result=error' ) );
			exit;
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_child_detail_profile_entity_seed_result=error' ) );
			exit;
		}
		$result = Child_Detail_Profile_Entity_Page_Template_Seeder::run( $page_repo );
		$query  = $result['success'] ? 'aio_child_detail_profile_entity_seed_result=success' : 'aio_child_detail_profile_entity_seed_result=error';
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&' . $query ) );
		exit;
	}

	/**
	 * Handles seed request for child/detail variant expansion super-batch (PT-13). Capability and nonce checked.
	 *
	 * @return void
	 */
	public function handle_seed_child_detail_variant_expansion_templates(): void {
		if ( ! isset( $_POST['aio_seed_child_detail_variant_expansion_nonce'] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['aio_seed_child_detail_variant_expansion_nonce'] ) ), 'aio_seed_child_detail_variant_expansion_templates' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_child_detail_variant_expansion_seed_result=error' ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_PAGE_TEMPLATES ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_child_detail_variant_expansion_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_child_detail_variant_expansion_seed_result=error' ) );
			exit;
		}
		$page_repo = $this->container->get( 'page_template_repository' );
		if ( ! $page_repo instanceof Page_Template_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_child_detail_variant_expansion_seed_result=error' ) );
			exit;
		}
		$result = Child_Detail_Variant_Expansion_Page_Template_Seeder::run( $page_repo );
		$query  = $result['success'] ? 'aio_child_detail_variant_expansion_seed_result=success' : 'aio_child_detail_variant_expansion_seed_result=error';
		\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&' . $query ) );
		exit;
	}
}
