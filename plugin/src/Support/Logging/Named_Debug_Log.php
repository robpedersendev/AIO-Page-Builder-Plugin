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
	 * Structured one-line JSON payload after the event id (parse with jq/grep `payload=`). Scalar values only; long strings truncated.
	 *
	 * @param string               $event_id Stable id: use only Named_Debug_Log_Event::*.
	 * @param array<string, mixed> $payload  Keys snake_case; bool|int|float|string only (no nested arrays/objects).
	 */
	public static function event_json_payload( string $event_id, array $payload ): void {
		if ( ! self::sink_enabled() ) {
			return;
		}
		$sanitized = array();
		foreach ( $payload as $k => $v ) {
			$key = is_string( $k ) ? $k : (string) $k;
			if ( is_bool( $v ) ) {
				$sanitized[ $key ] = $v;
			} elseif ( is_int( $v ) || is_float( $v ) ) {
				$sanitized[ $key ] = $v;
			} elseif ( is_string( $v ) ) {
				$max = 500;
				if ( strlen( $v ) > $max ) {
					$sanitized[ $key ] = substr( $v, 0, $max ) . '…';
				} else {
					$sanitized[ $key ] = $v;
				}
			} elseif ( $v === null ) {
				$sanitized[ $key ] = '';
			} else {
				$sanitized[ $key ] = get_debug_type( $v );
			}
		}
		$flags = 0;
		if ( \defined( 'JSON_UNESCAPED_SLASHES' ) ) {
			$flags |= JSON_UNESCAPED_SLASHES;
		}
		if ( \defined( 'JSON_INVALID_UTF8_SUBSTITUTE' ) ) {
			$flags |= JSON_INVALID_UTF8_SUBSTITUTE;
		}
		$json = \wp_json_encode( $sanitized, $flags );
		if ( ! is_string( $json ) ) {
			$json = '{}';
		}
		self::event( $event_id, 'payload=' . $json );
	}

	/**
	 * Whether _aio_plan_definition diagnostics (get trace, save verify ok, UI state snapshot) run. Same gate as {@see event()}: WP_DEBUG and WP_DEBUG_LOG — no extra constants.
	 */
	public static function build_plan_meta_trace_enabled(): bool {
		return self::sink_enabled();
	}
}
