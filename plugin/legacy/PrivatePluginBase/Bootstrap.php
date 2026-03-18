<?php
/**
 * LEGACY — NOT LOADED BY ACTIVE PLUGIN.
 * Old PrivatePluginBase bootstrap. Active entry: aio-page-builder.php → AIOPageBuilder\Bootstrap\Plugin.
 * Quarantined in plugin/legacy/; see legacy/README.md. Do not use in production.
 *
 * Plugin bootstrap.
 *
 * @package PrivatePluginBase
 */

declare(strict_types=1);

namespace PrivatePluginBase;

defined( 'ABSPATH' ) || exit;

/**
 * Bootstraps the plugin and wires core services.
 */
final class Bootstrap {

	/**
	 * Main plugin file path.
	 *
	 * @var string
	 */
	private static string $plugin_file;

	/**
	 * Initializes the plugin.
	 *
	 * @param string $plugin_file Path to the main plugin file.
	 * @return void
	 */
	public static function init( string $plugin_file ): void {
		self::$plugin_file = $plugin_file;

		register_activation_hook( $plugin_file, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( $plugin_file, array( __CLASS__, 'deactivate' ) );

		add_action( 'init', array( __CLASS__, 'register_services' ), 0 );
	}

	/**
	 * Returns the main plugin file path.
	 *
	 * @return string
	 */
	public static function plugin_file(): string {
		return self::$plugin_file;
	}

	/**
	 * Activation callback.
	 *
	 * @return void
	 */
	public static function activate(): void {
		Activation::run();
	}

	/**
	 * Deactivation callback.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		Deactivation::run();
	}

	/**
	 * Registers services on init.
	 *
	 * @return void
	 */
	public static function register_services(): void {
		Admin\Menu::register();
		Rest\NamespaceController::register();
		Settings\Registrar::register();
		Reporting\Service::register();
	}
}
