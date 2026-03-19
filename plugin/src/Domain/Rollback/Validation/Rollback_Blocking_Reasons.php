<?php
/**
 * Stable blocking reason codes for rollback eligibility (spec §38.4, §41.9).
 *
 * Machine-readable reasons when rollback is not eligible. Used by Rollback_Eligibility_Result.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Validation;

defined( 'ABSPATH' ) || exit;

/**
 * Blocking reason keys for rollback eligibility evaluation.
 */
final class Rollback_Blocking_Reasons {

	/** Pre-change snapshot not found or not loadable. */
	public const PRE_SNAPSHOT_MISSING = 'pre_snapshot_missing';

	/** Post-change snapshot not found or not loadable. */
	public const POST_SNAPSHOT_MISSING = 'post_snapshot_missing';

	/** No rollback handler registered for this action type. */
	public const NO_HANDLER_FOR_ACTION_TYPE = 'no_handler_for_action_type';

	/** Target object (page, menu, token set) no longer exists or is not resolvable. */
	public const TARGET_UNRESOLVABLE = 'target_unresolvable';

	/** Snapshot marked expired by retention policy. */
	public const SNAPSHOT_EXPIRED = 'snapshot_expired';

	/** Snapshot marked invalidated (e.g. target gone at capture time). */
	public const SNAPSHOT_INVALIDATED = 'snapshot_invalidated';

	/** Rollback was already performed for this snapshot. */
	public const SNAPSHOT_USED = 'snapshot_used';

	/** User lacks required permission to execute rollback. */
	public const PERMISSION_DENIED = 'permission_denied';

	/** A later action on the same target makes rollback unsafe without explicit override. */
	public const NEWER_CHANGE_CONFLICT = 'newer_change_conflict';

	/** Pre snapshot is not a pre_change type (invalid pairing). */
	public const PRE_SNAPSHOT_TYPE_INVALID = 'pre_snapshot_type_invalid';

	/** Post snapshot is not a post_change type (invalid pairing). */
	public const POST_SNAPSHOT_TYPE_INVALID = 'post_snapshot_type_invalid';

	/** @var array<int, string> All blocking reason codes. */
	private static ?array $all = null;

	/**
	 * Returns all blocking reason codes.
	 *
	 * @return array<int, string>
	 */
	public static function get_all(): array {
		if ( self::$all !== null ) {
			return self::$all;
		}
		self::$all = array(
			self::PRE_SNAPSHOT_MISSING,
			self::POST_SNAPSHOT_MISSING,
			self::NO_HANDLER_FOR_ACTION_TYPE,
			self::TARGET_UNRESOLVABLE,
			self::SNAPSHOT_EXPIRED,
			self::SNAPSHOT_INVALIDATED,
			self::SNAPSHOT_USED,
			self::PERMISSION_DENIED,
			self::NEWER_CHANGE_CONFLICT,
			self::PRE_SNAPSHOT_TYPE_INVALID,
			self::POST_SNAPSHOT_TYPE_INVALID,
		);
		return self::$all;
	}

	/**
	 * Returns whether the given code is a valid blocking reason.
	 *
	 * @param string $code
	 * @return bool
	 */
	public static function is_valid( string $code ): bool {
		return in_array( $code, self::get_all(), true );
	}
}
