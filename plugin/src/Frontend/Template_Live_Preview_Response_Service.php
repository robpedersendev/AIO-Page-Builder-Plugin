<?php
/**
 * Response helpers for template live preview (cache bypass + security headers).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Frontend;

use AIOPageBuilder\Infrastructure\Http\Header_Policy_Service;

defined( 'ABSPATH' ) || exit;

/**
 * Sends no-store cache headers, CDN hints, and preview-specific security policies.
 */
final class Template_Live_Preview_Response_Service {

	/**
	 * @return list<string> Primary Cache-Control / Pragma / Expires lines emitted directly (tests compare without invoking header()).
	 */
	public static function no_cache_direct_header_lines(): array {
		return array(
			'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private',
			'Cache-Control: post-check=0, pre-check=0',
			'Pragma: no-cache',
			'Expires: Wed, 11 Jan 1984 05:00:00 GMT',
			'X-Robots-Tag: noindex, nofollow, noarchive, nosnippet',
			'Surrogate-Control: no-store',
			'Vary: Cookie',
		);
	}

	/**
	 * @return void
	 */
	public static function send_no_cache_headers(): void {
		if ( ! \defined( 'DONOTCACHEPAGE' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WP ecosystem constant.
			\define( 'DONOTCACHEPAGE', true );
		}

		if ( \function_exists( 'nocache_headers' ) ) {
			\nocache_headers();
		}

		\header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true );
		\header( 'Cache-Control: post-check=0, pre-check=0', false );
		\header( 'Pragma: no-cache', true );
		\header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT', true );
		\header( 'X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true );
		\header( 'Surrogate-Control: no-store', true );
		\header( 'Vary: Cookie', true );

		\add_filter(
			'wp_headers',
			static function ( array $headers ): array {
				$headers['Cache-Control']             = 'no-store, no-cache, must-revalidate, max-age=0, private';
				$headers['Pragma']                    = 'no-cache';
				$headers['Expires']                   = 'Wed, 11 Jan 1984 05:00:00 GMT';
				$headers['X-Robots-Tag']              = 'noindex, nofollow, noarchive, nosnippet';
				$headers['Surrogate-Control']         = 'no-store';
				$headers['CDN-Cache-Control']         = 'no-store';
				$headers['X-LiteSpeed-Cache-Control'] = 'no-cache';
				return $headers;
			},
			99999
		);
	}

	/**
	 * @return void
	 */
	public static function send_preview_response_headers(): void {
		self::send_no_cache_headers();
		Header_Policy_Service::apply_template_live_preview_policies();
	}

	/**
	 * @return list<string> All direct header() lines for no-cache + preview security (for tests).
	 */
	public static function preview_response_direct_header_lines(): array {
		return array_merge( self::no_cache_direct_header_lines(), Header_Policy_Service::template_live_preview_security_header_lines() );
	}
}
