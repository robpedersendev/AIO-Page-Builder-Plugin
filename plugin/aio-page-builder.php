<?php
/**
 * Plugin Name: AIO Page Builder
 * Plugin URI: https://example.com/aio-page-builder
 * Description: Private-distribution WordPress plugin built with WordPress-style engineering standards.
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

$autoload = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

require_once __DIR__ . '/src/bootstrap.php';