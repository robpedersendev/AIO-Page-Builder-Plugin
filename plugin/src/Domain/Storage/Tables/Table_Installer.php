<?php
/**
 * Installs or upgrades custom tables and records schema version (spec §11, §53.1, §58.4).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Tables;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Migrations\Schema_Version_Tracker;
use AIOPageBuilder\Infrastructure\Config\Versions;

/**
 * Deterministic installer: builds definitions from manifest, runs dbDelta for each table,
 * records table_schema version on full success. Safe re-entry and missing-table recovery.
 * No repositories or row inserts (except version recording).
 */
final class Table_Installer {

	/** @var \wpdb|object Database abstraction (prefix, prepare, get_var). */
	private $wpdb;

	/** @var DbDelta_Runner */
	private DbDelta_Runner $db_delta;

	/** @var Schema_Version_Tracker */
	private Schema_Version_Tracker $tracker;

	public function __construct( $wpdb, DbDelta_Runner $db_delta, Schema_Version_Tracker $tracker ) {
		$this->wpdb     = $wpdb;
		$this->db_delta = $db_delta;
		$this->tracker  = $tracker;
	}

	/**
	 * Creates or upgrades all manifest tables. Records table_schema version only when all succeed.
	 * Idempotent: safe to call on fresh install or re-run.
	 *
	 * @return array{success: bool, message: string, failed_table: string|null} success false when any table fails; message sanitized.
	 */
	public function install_or_upgrade(): array {
		$definitions  = Table_Schema_Definitions::get_definitions( $this->wpdb );
		$code_version = Versions::table_schema();

		foreach ( $definitions as $def ) {
			$name   = $def['name'];
			$result = $this->db_delta->run( $this->wpdb, $def['sql'] );
			if ( ! $result['success'] ) {
				return array(
					'success'      => false,
					'message'      => $result['error'] ?: 'Table creation or upgrade failed.',
					'failed_table' => $name,
				);
			}
		}

		$this->tracker->set_installed_version( 'table_schema', $code_version );
		return array(
			'success'      => true,
			'message'      => '',
			'failed_table' => null,
		);
	}

	/**
	 * Returns whether a table exists (by suffix).
	 *
	 * @param string $suffix One of Table_Names constants.
	 * @return bool
	 */
	public function table_exists( string $suffix ): bool {
		$full   = Table_Names::full_name( $this->wpdb, $suffix );
		$result = $this->wpdb->get_var( $this->wpdb->prepare( 'SHOW TABLES LIKE %s', $full ) );
		return $result === $full;
	}

	/**
	 * Returns which manifest tables are missing. Used for recovery detection.
	 *
	 * @return list<string> List of Table_Names suffixes that are missing.
	 */
	public function get_missing_tables(): array {
		$missing = array();
		foreach ( Table_Names::all() as $suffix ) {
			if ( ! $this->table_exists( $suffix ) ) {
				$missing[] = $suffix;
			}
		}
		return $missing;
	}
}
