<?php
/**
 * Reads token set rows from custom table for export (spec §52.4). Export-safe only; no secrets.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Export;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Export\Registry_Export_Fragment_Builder;
use AIOPageBuilder\Domain\Storage\Tables\Table_Names;

/**
 * Lists token set records for export. value_payload is sanitized (spec §52.6).
 */
final class Export_Token_Set_Reader {

	/** @var \wpdb */
	private \wpdb $wpdb;

	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Returns all token set rows for export (export-safe columns; value_payload sanitized).
	 *
	 * @param int $limit Max rows (0 = no limit).
	 * @return array<int, array<string, mixed>>
	 */
	public function list_for_export( int $limit = 0 ): array {
		$table = $this->wpdb->prefix . Table_Names::TOKEN_SETS;
		if ( $this->wpdb->get_var( $this->wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return array();
		}
		$sql = "SELECT id, token_set_ref, source_type, state, plan_ref, scope_ref, value_payload, schema_version, created_at, applied_at, acceptance_status FROM `{$table}` ORDER BY created_at ASC";
		if ( $limit > 0 ) {
			$sql .= $this->wpdb->prepare( ' LIMIT %d', $limit );
		}
		$rows = $this->wpdb->get_results( $sql, ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return array();
		}
		$out = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			if ( isset( $row['value_payload'] ) && is_string( $row['value_payload'] ) ) {
				$decoded              = json_decode( $row['value_payload'], true );
				$row['value_payload'] = is_array( $decoded ) ? Registry_Export_Fragment_Builder::sanitize_payload( $decoded ) : array();
			}
			$out[] = $row;
		}
		return $out;
	}
}
