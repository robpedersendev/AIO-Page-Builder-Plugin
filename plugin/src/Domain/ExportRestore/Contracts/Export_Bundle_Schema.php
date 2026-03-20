<?php
/**
 * Export bundle manifest schema and category constants (spec §52.2–52.6, export-bundle-structure-contract.md).
 *
 * Required manifest keys, included/optional/excluded category lists for validation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Manifest and category schema. Used for validation and bundle generation.
 */
final class Export_Bundle_Schema {

	/** Required root keys in manifest.json. */
	public const MANIFEST_REQUIRED_KEYS = array(
		'export_type',
		'export_timestamp',
		'plugin_version',
		'schema_version',
		'source_site_url',
		'included_categories',
		'excluded_categories',
		'package_checksum_list',
		'restore_notes',
		'compatibility_flags',
	);

	/**
	 * Sub-key under the profiles category that carries serialized profile snapshots (v2).
	 * Export assemblers include this key when exporting the profiles category.
	 */
	public const PROFILES_SNAPSHOT_HISTORY_KEY = 'profile_snapshot_history';

	/** Included data category keys (spec §52.4). Styling (Prompt 257): global settings and per-entity payloads. */
	public const INCLUDED_CATEGORIES = array(
		'settings',
		'styling',
		'profiles',
		'registries',
		'compositions',
		'plans',
		'token_sets',
		'uninstall_restore_metadata',
	);

	/** Optional data category keys (spec §52.5). */
	public const OPTIONAL_CATEGORIES = array(
		'raw_ai_artifacts',
		'normalized_ai_outputs',
		'crawl_snapshots',
		'logs',
		'reporting_history',
		'rollback_snapshots',
		'acf_field_groups_mirror',
	);

	/** Permanently excluded; must never appear in export (spec §52.6). */
	public const EXCLUDED_CATEGORIES = array(
		'api_keys',
		'passwords',
		'auth_session_tokens',
		'runtime_lock_rows',
		'temporary_cache',
		'corrupted_remnants',
	);

	/** ZIP root directory names (spec §52.2). */
	public const ZIP_ROOT_DIRS = array(
		'settings',
		'styling',
		'profiles',
		'registries',
		'compositions',
		'plans',
		'tokens',
		'artifacts',
		'logs',
		'docs',
		'acf_field_groups_mirror',
	);

	/**
	 * Checks that manifest array has all required root keys.
	 *
	 * @param array<string, mixed> $manifest Decoded manifest.
	 * @return bool
	 */
	public static function manifest_has_required_keys( array $manifest ): bool {
		foreach ( self::MANIFEST_REQUIRED_KEYS as $key ) {
			if ( ! array_key_exists( $key, $manifest ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns whether the category is a valid included category.
	 *
	 * @param string $category Category key.
	 * @return bool
	 */
	public static function is_included_category( string $category ): bool {
		return in_array( $category, self::INCLUDED_CATEGORIES, true );
	}

	/**
	 * Returns whether the category is a valid optional category.
	 *
	 * @param string $category Category key.
	 * @return bool
	 */
	public static function is_optional_category( string $category ): bool {
		return in_array( $category, self::OPTIONAL_CATEGORIES, true );
	}

	/**
	 * Returns whether the category is in the permanently excluded list.
	 *
	 * @param string $category Category key.
	 * @return bool
	 */
	public static function is_excluded_category( string $category ): bool {
		return in_array( $category, self::EXCLUDED_CATEGORIES, true );
	}

	/**
	 * Returns whether the category is allowed in export (included or optional).
	 *
	 * @param string $category Category key.
	 * @return bool
	 */
	public static function is_allowed_category( string $category ): bool {
		return self::is_included_category( $category ) || self::is_optional_category( $category );
	}
}
