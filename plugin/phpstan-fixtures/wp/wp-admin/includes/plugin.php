<?php
/**
 * PHPStan fixture only: Plugin::activate() requires plugin.php for deactivate_plugins.
 *
 * @package AIOPageBuilder
 */

if ( ! function_exists( 'deactivate_plugins' ) ) {
	function deactivate_plugins( $plugins, $silent = false, $deprecated = null ) { // phpcs:ignore WordPress.WP
	}
}
