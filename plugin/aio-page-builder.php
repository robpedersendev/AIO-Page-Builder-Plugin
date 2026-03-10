<?php
/**
 * Plugin Name: AIO Page Builder
 * Plugin URI: https://example.com/aio-page-builder
 * Description: Private-distribution WordPress plugin for structured page building with AI-assisted planning.
 * Version: 0.1.0
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Author: Steady Hand Marketing
 * Author URI: https://example.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: aio-page-builder
 * Domain Path: /languages
 * Update URI: https://example.com/aio-page-builder
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

/*
 * Runtime constants — for later extraction to a dedicated constants loader (e.g. Prompt 003).
 * Path and URL are required for bootstrap; version is runtime-only until versioning contract exists.
 */
if ( ! defined( 'AIO_PAGE_BUILDER_FILE' ) ) {
	define( 'AIO_PAGE_BUILDER_FILE', __FILE__ );
}
if ( ! defined( 'AIO_PAGE_BUILDER_DIR' ) ) {
	define( 'AIO_PAGE_BUILDER_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'AIO_PAGE_BUILDER_URL' ) ) {
	define( 'AIO_PAGE_BUILDER_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'AIO_PAGE_BUILDER_VERSION' ) ) {
	define( 'AIO_PAGE_BUILDER_VERSION', '0.1.0' );
}

require_once __DIR__ . '/src/Bootstrap/Plugin.php';

use AIOPageBuilder\Bootstrap\Plugin;

register_activation_hook( __FILE__, array( Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Plugin::class, 'deactivate' ) );

add_action( 'plugins_loaded', array( Plugin::class, 'bootstrap' ), 0 );
