<?php
/**
 * WordPress-style stubs for Industry_Bundle_Apply_Service unit tests (global namespace).
 *
 * @package AIOPageBuilder
 */

if ( ! defined( 'AIO_PB_INDUSTRY_BUNDLE_APPLY_WP_STUBS_LOADED' ) ) {
	define( 'AIO_PB_INDUSTRY_BUNDLE_APPLY_WP_STUBS_LOADED', true );
	require_once __DIR__ . '/wp-options-stub.php';
}

if ( ! function_exists( 'sanitize_key' ) ) {
	/**
	 * @param string $key Raw key.
	 * @return string
	 */
	function sanitize_key( string $key ): string {
		return strtolower( preg_replace( '/[^a-z0-9_\\-]/', '', $key ) );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * @param string $s String.
	 * @return string
	 */
	function sanitize_text_field( string $s ): string {
		return trim( $s );
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

if ( ! function_exists( 'wp_generate_uuid4' ) ) {
	/**
	 * @return string
	 */
	function wp_generate_uuid4(): string {
		return '00000000-0000-4000-8000-000000000000';
	}
}
