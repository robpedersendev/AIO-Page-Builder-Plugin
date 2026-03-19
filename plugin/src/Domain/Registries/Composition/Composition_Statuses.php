<?php
/**
 * Composition lifecycle statuses and validation result values (spec §10.10, §14.7, composition-validation-state-machine.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Composition;

defined( 'ABSPATH' ) || exit;

/**
 * Lifecycle statuses for custom template composition object (object-model §3.3).
 * Validation result is stored separately (Composition_Validation_Codes / validation_status).
 */
final class Composition_Statuses {

	/** Editable; not yet activated. */
	public const DRAFT = 'draft';

	/** In use; eligible for page creation subject to validation_result. */
	public const ACTIVE = 'active';

	/** Retained but not selectable for new page creation. */
	public const ARCHIVED = 'archived';

	/** @var array<int, string> */
	private static ?array $lifecycle_statuses = null;

	/**
	 * Returns all allowed lifecycle status values.
	 *
	 * @return array<int, string>
	 */
	public static function get_lifecycle_statuses(): array {
		if ( self::$lifecycle_statuses !== null ) {
			return self::$lifecycle_statuses;
		}
		self::$lifecycle_statuses = array(
			self::DRAFT,
			self::ACTIVE,
			self::ARCHIVED,
		);
		return self::$lifecycle_statuses;
	}

	/**
	 * Returns whether the given value is a valid lifecycle status.
	 *
	 * @param string $status Status value.
	 * @return bool
	 */
	public static function is_valid_lifecycle_status( string $status ): bool {
		return in_array( $status, self::get_lifecycle_statuses(), true );
	}
}
