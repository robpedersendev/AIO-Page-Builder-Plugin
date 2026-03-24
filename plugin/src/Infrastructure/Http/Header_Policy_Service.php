<?php
/**
 * Centralized HTTP header policies for narrow routes (preview, diagnostics, etc.).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Http;

defined( 'ABSPATH' ) || exit;

/**
 * Applies security and leakage-control headers for the template live preview route.
 *
 * Referrer-Policy is no-referrer so preview URLs are not leaked to third parties via Referer.
 * X-Frame-Options SAMEORIGIN allows same-origin iframe embedding from wp-admin.
 * CSP is intentionally minimal (no strict script-src) to avoid breaking themes with inline assets.
 */
final class Header_Policy_Service {

	/**
	 * @return list<string> Header lines (no CRLF) sent on the live preview route for tests and documentation.
	 */
	public static function template_live_preview_security_header_lines(): array {
		$csp = "frame-ancestors 'self'; base-uri 'self'; object-src 'none'; form-action 'self'";
		return array(
			'Referrer-Policy: no-referrer',
			'X-Frame-Options: SAMEORIGIN',
			'Content-Security-Policy: ' . $csp,
		);
	}

	/**
	 * @return void
	 */
	public static function apply_template_live_preview_policies(): void {
		foreach ( self::template_live_preview_security_header_lines() as $line ) {
			\header( $line, true );
		}
	}
}
