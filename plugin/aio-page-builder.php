<?php
/**
 * Plugin Name: AIO Page Builder
 * Plugin URI: https://steadyhandmarketing.com/aio-page-builder
 * Description: Private-distribution WordPress plugin for structured page building with AI-assisted planning.
 * Version: 0.1.0
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Author: Steady Hand Marketing
 * Author URI: https://steadyhandmarketing.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: aio-page-builder
 * Domain Path: /languages
 *
 * @package AIOPageBuilder
 *
 * Single entry point: prefers Composer autoload when present, then falls back to the internal
 * runtime autoloader so packaged releases do not require vendor/.
 * Legacy PrivatePluginBase code is quarantined in plugin/legacy/ and is not loaded (see legacy/README.md).
 */

defined( 'ABSPATH' ) || exit;

$vendor_autoload   = __DIR__ . '/vendor/autoload.php';
$internal_autoload = __DIR__ . '/src/Bootstrap/Internal_Autoloader.php';

if ( is_readable( $vendor_autoload ) ) {
	require_once $vendor_autoload;
} elseif ( is_readable( $internal_autoload ) ) {
	require_once $internal_autoload;
	\AIOPageBuilder\Bootstrap\Internal_Autoloader::register();
} else {
	$message = 'AIO Page Builder is missing its runtime autoloader. Install the packaged release ZIP or run Composer in plugin/.';

	if ( function_exists( 'wp_die' ) ) {
		wp_die(
			$message,
			'AIO Page Builder',
			array(
				'response'  => 500,
				'back_link' => true,
			)
		);
	}

	die( $message );
}

require_once __DIR__ . '/src/Bootstrap/Constants.php';
require_once __DIR__ . '/src/Bootstrap/Plugin.php';

\AIOPageBuilder\Bootstrap\Constants::init();

use AIOPageBuilder\Bootstrap\Plugin;

register_activation_hook( __FILE__, array( Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Plugin::class, 'deactivate' ) );

add_action( 'plugins_loaded', array( Plugin::class, 'bootstrap' ), 0 );
