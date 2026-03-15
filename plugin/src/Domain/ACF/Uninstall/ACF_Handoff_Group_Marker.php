<?php
/**
 * Marker for ACF field groups handed off from AIO Page Builder (acf-native-handoff-contract).
 * Additive metadata to identify handed-off groups; does not alter field names or values.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Uninstall;

defined( 'ABSPATH' ) || exit;

/**
 * Identifies handed-off native ACF groups so they are not overwritten by unrelated logic
 * and can be recognized after uninstall.
 */
final class ACF_Handoff_Group_Marker {

	/** Meta key added to group array to mark origin as AIO Page Builder handoff. */
	public const HANDOFF_ORIGIN_KEY = '_aio_handoff_origin';

	/** Value stored for HANDOFF_ORIGIN_KEY when the group was materialized by this plugin. */
	public const HANDOFF_ORIGIN_VALUE = 'aio_page_builder';

	/**
	 * Marks a group array as handed off from AIO Page Builder.
	 *
	 * @param array<string, mixed> $group ACF field group array (key, title, fields, location, etc.).
	 * @return array<string, mixed> Same array with marker added.
	 */
	public static function mark( array $group ): array {
		$group[ self::HANDOFF_ORIGIN_KEY ] = self::HANDOFF_ORIGIN_VALUE;
		return $group;
	}

	/**
	 * Returns whether the group array or stored group is marked as our handoff.
	 *
	 * @param array<string, mixed> $group ACF field group array (from acf_get_field_group or build).
	 * @return bool
	 */
	public static function is_handoff_group( array $group ): bool {
		$origin = $group[ self::HANDOFF_ORIGIN_KEY ] ?? '';
		return $origin === self::HANDOFF_ORIGIN_VALUE;
	}
}
