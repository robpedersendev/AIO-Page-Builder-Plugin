<?php
/**
 * Composition validation result (sub-)states (composition-validation-state-machine.md §3).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Composition;

defined( 'ABSPATH' ) || exit;

/**
 * Validation result values stored separately from lifecycle status.
 * Drives eligibility for activation and one-pager generation.
 */
final class Composition_Validation_Result {

	/** Validation not yet run or composition just created. */
	public const PENDING_VALIDATION = 'pending_validation';

	/** All checks passed; no warnings. */
	public const VALID = 'valid';

	/** One or more non-blocking warnings; no blocking failures. */
	public const WARNING = 'warning';

	/** One or more blocking failures. */
	public const VALIDATION_FAILED = 'validation_failed';

	/** References deprecated section(s); policy-defined eligibility. */
	public const DEPRECATED_CONTEXT = 'deprecated_context';

	/** @var array<int, string> */
	private static ?array $results = null;

	/**
	 * Returns all allowed validation result values.
	 *
	 * @return array<int, string>
	 */
	public static function all(): array {
		if ( self::$results !== null ) {
			return self::$results;
		}
		self::$results = array(
			self::PENDING_VALIDATION,
			self::VALID,
			self::WARNING,
			self::VALIDATION_FAILED,
			self::DEPRECATED_CONTEXT,
		);
		return self::$results;
	}

	/**
	 * Returns whether the given value is a valid validation result.
	 *
	 * @param string $result Result value.
	 * @return bool
	 */
	public static function is_valid( string $result ): bool {
		return in_array( $result, self::all(), true );
	}

	/**
	 * Returns whether this result allows activation (draft → active).
	 *
	 * @param string $result Validation result value.
	 * @return bool
	 */
	public static function allows_activation( string $result ): bool {
		return $result === self::VALID || $result === self::WARNING;
	}
}
