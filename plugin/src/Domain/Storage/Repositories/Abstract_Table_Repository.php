<?php
/**
 * Base for table-backed repositories. Uses prepared queries only (spec §9.5, §11).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Tables\Table_Names;

/**
 * Operational table access. Subclasses define table suffix and key column.
 * No permission checks; callers must be authorized.
 */
abstract class Abstract_Table_Repository implements Repository_Interface {

	/** @var \wpdb|object */
	protected $wpdb;

	public function __construct( $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Returns the table suffix (Table_Names constant).
	 */
	abstract protected function get_table_suffix(): string;

	/**
	 * Returns the column name used as stable ref for get_by_key (e.g. job_ref, artifact_ref).
	 */
	abstract protected function get_key_column(): string;

	protected function get_table_name(): string {
		return Table_Names::full_name( $this->wpdb, $this->get_table_suffix() );
	}

	/**
	 * Maps a table row to a normalized record array. Subclasses may override.
	 *
	 * @param object $row Raw row from wpdb.
	 * @return array<string, mixed>
	 */
	protected function row_to_record( object $row ): array {
		return (array) $row;
	}

	/** @inheritdoc */
	public function get_by_id( int $id ): ?array {
		$table    = $this->get_table_name();
		$prepared = $this->wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d LIMIT 1", $id );
		$row      = $this->wpdb->get_row( $prepared );
		if ( $row === null ) {
			return null;
		}
		return $this->row_to_record( $row );
	}

	/** @inheritdoc */
	public function get_by_key( string $key ): ?array {
		$key = $this->sanitize_key( $key );
		if ( $key === '' ) {
			return null;
		}
		$table    = $this->get_table_name();
		$col      = $this->get_key_column();
		$prepared = $this->wpdb->prepare( "SELECT * FROM `{$table}` WHERE `{$col}` = %s LIMIT 1", $key );
		$row      = $this->wpdb->get_row( $prepared );
		if ( $row === null ) {
			return null;
		}
		return $this->row_to_record( $row );
	}

	/** @inheritdoc */
	public function list_by_status( string $status, int $limit = 0, int $offset = 0 ): array {
		// Subclasses override when the table has a status column; default empty.
		return array();
	}

	/** @inheritdoc */
	public function save( array $data ): int {
		// Subclasses implement; default no-op (skeleton).
		return 0;
	}

	/** @inheritdoc */
	public function exists( $key_or_id ): bool {
		if ( is_int( $key_or_id ) ) {
			return $this->get_by_id( $key_or_id ) !== null;
		}
		return $this->get_by_key( (string) $key_or_id ) !== null;
	}

	protected function sanitize_key( string $key ): string {
		return \sanitize_text_field( substr( $key, 0, 64 ) );
	}
}
