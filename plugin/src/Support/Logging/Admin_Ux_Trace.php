<?php
/**
 * Structured admin UX trace lines for WP_DEBUG builds (JSON after [AIO_UX] prefix).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Support\Logging;

defined( 'ABSPATH' ) || exit;

/**
 * Emits one JSON object per line. Gate: WP_DEBUG only. Sink: error_log when WP_DEBUG_LOG; else uploads aio-admin-ux-trace.log.
 */
final class Admin_Ux_Trace {

	public const LINE_PREFIX     = '[AIO_UX]';
	public const SCHEMA_VERSION  = '1';
	public const UPLOAD_FILENAME = 'aio-admin-ux-trace.log';

	public const CATEGORY_ADMIN_UX = 'admin_ux';

	/** @var int Monotonic sequence for the current PHP request. */
	private static int $php_sequence = 0;

	/**
	 * True when diagnostic tracing should run (WP_DEBUG only).
	 */
	public static function enabled(): bool {
		return \defined( 'WP_DEBUG' ) && \WP_DEBUG;
	}

	/**
	 * @param array<string, mixed> $partial Required: severity, category, facet. Optional: tags, hub, tab, subtab, message_id, expected, actual, detail, source.
	 */
	public static function emit( array $partial ): void {
		if ( ! self::enabled() ) {
			return;
		}
		$record = self::compose_record( $partial );
		$json   = \wp_json_encode( $record, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE );
		if ( ! is_string( $json ) ) {
			return;
		}
		if ( \strlen( $json ) > 12000 ) {
			$json = \wp_json_encode(
				array(
					'schema_version' => self::SCHEMA_VERSION,
					'ts_utc'         => \gmdate( 'c' ),
					'sequence'       => self::$php_sequence,
					'severity'       => 'warning',
					'category'       => self::CATEGORY_ADMIN_UX,
					'facet'          => 'render',
					'detail'         => 'ux_trace_payload_truncated',
					'source'         => 'php',
				),
				\JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE
			);
			if ( ! is_string( $json ) ) {
				return;
			}
		}
		self::write_line( self::LINE_PREFIX . $json );
	}

	/**
	 * Hub navigation / render boundary (server).
	 *
	 * @param array<int, string> $tags
	 */
	public static function hub_entry( string $hub_page_slug, string $tab, string $subtab = '', array $tags = array() ): void {
		$ctx = self::capture_request_context( $hub_page_slug, $tab, $subtab );
		self::emit(
			array_merge(
				$ctx,
				array(
					'severity' => 'flow',
					'category' => self::CATEGORY_ADMIN_UX,
					'facet'    => 'navigation',
					'tags'     => array_merge( array( 'hub:' . $hub_page_slug, 'tab:' . $tab ), $subtab !== '' ? array( 'subtab:' . $subtab ) : array(), $tags ),
					'source'   => 'php',
				)
			)
		);
	}

	/**
	 * Admin notice rendered (stable id + class).
	 *
	 * @param array<int, string> $tags
	 */
	public static function notice_rendered( string $message_id, string $notice_class, array $tags = array() ): void {
		self::emit(
			array(
				'severity'   => 'actual',
				'category'   => self::CATEGORY_ADMIN_UX,
				'facet'      => 'notice',
				'message_id' => self::clamp_token( $message_id, 120 ),
				'actual'     => array(
					'notice_class' => self::clamp_token( $notice_class, 64 ),
				),
				'tags'       => $tags,
				'source'     => 'php',
			)
		);
	}

	/**
	 * Admin-post or state-changing action boundary.
	 *
	 * @param array<string, mixed> $expected Optional expected outcome summary.
	 * @param array<string, mixed> $actual   Optional actual outcome summary.
	 * @param array<int, string>   $tags
	 */
	public static function admin_post_boundary(
		string $action_slug,
		string $phase,
		array $expected = array(),
		array $actual = array(),
		array $tags = array()
	): void {
		$row = array(
			'severity' => 'flow',
			'category' => self::CATEGORY_ADMIN_UX,
			'facet'    => 'admin_post',
			'detail'   => self::clamp_token( $action_slug . ':' . $phase, 200 ),
			'tags'     => array_merge( array( 'action:' . self::clamp_token( $action_slug, 80 ) ), $tags ),
			'source'   => 'php',
		);
		if ( $expected !== array() ) {
			$row['expected'] = self::shallow_scalarize( $expected, 6 );
		}
		if ( $actual !== array() ) {
			$row['actual'] = self::shallow_scalarize( $actual, 6 );
		}
		self::emit( $row );
	}

	/**
	 * @param array<string, mixed> $partial
	 * @return array<string, mixed>
	 */
	public static function compose_record( array $partial ): array {
		++self::$php_sequence;
		$base = array(
			'schema_version' => self::SCHEMA_VERSION,
			'ts_utc'         => \gmdate( 'c' ),
			'sequence'       => self::$php_sequence,
			'source'         => isset( $partial['source'] ) && is_string( $partial['source'] ) ? self::clamp_token( $partial['source'], 16 ) : 'php',
			'actor_user_id'  => (int) \get_current_user_id(),
			'request_method' => self::request_method(),
			'url_path'       => self::admin_url_path(),
			'query_snapshot' => self::allowed_get_snapshot(),
		);
		unset( $partial['source'] );
		$merged = array_merge( $base, $partial );
		return self::whiten_record( $merged );
	}

	/**
	 * @return array<string, string|int>
	 */
	private static function capture_request_context( string $hub, string $tab, string $subtab ): array {
		return array(
			'hub'    => self::clamp_token( $hub, 120 ),
			'tab'    => self::clamp_token( $tab, 64 ),
			'subtab' => self::clamp_token( $subtab, 64 ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	public static function allowed_get_snapshot(): array {
		$allow = array(
			'page',
			'aio_tab',
			'aio_subtab',
			'plan_id',
			'id',
			'step',
			'detail',
			'run_id',
			'aio_bp_from_run',
			'aio_bp_repair_result',
		);
		$out   = array();
		foreach ( $allow as $key ) {
			if ( ! isset( $_GET[ $key ] ) ) {
				continue;
			}
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per key below.
			$raw         = \wp_unslash( $_GET[ $key ] );
			$out[ $key ] = self::sanitize_query_value( $key, $raw );
		}
		return $out;
	}

	/**
	 * @param mixed $raw Raw GET value.
	 */
	private static function sanitize_query_value( string $key, $raw ): string {
		if ( is_array( $raw ) ) {
			return '(array)';
		}
		$s = (string) $raw;
		if ( $key === 'page' ) {
			return self::clamp_token( \sanitize_key( $s ), 120 );
		}
		if ( $key === 'aio_tab' || $key === 'aio_subtab' || $key === 'aio_bp_from_run' || $key === 'aio_bp_repair_result' ) {
			return self::clamp_token( \sanitize_key( $s ), 64 );
		}
		if ( $key === 'id' ) {
			$n = (int) $s;
			return $n > 0 ? (string) $n : '';
		}
		if ( $key === 'plan_id' || $key === 'run_id' ) {
			$t   = \sanitize_text_field( $s );
			$len = \strlen( $t );
			$h   = $len > 0 ? \substr( \md5( $t ), 0, 8 ) : '';
			return 'len=' . (string) $len . ',h=' . $h;
		}
		return self::clamp_token( \sanitize_text_field( $s ), 128 );
	}

	private static function request_method(): string {
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || ! is_string( $_SERVER['REQUEST_METHOD'] ) ) {
			return 'GET';
		}
		$m = \strtoupper( \sanitize_text_field( \wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) );
		return $m !== '' ? self::clamp_token( $m, 12 ) : 'GET';
	}

	private static function admin_url_path(): string {
		if ( ! isset( $_SERVER['SCRIPT_NAME'] ) || ! is_string( $_SERVER['SCRIPT_NAME'] ) ) {
			return '';
		}
		$sn = \wp_unslash( $_SERVER['SCRIPT_NAME'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Basename only; not output.
		$sn = is_string( $sn ) ? $sn : '';
		$b  = \basename( $sn );
		return self::clamp_token( $b, 64 );
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	private static function whiten_record( array $row ): array {
		$sev             = isset( $row['severity'] ) && is_string( $row['severity'] ) ? self::normalize_severity( $row['severity'] ) : 'info';
		$row['severity'] = $sev;
		if ( isset( $row['category'] ) && is_string( $row['category'] ) ) {
			$row['category'] = self::clamp_token( $row['category'], 64 );
		} else {
			$row['category'] = self::CATEGORY_ADMIN_UX;
		}
		if ( isset( $row['facet'] ) && is_string( $row['facet'] ) ) {
			$row['facet'] = self::normalize_facet( $row['facet'] );
		} else {
			$row['facet'] = 'render';
		}
		if ( isset( $row['tags'] ) && is_array( $row['tags'] ) ) {
			$tags = array();
			foreach ( $row['tags'] as $t ) {
				if ( is_string( $t ) && $t !== '' ) {
					$tags[] = self::clamp_token( $t, 120 );
				}
			}
			$row['tags'] = \array_slice( $tags, 0, 24 );
		}
		if ( isset( $row['hub'] ) && is_string( $row['hub'] ) ) {
			$row['hub'] = self::clamp_token( $row['hub'], 120 );
		}
		if ( isset( $row['tab'] ) && is_string( $row['tab'] ) ) {
			$row['tab'] = self::clamp_token( $row['tab'], 64 );
		}
		if ( isset( $row['subtab'] ) && is_string( $row['subtab'] ) ) {
			$row['subtab'] = self::clamp_token( $row['subtab'], 64 );
		}
		if ( isset( $row['message_id'] ) && is_string( $row['message_id'] ) ) {
			$row['message_id'] = self::clamp_token( $row['message_id'], 120 );
		}
		if ( isset( $row['detail'] ) && is_string( $row['detail'] ) ) {
			$row['detail'] = self::clamp_token( $row['detail'], 500 );
		}
		return $row;
	}

	private static function normalize_severity( string $s ): string {
		$allowed = array(
			'info',
			'warning',
			'error',
			'critical',
			'flow',
			'expected',
			'actual',
			'assert_ok',
			'assert_fail',
		);
		$k       = \sanitize_key( $s );
		return \in_array( $k, $allowed, true ) ? $k : 'info';
	}

	private static function normalize_facet( string $s ): string {
		$allowed = array(
			'navigation',
			'form_submit',
			'admin_post',
			'redirect',
			'notice',
			'capability',
			'render',
			'client_interaction',
			'dom_marker',
		);
		$k       = \sanitize_key( $s );
		return \in_array( $k, $allowed, true ) ? $k : 'render';
	}

	/**
	 * @param array<string, mixed> $data
	 * @return array<string, scalar>
	 */
	private static function shallow_scalarize( array $data, int $max_keys ): array {
		$out = array();
		$n   = 0;
		foreach ( $data as $k => $v ) {
			if ( $n >= $max_keys ) {
				break;
			}
			if ( ! is_string( $k ) ) {
				continue;
			}
			$k = self::clamp_token( \sanitize_key( $k ), 48 );
			if ( is_bool( $v ) || is_int( $v ) || is_float( $v ) ) {
				$out[ $k ] = $v;
			} elseif ( is_string( $v ) ) {
				$out[ $k ] = self::clamp_token( $v, 200 );
			} else {
				$out[ $k ] = '(complex)';
			}
			++$n;
		}
		return $out;
	}

	private static function clamp_token( string $s, int $max ): string {
		if ( $max <= 0 ) {
			return '';
		}
		if ( \strlen( $s ) <= $max ) {
			return $s;
		}
		return \substr( $s, 0, $max - 1 ) . '…';
	}

	private static function write_line( string $line ): void {
		if ( \defined( 'WP_DEBUG_LOG' ) && \WP_DEBUG_LOG ) {
			\call_user_func( 'error_log', $line );
			return;
		}
		if ( ! \function_exists( 'wp_upload_dir' ) ) {
			return;
		}
		$dir = \wp_upload_dir();
		if ( ! is_array( $dir ) || ! empty( $dir['error'] ) ) {
			return;
		}
		$base = isset( $dir['basedir'] ) ? (string) $dir['basedir'] : '';
		if ( $base === '' ) {
			return;
		}
		$path = \trailingslashit( $base ) . self::UPLOAD_FILENAME;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Debug-only fallback sink.
		@\file_put_contents( $path, $line . "\n", \FILE_APPEND | \LOCK_EX );
	}
}
