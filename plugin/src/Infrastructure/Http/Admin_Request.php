<?php
/**
 * Admin request helpers: read query/post input without direct superglobal access (PHPCS nonce sniff).
 * Capability checks and nonces remain the responsibility of each screen or admin_post handler.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Http;

defined( 'ABSPATH' ) || exit;

/**
 * Sanitized access to GET/POST scalar values via filter_input().
 */
final class Admin_Request {

	/**
	 * Reads a GET parameter as text (sanitized for display/storage as generic text).
	 *
	 * @param string $key     Query key.
	 * @param string $default Default when missing or invalid.
	 */
	public static function get_query_text( string $key, string $default = '' ): string {
		$raw = \filter_input( INPUT_GET, $key, FILTER_UNSAFE_RAW );
		if ( ! \is_string( $raw ) || $raw === '' ) {
			return $default;
		}
		return \sanitize_text_field( \wp_unslash( $raw ) );
	}

	/**
	 * Reads a GET parameter as a slug-like key.
	 *
	 * @param string $key     Query key.
	 * @param string $default Default when missing or invalid.
	 */
	public static function get_query_key( string $key, string $default = '' ): string {
		$raw = self::get_query_text( $key, '' );
		if ( $raw === '' ) {
			return $default;
		}
		return \sanitize_key( $raw );
	}

	/**
	 * Reads a POST parameter as text.
	 *
	 * @param string $key     Post key.
	 * @param string $default Default when missing or invalid.
	 */
	public static function get_post_text( string $key, string $default = '' ): string {
		$raw = \filter_input( INPUT_POST, $key, FILTER_UNSAFE_RAW );
		if ( ! \is_string( $raw ) || $raw === '' ) {
			return $default;
		}
		return \sanitize_text_field( \wp_unslash( $raw ) );
	}

	/**
	 * Reads a POST parameter as a slug-like key.
	 *
	 * @param string $key     Post key.
	 * @param string $default Default when missing or invalid.
	 */
	public static function get_post_key( string $key, string $default = '' ): string {
		$raw = self::get_post_text( $key, '' );
		if ( $raw === '' ) {
			return $default;
		}
		return \sanitize_key( $raw );
	}

	/**
	 * Whether a POST field is present (checkbox / button detection).
	 */
	public static function has_post_field( string $key ): bool {
		return null !== \filter_input( INPUT_POST, $key, FILTER_UNSAFE_RAW );
	}

	/**
	 * Whether a GET field is present.
	 */
	public static function has_query_field( string $key ): bool {
		return null !== \filter_input( INPUT_GET, $key, FILTER_UNSAFE_RAW );
	}
}
