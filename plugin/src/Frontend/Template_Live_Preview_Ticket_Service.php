<?php
/**
 * Server-stored opaque tickets for template live preview (short TTL, session-bound, blog-scoped).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Issues, validates, and consumes preview tickets stored in transients.
 */
final class Template_Live_Preview_Ticket_Service {

	public const QUERY_FLAG = 'aio_pb_tpl_live';

	public const QUERY_TICKET = 'ticket';

	public const TYPE_PAGE = 'page';

	public const TYPE_SECTION = 'section';

	public const SHELL_MINIMAL = 'minimal';

	public const SHELL_COMPAT = 'compat';

	/** @var int Default time-to-live in seconds (10 minutes). */
	public const DEFAULT_TTL_SECONDS = 600;

	/** @var int Maximum successful document responses per ticket (initial render + one grace reload). */
	private const MAX_USES = 2;

	/** @var int Max ticket issuances per minute per user, template type, and template key. */
	private const RATE_ISSUE_PER_MINUTE = 5;

	/** @var int Max preview document requests per rolling window per user and blog. */
	private const RATE_DOC_PER_WINDOW = 20;

	/** @var int Rolling window for document rate limit (seconds). */
	private const RATE_DOC_WINDOW_SECONDS = 300;

	private const TRANSIENT_PREFIX = 'aio_pb_tpl_tk_';

	private const SALT_SESSION = 'aio_pb_tpl_live_sess_fp_v1';

	/**
	 * @param string $raw_ticket_id Opaque ticket id from the query string.
	 * @return string Hex sha256 hash for logging (never log raw ticket).
	 */
	public static function hash_ticket_for_log( string $raw_ticket_id ): string {
		return \hash( 'sha256', $raw_ticket_id );
	}

	/**
	 * @param string               $type 'page' or 'section'.
	 * @param string               $template_key Sanitized template internal key.
	 * @param int                  $user_id WordPress user ID.
	 * @param int                  $ttl_seconds Validity window from now.
	 * @param array<string, mixed> $extra Optional: category_class, family, purpose_family, reduced_motion, shell_mode.
	 * @return array{ticket: string, error: string}
	 */
	public static function issue( string $type, string $template_key, int $user_id, int $ttl_seconds = self::DEFAULT_TTL_SECONDS, array $extra = array() ): array {
		$template_key = \sanitize_key( $template_key );
		if ( $template_key === '' || $user_id <= 0 ) {
			return array(
				'ticket' => '',
				'error'  => 'invalid_input',
			);
		}
		$typ         = $type === self::TYPE_SECTION ? self::TYPE_SECTION : self::TYPE_PAGE;
		$ttl_seconds = \max( 300, \min( 3600, $ttl_seconds ) );

		$blog_id = (int) \get_current_blog_id();
		if ( self::is_issue_rate_limited( $user_id, $blog_id, $typ, $template_key ) ) {
			return array(
				'ticket' => '',
				'error'  => 'rate_limited_issue',
			);
		}

		$ticket_id = \bin2hex( \random_bytes( 32 ) );
		if ( $ticket_id === '' ) {
			return array(
				'ticket' => '',
				'error'  => 'entropy_failed',
			);
		}

		$now    = \time();
		$record = array(
			'typ'         => $typ,
			'key'         => $template_key,
			'uid'         => $user_id,
			'blog_id'     => $blog_id,
			'issued_at'   => $now,
			'expires_at'  => $now + $ttl_seconds,
			'session_fp'  => self::session_fingerprint_current(),
			'use_count'   => 0,
			'consumed_at' => 0,
			'cc'          => '',
			'fam'         => '',
			'pf'          => '',
			'rm'          => false,
			'shell'       => self::SHELL_MINIMAL,
		);
		if ( isset( $extra['category_class'] ) && \is_string( $extra['category_class'] ) ) {
			$record['cc'] = \sanitize_key( $extra['category_class'] );
		}
		if ( isset( $extra['family'] ) && \is_string( $extra['family'] ) ) {
			$record['fam'] = \sanitize_key( $extra['family'] );
		}
		if ( isset( $extra['purpose_family'] ) && \is_string( $extra['purpose_family'] ) ) {
			$record['pf'] = \sanitize_key( $extra['purpose_family'] );
		}
		if ( ! empty( $extra['reduced_motion'] ) ) {
			$record['rm'] = true;
		}
		if ( isset( $extra['shell_mode'] ) && \is_string( $extra['shell_mode'] ) && $extra['shell_mode'] === self::SHELL_COMPAT ) {
			$record['shell'] = self::SHELL_COMPAT;
		}

		$key = self::transient_key_for_ticket( $ticket_id );
		\set_transient( $key, $record, $ttl_seconds + 120 );

		return array(
			'ticket' => $ticket_id,
			'error'  => '',
		);
	}

	/**
	 * Validates the ticket for the current request context and increments use count (consumes a use slot).
	 *
	 * @param string $raw_ticket_id Ticket from the query string.
	 * @return array{ok: bool, code: string, record: array<string, mixed>|null, ticket_hash: string}
	 */
	public static function validate_and_consume( string $raw_ticket_id ): array {
		$ticket_hash = self::hash_ticket_for_log( $raw_ticket_id );
		$sanitized   = self::sanitize_ticket_id( $raw_ticket_id );
		if ( $sanitized === '' ) {
			return array(
				'ok'          => false,
				'code'        => 'invalid_ticket',
				'record'      => null,
				'ticket_hash' => $ticket_hash,
			);
		}

		$key = self::transient_key_for_ticket( $sanitized );
		$raw = \get_transient( $key );
		if ( ! \is_array( $raw ) ) {
			return array(
				'ok'          => false,
				'code'        => 'unknown_or_expired',
				'record'      => null,
				'ticket_hash' => $ticket_hash,
			);
		}

		$record = self::normalize_record( $raw );
		if ( $record === null ) {
			\delete_transient( $key );
			return array(
				'ok'          => false,
				'code'        => 'invalid_ticket',
				'record'      => null,
				'ticket_hash' => $ticket_hash,
			);
		}

		if ( $record['expires_at'] < \time() ) {
			\delete_transient( $key );
			return array(
				'ok'          => false,
				'code'        => 'expired',
				'record'      => null,
				'ticket_hash' => $ticket_hash,
			);
		}

		if ( (int) $record['use_count'] >= self::MAX_USES ) {
			return array(
				'ok'          => false,
				'code'        => 'exhausted',
				'record'      => null,
				'ticket_hash' => $ticket_hash,
			);
		}

		$uid = (int) \get_current_user_id();
		if ( $uid <= 0 || $uid !== (int) $record['uid'] ) {
			return array(
				'ok'          => false,
				'code'        => 'wrong_user',
				'record'      => null,
				'ticket_hash' => $ticket_hash,
			);
		}

		$blog_now = (int) \get_current_blog_id();
		if ( $blog_now !== (int) $record['blog_id'] ) {
			return array(
				'ok'          => false,
				'code'        => 'wrong_blog',
				'record'      => null,
				'ticket_hash' => $ticket_hash,
			);
		}

		$cur_fp = self::session_fingerprint_current();
		if ( ! \hash_equals( (string) $record['session_fp'], $cur_fp ) ) {
			return array(
				'ok'          => false,
				'code'        => 'wrong_session',
				'record'      => null,
				'ticket_hash' => $ticket_hash,
			);
		}

		if ( self::is_document_rate_limited( $uid, $blog_now ) ) {
			return array(
				'ok'          => false,
				'code'        => 'rate_limited_document',
				'record'      => null,
				'ticket_hash' => $ticket_hash,
			);
		}

		$record['use_count']   = (int) $record['use_count'] + 1;
		$record['consumed_at'] = \time();
		$ttl_remain            = \max( 60, (int) $record['expires_at'] - \time() );
		\set_transient( $key, $record, $ttl_remain + 60 );

		return array(
			'ok'          => true,
			'code'        => 'ok',
			'record'      => $record,
			'ticket_hash' => $ticket_hash,
		);
	}

	/**
	 * @param string $raw_ticket_id Raw ticket string.
	 * @return void
	 */
	public static function revoke( string $raw_ticket_id ): void {
		$sanitized = self::sanitize_ticket_id( $raw_ticket_id );
		if ( $sanitized === '' ) {
			return;
		}
		\delete_transient( self::transient_key_for_ticket( $sanitized ) );
	}

	/**
	 * @return bool True when transient storage accepts a write/read probe (best-effort).
	 */
	public static function probe_storage_health(): bool {
		$probe = 'aio_pb_tpl_tk_probe_' . \wp_generate_password( 12, false, false );
		\set_transient( $probe, '1', 60 );
		$ok = \get_transient( $probe ) === '1';
		\delete_transient( $probe );
		return $ok;
	}

	/**
	 * @param string $ticket_id Ticket id.
	 * @return string
	 */
	private static function transient_key_for_ticket( string $ticket_id ): string {
		return self::TRANSIENT_PREFIX . $ticket_id;
	}

	/**
	 * @param string $raw Raw query value.
	 * @return string Sanitized 64-char hex or empty.
	 */
	private static function sanitize_ticket_id( string $raw ): string {
		$raw = \trim( $raw );
		if ( $raw === '' || \strlen( $raw ) !== 64 ) {
			return '';
		}
		if ( ! \preg_match( '/^[a-f0-9]{64}$/', $raw ) ) {
			return '';
		}
		return $raw;
	}

	/**
	 * @param array<string, mixed> $raw Raw transient payload.
	 * @return array<string, mixed>|null
	 */
	private static function normalize_record( array $raw ): ?array {
		$typ = isset( $raw['typ'] ) && \is_string( $raw['typ'] ) ? $raw['typ'] : '';
		if ( $typ !== self::TYPE_PAGE && $typ !== self::TYPE_SECTION ) {
			return null;
		}
		$key = isset( $raw['key'] ) && \is_string( $raw['key'] ) ? \sanitize_key( $raw['key'] ) : '';
		if ( $key === '' ) {
			return null;
		}
		return array(
			'typ'         => $typ,
			'key'         => $key,
			'uid'         => isset( $raw['uid'] ) ? (int) $raw['uid'] : 0,
			'blog_id'     => isset( $raw['blog_id'] ) ? (int) $raw['blog_id'] : 0,
			'issued_at'   => isset( $raw['issued_at'] ) ? (int) $raw['issued_at'] : 0,
			'expires_at'  => isset( $raw['expires_at'] ) ? (int) $raw['expires_at'] : 0,
			'session_fp'  => isset( $raw['session_fp'] ) && \is_string( $raw['session_fp'] ) ? $raw['session_fp'] : '',
			'use_count'   => isset( $raw['use_count'] ) ? (int) $raw['use_count'] : 0,
			'consumed_at' => isset( $raw['consumed_at'] ) ? (int) $raw['consumed_at'] : 0,
			'cc'          => isset( $raw['cc'] ) && \is_string( $raw['cc'] ) ? \sanitize_key( $raw['cc'] ) : '',
			'fam'         => isset( $raw['fam'] ) && \is_string( $raw['fam'] ) ? \sanitize_key( $raw['fam'] ) : '',
			'pf'          => isset( $raw['pf'] ) && \is_string( $raw['pf'] ) ? \sanitize_key( $raw['pf'] ) : '',
			'rm'          => ! empty( $raw['rm'] ),
			'shell'       => ( isset( $raw['shell'] ) && \is_string( $raw['shell'] ) && $raw['shell'] === self::SHELL_COMPAT ) ? self::SHELL_COMPAT : self::SHELL_MINIMAL,
		);
	}

	/**
	 * @return string Session fingerprint for the current browser session (WordPress session token).
	 */
	public static function session_fingerprint_current(): string {
		$token = '';
		if ( \function_exists( 'wp_get_session_token' ) ) {
			$t = \wp_get_session_token();
			if ( \is_string( $t ) && $t !== '' ) {
				$token = $t;
			}
		}
		if ( $token === '' ) {
			$token = 'no_session_token';
		}
		$salt = \function_exists( 'wp_salt' ) ? \wp_salt( self::SALT_SESSION ) : 'fallback_salt';
		return \hash( 'sha256', $token . $salt );
	}

	/**
	 * @param int    $user_id User ID.
	 * @param int    $blog_id Blog ID.
	 * @param string $type Template type.
	 * @param string $template_key Template key.
	 * @return bool
	 */
	private static function is_issue_rate_limited( int $user_id, int $blog_id, string $type, string $template_key ): bool {
		$key   = 'aio_pb_tpl_rl_issue_' . $user_id . '_' . $blog_id . '_' . $type . '_' . $template_key;
		$count = (int) \get_transient( $key );
		if ( $count >= self::RATE_ISSUE_PER_MINUTE ) {
			return true;
		}
		\set_transient( $key, $count + 1, 60 );
		return false;
	}

	/**
	 * @param int $user_id User ID.
	 * @param int $blog_id Blog ID.
	 * @return bool
	 */
	private static function is_document_rate_limited( int $user_id, int $blog_id ): bool {
		$key   = 'aio_pb_tpl_rl_doc_' . $user_id . '_' . $blog_id;
		$count = (int) \get_transient( $key );
		if ( $count >= self::RATE_DOC_PER_WINDOW ) {
			return true;
		}
		\set_transient( $key, $count + 1, self::RATE_DOC_WINDOW_SECONDS );
		return false;
	}
}
