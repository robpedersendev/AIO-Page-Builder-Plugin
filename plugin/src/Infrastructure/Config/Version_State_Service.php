<?php
/**
 * Persists version state for upgrade checks and export manifests.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Stores version map and last migration timestamp in a dedicated option.
 */
final class Version_State_Service {

	/**
	 * Returns current version state payload (from code), suitable for persistence.
	 *
	 * @return array{plugin_version: string, global_schema_version: string, table_schema_version: string, registry_schema_version: string, export_schema_version: string, last_migrated_at: string}
	 */
	public function build_current_state(): array {
		return array(
			'plugin_version'          => Versions::plugin(),
			'global_schema_version'   => Versions::global_schema(),
			'table_schema_version'    => Versions::table_schema(),
			'registry_schema_version' => Versions::registry_schema(),
			'export_schema_version'   => Versions::export_schema(),
			'last_migrated_at'        => gmdate( 'c' ),
		);
	}

	/**
	 * Reads persisted version state.
	 *
	 * @return array<string, mixed>
	 */
	public function get_persisted_state(): array {
		$raw = \get_option( Option_Names::PB_VERSION_STATE, array() );
		return is_array( $raw ) ? $raw : array();
	}

	/**
	 * Persists the current code version state (idempotent).
	 *
	 * @return bool
	 */
	public function persist_current_state(): bool {
		$state = $this->build_current_state();
		return \update_option( Option_Names::PB_VERSION_STATE, $state, false );
	}
}
