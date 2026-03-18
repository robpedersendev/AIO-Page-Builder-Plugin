<?php
/**
 * Test bootstrap: defines sanitize_text_field when not in WordPress (for unit tests that load code calling it).
 *
 * @package AIOPageBuilder
 */

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( preg_replace( '/[\x00-\x1F\x7F]/u', '', (string) $str ) );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
	}
}
