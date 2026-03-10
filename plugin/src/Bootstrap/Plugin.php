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
require_once $bootstrap_dir . '/../Infrastructure/Container/Providers/Config_Provider.php';
require_once $bootstrap_dir . '/../Infrastructure/Container/Providers/Diagnostics_Provider.php';
require_once $bootstrap_dir . '/../Infrastructure/Container/Providers/Admin_Router_Provider.php';
require_once $bootstrap_dir . '/../Infrastructure/Container/Providers/Capability_Provider.php';
require_once $bootstrap_dir . '/Module_Registrar.php';
require_once $bootstrap_dir . '/Lifecycle_Manager.php';
require_once $bootstrap_dir . '/../Admin/Screens/Dashboard_Screen.php';
require_once $bootstrap_dir . '/../Admin/Screens/Settings_Screen.php';
require_once $bootstrap_dir . '/../Admin/Screens/Diagnostics_Screen.php';
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
		$menu = new \AIOPageBuilder\Admin\Admin_Menu();
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
	 */
}
