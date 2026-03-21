<?php
/**
 * Persistence for profile snapshots (v2-scope-backlog.md §3). Table: aio_profile_snapshots.
 *
 * All reads return newest-first. brand_profile and business_profile are JSON-encoded on write
 * and decoded on hydration. Schema mismatches are silently discarded to avoid crashes.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Profile;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Tables\Table_Names;
use AIOPageBuilder\Infrastructure\Config\Versions;
use AIOPageBuilder\Infrastructure\Db\Wpdb_Prepared_Results;

/**
 * CRUD repository for Profile_Snapshot_Data objects backed by the custom profile_snapshots table.
 */
final class Profile_Snapshot_Repository implements Profile_Snapshot_Repository_Interface {

	/** @var \wpdb|object */
	private $wpdb;

	/** @param \wpdb|object $wpdb WordPress database abstraction. */
	public function __construct( $wpdb ) {
		$this->wpdb = $wpdb;
	}

	// -------------------------------------------------------------------------
	// Write operations
	// -------------------------------------------------------------------------

	/**
	 * Persists a new snapshot. Silently skips when snapshot_id already exists (duplicate prevention).
	 *
	 * @param Profile_Snapshot_Data $snapshot
	 * @return bool True when a row was inserted; false when the ID already exists or insert fails.
	 */
	public function save( Profile_Snapshot_Data $snapshot ): bool {
		if ( $this->get_by_id( $snapshot->snapshot_id ) !== null ) {
			return false;
		}
		$table  = $this->table();
		$result = $this->wpdb->insert(
			$table,
			array(
				'snapshot_id'            => $snapshot->snapshot_id,
				'scope_type'             => $snapshot->scope_type,
				'scope_id'               => $snapshot->scope_id,
				'source'                 => $snapshot->source,
				'profile_schema_version' => $snapshot->profile_schema_version,
				'brand_profile'          => \wp_json_encode( $snapshot->brand_profile ),
				'business_profile'       => \wp_json_encode( $snapshot->business_profile ),
				'created_at'             => $snapshot->created_at,
				'schema_version'         => Versions::PROFILE_SCHEMA_VERSION,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		return $result !== false && $result > 0;
	}

	/**
	 * Deletes snapshot by its string ID. No-op when not found.
	 *
	 * @param string $snapshot_id
	 * @return bool True when a row was deleted.
	 */
	public function delete( string $snapshot_id ): bool {
		$result = $this->wpdb->delete(
			$this->table(),
			array( 'snapshot_id' => $snapshot_id ),
			array( '%s' )
		);
		return $result !== false && $result > 0;
	}

	// -------------------------------------------------------------------------
	// Read operations
	// -------------------------------------------------------------------------

	/**
	 * Returns all snapshots ordered newest-first.
	 *
	 * @param int $limit  Maximum rows to return (0 = no limit).
	 * @return array<int, Profile_Snapshot_Data>
	 */
	public function get_all( int $limit = 0 ): array {
		$table = $this->table();
		$this->assert_table_identifier( $table );
		if ( $limit > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = Wpdb_Prepared_Results::get_results(
				$this->wpdb,
				'SELECT * FROM %i ORDER BY created_at DESC, id DESC LIMIT %d',
				array( $table, $limit ),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = Wpdb_Prepared_Results::get_results(
				$this->wpdb,
				'SELECT * FROM %i ORDER BY created_at DESC, id DESC',
				array( $table ),
				ARRAY_A
			);
		}
		if ( ! is_array( $rows ) ) {
			return array();
		}
		return \array_values( \array_filter( \array_map( array( $this, 'hydrate' ), $rows ) ) );
	}

	/**
	 * Returns a single snapshot by ID or null when not found.
	 *
	 * @param string $snapshot_id
	 * @return Profile_Snapshot_Data|null
	 */
	public function get_by_id( string $snapshot_id ): ?Profile_Snapshot_Data {
		$table = $this->table();
		$this->assert_table_identifier( $table );
		$sql = 'SELECT * FROM %i WHERE snapshot_id = %s LIMIT 1';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( $sql, $table, $snapshot_id ),
			ARRAY_A
		);
		if ( ! is_array( $row ) ) {
			return null;
		}
		return $this->hydrate( $row );
	}

	/**
	 * Returns the most recent snapshot (newest by created_at) or null when none exist.
	 *
	 * @return Profile_Snapshot_Data|null
	 */
	public function get_latest(): ?Profile_Snapshot_Data {
		$all = $this->get_all( 1 );
		return $all[0] ?? null;
	}

	/**
	 * Returns total count of stored snapshots.
	 *
	 * @return int
	 */
	public function count(): int {
		$table = $this->table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$n = Wpdb_Prepared_Results::get_var( $this->wpdb, 'SELECT COUNT(*) FROM %i', array( $table ) );
		return (int) $n;
	}

	// -------------------------------------------------------------------------
	// Hydration
	// -------------------------------------------------------------------------

	/**
	 * Hydrates a database row array into a Profile_Snapshot_Data. Returns null for invalid rows.
	 *
	 * @param array<string, mixed> $row
	 * @return Profile_Snapshot_Data|null
	 */
	private function hydrate( array $row ): ?Profile_Snapshot_Data {
		$snapshot_id = (string) ( $row['snapshot_id'] ?? '' );
		if ( $snapshot_id === '' ) {
			return null;
		}
		$brand_json    = is_string( $row['brand_profile'] ?? null ) ? $row['brand_profile'] : '{}';
		$business_json = is_string( $row['business_profile'] ?? null ) ? $row['business_profile'] : '{}';
		$brand         = \json_decode( $brand_json, true );
		$business      = \json_decode( $business_json, true );
		return new Profile_Snapshot_Data(
			$snapshot_id,
			(string) ( $row['scope_type'] ?? 'other' ),
			(string) ( $row['scope_id'] ?? '' ),
			(string) ( $row['created_at'] ?? '' ),
			(string) ( $row['profile_schema_version'] ?? Versions::PROFILE_SCHEMA_VERSION ),
			is_array( $brand ) ? $brand : array(),
			is_array( $business ) ? $business : array(),
			(string) ( $row['source'] ?? 'manual' )
		);
	}

	/** Returns the fully qualified table name. */
	private function table(): string {
		return $this->wpdb->prefix . Table_Names::PROFILE_SNAPSHOTS;
	}

	/**
	 * @throws \InvalidArgumentException When the table name is not a simple SQL identifier.
	 */
	private function assert_table_identifier( string $table ): void {
		if ( ! preg_match( '/^[A-Za-z0-9_]+$/', $table ) ) {
			throw new \InvalidArgumentException( 'Invalid profile snapshots table identifier.' );
		}
	}
}
