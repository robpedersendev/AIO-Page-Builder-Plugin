<?php
/**
 * Canonical snapshot type keys for registry-oriented snapshots (spec §10.8, §14.8).
 * Maps to scope_type + scope_id for querying and payload structure.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Snapshots;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Snapshots\Version_Snapshot_Schema;

/**
 * Explicit snapshot subtypes within each scope. Used by capture services and payload builders.
 */
final class Snapshot_Type_Keys {

	/** Section registry state: active sections, keys, categories, compatibility. */
	public const SECTION_REGISTRY = 'section_registry';

	/** Page template registry state: templates, ordered sections, archetypes. */
	public const PAGE_TEMPLATE_REGISTRY = 'page_template_registry';

	/** Composition validation context: composition_id, ordered sections, validation state, source refs. */
	public const COMPOSITION_CONTEXT = 'composition_context';

	/** Schema definition snapshot (section schema, page template schema, table manifest). */
	public const SCHEMA_SECTION_REGISTRY = 'schema_section_registry';

	/** Compatibility rules state at a point in time. */
	public const COMPATIBILITY_STATE = 'compatibility_state';

	/** @var array<string, string> Snapshot type → scope_type. */
	private static ?array $type_to_scope = null;

	/**
	 * Returns scope_type for a snapshot type.
	 *
	 * @param string $snapshot_type One of the TYPE constants.
	 * @return string Version_Snapshot_Schema::SCOPE_* constant.
	 */
	public static function get_scope_type_for( string $snapshot_type ): string {
		$map = self::get_type_to_scope_map();
		return $map[ $snapshot_type ] ?? Version_Snapshot_Schema::SCOPE_REGISTRY;
	}

	/**
	 * Returns whether the snapshot type is registry-oriented (in scope for this prompt).
	 *
	 * @param string $snapshot_type
	 * @return bool
	 */
	public static function is_registry_oriented( string $snapshot_type ): bool {
		$registry_types = array(
			self::SECTION_REGISTRY,
			self::PAGE_TEMPLATE_REGISTRY,
			self::COMPOSITION_CONTEXT,
			self::SCHEMA_SECTION_REGISTRY,
			self::COMPATIBILITY_STATE,
		);
		return in_array( $snapshot_type, $registry_types, true );
	}

	/**
	 * Returns all registry-oriented snapshot types.
	 *
	 * @return list<string>
	 */
	public static function get_registry_oriented_types(): array {
		return array(
			self::SECTION_REGISTRY,
			self::PAGE_TEMPLATE_REGISTRY,
			self::COMPOSITION_CONTEXT,
			self::SCHEMA_SECTION_REGISTRY,
			self::COMPATIBILITY_STATE,
		);
	}

	/**
	 * @return array<string, string>
	 */
	private static function get_type_to_scope_map(): array {
		if ( self::$type_to_scope !== null ) {
			return self::$type_to_scope;
		}
		self::$type_to_scope = array(
			self::SECTION_REGISTRY        => Version_Snapshot_Schema::SCOPE_REGISTRY,
			self::PAGE_TEMPLATE_REGISTRY  => Version_Snapshot_Schema::SCOPE_REGISTRY,
			self::COMPOSITION_CONTEXT     => Version_Snapshot_Schema::SCOPE_REGISTRY,
			self::SCHEMA_SECTION_REGISTRY => Version_Snapshot_Schema::SCOPE_SCHEMA,
			self::COMPATIBILITY_STATE     => Version_Snapshot_Schema::SCOPE_COMPATIBILITY,
		);
		return self::$type_to_scope;
	}
}
