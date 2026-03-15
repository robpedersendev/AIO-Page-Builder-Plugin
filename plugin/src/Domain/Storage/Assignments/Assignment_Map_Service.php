<?php
/**
 * Service for normalized assignment map rows (spec §11.7, custom-table-manifest §3.7). CRUD and query only; no business policy.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Assignments;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Tables\Table_Names;

/**
 * Assignment map table access. Callers must be authorized. All identifiers sanitized; prepared statements for SQL.
 */
final class Assignment_Map_Service implements Assignment_Map_Service_Interface {

	private const SCHEMA_VERSION = '1';

	/** @var \wpdb|object */
	private $wpdb;

	private string $table;

	public function __construct( $wpdb ) {
		$this->wpdb  = $wpdb;
		$this->table = Table_Names::full_name( $wpdb, Table_Names::ASSIGNMENT_MAPS );
	}

	/**
	 * Inserts a new assignment row. map_type must be valid per Assignment_Types.
	 *
	 * @param string               $map_type   One of Assignment_Types constants.
	 * @param string               $source_ref Source identifier (e.g. page id, plan id).
	 * @param string               $target_ref Target identifier (e.g. template key, object ref).
	 * @param string               $scope_ref  Optional scope (e.g. composition id); empty string stored as NULL.
	 * @param string|null          $payload    Optional JSON or text; no secrets.
	 * @return int Inserted row id, or 0 on failure.
	 */
	public function create( string $map_type, string $source_ref, string $target_ref, string $scope_ref = '', ?string $payload = null ): int {
		$map_type   = $this->sanitize_map_type( $map_type );
		$source_ref = $this->sanitize_ref( $source_ref );
		$target_ref = $this->sanitize_ref( $target_ref );
		$scope_ref  = $this->sanitize_ref( $scope_ref );
		if ( $map_type === '' || $source_ref === '' || $target_ref === '' ) {
			return 0;
		}
		$scope_val  = $scope_ref !== '' ? $scope_ref : null;
		$payload_val = $payload !== null && $payload !== '' ? $payload : null;
		$result = $this->wpdb->query(
			$this->wpdb->prepare(
				"INSERT INTO `{$this->table}` (`map_type`,`source_ref`,`target_ref`,`scope_ref`,`payload`,`schema_version`) VALUES (%s,%s,%s,%s,%s,%s)",
				$map_type,
				$source_ref,
				$target_ref,
				$scope_val,
				$payload_val,
				self::SCHEMA_VERSION
			)
		);
		if ( $result !== 1 ) {
			return 0;
		}
		return (int) $this->wpdb->insert_id;
	}

	/**
	 * Updates an existing row by id. Only non-null arguments are updated.
	 *
	 * @param int      $id         Row id.
	 * @param string|null $map_type   New map_type if provided.
	 * @param string|null $source_ref New source_ref if provided.
	 * @param string|null $target_ref New target_ref if provided.
	 * @param string|null $scope_ref  New scope_ref if provided (empty string clears).
	 * @param string|null $payload   New payload if provided (empty string clears).
	 * @return bool True if one row updated.
	 */
	public function update( int $id, ?string $map_type = null, ?string $source_ref = null, ?string $target_ref = null, ?string $scope_ref = null, ?string $payload = null ): bool {
		if ( $id <= 0 ) {
			return false;
		}
		$set    = array( 'schema_version' => self::SCHEMA_VERSION );
		$formats = array( '%s' );
		if ( $map_type !== null ) {
			$m = $this->sanitize_map_type( $map_type );
			if ( $m === '' ) {
				return false;
			}
			$set['map_type'] = $m;
			$formats[] = '%s';
		}
		if ( $source_ref !== null ) {
			$set['source_ref'] = $this->sanitize_ref( $source_ref );
			$formats[] = '%s';
		}
		if ( $target_ref !== null ) {
			$set['target_ref'] = $this->sanitize_ref( $target_ref );
			$formats[] = '%s';
		}
		if ( $scope_ref !== null ) {
			$set['scope_ref'] = $scope_ref === '' ? null : $this->sanitize_ref( $scope_ref );
			$formats[] = '%s';
		}
		if ( $payload !== null ) {
			$set['payload'] = $payload === '' ? null : $payload;
			$formats[] = '%s';
		}
		$result = $this->wpdb->update( $this->table, $set, array( 'id' => $id ), $formats, array( '%d' ) );
		return $result === 1;
	}

	/**
	 * Deletes a row by id.
	 *
	 * @param int $id Row id.
	 * @return bool True if one row deleted.
	 */
	public function delete( int $id ): bool {
		if ( $id <= 0 ) {
			return false;
		}
		$result = $this->wpdb->delete( $this->table, array( 'id' => $id ), array( '%d' ) );
		return $result === 1;
	}

	/**
	 * Fetches one row by id.
	 *
	 * @param int $id Row id.
	 * @return array<string, mixed>|null Row as associative array or null.
	 */
	public function get_by_id( int $id ): ?array {
		if ( $id <= 0 ) {
			return null;
		}
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM `{$this->table}` WHERE id = %d LIMIT 1", $id ),
			\ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Lists rows by map_type.
	 *
	 * @param string $map_type One of Assignment_Types constants.
	 * @param int    $limit    Max rows (0 = no limit cap; service caps at 500).
	 * @param int    $offset   Offset.
	 * @return list<array<string, mixed>>
	 */
	public function list_by_type( string $map_type, int $limit = 0, int $offset = 0 ): array {
		$map_type = $this->sanitize_map_type( $map_type );
		if ( $map_type === '' ) {
			return array();
		}
		$limit  = $limit > 0 ? min( 500, $limit ) : 500;
		$offset = max( 0, $offset );
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM `{$this->table}` WHERE map_type = %s ORDER BY id ASC LIMIT %d OFFSET %d",
				$map_type,
				$limit,
				$offset
			),
			\ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Lists rows by map_type and source_ref.
	 *
	 * @param string $map_type   One of Assignment_Types constants.
	 * @param string $source_ref Source identifier.
	 * @param int    $limit     Max rows (0 = 100).
	 * @return list<array<string, mixed>>
	 */
	public function list_by_source( string $map_type, string $source_ref, int $limit = 0 ): array {
		$map_type   = $this->sanitize_map_type( $map_type );
		$source_ref = $this->sanitize_ref( $source_ref );
		if ( $map_type === '' || $source_ref === '' ) {
			return array();
		}
		$limit = $limit > 0 ? min( 500, $limit ) : 100;
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM `{$this->table}` WHERE map_type = %s AND source_ref = %s ORDER BY id ASC LIMIT %d",
				$map_type,
				$source_ref,
				$limit
			),
			\ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Lists target_ref values only by map_type and source_ref (read-path optimization; Prompt 295).
	 * Fetches minimal column data for visible-group resolution without full row load.
	 *
	 * @param string $map_type   One of Assignment_Types constants.
	 * @param string $source_ref Source identifier (e.g. page id).
	 * @param int    $limit     Max rows (0 = 500).
	 * @return list<string>
	 */
	public function list_target_refs_by_source( string $map_type, string $source_ref, int $limit = 0 ): array {
		$map_type   = $this->sanitize_map_type( $map_type );
		$source_ref = $this->sanitize_ref( $source_ref );
		if ( $map_type === '' || $source_ref === '' ) {
			return array();
		}
		$limit = $limit > 0 ? min( 500, $limit ) : 500;
		$rows = $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT target_ref FROM `{$this->table}` WHERE map_type = %s AND source_ref = %s ORDER BY id ASC LIMIT %d",
				$map_type,
				$source_ref,
				$limit
			)
		);
		if ( ! is_array( $rows ) ) {
			return array();
		}
		$out = array();
		foreach ( $rows as $ref ) {
			if ( is_string( $ref ) && $ref !== '' ) {
				$out[] = $ref;
			}
		}
		return $out;
	}

	/**
	 * Returns the first target_ref for a source_ref and map_type, or null.
	 *
	 * @param string $map_type   One of Assignment_Types constants.
	 * @param string $source_ref Source identifier (e.g. page id).
	 * @return string|null
	 */
	public function get_target_for_source( string $map_type, string $source_ref ): ?string {
		$rows = $this->list_by_source( $map_type, $source_ref, 1 );
		if ( empty( $rows ) ) {
			return null;
		}
		$target = $rows[0]['target_ref'] ?? null;
		return $target !== null && $target !== '' ? (string) $target : null;
	}

	/**
	 * Deletes all rows matching map_type and source_ref.
	 *
	 * @param string $map_type   One of Assignment_Types constants.
	 * @param string $source_ref Source identifier.
	 * @return int Number of rows deleted.
	 */
	public function delete_by_source_and_type( string $map_type, string $source_ref ): int {
		$map_type   = $this->sanitize_map_type( $map_type );
		$source_ref = $this->sanitize_ref( $source_ref );
		if ( $map_type === '' || $source_ref === '' ) {
			return 0;
		}
		$result = $this->wpdb->delete(
			$this->table,
			array( 'map_type' => $map_type, 'source_ref' => $source_ref ),
			array( '%s', '%s' )
		);
		return is_int( $result ) ? $result : 0;
	}

	/**
	 * Lists rows by map_type and target_ref.
	 *
	 * @param string $map_type   One of Assignment_Types constants.
	 * @param string $target_ref Target identifier.
	 * @param int    $limit     Max rows (0 = 100).
	 * @return list<array<string, mixed>>
	 */
	public function list_by_target( string $map_type, string $target_ref, int $limit = 0 ): array {
		$map_type   = $this->sanitize_map_type( $map_type );
		$target_ref = $this->sanitize_ref( $target_ref );
		if ( $map_type === '' || $target_ref === '' ) {
			return array();
		}
		$limit = $limit > 0 ? min( 500, $limit ) : 100;
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM `{$this->table}` WHERE map_type = %s AND target_ref = %s ORDER BY id ASC LIMIT %d",
				$map_type,
				$target_ref,
				$limit
			),
			\ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	private function sanitize_map_type( string $map_type ): string {
		$map_type = \sanitize_text_field( $map_type );
		return Assignment_Types::is_valid( $map_type ) ? $map_type : '';
	}

	private function sanitize_ref( string $ref ): string {
		$ref = \sanitize_text_field( $ref );
		return substr( $ref, 0, 64 );
	}
}
