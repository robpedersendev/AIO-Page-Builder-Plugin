<?php
/**
 * Global sanitize_key / wp_json_encode for industry unit tests.
 *
 * @package AIOPageBuilder
 */

if ( ! function_exists( 'sanitize_key' ) ) {
	/**
	 * @param string $key Raw key.
	 * @return string
	 */
	function sanitize_key( string $key ): string {
		return strtolower( preg_replace( '/[^a-z0-9_\\-]/', '', $key ) );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * @param mixed $data Data.
	 * @return string
	 */
	function wp_json_encode( $data ): string {
		$r = json_encode( $data );
		return is_string( $r ) ? $r : '';
	}
}
