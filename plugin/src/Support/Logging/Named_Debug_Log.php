<?php
/**
 * Canonical named debug log line (WP_DEBUG + WP_DEBUG_LOG). One stable event id per call site.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Support\Logging;

defined( 'ABSPATH' ) || exit;

/**
 * Development-only sink; no-op when debug log is off. Never pass secrets, tokens, or raw credentials.
 */
final class Named_Debug_Log {

	/**
	 * @param string $event_id Stable id: use only Named_Debug_Log_Event::*.
	 * @param string $detail   Safe context (ids, counts, statuses); no secrets.
	 */
	public static function event( string $event_id, string $detail = '' ): void {
		if ( ! \defined( 'WP_DEBUG' ) || ! \WP_DEBUG ) {
			return;
		}
		if ( ! \defined( 'WP_DEBUG_LOG' ) || ! \WP_DEBUG_LOG ) {
			return;
		}
		$line = '[AIO Page Builder][' . $event_id . ']';
		if ( $detail !== '' ) {
			$line .= ' ' . $detail;
		}
		\call_user_func( 'error_log', $line );
	}
}
