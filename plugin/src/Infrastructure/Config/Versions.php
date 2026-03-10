<?php
/**
 * Version map for plugin, schemas, tables, registries, and exports.
 * Single source of truth for contract versions; migrations and schemas depend on these keys.
 * Initial values are placeholders and will be advanced by later prompts.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes version identifiers for upgrade detection, migration control, and schema compatibility.
 * Machine-readable and immutable at runtime. Future prompts may append domain-specific keys
 * but must not rename the root keys defined here (spec §58.4, §58.5).
 */
final class Versions {

	/**
	 * Stable version map keys. Do not rename; append only for new domains.
	 *
	 * - plugin: Plugin release version (synced with Constants::plugin_version()).
	 * - global_schema: Global schema contract version for compatibility checks.
	 * - table_schema: Custom table schema version for migrations.
	 * - registry_schema: Section/page template registry schema version.
	 * - export_schema: Export manifest and backup schema version.
	 *
	 * @var array<string, string>
	 */
	private static array $map = array();

	/**
	 * Returns the full version map. Keys are stable; values are placeholders until
	 * migrations and registries are implemented.
	 *
	 * @return array<string, string> Associative array of version key => version string.
	 */
	public static function all(): array {
		if ( self::$map !== array() ) {
			return self::$map;
		}
		self::$map = array(
			'plugin'         => \AIOPageBuilder\Bootstrap\Constants::plugin_version(),
			'global_schema'  => '1',  // Initial placeholder; advance when global schema contract is set.
			'table_schema'   => '1',  // Initial placeholder; advance when custom tables are introduced.
			'registry_schema' => '1',  // Initial placeholder; advance when registry format is locked.
			'export_schema'  => '1',  // Initial placeholder; advance when export manifest is defined.
		);
		return self::$map;
	}

	/** @return string Plugin version. */
	public static function plugin(): string {
		$all = self::all();
		return $all['plugin'];
	}

	/** @return string Global schema version. */
	public static function global_schema(): string {
		$all = self::all();
		return $all['global_schema'];
	}

	/** @return string Table schema version. */
	public static function table_schema(): string {
		$all = self::all();
		return $all['table_schema'];
	}

	/** @return string Registry schema version. */
	public static function registry_schema(): string {
		$all = self::all();
		return $all['registry_schema'];
	}

	/** @return string Export schema version. */
	public static function export_schema(): string {
		$all = self::all();
		return $all['export_schema'];
	}

	/**
	 * Returns the list of version keys used in the stored version map (migration-contract.md).
	 * Do not rename; append only for new domains.
	 *
	 * @return list<string>
	 */
	public static function version_keys(): array {
		return array( 'plugin', 'global_schema', 'table_schema', 'registry_schema', 'export_schema' );
	}
}
