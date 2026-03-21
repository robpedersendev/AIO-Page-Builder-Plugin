<?php
/**
 * Minimal WordPress stubs for Assign_Page_Hierarchy_Handler integration tests.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Domain\Execution\Handlers;

if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\get_post' ) ) {
	/**
	 * @param int $id Post ID.
	 * @return null
	 */
	function get_post( int $id ) {
		return null;
	}
}
if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\wp_update_post' ) ) {
	/**
	 * @param array $args         Args.
	 * @param bool  $return_error Whether to return WP_Error.
	 * @return int
	 */
	function wp_update_post( array $args, bool $return_error = false ) {
		return 0;
	}
}
if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\is_wp_error' ) ) {
	/**
	 * @param mixed $thing Value.
	 * @return bool
	 */
	function is_wp_error( $thing ): bool {
		return false;
	}
}
if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\__' ) ) {
	/**
	 * @param string $text   Text.
	 * @param string $domain Domain.
	 * @return string
	 */
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}
