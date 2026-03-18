<?php
/**
 * Crawl snapshot service: session creation, page record persistence, read by session/URL/status (spec §11.1, §24.15, §24.16, §58.4).
 * No URL discovery or fetching; storage and payload building only. Supports classification result persistence.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Snapshots;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Crawler\Classification\Classification_Result;
use AIOPageBuilder\Domain\Crawler\Classification\Crawl_Template_Family_Matcher;
use AIOPageBuilder\Domain\Crawler\Classification\Crawl_Template_Match_Result;
use AIOPageBuilder\Domain\Crawler\Extraction\Extraction_Result;
use AIOPageBuilder\Domain\Crawler\Profiles\Crawl_Profile_Service;

/**
 * Creates crawl sessions (metadata in options), stores and retrieves page snapshot records (table).
 * Schema-version aware; diagnostics hooks can be wired by callers.
 */
final class Crawl_Snapshot_Service {

	/** Option key prefix for session metadata (one option per run: {prefix}{crawl_run_id}). */
	private const SESSION_OPTION_PREFIX = 'aio_page_builder_crawl_session_';

	/** Max length for crawl_run_id used in option key. */
	private const RUN_ID_OPTION_MAX = 64;

	/** @var Crawl_Snapshot_Repository */
	private $repository;

	/** @var Crawl_Profile_Service */
	private $profile_service;

	/** @var Crawl_Template_Family_Matcher */
	private $template_family_matcher;

	public function __construct(
		Crawl_Snapshot_Repository $repository,
		Crawl_Profile_Service $profile_service,
		Crawl_Template_Family_Matcher $template_family_matcher
	) {
		$this->repository              = $repository;
		$this->profile_service         = $profile_service;
		$this->template_family_matcher = $template_family_matcher;
	}

	/**
	 * Creates a new crawl session: generates run id, stores session payload in options.
	 *
	 * @param string               $site_host Canonical host for the crawl.
	 * @param array<string, mixed> $settings Optional crawl settings (no secrets).
	 * @return string Crawl run id, or empty string on failure.
	 */
	public function create_session( string $site_host, array $settings = array() ): string {
		$crawl_run_id = $this->generate_run_id();
		if ( $crawl_run_id === '' ) {
			return '';
		}
		$profile_key = $this->profile_service->resolve_profile_key(
			(string) ( $settings['crawl_profile_key'] ?? '' )
		);
		$now         = $this->iso8601_now();
		$payload     = Crawl_Snapshot_Payload_Builder::build_session_payload(
			$crawl_run_id,
			$site_host,
			$now,
			null,
			$settings,
			0,
			0,
			0,
			0,
			Crawl_Snapshot_Payload_Builder::SESSION_STATUS_RUNNING,
			$profile_key
		);
		$option_key  = $this->session_option_key( $crawl_run_id );
		if ( $option_key === '' ) {
			return '';
		}
		$saved = \update_option( $option_key, $payload, false );
		if ( ! $saved ) {
			return '';
		}
		return $crawl_run_id;
	}

	/**
	 * Lists crawl sessions (runs that have at least one page record). Merges option-stored session payloads where present.
	 *
	 * @param int $limit Max sessions to return (default 50).
	 * @return list<array<string, mixed>>
	 */
	public function list_sessions( int $limit = 50 ): array {
		$run_ids  = $this->repository->list_crawl_run_ids( $limit );
		$sessions = array();
		foreach ( $run_ids as $run_id ) {
			$payload = $this->get_session( $run_id );
			if ( $payload !== null ) {
				$payload['crawl_run_id'] = $run_id;
				$sessions[]              = $payload;
			} else {
				$sessions[] = array(
					'crawl_run_id'      => $run_id,
					'site_host'         => '',
					'crawl_profile_key' => 'full_public_baseline',
					'started_at'        => null,
					'ended_at'          => null,
					'final_status'      => 'unknown',
					'total_discovered'  => 0,
					'accepted_count'    => 0,
					'excluded_count'    => 0,
					'failed_count'      => 0,
				);
			}
		}
		usort(
			$sessions,
			function ( $a, $b ) {
				$t1 = $a['started_at'] ?? '';
				$t2 = $b['started_at'] ?? '';
				return strcmp( (string) $t2, (string) $t1 );
			}
		);
		return $sessions;
	}

	/**
	 * Returns session payload for a crawl run, or null if not found.
	 *
	 * @param string $crawl_run_id Crawl run identifier.
	 * @return array<string, mixed>|null Session record.
	 */
	public function get_session( string $crawl_run_id ): ?array {
		$key = $this->session_option_key( $crawl_run_id );
		if ( $key === '' ) {
			return null;
		}
		$payload = \get_option( $key, null );
		if ( ! is_array( $payload ) ) {
			return null;
		}
		return $payload;
	}

	/**
	 * Updates session metadata (e.g. ended_at, counts, final_status).
	 *
	 * @param string               $crawl_run_id Crawl run identifier.
	 * @param array<string, mixed> $overrides    Keys from Crawl_Snapshot_Payload_Builder::SESSION_*.
	 * @return bool True if option updated.
	 */
	public function update_session( string $crawl_run_id, array $overrides ): bool {
		$existing = $this->get_session( $crawl_run_id );
		if ( $existing === null ) {
			return false;
		}
		$allowed = array(
			Crawl_Snapshot_Payload_Builder::SESSION_ENDED_AT,
			Crawl_Snapshot_Payload_Builder::SESSION_TOTAL_DISCOVERED,
			Crawl_Snapshot_Payload_Builder::SESSION_ACCEPTED_COUNT,
			Crawl_Snapshot_Payload_Builder::SESSION_EXCLUDED_COUNT,
			Crawl_Snapshot_Payload_Builder::SESSION_FAILED_COUNT,
			Crawl_Snapshot_Payload_Builder::SESSION_FINAL_STATUS,
		);
		foreach ( $allowed as $k ) {
			if ( array_key_exists( $k, $overrides ) ) {
				$existing[ $k ] = $overrides[ $k ];
			}
		}
		$option_key = $this->session_option_key( $crawl_run_id );
		return $option_key !== '' && \update_option( $option_key, $existing, false );
	}

	/**
	 * Stores a page snapshot record (insert or update by run_id + url).
	 *
	 * @param string               $crawl_run_id Crawl run identifier.
	 * @param string               $url         Normalized URL.
	 * @param array<string, mixed> $overrides   Optional field overrides (title_snapshot, crawl_status, etc.).
	 * @return int Inserted or updated row id; 0 on failure.
	 */
	public function store_page_record( string $crawl_run_id, string $url, array $overrides = array() ): int {
		$payload = Crawl_Snapshot_Payload_Builder::build_page_payload( $crawl_run_id, $url, $overrides );
		if ( empty( $payload ) ) {
			return 0;
		}
		return $this->repository->save( $payload );
	}

	/**
	 * Stores classification outcome for a page (updates page_classification, indexability_flags, content_hash).
	 *
	 * @param string                $crawl_run_id   Crawl run identifier.
	 * @param string                $url            Normalized URL.
	 * @param Classification_Result $result         Classification result from Meaningful_Page_Classifier.
	 * @param string|null           $title_snapshot  Optional title to store with the record.
	 * @return int Updated row id; 0 on failure.
	 */
	public function record_classification( string $crawl_run_id, string $url, Classification_Result $result, ?string $title_snapshot = null ): int {
		$reasons_str = implode( ',', $result->reason_codes );
		$overrides   = array(
			Crawl_Snapshot_Payload_Builder::PAGE_CLASSIFICATION     => $result->classification,
			Crawl_Snapshot_Payload_Builder::PAGE_INDEXABILITY_FLAGS  => $reasons_str !== '' ? $reasons_str : null,
			Crawl_Snapshot_Payload_Builder::PAGE_CONTENT_HASH        => $result->content_hash,
		);
		if ( $title_snapshot !== null && $title_snapshot !== '' ) {
			$overrides[ Crawl_Snapshot_Payload_Builder::PAGE_TITLE_SNAPSHOT ] = $title_snapshot;
		}
		return $this->store_page_record( $crawl_run_id, $url, $overrides );
	}

	/**
	 * Stores extraction outcome for a page (summary_data, optional title_snapshot and meta_snapshot from page_summary).
	 *
	 * @param string            $crawl_run_id Crawl run identifier.
	 * @param string            $url          Normalized URL.
	 * @param Extraction_Result $result     Extraction result from Navigation_Extractor + Content_Summary_Extractor.
	 * @return int Updated row id; 0 on failure.
	 */
	public function record_extraction( string $crawl_run_id, string $url, Extraction_Result $result ): int {
		$overrides = array(
			Crawl_Snapshot_Payload_Builder::PAGE_SUMMARY_DATA => $result->to_summary_data_json(),
		);
		$title     = $result->page_summary['title'] ?? '';
		if ( $title !== '' ) {
			$overrides[ Crawl_Snapshot_Payload_Builder::PAGE_TITLE_SNAPSHOT ] = $title;
		}
		$meta = $result->page_summary['meta_description'] ?? '';
		if ( $meta !== '' ) {
			$overrides[ Crawl_Snapshot_Payload_Builder::PAGE_META_SNAPSHOT ] = $meta;
		}
		return $this->store_page_record( $crawl_run_id, $url, $overrides );
	}

	/**
	 * Enriches a page snapshot with advisory template-family matching hints (Prompt 209).
	 * Loads the page record, runs the matcher, persists the result into hierarchy_clues (merged with existing if present).
	 *
	 * @param string $crawl_run_id Crawl run identifier.
	 * @param string $url          Normalized URL.
	 * @return int Updated row id; 0 if page not found or save failed.
	 */
	public function enrich_page_with_template_hint( string $crawl_run_id, string $url ): int {
		$page = $this->repository->get_by_run_and_url( $crawl_run_id, $url );
		if ( $page === null ) {
			return 0;
		}
		$match_result  = $this->template_family_matcher->match( $page );
		$existing_json = isset( $page['hierarchy_clues'] ) && $page['hierarchy_clues'] !== null && (string) $page['hierarchy_clues'] !== ''
			? (string) $page['hierarchy_clues']
			: null;
		$merged        = $this->merge_hierarchy_clues_with_hint( $existing_json, $match_result );
		$overrides     = array(
			Crawl_Snapshot_Payload_Builder::PAGE_HIERARCHY_CLUES => $merged,
		);
		return $this->store_page_record( $crawl_run_id, $url, $overrides );
	}

	/**
	 * Merges template-family hint payload into existing hierarchy_clues JSON, or returns new JSON.
	 *
	 * @param string|null                 $existing_json Current hierarchy_clues value.
	 * @param Crawl_Template_Match_Result $match_result  Matcher result to persist.
	 * @return string JSON string for hierarchy_clues column.
	 */
	private function merge_hierarchy_clues_with_hint( ?string $existing_json, Crawl_Template_Match_Result $match_result ): string {
		$hint_payload = $match_result->to_payload();
		if ( $existing_json === null || trim( $existing_json ) === '' ) {
			$json = \wp_json_encode( $hint_payload );
			return is_string( $json ) ? $json : '{}';
		}
		$decoded = json_decode( $existing_json, true );
		if ( ! is_array( $decoded ) ) {
			$json = \wp_json_encode( $hint_payload );
			return is_string( $json ) ? $json : '{}';
		}
		foreach ( $hint_payload as $key => $value ) {
			$decoded[ $key ] = $value;
		}
		$json = \wp_json_encode( $decoded );
		return is_string( $json ) ? $json : '{}';
	}

	/**
	 * Returns a single page record by crawl run and URL.
	 *
	 * @param string $crawl_run_id Crawl run identifier.
	 * @param string $url          Normalized URL.
	 * @return array<string, mixed>|null Page record.
	 */
	public function get_page_by_run_and_url( string $crawl_run_id, string $url ): ?array {
		return $this->repository->get_by_run_and_url( $crawl_run_id, $url );
	}

	/**
	 * Lists page records for a crawl run.
	 *
	 * @param string      $crawl_run_id Crawl run identifier.
	 * @param string|null $status     Optional filter by crawl_status.
	 * @param int         $limit       Max rows (0 = no limit).
	 * @param int         $offset      Offset.
	 * @return list<array<string, mixed>>
	 */
	public function list_pages_by_run( string $crawl_run_id, ?string $status = null, int $limit = 0, int $offset = 0 ): array {
		return $this->repository->list_by_run_id( $crawl_run_id, $status, $limit, $offset );
	}

	/**
	 * Lists page records by crawl_status across all runs.
	 *
	 * @param string $status crawl_status value (pending, completed, error).
	 * @param int    $limit  Max rows (0 = no limit).
	 * @param int    $offset Offset.
	 * @return list<array<string, mixed>>
	 */
	public function list_pages_by_status( string $status, int $limit = 0, int $offset = 0 ): array {
		return $this->repository->list_by_status( $status, $limit, $offset );
	}

	/**
	 * Returns current schema version for snapshot records (spec §58.4).
	 *
	 * @return string
	 */
	public static function get_schema_version(): string {
		return Crawl_Snapshot_Payload_Builder::SCHEMA_VERSION;
	}

	private function generate_run_id(): string {
		$raw = \wp_generate_uuid4();
		return \sanitize_text_field( substr( $raw, 0, self::RUN_ID_OPTION_MAX ) );
	}

	private function session_option_key( string $crawl_run_id ): string {
		$id = \sanitize_text_field( substr( $crawl_run_id, 0, self::RUN_ID_OPTION_MAX ) );
		if ( $id === '' ) {
			return '';
		}
		return self::SESSION_OPTION_PREFIX . $id;
	}

	private function iso8601_now(): string {
		return \gmdate( 'Y-m-d\TH:i:s\Z' );
	}
}
