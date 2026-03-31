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
	 * Whether the named debug sink is active (same condition as {@see event()}).
	 */
	private static function sink_enabled(): bool {
		return \defined( 'WP_DEBUG' ) && \WP_DEBUG
			&& \defined( 'WP_DEBUG_LOG' ) && \WP_DEBUG_LOG;
	}

	/**
	 * @param string $event_id Stable id: use only Named_Debug_Log_Event::*.
	 * @param string $detail   Safe context (ids, counts, statuses); no secrets.
	 */
	public static function event( string $event_id, string $detail = '' ): void {
		if ( ! self::sink_enabled() ) {
			return;
		}
		$line = '[AIO Page Builder][' . $event_id . ']';
		if ( $detail !== '' ) {
			$line .= ' ' . $detail;
		}
		\call_user_func( 'error_log', $line );
	}

	/**
	 * Whether _aio_plan_definition diagnostics (get trace, save verify ok, UI state snapshot) run. Same gate as {@see event()}: WP_DEBUG and WP_DEBUG_LOG — no extra constants.
	 */
	public static function build_plan_meta_trace_enabled(): bool {
		return self::sink_enabled();
	}
}
