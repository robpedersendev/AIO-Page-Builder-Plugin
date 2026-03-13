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
use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plans_Screen;
use AIOPageBuilder\Admin\Screens\Crawler\Crawler_Comparison_Screen;
use AIOPageBuilder\Admin\Screens\Crawler\Crawler_Sessions_Screen;
use AIOPageBuilder\Admin\Screens\Dashboard\Dashboard_Screen;
use AIOPageBuilder\Admin\Screens\Diagnostics_Screen;
use AIOPageBuilder\Admin\Screens\Logs\Queue_Logs_Screen;
use AIOPageBuilder\Admin\Screens\ImportExport\Import_Export_Screen;
use AIOPageBuilder\Admin\Screens\Settings\Privacy_Reporting_Settings_Screen;
use AIOPageBuilder\Admin\Screens\Settings_Screen;
use AIOPageBuilder\Domain\FormProvider\Form_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\ExpansionPack\Page_Template_And_Composition_Expansion_Pack_Seeder;
use AIOPageBuilder\Domain\Registries\Section\ExpansionPack\Section_Expansion_Pack_Seeder;
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
		\add_action( 'admin_post_aio_seed_page_composition_expansion_pack', array( $this, 'handle_seed_page_composition_expansion_pack' ), 10 );

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
		$queue_logs         = new Queue_Logs_Screen( $this->container );
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
			$queue_logs->get_title(),
			__( 'Queue & Logs', 'aio-page-builder' ),
			$queue_logs->get_capability(),
			Queue_Logs_Screen::SLUG,
			array( $queue_logs, 'render' )
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
}
