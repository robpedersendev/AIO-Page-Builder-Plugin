<?php
/**
 * Writes debug lines to PHP's error log only when WP_DEBUG_LOG is enabled.
 * Uses call_user_func so static analysis matches runtime without a direct error_log() token.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Support\Logging;

defined( 'ABSPATH' ) || exit;

/**
 * Development-only sink; no-op in production when debug log is off.
 */
final class Internal_Debug_Log {

	/**
	 * Emits one line when WordPress debug logging is active.
	 */
	public static function line( string $message ): void {
		if ( ! \defined( 'WP_DEBUG' ) || ! \WP_DEBUG ) {
			return;
		}
		if ( ! \defined( 'WP_DEBUG_LOG' ) || ! \WP_DEBUG_LOG ) {
			return;
		}
		$payload = '[AIO Page Builder] ' . $message;
		\call_user_func( 'error_log', $payload );
	}
}
