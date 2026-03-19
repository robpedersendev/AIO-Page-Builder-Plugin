<?php
/**
 * Data access for crawl snapshot page records (spec §11.1, §24.15). Backing: table aio_crawl_snapshots.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Snapshots;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Repositories\Abstract_Table_Repository;
use AIOPageBuilder\Domain\Storage\Tables\Table_Names;

/**
 * Repository for crawl snapshot table. Identity per record is (crawl_run_id, url).
 * get_by_key accepts composite key: "crawl_run_id\nurl" (newline is invalid in URL).
 */
final class Crawl_Snapshot_Repository extends Abstract_Table_Repository {

	private const COMPOSITE_KEY_DELIMITER = "\n";

	/** @inheritdoc */
	protected function get_table_suffix(): string {
		return Table_Names::CRAWL_SNAPSHOTS;
	}

	/** @inheritdoc */
	protected function get_key_column(): string {
		return 'crawl_run_id';
	}

	/**
	 * Returns a single page record by crawl run and URL.
	 *
	 * @param string $crawl_run_id Crawl run identifier.
	 * @param string $url          Normalized URL.
	 * @return array<string, mixed>|null Row as record or null if not found.
	 */
	public function get_by_run_and_url( string $crawl_run_id, string $url ): ?array {
		$run_id = $this->sanitize_run_id( $crawl_run_id );
		$url    = $this->sanitize_url( $url );
		if ( $run_id === '' || $url === '' ) {
			return null;
		}
		$table    = $this->get_table_name();
		$prepared = $this->wpdb->prepare(
			"SELECT * FROM `{$table}` WHERE `crawl_run_id` = %s AND `url` = %s LIMIT 1",
			$run_id,
			$url
		);
		$row      = $this->wpdb->get_row( $prepared );
		if ( $row === null ) {
			return null;
		}
		return $this->row_to_record( $row );
	}

	/** @inheritdoc */
	public function get_by_key( string $key ): ?array {
		$parts = explode( self::COMPOSITE_KEY_DELIMITER, $key, 2 );
		if ( count( $parts ) === 2 ) {
			return $this->get_by_run_and_url( $parts[0], $parts[1] );
		}
		return null;
	}

	/**
	 * Lists page records for a crawl run, optionally by status.
	 *
	 * @param string      $crawl_run_id Crawl run identifier.
	 * @param string|null $status       Optional filter by crawl_status.
	 * @param int         $limit         Max rows (0 = no limit).
	 * @param int         $offset        Offset.
	 * @return array<int, array<string, mixed>>
	 */
	public function list_by_run_id( string $crawl_run_id, ?string $status = null, int $limit = 0, int $offset = 0 ): array {
		$run_id = $this->sanitize_run_id( $crawl_run_id );
		if ( $run_id === '' ) {
			return array();
		}
		$table = $this->get_table_name();
		$sql   = "SELECT * FROM `{$table}` WHERE `crawl_run_id` = %s";
		$args  = array( $run_id );
		if ( $status !== null && $status !== '' ) {
			$sql   .= ' AND `crawl_status` = %s';
			$args[] = $status;
		}
		$sql .= ' ORDER BY `id` ASC';
		if ( $limit > 0 ) {
			$sql   .= ' LIMIT %d';
			$args[] = $limit;
		}
		if ( $offset > 0 ) {
			$sql   .= ' OFFSET %d';
			$args[] = $offset;
		}
		$prepared = $this->wpdb->prepare( $sql, ...$args );
		$rows     = $this->wpdb->get_results( $prepared );
		if ( ! is_array( $rows ) ) {
			return array();
		}
		$out = array();
		foreach ( $rows as $row ) {
			if ( is_object( $row ) ) {
				$out[] = $this->row_to_record( $row );
			}
		}
		return $out;
	}

	/**
	 * Lists distinct crawl run ids from the snapshot table (most recent first).
	 *
	 * @param int $limit Max number of run ids (0 = no limit).
	 * @return array<int, string>
	 */
	public function list_crawl_run_ids( int $limit = 50 ): array {
		$table = $this->get_table_name();
		$sql   = "SELECT crawl_run_id FROM `{$table}` GROUP BY crawl_run_id ORDER BY MAX(id) DESC";
		if ( $limit > 0 ) {
			$sql     .= ' LIMIT %d';
			$prepared = $this->wpdb->prepare( $sql, $limit );
		} else {
			$prepared = $sql;
		}
		$col = $this->wpdb->get_col( $prepared );
		if ( ! is_array( $col ) ) {
			return array();
		}
		return array_values( array_filter( array_map( 'strval', $col ) ) );
	}

	/** @inheritdoc */
	public function list_by_status( string $status, int $limit = 0, int $offset = 0 ): array {
		$table = $this->get_table_name();
		$sql   = "SELECT * FROM `{$table}` WHERE `crawl_status` = %s ORDER BY `crawled_at` DESC, `id` ASC";
		$args  = array( $status );
		if ( $limit > 0 ) {
			$sql   .= ' LIMIT %d';
			$args[] = $limit;
		}
		if ( $offset > 0 ) {
			$sql   .= ' OFFSET %d';
			$args[] = $offset;
		}
		$prepared = $this->wpdb->prepare( $sql, ...$args );
		$rows     = $this->wpdb->get_results( $prepared );
		if ( ! is_array( $rows ) ) {
			return array();
		}
		$out = array();
		foreach ( $rows as $row ) {
			if ( is_object( $row ) ) {
				$out[] = $this->row_to_record( $row );
			}
		}
		return $out;
	}

	/**
	 * Persists a page snapshot record. Uses payload built by Crawl_Snapshot_Payload_Builder.
	 * On duplicate (crawl_run_id, url) updates the existing row.
	 *
	 * @param array<string, mixed> $data Normalized page payload (must include crawl_run_id, url).
	 * @return int Inserted or updated row id; 0 on failure.
	 */
	public function save( array $data ): int {
		$payload = Crawl_Snapshot_Payload_Builder::build_page_payload(
			(string) ( $data[ Crawl_Snapshot_Payload_Builder::PAGE_CRAWL_RUN_ID ] ?? '' ),
			(string) ( $data[ Crawl_Snapshot_Payload_Builder::PAGE_URL ] ?? '' ),
			$data
		);
		if ( empty( $payload ) ) {
			return 0;
		}
		$table = $this->get_table_name();
		$id    = (int) ( $data['id'] ?? 0 );
		if ( $id > 0 ) {
			$updated = $this->update_row( $id, $payload );
			return $updated ? $id : 0;
		}
		return $this->insert_row( $payload );
	}

	/** @inheritdoc */
	public function exists( $key_or_id ): bool {
		if ( is_int( $key_or_id ) ) {
			return $this->get_by_id( $key_or_id ) !== null;
		}
		return $this->get_by_key( (string) $key_or_id ) !== null;
	}

	/**
	 * Inserts one row. Payload must already be normalized and include schema_version.
	 *
	 * @param array<string, mixed> $payload
	 * @return int Inserted row id or 0 on failure.
	 */
	private function insert_row( array $payload ): int {
		$table           = $this->get_table_name();
		$values          = array(
			$payload[ Crawl_Snapshot_Payload_Builder::PAGE_CRAWL_RUN_ID ],
			$payload[ Crawl_Snapshot_Payload_Builder::PAGE_URL ],
			$payload[ Crawl_Snapshot_Payload_Builder::PAGE_CANONICAL_URL ],
			$payload[ Crawl_Snapshot_Payload_Builder::PAGE_TITLE_SNAPSHOT ],
			$payload[ Crawl_Snapshot_Payload_Builder::PAGE_META_SNAPSHOT ],
			$payload[ Crawl_Snapshot_Payload_Builder::PAGE_INDEXABILITY_FLAGS ],
			$payload[ Crawl_Snapshot_Payload_Builder::PAGE_CLASSIFICATION ],
			$payload[ Crawl_Snapshot_Payload_Builder::PAGE_HIERARCHY_CLUES ],
			(int) ( $payload[ Crawl_Snapshot_Payload_Builder::PAGE_NAVIGATION ] ?? 0 ),
			$payload[ Crawl_Snapshot_Payload_Builder::PAGE_SUMMARY_DATA ],
			$payload[ Crawl_Snapshot_Payload_Builder::PAGE_CONTENT_HASH ],
			$payload[ Crawl_Snapshot_Payload_Builder::PAGE_CRAWL_STATUS ],
			$payload[ Crawl_Snapshot_Payload_Builder::PAGE_ERROR_STATE ],
			$payload[ Crawl_Snapshot_Payload_Builder::PAGE_CRAWLED_AT ],
			$payload[ Crawl_Snapshot_Payload_Builder::PAGE_SCHEMA_VERSION ],
		);
		$col_list        = '`crawl_run_id`,`url`,`canonical_url`,`title_snapshot`,`meta_snapshot`,`indexability_flags`,`page_classification`,`hierarchy_clues`,`navigation_participation`,`summary_data`,`content_hash`,`crawl_status`,`error_state`,`crawled_at`,`schema_version`';
		$placeholders    = implode( ', ', array_fill( 0, count( $values ), '%s' ) );
		$prepared_values = $this->cast_values_for_prepare( $values );
		$result          = $this->wpdb->query(
			$this->wpdb->prepare(
				"INSERT INTO `{$table}` ({$col_list}) VALUES ({$placeholders})",
				...$prepared_values
			)
		);
		if ( $result !== 1 ) {
			return 0;
		}
		return (int) $this->wpdb->insert_id;
	}

	/**
	 * Updates an existing row by id.
	 *
	 * @param int                  $id      Row id.
	 * @param array<string, mixed> $payload Normalized payload (same keys as insert).
	 * @return bool True if one row updated.
	 */
	private function update_row( int $id, array $payload ): bool {
		$table  = $this->get_table_name();
		$set    = array(
			'canonical_url'            => $payload[ Crawl_Snapshot_Payload_Builder::PAGE_CANONICAL_URL ],
			'title_snapshot'           => $payload[ Crawl_Snapshot_Payload_Builder::PAGE_TITLE_SNAPSHOT ],
			'meta_snapshot'            => $payload[ Crawl_Snapshot_Payload_Builder::PAGE_META_SNAPSHOT ],
			'indexability_flags'       => $payload[ Crawl_Snapshot_Payload_Builder::PAGE_INDEXABILITY_FLAGS ],
			'page_classification'      => $payload[ Crawl_Snapshot_Payload_Builder::PAGE_CLASSIFICATION ],
			'hierarchy_clues'          => $payload[ Crawl_Snapshot_Payload_Builder::PAGE_HIERARCHY_CLUES ],
			'navigation_participation' => (int) ( $payload[ Crawl_Snapshot_Payload_Builder::PAGE_NAVIGATION ] ?? 0 ),
			'summary_data'             => $payload[ Crawl_Snapshot_Payload_Builder::PAGE_SUMMARY_DATA ],
			'content_hash'             => $payload[ Crawl_Snapshot_Payload_Builder::PAGE_CONTENT_HASH ],
			'crawl_status'             => $payload[ Crawl_Snapshot_Payload_Builder::PAGE_CRAWL_STATUS ],
			'error_state'              => $payload[ Crawl_Snapshot_Payload_Builder::PAGE_ERROR_STATE ],
			'crawled_at'               => $payload[ Crawl_Snapshot_Payload_Builder::PAGE_CRAWLED_AT ],
			'schema_version'           => $payload[ Crawl_Snapshot_Payload_Builder::PAGE_SCHEMA_VERSION ],
		);
		$format = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );
		$result = $this->wpdb->update( $table, $set, array( 'id' => $id ), $format, array( '%d' ) );
		return $result === 1;
	}

	/**
	 * Casts values for wpdb::prepare (all %s for simplicity; wpdb accepts int as %s).
	 *
	 * @param array<int, mixed> $values
	 * @return array<int, string|int>
	 */
	private function cast_values_for_prepare( array $values ): array {
		$out = array();
		foreach ( $values as $v ) {
			$out[] = $v === null ? '' : ( is_int( $v ) ? $v : (string) $v );
		}
		return $out;
	}

	private function sanitize_run_id( string $id ): string {
		return \sanitize_text_field( substr( $id, 0, 64 ) );
	}

	private function sanitize_url( string $url ): string {
		$u = \esc_url_raw( $url );
		return $u !== false ? substr( $u, 0, 2048 ) : '';
	}
}
