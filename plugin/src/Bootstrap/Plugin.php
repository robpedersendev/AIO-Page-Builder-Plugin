<?php
/**
 * Root bootstrap plugin class.
 *
 * Single controlled entry point for activation, deactivation, and runtime.
 * Wires lifecycle hooks and delegates to domain only via services added in later prompts.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Bootstrap;

defined( 'ABSPATH' ) || exit;

/*
 * Load container and registrar dependencies for run(). Not loaded during activate/deactivate.
 */
$bootstrap_dir = __DIR__;
require_once $bootstrap_dir . '/../Infrastructure/Container/Service_Provider_Interface.php';
require_once $bootstrap_dir . '/../Infrastructure/Container/Service_Container.php';
require_once $bootstrap_dir . '/../Infrastructure/Config/Versions.php';
require_once $bootstrap_dir . '/../Infrastructure/Config/Plugin_Config.php';
require_once $bootstrap_dir . '/../Infrastructure/Config/Option_Names.php';
require_once $bootstrap_dir . '/../Infrastructure/Settings/Settings_Service.php';
require_once $bootstrap_dir . '/../Infrastructure/Settings/Option_Store.php';
require_once $bootstrap_dir . '/../Domain/Storage/Profile/Profile_Schema.php';
require_once $bootstrap_dir . '/../Domain/Storage/Profile/Profile_Validation_Result.php';
require_once $bootstrap_dir . '/../Domain/Storage/Profile/Profile_Normalizer.php';
require_once $bootstrap_dir . '/../Domain/Storage/Profile/Profile_Store.php';
require_once $bootstrap_dir . '/../Domain/Storage/Profile/Profile_Snapshot_Helper.php';
require_once $bootstrap_dir . '/../Domain/Storage/Migrations/Schema_Version_Tracker.php';
require_once $bootstrap_dir . '/../Domain/Storage/Tables/Table_Names.php';
require_once $bootstrap_dir . '/../Domain/Storage/Tables/Table_Schema_Definitions.php';
require_once $bootstrap_dir . '/../Domain/Storage/Tables/DbDelta_Runner.php';
require_once $bootstrap_dir . '/../Domain/Storage/Tables/Table_Installer.php';
require_once $bootstrap_dir . '/../Domain/Storage/Assignments/Assignment_Types.php';
require_once $bootstrap_dir . '/../Domain/Storage/Assignments/Assignment_Map_Service.php';
require_once $bootstrap_dir . '/../Infrastructure/Files/Plugin_Path_Manager.php';
require_once $bootstrap_dir . '/../Domain/Storage/Objects/Object_Type_Keys.php';
require_once $bootstrap_dir . '/../Domain/Storage/Objects/Object_Status_Families.php';
require_once $bootstrap_dir . '/../Domain/Storage/Objects/Post_Type_Registrar.php';
require_once $bootstrap_dir . '/../Domain/Storage/Repositories/Repository_Interface.php';
require_once $bootstrap_dir . '/../Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $bootstrap_dir . '/../Domain/Storage/Repositories/Abstract_Table_Repository.php';
require_once $bootstrap_dir . '/../Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $bootstrap_dir . '/../Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $bootstrap_dir . '/../Domain/Storage/Repositories/Composition_Repository.php';
require_once $bootstrap_dir . '/../Domain/Storage/Repositories/Documentation_Repository.php';
require_once $bootstrap_dir . '/../Domain/Storage/Repositories/Version_Snapshot_Repository.php';
require_once $bootstrap_dir . '/../Domain/Storage/Repositories/Build_Plan_Repository.php';
require_once $bootstrap_dir . '/../Domain/Storage/Repositories/AI_Run_Repository.php';
require_once $bootstrap_dir . '/../Domain/Storage/Repositories/Job_Queue_Repository.php';
require_once $bootstrap_dir . '/../Support/Logging/Log_Severities.php';
require_once $bootstrap_dir . '/../Support/Logging/Log_Categories.php';
require_once $bootstrap_dir . '/../Support/Logging/Error_Record.php';
require_once $bootstrap_dir . '/../Support/Logging/Logger_Interface.php';
require_once $bootstrap_dir . '/../Support/Logging/Null_Logger.php';
require_once $bootstrap_dir . '/../Infrastructure/Container/Providers/Config_Provider.php';
require_once $bootstrap_dir . '/../Infrastructure/Container/Providers/Diagnostics_Provider.php';
require_once $bootstrap_dir . '/../Infrastructure/Container/Providers/Admin_Router_Provider.php';
require_once $bootstrap_dir . '/../Infrastructure/Container/Providers/Capability_Provider.php';
require_once $bootstrap_dir . '/../Infrastructure/Container/Providers/Object_Registration_Provider.php';
require_once $bootstrap_dir . '/../Infrastructure/Container/Providers/Repositories_Provider.php';
require_once $bootstrap_dir . '/../Infrastructure/Container/Providers/Storage_Services_Provider.php';
require_once $bootstrap_dir . '/../Domain/AI/Onboarding/Onboarding_Statuses.php';
require_once $bootstrap_dir . '/../Domain/AI/Onboarding/Onboarding_Step_Keys.php';
require_once $bootstrap_dir . '/../Domain/AI/Onboarding/Onboarding_Draft_Service.php';
require_once $bootstrap_dir . '/../Domain/AI/Onboarding/Onboarding_Prefill_Service.php';
require_once $bootstrap_dir . '/../Domain/AI/Onboarding/Onboarding_UI_State_Builder.php';
require_once $bootstrap_dir . '/../Infrastructure/Container/Providers/Onboarding_Provider.php';
require_once $bootstrap_dir . '/Module_Registrar.php';
require_once $bootstrap_dir . '/Lifecycle_Manager.php';
require_once $bootstrap_dir . '/../Admin/Screens/Dashboard_Screen.php';
require_once $bootstrap_dir . '/../Admin/Screens/Settings_Screen.php';
require_once $bootstrap_dir . '/../Admin/Screens/Diagnostics_Screen.php';
require_once $bootstrap_dir . '/../Admin/Screens/AI/Onboarding_Screen.php';
require_once $bootstrap_dir . '/../Admin/Screens/Crawler/Crawler_Sessions_Screen.php';
require_once $bootstrap_dir . '/../Admin/Screens/Crawler/Crawler_Session_Detail_Screen.php';
require_once $bootstrap_dir . '/../Admin/Screens/Crawler/Crawler_Comparison_Screen.php';
require_once $bootstrap_dir . '/../Admin/Admin_Menu.php';

/**
 * Plugin bootstrap. Activation, deactivation, and run() are the only public entrypoints.
 */
final class Plugin {

	/** @var \AIOPageBuilder\Infrastructure\Container\Service_Container|null Container for bootstrap wiring; set in run(). */
	private ?\AIOPageBuilder\Infrastructure\Container\Service_Container $container = null;

	/**
	 * Called on plugins_loaded. Instantiates the plugin and runs it.
	 * Future: will register autoloader, environment check, and service wiring here.
	 *
	 * @return void
	 */
	public static function bootstrap(): void {
		$plugin = new self();
		$plugin->run();
	}

	/**
	 * Activation hook. Delegates to Lifecycle_Manager; aborts on blocking failure.
	 *
	 * @return void
	 */
	public static function activate(): void {
		$manager = new Lifecycle_Manager();
		$result  = $manager->activate();
		if ( $result->is_blocking() ) {
			if ( function_exists( 'deactivate_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
				deactivate_plugins( Constants::plugin_basename() );
			}
			\wp_die(
				\esc_html( $result->message ?: 'Plugin activation failed.' ),
				'Activation Failed',
				array( 'response' => 500, 'back_link' => true )
			);
		}
	}

	/**
	 * Deactivation hook. Delegates to Lifecycle_Manager; non-destructive.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		$manager = new Lifecycle_Manager();
		$manager->deactivate();
	}

	/**
	 * Runtime entry point. Runs after WordPress has loaded.
	 * Wires bootstrap through Module_Registrar; no ad hoc service instantiation here.
	 *
	 * @return void
	 */
	public function run(): void {
		Constants::plugin_file();
		$container = new \AIOPageBuilder\Infrastructure\Container\Service_Container();
		$registrar = new Module_Registrar( $container );
		$registrar->register_bootstrap();
		$this->container = $registrar->container();
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 10 );
		}
	}

	/**
	 * Registers the plugin admin menu and routes to screen classes.
	 *
	 * @return void
	 */
	public function register_admin_menu(): void {
		$menu = new \AIOPageBuilder\Admin\Admin_Menu( $this->container );
		$menu->register();
	}

	/*
	 * Manual verification checklist (Prompt 002):
	 * - [ ] Plugin appears in WP admin Plugins list.
	 * - [ ] Activate: no fatal error; activation completes.
	 * - [ ] Deactivate: no fatal error; deactivation completes.
	 * - [ ] Direct access to uninstall.php: exits without defining WP_UNINSTALL_PLUGIN (safe).
	 * - [ ] No options, capabilities, or content deleted by root/bootstrap/uninstall stubs.
	 *
	 * Manual verification checklist (Prompt 003):
	 * - [ ] Constants load before plugin run (root calls Constants::init() before bootstrap).
	 * - [ ] No duplicate version/path definitions in root file.
	 * - [ ] Min WP 6.6, min PHP 8.1 (Constants::min_wp_version(), min_php_version()).
	 * - [ ] Versions::all() returns plugin, global_schema, table_schema, registry_schema, export_schema.
	 *
	 * Manual verification checklist (Prompt 005):
	 * - [ ] Activation calls Lifecycle_Manager::activate(); deactivation calls Lifecycle_Manager::deactivate().
	 * - [ ] Blocking failure from a phase aborts activation (plugin deactivated, message shown) without fatal.
	 * - [ ] Uninstall file guarded by WP_UNINSTALL_PLUGIN; uninstall runs Lifecycle_Manager::uninstall(); no deletion.
	 *
	 * Manual verification checklist (Prompt 056 – Crawler admin):
	 * - [ ] AIO Page Builder menu shows submenus: Dashboard, Settings, Diagnostics, Crawl Sessions, Crawl Comparison.
	 * - [ ] Crawl Sessions: list view shows run ID, site host, status, counts, Started, View pages link (or "No crawl sessions yet").
	 * - [ ] Crawl Sessions: clicking View pages opens session detail with Run ID, Site host, Status, Page snapshots table (URL, Title, Classification, Nav, Status) or "No page records".
	 * - [ ] Crawl Comparison: form with Prior run and New run dropdowns, Compare button; after submit shows summary table and page changes table when two runs selected.
	 * - [ ] No raw HTML or unbounded content in list/detail; errors are user-friendly (e.g. Invalid run ID).
	 */
}
