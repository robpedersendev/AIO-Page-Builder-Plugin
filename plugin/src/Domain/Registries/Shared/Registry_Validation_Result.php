<?php
/**
 * Shared registry validation result structure (spec §12.13–12.15, §13.13, §14.7).
 * Machine-readable codes; distinct from operation results (Section_Registry_Result etc.).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Shared;

defined( 'ABSPATH' ) || exit;

/**
 * Registry-level validation result. Used by Registry_Integrity_Validator and deprecation checks.
 */
final class Registry_Validation_Result {

	/** Cross-reference points to missing object. */
	public const CODE_REFERENCE_MISSING = 'reference_missing';

	/** Cross-reference points to deprecated object. */
	public const CODE_REFERENCE_DEPRECATED = 'reference_deprecated';

	/** Replacement reference is invalid or points to non-existent object. */
	public const CODE_REPLACEMENT_INVALID = 'replacement_invalid';

	/** Replacement reference points to deprecated object. */
	public const CODE_REPLACEMENT_DEPRECATED = 'replacement_deprecated';

	/** Deprecation reason is required but empty. */
	public const CODE_DEPRECATION_REASON_REQUIRED = 'deprecation_reason_required';

	/** Object has deprecated dependencies (warning, not blocking). */
	public const CODE_HAS_DEPRECATED_DEPENDENCIES = 'has_deprecated_dependencies';

	/** Compatibility rule violated. */
	public const CODE_COMPATIBILITY_VIOLATION = 'compatibility_violation';

	/** Object not eligible for new selection. */
	public const CODE_NOT_ELIGIBLE_FOR_NEW_USE = 'not_eligible_for_new_use';

	/** @var bool */
	public readonly bool $valid;

	/** @var array<int, string> */
	public readonly array $errors;

	/** @var array<int, string> Non-blocking. */
	public readonly array $warnings;

	/** @var array<int, string> Machine-readable codes. */
	public readonly array $codes;

	public function __construct(
		bool $valid,
		array $errors = array(),
		array $warnings = array(),
		array $codes = array()
	) {
		$this->valid    = $valid;
		$this->errors   = array_values( $errors );
		$this->warnings = array_values( $warnings );
		$this->codes    = array_values( $codes );
	}

	public static function valid(): self {
		return new self( true );
	}

	public static function invalid( array $errors, array $codes = array() ): self {
		return new self( false, $errors, array(), $codes );
	}

	public static function valid_with_warnings( array $warnings, array $codes = array() ): self {
		return new self( true, array(), $warnings, $codes );
	}
}
