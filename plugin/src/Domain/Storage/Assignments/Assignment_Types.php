<?php
/**
 * Stable assignment map types for normalized mappings (spec §11.7, custom-table-manifest §3.7).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Assignments;

defined( 'ABSPATH' ) || exit;

/**
 * Canonical map_type values for aio_assignment_maps. Used by Assignment_Map_Service for validation and queries.
 * Do not rename; stored in database and referenced by export/restore.
 */
final class Assignment_Types {

	/** Page → field group (ACF field group key). */
	public const PAGE_FIELD_GROUP = 'page_field_group';

	/** Page → template (page template internal key or id). */
	public const PAGE_TEMPLATE = 'page_template';

	/** Plan → object (Build Plan to target object ref). */
	public const PLAN_OBJECT = 'plan_object';

	/** Template → dependency (template to dependency ref). */
	public const TEMPLATE_DEPENDENCY = 'template_dependency';

	/** Composition → section (composition to section template ref). */
	public const COMPOSITION_SECTION = 'composition_section';

	/** Page → composition (page post ID to composition_id). Used when page is composition-driven. */
	public const PAGE_COMPOSITION = 'page_composition';

	/** @var array<int, string>|null */
	private static ?array $all = null;

	/**
	 * Returns all allowed map_type values in stable order.
	 *
	 * @return array<int, string>
	 */
	public static function all(): array {
		if ( self::$all !== null ) {
			return self::$all;
		}
		self::$all = array(
			self::PAGE_FIELD_GROUP,
			self::PAGE_TEMPLATE,
			self::PAGE_COMPOSITION,
			self::PLAN_OBJECT,
			self::TEMPLATE_DEPENDENCY,
			self::COMPOSITION_SECTION,
		);
		return self::$all;
	}

	/**
	 * Returns whether the given string is a valid map_type.
	 *
	 * @param string $map_type Value to check.
	 * @return bool
	 */
	public static function is_valid( string $map_type ): bool {
		return in_array( $map_type, self::all(), true );
	}
}
