<?php
/**
 * Tabbed hub renderers and legacy submenu redirects for consolidated admin navigation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Screens\AI\AI_Providers_Screen;
use AIOPageBuilder\Admin\Screens\AI\AI_Runs_Screen;
use AIOPageBuilder\Admin\Screens\AI\Onboarding_Screen;
use AIOPageBuilder\Admin\Screens\AI\Profile_Snapshot_History_Panel;
use AIOPageBuilder\Admin\Screens\AI\Prompt_Experiments_Screen;
use AIOPageBuilder\Admin\Screens\Analytics\Template_Analytics_Screen;
use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plan_Analytics_Screen;
use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plans_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Compositions_Screen;
use AIOPageBuilder\Admin\Screens\Docs\Documentation_Detail_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Page_Template_Detail_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Page_Templates_Directory_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Section_Template_Detail_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Section_Templates_Directory_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Template_Compare_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Template_Lab_Chat_Screen;
use AIOPageBuilder\Admin\Screens\Crawler\Crawler_Comparison_Screen;
use AIOPageBuilder\Admin\Screens\Crawler\Crawler_Sessions_Screen;
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
use AIOPageBuilder\Admin\Screens\Industry\Industry_Style_Preset_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Style_Layer_Comparison_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Conversion_Goal_Comparison_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Starter_Bundle_Comparison_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Subtype_Comparison_Screen;
use AIOPageBuilder\Admin\Screens\Settings\Global_Component_Override_Settings_Screen;
use AIOPageBuilder\Admin\Screens\Settings\Global_Style_Token_Settings_Screen;
use AIOPageBuilder\Admin\Screens\Settings\Privacy_Reporting_Settings_Screen;
use AIOPageBuilder\Admin\Screens\Settings_Screen;
use AIOPageBuilder\Bootstrap\Capability_Registrar;
use AIOPageBuilder\Domain\AI\UI\AI_Providers_UI_State_Builder;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders consolidated hub screens and registers visible/hidden admin submenus.
 */
final class Admin_Menu_Hub_Renderer {

	private Service_Container $container;

	/** @var Settings_Screen */
	private $settings;

	/** @var Privacy_Reporting_Settings_Screen */
	private $privacy_reporting;

	/** @var Diagnostics_Screen */
	private $diagnostics;

	/** @var ACF_Architecture_Diagnostics_Screen */
	private $acf_diagnostics;

	/** @var Form_Provider_Health_Screen */
	private $form_provider_health;

	/** @var Onboarding_Screen */
	private $onboarding;

	/** @var Profile_Snapshot_History_Panel */
	private $profile_snapshots;

	/** @var Crawler_Sessions_Screen */
	private $crawler_sessions;

	/** @var Crawler_Comparison_Screen */
	private $crawler_comparison;

	/** @var AI_Runs_Screen */
	private $ai_runs;

	/** @var AI_Providers_Screen */
	private $ai_providers;

	/** @var Prompt_Experiments_Screen */
	private $prompt_experiments;

	/** @var Build_Plans_Screen */
	private $build_plans;

	/** @var Page_Templates_Directory_Screen */
	private $page_templates_dir;

	/** @var Page_Template_Detail_Screen */
	private $page_template_detail;

	/** @var Section_Templates_Directory_Screen */
	private $section_templates_dir;

	/** @var Section_Template_Detail_Screen */
	private $section_template_detail;

	/** @var Documentation_Detail_Screen */
	private $documentation_detail;

	/** @var Template_Compare_Screen */
	private $template_compare_screen;

	/** @var Compositions_Screen */
	private $compositions_screen;

	/** @var Template_Lab_Chat_Screen */
	private $template_lab_chat_screen;

	/** @var Build_Plan_Analytics_Screen */
	private $build_plan_analytics;

	/** @var Template_Analytics_Screen */
	private $template_analytics;

	/** @var Queue_Logs_Screen */
	private $queue_logs;

	/** @var Support_Triage_Dashboard_Screen */
	private $support_triage;

	/** @var Post_Release_Health_Screen */
	private $post_release_health;

	/** @var Industry_Profile_Settings_Screen */
	private $industry_profile;

	/** @var Industry_Override_Management_Screen */
	private $industry_override_management;

	/** @var Industry_Author_Dashboard_Screen */
	private $industry_author_dashboard;

	/** @var Industry_Health_Report_Screen */
	private $industry_health_report;

	/** @var Industry_Stale_Content_Report_Screen */
	private $industry_stale_content_report;

	/** @var Industry_Pack_Family_Comparison_Screen */
	private $industry_pack_family_comparison;

	/** @var Future_Industry_Readiness_Screen */
	private $industry_future_readiness;

	/** @var Future_Subtype_Readiness_Screen */
	private $industry_future_subtype_readiness;

	/** @var Industry_Maturity_Delta_Report_Screen */
	private $industry_maturity_delta_report;

	/** @var Industry_Drift_Report_Screen */
	private $industry_drift_report;

	/** @var Industry_Scaffold_Promotion_Readiness_Report_Screen */
	private $industry_scaffold_promotion_readiness;

	/** @var Industry_Guided_Repair_Screen */
	private $industry_guided_repair;

	/** @var Industry_Subtype_Comparison_Screen */
	private $industry_subtype_comparison;

	/** @var Industry_Starter_Bundle_Comparison_Screen */
	private $industry_bundle_comparison;

	/** @var Conversion_Goal_Comparison_Screen */
	private $industry_goal_comparison;

	/** @var Industry_Bundle_Import_Preview_Screen */
	private $industry_bundle_import_preview;

	/** @var Industry_Style_Preset_Screen */
	private $industry_style_preset;

	/** @var Industry_Style_Layer_Comparison_Screen */
	private $industry_style_layer_comparison;

	/** @var Global_Style_Token_Settings_Screen */
	private $global_style_tokens;

	/** @var Global_Component_Override_Settings_Screen */
	private $global_component_overrides;

	/** @var Import_Export_Screen */
	private $import_export;

	/**
	 * @param Service_Container              $container         Service container.
	 * @param Profile_Snapshot_History_Panel $profile_snapshots Shared panel instance (hooks registered by caller).
	 */
	public function __construct( Service_Container $container, Profile_Snapshot_History_Panel $profile_snapshots ) {
		$this->container         = $container;
		$this->profile_snapshots = $profile_snapshots;

		$this->settings                              = new Settings_Screen();
		$this->privacy_reporting                     = new Privacy_Reporting_Settings_Screen( $this->container );
		$this->diagnostics                           = new Diagnostics_Screen();
		$this->acf_diagnostics                       = new ACF_Architecture_Diagnostics_Screen( $this->container );
		$this->form_provider_health                  = new Form_Provider_Health_Screen( $this->container );
		$this->onboarding                            = new Onboarding_Screen( $this->container );
		$this->crawler_sessions                      = new Crawler_Sessions_Screen( $this->container );
		$this->crawler_comparison                    = new Crawler_Comparison_Screen( $this->container );
		$this->ai_runs                               = new AI_Runs_Screen( $this->container );
		$this->ai_providers                          = new AI_Providers_Screen( $this->container );
		$this->prompt_experiments                    = new Prompt_Experiments_Screen( $this->container );
		$this->build_plans                           = new Build_Plans_Screen( $this->container );
		$this->page_templates_dir                    = new Page_Templates_Directory_Screen( $this->container );
		$this->page_template_detail                  = new Page_Template_Detail_Screen( $this->container );
		$this->section_templates_dir                 = new Section_Templates_Directory_Screen( $this->container );
		$this->section_template_detail               = new Section_Template_Detail_Screen( $this->container );
		$this->documentation_detail                  = new Documentation_Detail_Screen( $this->container );
		$this->template_compare_screen               = new Template_Compare_Screen( $this->container );
		$this->compositions_screen                   = new Compositions_Screen( $this->container );
		$this->template_lab_chat_screen              = new Template_Lab_Chat_Screen( $this->container );
		$this->build_plan_analytics                  = new Build_Plan_Analytics_Screen( $this->container );
		$this->template_analytics                    = new Template_Analytics_Screen( $this->container );
		$this->queue_logs                            = new Queue_Logs_Screen( $this->container );
		$this->support_triage                        = new Support_Triage_Dashboard_Screen( $this->container );
		$this->post_release_health                   = new Post_Release_Health_Screen( $this->container );
		$this->industry_profile                      = new Industry_Profile_Settings_Screen( $this->container );
		$this->industry_override_management          = new Industry_Override_Management_Screen();
		$this->industry_author_dashboard             = new Industry_Author_Dashboard_Screen( $this->container );
		$this->industry_health_report                = new Industry_Health_Report_Screen( $this->container );
		$this->industry_stale_content_report         = new Industry_Stale_Content_Report_Screen( $this->container );
		$this->industry_pack_family_comparison       = new Industry_Pack_Family_Comparison_Screen( $this->container );
		$this->industry_future_readiness             = new Future_Industry_Readiness_Screen( $this->container );
		$this->industry_future_subtype_readiness     = new Future_Subtype_Readiness_Screen( $this->container );
		$this->industry_maturity_delta_report        = new Industry_Maturity_Delta_Report_Screen( $this->container );
		$this->industry_drift_report                 = new Industry_Drift_Report_Screen( $this->container );
		$this->industry_scaffold_promotion_readiness = new Industry_Scaffold_Promotion_Readiness_Report_Screen( $this->container );
		$this->industry_guided_repair                = new Industry_Guided_Repair_Screen( $this->container );
		$this->industry_subtype_comparison           = new Industry_Subtype_Comparison_Screen( $this->container );
		$this->industry_bundle_comparison            = new Industry_Starter_Bundle_Comparison_Screen( $this->container );
		$this->industry_goal_comparison              = new Conversion_Goal_Comparison_Screen( $this->container );
		$this->industry_bundle_import_preview        = new Industry_Bundle_Import_Preview_Screen( $this->container );
		$this->industry_style_preset                 = new Industry_Style_Preset_Screen( $this->container );
		$this->industry_style_layer_comparison       = new Industry_Style_Layer_Comparison_Screen( $this->container );
		$this->global_style_tokens                   = new Global_Style_Token_Settings_Screen( $this->container );
		$this->global_component_overrides            = new Global_Component_Override_Settings_Screen( $this->container );
		$this->import_export                         = new Import_Export_Screen( $this->container );
	}

	/**
	 * Registers visible and hidden submenus (not the top-level menu).
	 *
	 * @param string $parent_slug Parent menu slug.
	 * @return void
	 */
	public function register_submenus( string $parent_slug ): void {
		\add_submenu_page(
			$parent_slug,
			$this->settings->get_title(),
			__( 'Settings', 'aio-page-builder' ),
			Capabilities::ACCESS_SETTINGS_HUB,
			Settings_Screen::SLUG,
			array( $this, 'render_settings_hub' )
		);

		\add_submenu_page(
			$parent_slug,
			$this->diagnostics->get_title(),
			__( 'Diagnostics', 'aio-page-builder' ),
			$this->diagnostics->get_capability(),
			Diagnostics_Screen::SLUG,
			array( $this, 'render_diagnostics_hub' )
		);

		\add_submenu_page(
			$parent_slug,
			$this->onboarding->get_title(),
			__( 'Onboarding', 'aio-page-builder' ),
			Capabilities::ACCESS_ONBOARDING_WORKSPACE,
			Onboarding_Screen::SLUG,
			array( $this, 'render_onboarding_hub' )
		);

		\add_submenu_page(
			$parent_slug,
			$this->ai_runs->get_title(),
			__( 'AI', 'aio-page-builder' ),
			Capabilities::ACCESS_AI_WORKSPACE,
			AI_Runs_Screen::HUB_PAGE_SLUG,
			array( $this, 'render_ai_workspace_hub' )
		);

		\add_submenu_page(
			$parent_slug,
			$this->crawler_sessions->get_title(),
			__( 'Crawler', 'aio-page-builder' ),
			$this->crawler_sessions->get_capability(),
			Crawler_Sessions_Screen::SLUG,
			array( $this, 'render_crawler_hub' )
		);

		\add_submenu_page(
			$parent_slug,
			$this->build_plans->get_title(),
			__( 'Plans & analytics', 'aio-page-builder' ),
			Capabilities::ACCESS_PLANS_WORKSPACE,
			Build_Plans_Screen::SLUG,
			array( $this, 'render_plans_hub' )
		);

		\add_submenu_page(
			$parent_slug,
			__( 'Template library', 'aio-page-builder' ),
			__( 'Template library', 'aio-page-builder' ),
			Capabilities::ACCESS_TEMPLATE_LIBRARY,
			Page_Templates_Directory_Screen::SLUG,
			array( $this, 'render_template_library_hub' )
		);

		\add_submenu_page(
			$parent_slug,
			$this->queue_logs->get_title(),
			__( 'Operations', 'aio-page-builder' ),
			$this->queue_logs->get_capability(),
			Queue_Logs_Screen::SLUG,
			array( $this, 'render_operations_hub' )
		);

		\add_submenu_page(
			$parent_slug,
			$this->industry_profile->get_title(),
			__( 'Industry', 'aio-page-builder' ),
			Capabilities::ACCESS_INDUSTRY_WORKSPACE,
			Industry_Profile_Settings_Screen::SLUG,
			array( $this, 'render_industry_hub' )
		);

		\add_submenu_page(
			$parent_slug,
			$this->global_style_tokens->get_title(),
			__( 'Global styling', 'aio-page-builder' ),
			$this->global_style_tokens->get_capability(),
			Global_Style_Token_Settings_Screen::SLUG,
			array( $this, 'render_styling_hub' )
		);

		$this->register_hidden_detail_pages( $parent_slug );
		$this->register_legacy_routes( $parent_slug );
	}

	/**
	 * Hidden routes (detail/docs) and legacy slug redirects.
	 *
	 * @param string $parent_slug Parent slug.
	 * @return void
	 */
	private function register_hidden_detail_pages( string $parent_slug ): void {
		// * Use ACCESS_TEMPLATE_LIBRARY so wp-admin does not block deep links before render() (registry MANAGE_* alone can fail role merge).
		$template_shell_cap = Capabilities::ACCESS_TEMPLATE_LIBRARY;
		\add_submenu_page(
			$parent_slug,
			$this->page_template_detail->get_title(),
			'',
			$template_shell_cap,
			Page_Template_Detail_Screen::SLUG,
			array( $this->page_template_detail, 'render' )
		);

		\add_submenu_page(
			$parent_slug,
			$this->section_template_detail->get_title(),
			'',
			$template_shell_cap,
			Section_Template_Detail_Screen::SLUG,
			array( $this->section_template_detail, 'render' )
		);

		\add_submenu_page(
			$parent_slug,
			$this->documentation_detail->get_title(),
			'',
			$template_shell_cap,
			Documentation_Detail_Screen::SLUG,
			array( $this->documentation_detail, 'render' )
		);
		// * Do not remove_submenu_page() here: Core user_can_access_admin_page() matches on $submenu
		// * and uses the submenu capability (ACCESS_TEMPLATE_LIBRARY). Removing the row forces a parent
		// * fallback and breaks deep links for roles without the parent cap. Empty titles are hidden in CSS.
	}

	/**
	 * Registers legacy page slugs that redirect into hubs (bookmarks and deep links).
	 *
	 * @param string $parent_slug Parent slug.
	 * @return void
	 */
	private function register_legacy_routes( string $parent_slug ): void {
		$routes = array(
			array(
				'slug' => Privacy_Reporting_Settings_Screen::SLUG,
				'cap'  => $this->privacy_reporting->get_capability(),
				'hub'  => Settings_Screen::SLUG,
				'tab'  => 'privacy',
			),
			array(
				'slug' => ACF_Architecture_Diagnostics_Screen::SLUG,
				'cap'  => $this->acf_diagnostics->get_capability(),
				'hub'  => Diagnostics_Screen::SLUG,
				'tab'  => 'acf',
			),
			array(
				'slug' => Form_Provider_Health_Screen::SLUG,
				'cap'  => $this->form_provider_health->get_capability(),
				'hub'  => Diagnostics_Screen::SLUG,
				'tab'  => 'form_provider',
			),
			array(
				'slug' => Profile_Snapshot_History_Panel::SLUG,
				'cap'  => $this->profile_snapshots->get_capability(),
				'hub'  => Onboarding_Screen::SLUG,
				'tab'  => 'snapshots',
			),
			array(
				'slug' => AI_Runs_Screen::SLUG,
				'cap'  => Capabilities::VIEW_AI_RUNS,
				'hub'  => AI_Runs_Screen::HUB_PAGE_SLUG,
				'tab'  => 'ai_runs',
			),
			array(
				'slug' => AI_Providers_Screen::SLUG,
				'cap'  => $this->ai_providers->get_capability(),
				'hub'  => AI_Runs_Screen::HUB_PAGE_SLUG,
				'tab'  => 'providers',
			),
			array(
				'slug' => Prompt_Experiments_Screen::SLUG,
				'cap'  => $this->prompt_experiments->get_capability(),
				'hub'  => AI_Runs_Screen::HUB_PAGE_SLUG,
				'tab'  => 'experiments',
			),
			array(
				'slug' => Import_Export_Screen::SLUG,
				'cap'  => Capabilities::ACCESS_IMPORT_EXPORT_TAB,
				'hub'  => Settings_Screen::SLUG,
				'tab'  => 'import_export',
			),
			array(
				'slug' => Crawler_Comparison_Screen::SLUG,
				'cap'  => $this->crawler_comparison->get_capability(),
				'hub'  => Crawler_Sessions_Screen::SLUG,
				'tab'  => 'comparison',
			),
			array(
				'slug' => Build_Plan_Analytics_Screen::SLUG,
				'cap'  => $this->build_plan_analytics->get_capability(),
				'hub'  => Build_Plans_Screen::SLUG,
				'tab'  => 'bp_analytics',
			),
			array(
				'slug' => Template_Analytics_Screen::SLUG,
				'cap'  => $this->template_analytics->get_capability(),
				'hub'  => Build_Plans_Screen::SLUG,
				'tab'  => 'template_analytics',
			),
			array(
				'slug' => Section_Templates_Directory_Screen::SLUG,
				'cap'  => $this->section_templates_dir->get_capability(),
				'hub'  => Page_Templates_Directory_Screen::SLUG,
				'tab'  => 'section_templates',
			),
			array(
				'slug' => Compositions_Screen::SLUG,
				'cap'  => $this->compositions_screen->get_capability(),
				'hub'  => Page_Templates_Directory_Screen::SLUG,
				'tab'  => 'compositions',
			),
			array(
				'slug' => Template_Compare_Screen::SLUG,
				'cap'  => $this->template_compare_screen->get_capability(),
				'hub'  => Page_Templates_Directory_Screen::SLUG,
				'tab'  => 'compare',
			),
			array(
				'slug' => Support_Triage_Dashboard_Screen::SLUG,
				'cap'  => $this->support_triage->get_capability(),
				'hub'  => Queue_Logs_Screen::SLUG,
				'tab'  => 'triage',
			),
			array(
				'slug' => Post_Release_Health_Screen::SLUG,
				'cap'  => $this->post_release_health->get_capability(),
				'hub'  => Queue_Logs_Screen::SLUG,
				'tab'  => 'post_release',
			),
			array(
				'slug' => Global_Component_Override_Settings_Screen::SLUG,
				'cap'  => $this->global_component_overrides->get_capability(),
				'hub'  => Global_Style_Token_Settings_Screen::SLUG,
				'tab'  => 'overrides',
			),
			array(
				'slug' => Industry_Override_Management_Screen::SLUG,
				'cap'  => $this->industry_override_management->get_capability(),
				'hub'  => Industry_Profile_Settings_Screen::SLUG,
				'tab'  => 'overrides',
			),
			array(
				'slug' => Industry_Author_Dashboard_Screen::SLUG,
				'cap'  => $this->industry_author_dashboard->get_capability(),
				'hub'  => Industry_Profile_Settings_Screen::SLUG,
				'tab'  => 'author',
			),
			array(
				'slug' => Industry_Health_Report_Screen::SLUG,
				'cap'  => $this->industry_health_report->get_capability(),
				'hub'  => Industry_Profile_Settings_Screen::SLUG,
				'tab'  => 'reports',
				'sub'  => 'health',
			),
			array(
				'slug' => Industry_Stale_Content_Report_Screen::SLUG,
				'cap'  => $this->industry_stale_content_report->get_capability(),
				'hub'  => Industry_Profile_Settings_Screen::SLUG,
				'tab'  => 'reports',
				'sub'  => 'stale',
			),
			array(
				'slug' => Industry_Drift_Report_Screen::SLUG,
				'cap'  => $this->industry_drift_report->get_capability(),
				'hub'  => Industry_Profile_Settings_Screen::SLUG,
				'tab'  => 'reports',
				'sub'  => 'drift',
			),
			array(
				'slug' => Industry_Maturity_Delta_Report_Screen::SLUG,
				'cap'  => $this->industry_maturity_delta_report->get_capability(),
				'hub'  => Industry_Profile_Settings_Screen::SLUG,
				'tab'  => 'reports',
				'sub'  => 'maturity',
			),
			array(
				'slug' => Future_Industry_Readiness_Screen::SLUG,
				'cap'  => $this->industry_future_readiness->get_capability(),
				'hub'  => Industry_Profile_Settings_Screen::SLUG,
				'tab'  => 'reports',
				'sub'  => 'future_industry',
			),
			array(
				'slug' => Future_Subtype_Readiness_Screen::SLUG,
				'cap'  => $this->industry_future_subtype_readiness->get_capability(),
				'hub'  => Industry_Profile_Settings_Screen::SLUG,
				'tab'  => 'reports',
				'sub'  => 'future_subtype',
			),
			array(
				'slug' => Industry_Scaffold_Promotion_Readiness_Report_Screen::SLUG,
				'cap'  => $this->industry_scaffold_promotion_readiness->get_capability(),
				'hub'  => Industry_Profile_Settings_Screen::SLUG,
				'tab'  => 'reports',
				'sub'  => 'scaffold',
			),
			array(
				'slug' => Industry_Pack_Family_Comparison_Screen::SLUG,
				'cap'  => $this->industry_pack_family_comparison->get_capability(),
				'hub'  => Industry_Profile_Settings_Screen::SLUG,
				'tab'  => 'reports',
				'sub'  => 'pack_family',
			),
			array(
				'slug' => Industry_Guided_Repair_Screen::SLUG,
				'cap'  => $this->industry_guided_repair->get_capability(),
				'hub'  => Industry_Profile_Settings_Screen::SLUG,
				'tab'  => 'repair',
			),
			array(
				'slug' => Industry_Subtype_Comparison_Screen::SLUG,
				'cap'  => $this->industry_subtype_comparison->get_capability(),
				'hub'  => Industry_Profile_Settings_Screen::SLUG,
				'tab'  => 'comparisons',
				'sub'  => 'subtype',
			),
			array(
				'slug' => Industry_Starter_Bundle_Comparison_Screen::SLUG,
				'cap'  => $this->industry_bundle_comparison->get_capability(),
				'hub'  => Industry_Profile_Settings_Screen::SLUG,
				'tab'  => 'comparisons',
				'sub'  => 'bundle',
			),
			array(
				'slug' => Conversion_Goal_Comparison_Screen::SLUG,
				'cap'  => $this->industry_goal_comparison->get_capability(),
				'hub'  => Industry_Profile_Settings_Screen::SLUG,
				'tab'  => 'comparisons',
				'sub'  => 'goal',
			),
			array(
				'slug' => Industry_Bundle_Import_Preview_Screen::SLUG,
				'cap'  => $this->industry_bundle_import_preview->get_capability(),
				'hub'  => Industry_Profile_Settings_Screen::SLUG,
				'tab'  => 'import',
			),
			array(
				'slug' => Industry_Style_Preset_Screen::SLUG,
				'cap'  => $this->industry_style_preset->get_capability(),
				'hub'  => Industry_Profile_Settings_Screen::SLUG,
				'tab'  => 'style',
			),
			array(
				'slug' => Industry_Style_Layer_Comparison_Screen::SLUG,
				'cap'  => $this->industry_style_layer_comparison->get_capability(),
				'hub'  => Industry_Profile_Settings_Screen::SLUG,
				'tab'  => 'comparisons',
				'sub'  => 'style_layer',
			),
		);

		foreach ( $routes as $route ) {
			$hub  = (string) $route['hub'];
			$tab  = (string) $route['tab'];
			$sub  = isset( $route['sub'] ) ? (string) $route['sub'] : null;
			$slug = (string) $route['slug'];
			$cap  = (string) $route['cap'];
			\add_submenu_page(
				$parent_slug,
				'',
				'',
				$cap,
				$slug,
				function () use ( $hub, $tab, $sub ): void {
					$this->redirect_legacy_to_hub( $hub, $tab, $sub );
				}
			);
			\remove_submenu_page( $parent_slug, $slug );
		}
	}

	/**
	 * Redirects a legacy admin page slug into a hub tab URL.
	 *
	 * @param string      $hub_slug Hub page slug.
	 * @param string      $tab      Primary tab.
	 * @param string|null $subtab   Optional secondary tab.
	 * @return void
	 */
	private function redirect_legacy_to_hub( string $hub_slug, string $tab, ?string $subtab ): void {
		$args = array(
			'page'                      => $hub_slug,
			Admin_Screen_Hub::QUERY_TAB => $tab,
		);
		if ( $subtab !== null && $subtab !== '' ) {
			$args[ Admin_Screen_Hub::QUERY_SUBTAB ] = $subtab;
		}
		$url         = \add_query_arg( $args, \admin_url( 'admin.php' ) );
		$passthrough = array( 'run_id', 'date_from', 'date_to', 'template_family', 'page_class' );
		foreach ( $passthrough as $key ) {
			if ( ! isset( $_GET[ $key ] ) ) {
				continue;
			}
			$url = \add_query_arg( $key, \sanitize_text_field( \wp_unslash( (string) $_GET[ $key ] ) ), $url );
		}
		\wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Settings + privacy hub.
	 *
	 * @return void
	 */
	public function render_settings_hub(): void {
		if ( ! Capabilities::current_user_can_for_route( Capabilities::ACCESS_SETTINGS_HUB ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access these settings.', 'aio-page-builder' ), 403 );
		}
		$tabs    = array(
			'general'       => array(
				'label' => __( 'General & seeding', 'aio-page-builder' ),
				'cap'   => Capabilities::MANAGE_SETTINGS,
			),
			'privacy'       => array(
				'label' => __( 'Privacy & reporting', 'aio-page-builder' ),
				'cap'   => Capabilities::MANAGE_REPORTING_AND_PRIVACY,
			),
			'import_export' => array(
				'label' => __( 'Import / Export', 'aio-page-builder' ),
				'cap'   => Capabilities::ACCESS_IMPORT_EXPORT_TAB,
			),
		);
		$default = Admin_Screen_Hub::first_accessible_tab( 'general', $tabs );
		$tab     = Admin_Screen_Hub::current_tab( $default, array_keys( $tabs ) );
		if ( ! isset( $tabs[ $tab ] ) || ! Capabilities::current_user_can_for_route( $tabs[ $tab ]['cap'] ) ) {
			$tab = $default;
		}
		$general_subtabs = array(
			Settings_Screen::SETTINGS_SUBTAB_OVERVIEW => array(
				'label' => __( 'Overview', 'aio-page-builder' ),
				'cap'   => Capabilities::MANAGE_SETTINGS,
			),
			Settings_Screen::SETTINGS_SUBTAB_SECTION_PAGE_TEMPLATES => array(
				'label' => __( 'Section & page templates', 'aio-page-builder' ),
				'cap'   => Capabilities::MANAGE_SETTINGS,
			),
		);
		?>
		<div class="wrap aio-hub-wrap">
			<h1><?php echo \esc_html__( 'Settings', 'aio-page-builder' ); ?></h1>
			<?php Admin_Screen_Hub::render_nav_tabs( Settings_Screen::SLUG, $tabs, $tab ); ?>
			<?php
			if ( $tab === 'privacy' ) {
				$this->privacy_reporting->render( true );
			} elseif ( $tab === 'import_export' ) {
				$this->import_export->render( true );
			} else {
				$sub_default = Admin_Screen_Hub::first_accessible_tab( Settings_Screen::SETTINGS_SUBTAB_OVERVIEW, $general_subtabs );
				$subtab      = Admin_Screen_Hub::current_subtab( $sub_default, array_keys( $general_subtabs ) );
				if ( ! isset( $general_subtabs[ $subtab ] ) || ! Capabilities::current_user_can_for_route( $general_subtabs[ $subtab ]['cap'] ) ) {
					$subtab = $sub_default;
				}
				Admin_Screen_Hub::render_subnav_tabs( Settings_Screen::SLUG, 'general', $general_subtabs, $subtab );
				$this->settings->render( true, $subtab );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Diagnostics hub.
	 *
	 * @return void
	 */
	public function render_diagnostics_hub(): void {
		if ( ! Capabilities::current_user_can_for_route( $this->diagnostics->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access diagnostics.', 'aio-page-builder' ), 403 );
		}
		$tabs    = array(
			'overview'      => array(
				'label' => __( 'Overview', 'aio-page-builder' ),
				'cap'   => $this->diagnostics->get_capability(),
			),
			'acf'           => array(
				'label' => __( 'ACF field architecture', 'aio-page-builder' ),
				'cap'   => $this->acf_diagnostics->get_capability(),
			),
			'form_provider' => array(
				'label' => __( 'Form provider health', 'aio-page-builder' ),
				'cap'   => $this->form_provider_health->get_capability(),
			),
		);
		$default = Admin_Screen_Hub::first_accessible_tab( 'overview', $tabs );
		$tab     = Admin_Screen_Hub::current_tab( $default, array_keys( $tabs ) );
		if ( ! isset( $tabs[ $tab ] ) || ! Capabilities::current_user_can_for_route( $tabs[ $tab ]['cap'] ) ) {
			$tab = $default;
		}
		?>
		<div class="wrap aio-hub-wrap">
			<h1><?php echo \esc_html__( 'Diagnostics', 'aio-page-builder' ); ?></h1>
			<?php Admin_Screen_Hub::render_nav_tabs( Diagnostics_Screen::SLUG, $tabs, $tab ); ?>
			<?php
			if ( $tab === 'acf' ) {
				$this->acf_diagnostics->render( true );
			} elseif ( $tab === 'form_provider' ) {
				$this->form_provider_health->render( true );
			} else {
				$this->diagnostics->render( true );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Onboarding hub (wizard + profile snapshot history).
	 *
	 * @return void
	 */
	public function render_onboarding_hub(): void {
		if ( ! Capabilities::current_user_can_for_route( Capabilities::ACCESS_ONBOARDING_WORKSPACE ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access onboarding.', 'aio-page-builder' ), 403 );
		}
		$tabs    = array(
			'onboarding' => array(
				'label' => $this->onboarding->get_title(),
				'cap'   => $this->onboarding->get_capability(),
			),
			'snapshots'  => array(
				'label' => $this->profile_snapshots->get_title(),
				'cap'   => $this->profile_snapshots->get_capability(),
			),
		);
		$default = Admin_Screen_Hub::first_accessible_tab( 'onboarding', $tabs );
		$tab     = Admin_Screen_Hub::current_tab( $default, array_keys( $tabs ) );
		if ( ! isset( $tabs[ $tab ] ) || ! Capabilities::current_user_can_for_route( $tabs[ $tab ]['cap'] ) ) {
			$tab = $default;
		}
		?>
		<div class="wrap aio-hub-wrap">
			<h1><?php echo \esc_html__( 'Onboarding', 'aio-page-builder' ); ?></h1>
			<?php Admin_Screen_Hub::render_nav_tabs( Onboarding_Screen::SLUG, $tabs, $tab ); ?>
			<?php
			if ( $tab === 'snapshots' ) {
				$this->profile_snapshots->render( true );
			} else {
				$this->onboarding->render( true );
			}
			?>
		</div>
		<?php
	}

	/**
	 * AI workspace hub (providers, runs, experiments).
	 *
	 * @return void
	 */
	public function render_ai_workspace_hub(): void {
		if ( ! Capabilities::current_user_can_for_route( Capabilities::ACCESS_AI_WORKSPACE ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access this workspace.', 'aio-page-builder' ), 403 );
		}
		$cap_map = Admin_Screen_Hub::ai_workspace_tab_caps();
		$tabs    = array(
			'providers'   => array(
				'label' => $this->ai_providers->get_title(),
				'cap'   => $cap_map['providers'],
			),
			'ai_runs'     => array(
				'label' => $this->ai_runs->get_title(),
				'cap'   => $cap_map['ai_runs'],
			),
			'experiments' => array(
				'label' => $this->prompt_experiments->get_title(),
				'cap'   => $cap_map['experiments'],
			),
		);
		$default = Admin_Screen_Hub::first_accessible_tab( 'providers', $tabs );
		$tab     = Admin_Screen_Hub::current_tab( $default, array_keys( $tabs ) );
		if ( ! isset( $tabs[ $tab ] ) || ! Capabilities::current_user_can_for_route( $tabs[ $tab ]['cap'] ) ) {
			$tab = $default;
		}
		?>
		<div class="wrap aio-hub-wrap">
			<h1><?php echo \esc_html__( 'AI', 'aio-page-builder' ); ?></h1>
			<?php Admin_Screen_Hub::render_nav_tabs( AI_Runs_Screen::HUB_PAGE_SLUG, $tabs, $tab ); ?>
			<?php $this->render_ai_workspace_credential_trust_banner(); ?>
			<?php
			if ( $tab === 'providers' ) {
				$this->ai_providers->render( true );
			} elseif ( $tab === 'ai_runs' ) {
				$this->ai_runs->render( true );
			} elseif ( $tab === 'experiments' ) {
				$this->prompt_experiments->render( true );
			} else {
				$this->ai_providers->render( true );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Non-secret indicator: whether credentials exist in the segregated store and whether a connection test passed.
	 *
	 * @return void
	 */
	private function render_ai_workspace_credential_trust_banner(): void {
		if ( ! $this->container->has( 'ai_providers_ui_state_builder' ) ) {
			return;
		}
		$builder = $this->container->get( 'ai_providers_ui_state_builder' );
		if ( ! $builder instanceof AI_Providers_UI_State_Builder ) {
			return;
		}
		$trust = $builder->build_credential_trust_banner();
		$level = isset( $trust['trust_level'] ) ? (string) $trust['trust_level'] : 'none';
		$tid   = isset( $trust['trust_level_id'] ) ? (string) $trust['trust_level_id'] : 'aio-ai-credential-trust-none';
		?>
		<div
			class="aio-ai-credential-trust-banner notice inline"
			role="status"
			id="<?php echo \esc_attr( $tid ); ?>"
			data-aio-ai-credential-trust="<?php echo \esc_attr( $level ); ?>"
			data-aio-ai-credential-trust-id="<?php echo \esc_attr( $tid ); ?>"
		>
			<p class="aio-ai-credential-trust-summary"><?php echo \esc_html( (string) ( $trust['summary'] ?? '' ) ); ?></p>
			<p class="aio-ai-credential-trust-detail"><?php echo \esc_html( (string) ( $trust['detail'] ?? '' ) ); ?></p>
		</div>
		<?php
	}

	/**
	 * Crawler hub.
	 *
	 * @return void
	 */
	public function render_crawler_hub(): void {
		if ( ! Capabilities::current_user_can_for_route( $this->crawler_sessions->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access the crawler.', 'aio-page-builder' ), 403 );
		}
		$tabs    = array(
			'sessions'   => array(
				'label' => __( 'Sessions', 'aio-page-builder' ),
				'cap'   => $this->crawler_sessions->get_capability(),
			),
			'comparison' => array(
				'label' => $this->crawler_comparison->get_title(),
				'cap'   => $this->crawler_comparison->get_capability(),
			),
		);
		$default = Admin_Screen_Hub::first_accessible_tab( 'sessions', $tabs );
		$tab     = Admin_Screen_Hub::current_tab( $default, array_keys( $tabs ) );
		if ( ! isset( $tabs[ $tab ] ) || ! Capabilities::current_user_can_for_route( $tabs[ $tab ]['cap'] ) ) {
			$tab = $default;
		}
		?>
		<div class="wrap aio-hub-wrap">
			<h1><?php echo \esc_html__( 'Crawler', 'aio-page-builder' ); ?></h1>
			<?php Admin_Screen_Hub::render_nav_tabs( Crawler_Sessions_Screen::SLUG, $tabs, $tab ); ?>
			<?php
			if ( $tab === 'comparison' ) {
				$this->crawler_comparison->render( true );
			} else {
				$this->crawler_sessions->render( true );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Plans & analytics hub.
	 *
	 * @return void
	 */
	public function render_plans_hub(): void {
		if ( ! Capabilities::current_user_can_for_route( Capabilities::ACCESS_PLANS_WORKSPACE ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access this workspace.', 'aio-page-builder' ), 403 );
		}
		$tabs    = array(
			'build_plans'        => array(
				'label' => $this->build_plans->get_title(),
				'cap'   => $this->build_plans->get_capability(),
			),
			'bp_analytics'       => array(
				'label' => $this->build_plan_analytics->get_title(),
				'cap'   => $this->build_plan_analytics->get_capability(),
			),
			'template_analytics' => array(
				'label' => $this->template_analytics->get_title(),
				'cap'   => $this->template_analytics->get_capability(),
			),
		);
		$default = Admin_Screen_Hub::first_accessible_tab( 'build_plans', $tabs );
		$tab     = Admin_Screen_Hub::current_tab( $default, array_keys( $tabs ) );
		if ( ! isset( $tabs[ $tab ] ) || ! Capabilities::current_user_can_for_route( $tabs[ $tab ]['cap'] ) ) {
			$tab = $default;
		}
		?>
		<div class="wrap aio-hub-wrap">
			<h1><?php echo \esc_html__( 'Plans & analytics', 'aio-page-builder' ); ?></h1>
			<?php Admin_Screen_Hub::render_nav_tabs( Build_Plans_Screen::SLUG, $tabs, $tab ); ?>
			<?php
			if ( $tab === 'bp_analytics' ) {
				$this->build_plan_analytics->render( true );
			} elseif ( $tab === 'template_analytics' ) {
				$this->template_analytics->render( true );
			} else {
				$this->build_plans->render( true );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Template library hub: Section templates, Page templates, compositions, compare (on-page tabs).
	 *
	 * @return void
	 */
	public function render_template_library_hub(): void {
		if ( ! Capabilities::current_user_can_or_site_admin( Capabilities::ACCESS_TEMPLATE_LIBRARY ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access the template library.', 'aio-page-builder' ), 403 );
		}
		$tabs = array(
			'section_templates' => array(
				'label' => __( 'Section templates', 'aio-page-builder' ),
				'cap'   => $this->section_templates_dir->get_capability(),
			),
			'page_templates'    => array(
				'label' => __( 'Page templates', 'aio-page-builder' ),
				'cap'   => $this->page_templates_dir->get_capability(),
			),
			'compositions'      => array(
				'label' => $this->compositions_screen->get_title(),
				'cap'   => $this->compositions_screen->get_capability(),
			),
			'compare'           => array(
				'label' => $this->template_compare_screen->get_title(),
				'cap'   => $this->template_compare_screen->get_capability(),
			),
			'template_lab'      => array(
				'label' => $this->template_lab_chat_screen->get_title(),
				'cap'   => $this->template_lab_chat_screen->get_capability(),
			),
		);

		$accessible_keys = array();
		foreach ( $tabs as $key => $info ) {
			if ( Capabilities::current_user_can_or_site_admin( $info['cap'] ) ) {
				$accessible_keys[] = $key;
			}
		}

		if ( $accessible_keys === array() && Capabilities::current_user_can_for_route( 'manage_options' ) ) {
			Capability_Registrar::register();
			$uid = (int) \get_current_user_id();
			if ( $uid > 0 && \function_exists( 'clean_user_cache' ) ) {
				\clean_user_cache( $uid );
			}
			$accessible_keys = array();
			foreach ( $tabs as $key => $info ) {
				if ( Capabilities::current_user_can_or_site_admin( $info['cap'] ) ) {
					$accessible_keys[] = $key;
				}
			}
		}

		if ( $accessible_keys === array() ) {
			?>
			<div class="wrap aio-hub-wrap">
				<h1><?php echo \esc_html__( 'Template library', 'aio-page-builder' ); ?></h1>
				<div class="notice notice-warning">
					<p><?php \esc_html_e( 'You can open the template library, but no tabs are available for your role. Ask an administrator to grant section template, page template, composition, or related capabilities.', 'aio-page-builder' ); ?></p>
				</div>
			</div>
			<?php
			return;
		}

		$default = $accessible_keys[0];
		$tab     = Admin_Screen_Hub::current_tab( $default, $accessible_keys );
		if ( ! \in_array( $tab, $accessible_keys, true ) ) {
			$tab = $default;
		}
		?>
		<div class="wrap aio-hub-wrap">
			<h1><?php echo \esc_html__( 'Template library', 'aio-page-builder' ); ?></h1>
			<?php Admin_Screen_Hub::render_nav_tabs( Page_Templates_Directory_Screen::SLUG, $tabs, $tab, array( Capabilities::class, 'current_user_can_or_site_admin' ) ); ?>
			<?php
			if ( $tab === 'section_templates' ) {
				$this->section_templates_dir->render( true );
			} elseif ( $tab === 'compositions' ) {
				$this->compositions_screen->render( true );
			} elseif ( $tab === 'compare' ) {
				$this->template_compare_screen->render( true );
			} elseif ( $tab === 'template_lab' ) {
				$this->template_lab_chat_screen->render( true );
			} else {
				$this->page_templates_dir->render( true );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Operations hub.
	 *
	 * @return void
	 */
	public function render_operations_hub(): void {
		if ( ! Capabilities::current_user_can_for_route( $this->queue_logs->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access operations.', 'aio-page-builder' ), 403 );
		}
		$tabs    = array(
			'queue'        => array(
				'label' => __( 'Queue & logs', 'aio-page-builder' ),
				'cap'   => $this->queue_logs->get_capability(),
			),
			'triage'       => array(
				'label' => $this->support_triage->get_title(),
				'cap'   => $this->support_triage->get_capability(),
			),
			'post_release' => array(
				'label' => $this->post_release_health->get_title(),
				'cap'   => $this->post_release_health->get_capability(),
			),
		);
		$default = Admin_Screen_Hub::first_accessible_tab( 'queue', $tabs );
		$tab     = Admin_Screen_Hub::current_tab( $default, array_keys( $tabs ) );
		if ( ! isset( $tabs[ $tab ] ) || ! Capabilities::current_user_can_for_route( $tabs[ $tab ]['cap'] ) ) {
			$tab = $default;
		}
		?>
		<div class="wrap aio-hub-wrap">
			<h1><?php echo \esc_html__( 'Operations', 'aio-page-builder' ); ?></h1>
			<?php Admin_Screen_Hub::render_nav_tabs( Queue_Logs_Screen::SLUG, $tabs, $tab ); ?>
			<?php
			if ( $tab === 'triage' ) {
				$this->support_triage->render( true );
			} elseif ( $tab === 'post_release' ) {
				$this->post_release_health->render( true );
			} else {
				$this->queue_logs->render( true );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Industry hub with nested report/comparison tabs.
	 *
	 * @return void
	 */
	public function render_industry_hub(): void {
		if ( ! Capabilities::current_user_can_for_route( Capabilities::ACCESS_INDUSTRY_WORKSPACE ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access industry tools.', 'aio-page-builder' ), 403 );
		}
		// * Order: everyday setup first (profile → style → import → repair), then customization, then advanced analysis last.
		$primary         = array(
			'profile'     => array(
				'label' => __( 'Industry & packs', 'aio-page-builder' ),
				'cap'   => $this->industry_profile->get_capability(),
			),
			'style'       => array(
				'label' => __( 'Look & style', 'aio-page-builder' ),
				'cap'   => $this->industry_style_preset->get_capability(),
			),
			'import'      => array(
				'label' => __( 'Import bundles', 'aio-page-builder' ),
				'cap'   => $this->industry_bundle_import_preview->get_capability(),
			),
			'repair'      => array(
				'label' => __( 'Guided fixes', 'aio-page-builder' ),
				'cap'   => $this->industry_guided_repair->get_capability(),
			),
			'overrides'   => array(
				'label' => __( 'Template overrides', 'aio-page-builder' ),
				'cap'   => $this->industry_override_management->get_capability(),
			),
			'author'      => array(
				'label' => __( 'Authoring', 'aio-page-builder' ),
				'cap'   => $this->industry_author_dashboard->get_capability(),
			),
			'reports'     => array(
				'label' => __( 'Health & reports', 'aio-page-builder' ),
				'cap'   => Capabilities::VIEW_LOGS,
			),
			'comparisons' => array(
				'label' => __( 'Compare options', 'aio-page-builder' ),
				'cap'   => Capabilities::VIEW_LOGS,
			),
		);
		$default_primary = Admin_Screen_Hub::first_accessible_tab( 'profile', $primary );
		$tab             = Admin_Screen_Hub::current_tab( $default_primary, array_keys( $primary ) );
		if ( ! isset( $primary[ $tab ] ) || ! Capabilities::current_user_can_for_route( $primary[ $tab ]['cap'] ) ) {
			$tab = $default_primary;
		}

		$report_subs  = array(
			'health'          => array(
				'label' => __( 'Overview', 'aio-page-builder' ),
				'hint'  => __( 'Quick check that your industry profile, packs, and bundles line up—start here if something looks wrong.', 'aio-page-builder' ),
				'cap'   => $this->industry_health_report->get_capability(),
			),
			'stale'           => array(
				'label' => __( 'Stale content', 'aio-page-builder' ),
				'hint'  => __( 'Finds content that may be outdated or needs a refresh.', 'aio-page-builder' ),
				'cap'   => $this->industry_stale_content_report->get_capability(),
			),
			'drift'           => array(
				'label' => __( 'Drift', 'aio-page-builder' ),
				'hint'  => __( 'Spots where the live site has moved away from your industry templates or rules.', 'aio-page-builder' ),
				'cap'   => $this->industry_drift_report->get_capability(),
			),
			'maturity'        => array(
				'label' => __( 'Maturity', 'aio-page-builder' ),
				'hint'  => __( 'Shows progress of your industry setup over time.', 'aio-page-builder' ),
				'cap'   => $this->industry_maturity_delta_report->get_capability(),
			),
			'future_industry' => array(
				'label' => __( 'Future industries', 'aio-page-builder' ),
				'hint'  => __( 'Readiness view if you add or switch whole industries later.', 'aio-page-builder' ),
				'cap'   => $this->industry_future_readiness->get_capability(),
			),
			'future_subtype'  => array(
				'label' => __( 'Future subtypes', 'aio-page-builder' ),
				'hint'  => __( 'Readiness view for narrower industry sub-types (e.g. a specialty within your field).', 'aio-page-builder' ),
				'cap'   => $this->industry_future_subtype_readiness->get_capability(),
			),
			'scaffold'        => array(
				'label' => __( 'Promotion readiness', 'aio-page-builder' ),
				'hint'  => __( 'Whether draft industry pieces are ready to promote to live use.', 'aio-page-builder' ),
				'cap'   => $this->industry_scaffold_promotion_readiness->get_capability(),
			),
			'pack_family'     => array(
				'label' => __( 'Pack families', 'aio-page-builder' ),
				'hint'  => __( 'Compare related industry pack groups side by side.', 'aio-page-builder' ),
				'cap'   => $this->industry_pack_family_comparison->get_capability(),
			),
		);
		$compare_subs = array(
			'subtype'     => array(
				'label' => __( 'Subtypes', 'aio-page-builder' ),
				'hint'  => __( 'Compare industry sub-types (specialties) to pick or audit the right one.', 'aio-page-builder' ),
				'cap'   => $this->industry_subtype_comparison->get_capability(),
			),
			'bundle'      => array(
				'label' => __( 'Starter bundles', 'aio-page-builder' ),
				'hint'  => __( 'Compare starter page/section bundles for your industry.', 'aio-page-builder' ),
				'cap'   => $this->industry_bundle_comparison->get_capability(),
			),
			'goal'        => array(
				'label' => __( 'Goals', 'aio-page-builder' ),
				'hint'  => __( 'Compare conversion goals and how they apply.', 'aio-page-builder' ),
				'cap'   => $this->industry_goal_comparison->get_capability(),
			),
			'style_layer' => array(
				'label' => __( 'Style layers', 'aio-page-builder' ),
				'hint'  => __( 'Compare style layer options for branding and polish.', 'aio-page-builder' ),
				'cap'   => $this->industry_style_layer_comparison->get_capability(),
			),
		);

		$subtab = '';
		if ( $tab === 'reports' ) {
			$default_sub = Admin_Screen_Hub::first_accessible_tab( 'health', $report_subs );
			$subtab      = Admin_Screen_Hub::current_subtab( $default_sub, array_keys( $report_subs ) );
			if ( ! isset( $report_subs[ $subtab ] ) || ! Capabilities::current_user_can_for_route( $report_subs[ $subtab ]['cap'] ) ) {
				$subtab = $default_sub;
			}
		} elseif ( $tab === 'comparisons' ) {
			$default_sub = Admin_Screen_Hub::first_accessible_tab( 'subtype', $compare_subs );
			$subtab      = Admin_Screen_Hub::current_subtab( $default_sub, array_keys( $compare_subs ) );
			if ( ! isset( $compare_subs[ $subtab ] ) || ! Capabilities::current_user_can_for_route( $compare_subs[ $subtab ]['cap'] ) ) {
				$subtab = $default_sub;
			}
		}

		?>
		<div class="wrap aio-hub-wrap aio-industry-hub">
			<h1><?php echo \esc_html__( 'Industry', 'aio-page-builder' ); ?></h1>
			<p class="description aio-industry-hub-intro">
				<?php \esc_html_e( 'Start with Industry & packs and Look & style for setup. Import bundles and Guided fixes when you are moving content. Health & reports and Compare options are for checking quality—use them when you want a deeper review.', 'aio-page-builder' ); ?>
			</p>
			<?php
			if ( isset( $_GET[ Onboarding_Screen::QUERY_ONBOARDING_INDUSTRY_RUN ] ) ) {
				$onb_run = \sanitize_text_field( \wp_unslash( (string) $_GET[ Onboarding_Screen::QUERY_ONBOARDING_INDUSTRY_RUN ] ) );
				if ( $onb_run !== '' && Capabilities::current_user_can_for_route( Capabilities::VIEW_AI_RUNS ) ) {
					$onb_run_url = Admin_Screen_Hub::tab_url(
						AI_Runs_Screen::HUB_PAGE_SLUG,
						'ai_runs',
						array( 'run_id' => $onb_run )
					);
					?>
					<div class="notice notice-success is-dismissible aio-onboarding-industry-run-notice" role="status">
						<p>
							<?php \esc_html_e( 'Planning from onboarding is saved as an AI run. You can open it anytime:', 'aio-page-builder' ); ?>
							<a href="<?php echo \esc_url( $onb_run_url ); ?>"><?php \esc_html_e( 'View AI run', 'aio-page-builder' ); ?></a>
						</p>
					</div>
					<?php
				}
			}
			?>
			<?php Admin_Screen_Hub::render_nav_tabs( Industry_Profile_Settings_Screen::SLUG, $primary, $tab ); ?>
			<?php if ( $tab === 'reports' ) : ?>
				<?php Admin_Screen_Hub::render_subnav_tabs( Industry_Profile_Settings_Screen::SLUG, 'reports', $report_subs, $subtab ); ?>
			<?php elseif ( $tab === 'comparisons' ) : ?>
				<?php Admin_Screen_Hub::render_subnav_tabs( Industry_Profile_Settings_Screen::SLUG, 'comparisons', $compare_subs, $subtab ); ?>
			<?php endif; ?>
			<?php
			if ( $tab === 'overrides' ) {
				$this->industry_override_management->render( true );
			} elseif ( $tab === 'author' ) {
				$this->industry_author_dashboard->render( true );
			} elseif ( $tab === 'import' ) {
				$this->industry_bundle_import_preview->render( true );
			} elseif ( $tab === 'repair' ) {
				$this->industry_guided_repair->render( true );
			} elseif ( $tab === 'style' ) {
				$this->industry_style_preset->render( true );
			} elseif ( $tab === 'reports' ) {
				if ( $subtab === 'stale' ) {
					$this->industry_stale_content_report->render( true );
				} elseif ( $subtab === 'drift' ) {
					$this->industry_drift_report->render( true );
				} elseif ( $subtab === 'maturity' ) {
					$this->industry_maturity_delta_report->render( true );
				} elseif ( $subtab === 'future_industry' ) {
					$this->industry_future_readiness->render( true );
				} elseif ( $subtab === 'future_subtype' ) {
					$this->industry_future_subtype_readiness->render( true );
				} elseif ( $subtab === 'scaffold' ) {
					$this->industry_scaffold_promotion_readiness->render( true );
				} elseif ( $subtab === 'pack_family' ) {
					$this->industry_pack_family_comparison->render( true );
				} else {
					$this->industry_health_report->render( true );
				}
			} elseif ( $tab === 'comparisons' ) {
				if ( $subtab === 'bundle' ) {
					$this->industry_bundle_comparison->render( true );
				} elseif ( $subtab === 'goal' ) {
					$this->industry_goal_comparison->render( true );
				} elseif ( $subtab === 'style_layer' ) {
					$this->industry_style_layer_comparison->render( true );
				} else {
					$this->industry_subtype_comparison->render( true );
				}
			} else {
				$this->industry_profile->render( true );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Global styling hub.
	 *
	 * @return void
	 */
	public function render_styling_hub(): void {
		if ( ! Capabilities::current_user_can_for_route( $this->global_style_tokens->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access global styling.', 'aio-page-builder' ), 403 );
		}
		$tabs    = array(
			'tokens'    => array(
				'label' => $this->global_style_tokens->get_title(),
				'cap'   => $this->global_style_tokens->get_capability(),
			),
			'overrides' => array(
				'label' => $this->global_component_overrides->get_title(),
				'cap'   => $this->global_component_overrides->get_capability(),
			),
		);
		$default = Admin_Screen_Hub::first_accessible_tab( 'tokens', $tabs );
		$tab     = Admin_Screen_Hub::current_tab( $default, array_keys( $tabs ) );
		if ( ! isset( $tabs[ $tab ] ) || ! Capabilities::current_user_can_for_route( $tabs[ $tab ]['cap'] ) ) {
			$tab = $default;
		}
		?>
		<div class="wrap aio-hub-wrap">
			<h1><?php echo \esc_html__( 'Global styling', 'aio-page-builder' ); ?></h1>
			<?php Admin_Screen_Hub::render_nav_tabs( Global_Style_Token_Settings_Screen::SLUG, $tabs, $tab ); ?>
			<?php
			if ( $tab === 'overrides' ) {
				$this->global_component_overrides->render( true );
			} else {
				$this->global_style_tokens->render( true );
			}
			?>
		</div>
		<?php
	}
}
