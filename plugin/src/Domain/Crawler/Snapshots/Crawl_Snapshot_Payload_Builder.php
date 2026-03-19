<?php
/**
 * Builds normalized crawl session and page snapshot payloads (spec ?11.1, ?24.9ÿÿÿ24.11, ?24.15, ?58.4).
 * Aligned to custom-table-manifest ?3.1; no fetcher or discovery logic.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Snapshots;

defined( 'ABSPATH' ) || exit;

/**
 * Normalized payload shapes for crawl session (run metadata) and page snapshot (per-URL record).
 * All fields are explicit and machine-readable; no unstructured blobs for core identity.
 */
final class Crawl_Snapshot_Payload_Builder {

	/** Schema version for snapshot records (spec ?58.4). */
	public const SCHEMA_VERSION = '1';

	// --- Session payload keys (stored in options; not in crawl_snapshots table) ---

	public const SESSION_CRAWL_RUN_ID      = 'crawl_run_id';
	public const SESSION_SITE_HOST         = 'site_host';
	public const SESSION_CRAWL_PROFILE_KEY = 'crawl_profile_key';
	public const SESSION_STARTED_AT        = 'started_at';
	public const SESSION_ENDED_AT          = 'ended_at';
	public const SESSION_SETTINGS          = 'crawl_settings';
	public const SESSION_TOTAL_DISCOVERED  = 'total_discovered';
	public const SESSION_ACCEPTED_COUNT    = 'accepted_count';
	public const SESSION_EXCLUDED_COUNT    = 'excluded_count';
	public const SESSION_FAILED_COUNT      = 'failed_count';
	public const SESSION_FINAL_STATUS      = 'final_status';
	public const SESSION_SCHEMA_VERSION    = 'schema_version';

	// --- Page record keys (table columns) ---

	public const PAGE_CRAWL_RUN_ID       = 'crawl_run_id';
	public const PAGE_URL                = 'url';
	public const PAGE_CANONICAL_URL      = 'canonical_url';
	public const PAGE_TITLE_SNAPSHOT     = 'title_snapshot';
	public const PAGE_META_SNAPSHOT      = 'meta_snapshot';
	public const PAGE_INDEXABILITY_FLAGS = 'indexability_flags';
	public const PAGE_CLASSIFICATION     = 'page_classification';
	public const PAGE_HIERARCHY_CLUES    = 'hierarchy_clues';
	public const PAGE_NAVIGATION         = 'navigation_participation';
	public const PAGE_SUMMARY_DATA       = 'summary_data';
	public const PAGE_CONTENT_HASH       = 'content_hash';
	public const PAGE_CRAWL_STATUS       = 'crawl_status';
	public const PAGE_ERROR_STATE        = 'error_state';
	public const PAGE_CRAWLED_AT         = 'crawled_at';
	public const PAGE_SCHEMA_VERSION     = 'schema_version';

	/** Allowed crawl_status values (manifest ?3.1; spec ?24.16). */
	public const STATUS_PENDING   = 'pending';
	public const STATUS_COMPLETED = 'completed';
	public const STATUS_ERROR     = 'error';

	/** Final session status values (spec ?24.15, ?24.16). */
	public const SESSION_STATUS_RUNNING   = 'running';
	public const SESSION_STATUS_PARTIAL   = 'partial';
	public const SESSION_STATUS_COMPLETED = 'completed';
	public const SESSION_STATUS_FAILED    = 'failed';

	/** Max lengths for sanitization (manifest). */
	private const URL_MAX_LENGTH            = 2048;
	private const RUN_ID_MAX_LENGTH         = 64;
	private const TITLE_MAX_LENGTH          = 512;
	private const ERROR_STATE_MAX_LENGTH    = 255;
	private const INDEXABILITY_MAX_LENGTH   = 255;
	private const CLASSIFICATION_MAX_LENGTH = 64;

	/** Max length for crawl_profile_key (bounded, spec ?24). */
	private const PROFILE_KEY_MAX_LENGTH = 64;

	/**
	 * Builds a normalized crawl session payload for storage (e.g. option).
	 *
	 * @param string               $crawl_run_id   Stable run identifier.
	 * @param string               $site_host      Canonical host for the crawl.
	 * @param string|null          $started_at     ISO 8601 datetime; null if not started.
	 * @param string|null          $ended_at       ISO 8601 datetime; null if not ended.
	 * @param array<string, mixed> $settings Optional crawl settings (bounded, no secrets).
	 * @param int                  $total_discovered Total discovered URLs.
	 * @param int                  $accepted_count  Accepted meaningful pages.
	 * @param int                  $excluded_count  Excluded pages.
	 * @param int                  $failed_count    Failed requests.
	 * @param string               $final_status   One of SESSION_STATUS_*.
	 * @param string               $crawl_profile_key Approved profile key (stored with session; default full_public_baseline).
	 * @return array<string, mixed> Session record; safe to store server-side.
	 */
	public static function build_session_payload(
		string $crawl_run_id,
		string $site_host,
		?string $started_at,
		?string $ended_at,
		array $settings = array(),
		int $total_discovered = 0,
		int $accepted_count = 0,
		int $excluded_count = 0,
		int $failed_count = 0,
		string $final_status = self::SESSION_STATUS_RUNNING,
		string $crawl_profile_key = 'full_public_baseline'
	): array {
		$run_id      = self::sanitize_run_id( $crawl_run_id );
		$host        = self::sanitize_host( $site_host );
		$status      = self::normalize_session_status( $final_status );
		$profile_key = \sanitize_text_field( self::truncate( trim( $crawl_profile_key ), self::PROFILE_KEY_MAX_LENGTH ) );
		if ( $profile_key === '' ) {
			$profile_key = 'full_public_baseline';
		}
		return array(
			self::SESSION_CRAWL_RUN_ID      => $run_id,
			self::SESSION_SITE_HOST         => $host,
			self::SESSION_CRAWL_PROFILE_KEY => $profile_key,
			self::SESSION_STARTED_AT        => $started_at !== null && $started_at !== '' ? $started_at : null,
			self::SESSION_ENDED_AT          => $ended_at !== null && $ended_at !== '' ? $ended_at : null,
			self::SESSION_SETTINGS          => self::sanitize_settings( $settings ),
			self::SESSION_TOTAL_DISCOVERED  => max( 0, $total_discovered ),
			self::SESSION_ACCEPTED_COUNT    => max( 0, $accepted_count ),
			self::SESSION_EXCLUDED_COUNT    => max( 0, $excluded_count ),
			self::SESSION_FAILED_COUNT      => max( 0, $failed_count ),
			self::SESSION_FINAL_STATUS      => $status,
			self::SESSION_SCHEMA_VERSION    => self::SCHEMA_VERSION,
		);
	}

	/**
	 * Builds a normalized page snapshot payload for table insert (manifest ?3.1).
	 *
	 * @param string               $crawl_run_id   Crawl run identifier.
	 * @param string               $url            Discovered URL (normalized).
	 * @param array<string, mixed> $overrides Optional overrides; only known keys applied.
	 * @return array<string, mixed> Page record; keys match table columns.
	 */
	public static function build_page_payload( string $crawl_run_id, string $url, array $overrides = array() ): array {
		$run_id = self::sanitize_run_id( $crawl_run_id );
		$url    = self::sanitize_url( $url );
		if ( $run_id === '' || $url === '' ) {
			return array();
		}
		$base    = array(
			self::PAGE_CRAWL_RUN_ID       => $run_id,
			self::PAGE_URL                => $url,
			self::PAGE_CANONICAL_URL      => null,
			self::PAGE_TITLE_SNAPSHOT     => null,
			self::PAGE_META_SNAPSHOT      => null,
			self::PAGE_INDEXABILITY_FLAGS => null,
			self::PAGE_CLASSIFICATION     => null,
			self::PAGE_HIERARCHY_CLUES    => null,
			self::PAGE_NAVIGATION         => 0,
			self::PAGE_SUMMARY_DATA       => null,
			self::PAGE_CONTENT_HASH       => null,
			self::PAGE_CRAWL_STATUS       => self::STATUS_PENDING,
			self::PAGE_ERROR_STATE        => null,
			self::PAGE_CRAWLED_AT         => null,
			self::PAGE_SCHEMA_VERSION     => self::SCHEMA_VERSION,
		);
		$allowed = array(
			self::PAGE_CANONICAL_URL,
			self::PAGE_TITLE_SNAPSHOT,
			self::PAGE_META_SNAPSHOT,
			self::PAGE_INDEXABILITY_FLAGS,
			self::PAGE_CLASSIFICATION,
			self::PAGE_HIERARCHY_CLUES,
			self::PAGE_NAVIGATION,
			self::PAGE_SUMMARY_DATA,
			self::PAGE_CONTENT_HASH,
			self::PAGE_CRAWL_STATUS,
			self::PAGE_ERROR_STATE,
			self::PAGE_CRAWLED_AT,
		);
		foreach ( $allowed as $key ) {
			if ( array_key_exists( $key, $overrides ) ) {
				$value = $overrides[ $key ];
				if ( $key === self::PAGE_CANONICAL_URL ) {
					$base[ $key ] = $value !== null && $value !== '' ? self::sanitize_url( (string) $value ) : null;
				} elseif ( $key === self::PAGE_TITLE_SNAPSHOT ) {
					$base[ $key ] = $value !== null && $value !== '' ? self::truncate( (string) $value, self::TITLE_MAX_LENGTH ) : null;
				} elseif ( $key === self::PAGE_INDEXABILITY_FLAGS ) {
					$base[ $key ] = $value !== null && $value !== '' ? self::truncate( (string) $value, self::INDEXABILITY_MAX_LENGTH ) : null;
				} elseif ( $key === self::PAGE_CLASSIFICATION ) {
					$base[ $key ] = $value !== null && $value !== '' ? self::truncate( (string) $value, self::CLASSIFICATION_MAX_LENGTH ) : null;
				} elseif ( $key === self::PAGE_ERROR_STATE ) {
					$base[ $key ] = $value !== null && $value !== '' ? self::truncate( (string) $value, self::ERROR_STATE_MAX_LENGTH ) : null;
				} elseif ( $key === self::PAGE_CRAWL_STATUS ) {
					$base[ $key ] = self::normalize_page_status( (string) $value );
				} elseif ( $key === self::PAGE_NAVIGATION ) {
					$base[ $key ] = is_numeric( $value ) ? max( 0, (int) $value ) : 0;
				} elseif ( $key === self::PAGE_CRAWLED_AT ) {
					$base[ $key ] = $value !== null && $value !== '' ? (string) $value : null;
				} else {
					$base[ $key ] = $value;
				}
			}
		}
		return $base;
	}

	/**
	 * Returns allowed page crawl_status values.
	 *
	 * @return array<int, string>
	 */
	public static function get_allowed_page_statuses(): array {
		return array( self::STATUS_PENDING, self::STATUS_COMPLETED, self::STATUS_ERROR );
	}

	/**
	 * Returns allowed session final_status values.
	 *
	 * @return array<int, string>
	 */
	public static function get_allowed_session_statuses(): array {
		return array( self::SESSION_STATUS_RUNNING, self::SESSION_STATUS_PARTIAL, self::SESSION_STATUS_COMPLETED, self::SESSION_STATUS_FAILED );
	}

	private static function sanitize_run_id( string $id ): string {
		return \sanitize_text_field( self::truncate( $id, self::RUN_ID_MAX_LENGTH ) );
	}

	private static function sanitize_host( string $host ): string {
		return \sanitize_text_field( self::truncate( $host, 255 ) );
	}

	private static function sanitize_url( string $url ): string {
		$u = \esc_url_raw( $url );
		return $u !== false ? self::truncate( $u, self::URL_MAX_LENGTH ) : '';
	}

	private static function sanitize_settings( array $settings ): array {
		$out = array();
		foreach ( $settings as $k => $v ) {
			if ( is_string( $k ) && strlen( $k ) <= 64 && ! is_object( $v ) ) {
				$out[ $k ] = is_scalar( $v ) ? $v : ( is_array( $v ) ? $v : (string) $v );
			}
		}
		return $out;
	}

	private static function normalize_page_status( string $status ): string {
		return in_array( $status, self::get_allowed_page_statuses(), true )
			? $status
			: self::STATUS_PENDING;
	}

	private static function normalize_session_status( string $status ): string {
		return in_array( $status, self::get_allowed_session_statuses(), true )
			? $status
			: self::SESSION_STATUS_RUNNING;
	}

	private static function truncate( string $s, int $max ): string {
		if ( $max <= 0 ) {
			return '';
		}
		return strlen( $s ) > $max ? substr( $s, 0, $max ) : $s;
	}
}
