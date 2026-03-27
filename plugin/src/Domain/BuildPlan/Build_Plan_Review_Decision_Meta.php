<?php
/**
 * Bounded review decision payload stored on plan items when status becomes rejected (spec §32.5 audit expectations).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan;

defined( 'ABSPATH' ) || exit;

/**
 * Sanitized, size-bounded metadata for deny/reject transitions (no freeform prose).
 */
final class Build_Plan_Review_Decision_Meta {

	public const SOURCE_ROW            = 'row';
	public const SOURCE_BULK_ALL       = 'bulk_all';
	public const SOURCE_BULK_SELECTED  = 'bulk_selected';

	/**
	 * Builds a stable rejection record for persistence on the plan item.
	 *
	 * @param int    $actor_user_id WordPress user ID (0 if unknown).
	 * @param string $source        One of SOURCE_*.
	 * @return array<string, mixed>
	 */
	public static function for_rejection( int $actor_user_id, string $source ): array {
		$source = in_array(
			$source,
			array( self::SOURCE_ROW, self::SOURCE_BULK_ALL, self::SOURCE_BULK_SELECTED ),
			true
		) ? $source : self::SOURCE_ROW;

		return array(
			'decision'        => 'rejected',
			'decided_at'      => gmdate( 'c' ),
			'actor_user_id'   => max( 0, $actor_user_id ),
			'source'          => $source,
		);
	}
}
