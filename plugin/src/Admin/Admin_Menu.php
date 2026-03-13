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
use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plan_Analytics_Screen;
use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plans_Screen;
use AIOPageBuilder\Admin\Screens\Crawler\Crawler_Comparison_Screen;
use AIOPageBuilder\Admin\Screens\Crawler\Crawler_Sessions_Screen;
use AIOPageBuilder\Admin\Screens\Dashboard\Dashboard_Screen;
use AIOPageBuilder\Admin\Screens\Diagnostics_Screen;
use AIOPageBuilder\Admin\Screens\Logs\Queue_Logs_Screen;
use AIOPageBuilder\Admin\Screens\Operations\Post_Release_Health_Screen;
use AIOPageBuilder\Admin\Screens\Support\Support_Triage_Dashboard_Screen;
use AIOPageBuilder\Admin\Screens\ImportExport\Import_Export_Screen;
use AIOPageBuilder\Admin\Screens\Settings\Privacy_Reporting_Settings_Screen;
use AIOPageBuilder\Admin\Screens\Settings_Screen;
use AIOPageBuilder\Domain\FormProvider\Form_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\ExpansionPack\Page_Template_And_Composition_Expansion_Pack_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\TopLevelBatch\Top_Level_Marketing_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\TopLevelLegalUtilityBatch\Top_Level_Legal_Utility_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\HubBatch\Hub_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\Section\ExpansionPack\Section_Expansion_Pack_Seeder;
use AIOPageBuilder\Domain\Registries\Section\HeroBatch\Hero_Intro_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\FeatureBenefitBatch\Feature_Benefit_Value_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\CtaSuperLibraryBatch\CTA_Super_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\LegalPolicyUtilityBatch\Legal_Policy_Utility_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\MediaListingProfileBatch\Media_Listing_Profile_Detail_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\ProcessTimelineFaqBatch\Process_Timeline_FAQ_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\TrustProofBatch\Trust_Proof_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
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
		\add_action( 'admin_post_aio_seed_hub_page_templates', array( $this, 'handle_seed_hub_page_templates' ), 10 );

		$dashboard   = new Dashboard_Screen( $this->container );
		$settings    = new Settings_Screen();
		$diagnostics = new Diagnostics_Screen();
		$onboarding  = new Onboarding_Screen( $this->container );
		$crawler_sessions  = new Crawler_Sessions_Screen( $this->container );
		$crawler_comparison = new Crawler_Comparison_Screen( $this->container );
		$ai_runs            = new AI_Runs_Screen( $this->container );
		$ai_providers       = new AI_Providers_Screen( $this->container );
		$prompt_experiments = new Prompt_Experiments_Screen( $this->container );
		$build_plans        = new Build_Plans_Screen( $this->container );
		$build_plan_analytics = new Build_Plan_Analytics_Screen( $this->container );
		$queue_logs         = new Queue_Logs_Screen( $this->container );
		$support_triage     = new Support_Triage_Dashboard_Screen( $this->container );
		$post_release_health = new Post_Release_Health_Screen( $this->container );
		$privacy_reporting  = new Privacy_Reporting_Settings_Screen( $this->container );
		$import_export     = new Import_Export_Screen( $this->container );

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
			$build_plan_analytics->get_title(),
			__( 'Build Plan Analytics', 'aio-page-builder' ),
			$build_plan_analytics->get_capability(),
			Build_Plan_Analytics_Screen::SLUG,
			array( $build_plan_analytics, 'render' )
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
			$import_export->get_title(),
			__( 'Import / Export', 'aio-page-builder' ),
			$import_export->get_capability(),
			Import_Export_Screen::SLUG,
			array( $import_export, 'render' )
		);
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
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_seed_result=error' ) );
			exit;
		}
		if ( ! $this->container instanceof Service_Container ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_seed_result=error' ) );
			exit;
		}
		$section_repo = $this->container->get( 'section_template_repository' );
		$page_repo    = $this->container->get( 'page_template_repository' );
		if ( ! $section_repo instanceof Section_Template_Repository || ! $page_repo instanceof Page_Template_Repository ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=' . Settings_Screen::SLUG . '&aio_seed_result=error' ) );
			exit;
		}
		$result = Form_Template_Seeder::run( $section_repo, $page_repo );
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
		if ( ! \current_user_can( 'manage_options' ) ) {
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
		if ( ! \current_user_can( 'manage_options' ) ) {
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
		if ( ! \current_user_can( 'manage_options' ) ) {
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
		if ( ! \current_user_can( 'manage_options' ) ) {
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
		if ( ! \current_user_can( 'manage_options' ) ) {
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
		if ( ! \current_user_can( 'manage_options' ) ) {
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
		if ( ! \current_user_can( 'manage_options' ) ) {
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
		if ( ! \current_user_can( 'manage_options' ) ) {
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
		if ( ! \current_user_can( 'manage_options' ) ) {
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
		if ( ! \current_user_can( 'manage_options' ) ) {
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
		if ( ! \current_user_can( 'manage_options' ) ) {
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
		if ( ! \current_user_can( 'manage_options' ) ) {
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
}
