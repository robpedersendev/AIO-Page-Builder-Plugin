<?php
/**
 * Stub for WordPress plugin API when running unit tests without WordPress.
 * Defines is_plugin_active (returns false) and get_plugin_data (returns Version 0).
 *
 * @package AIOPageBuilder
 */

if ( ! function_exists( 'is_plugin_active' ) ) {
	function is_plugin_active( $plugin ) {
		return false;
	}
}
if ( ! function_exists( 'get_plugin_data' ) ) {
	function get_plugin_data( $path, $markup = true, $translate = true ) {
		return array( 'Version' => '0' );
	}
}
if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', sys_get_temp_dir() );
}
