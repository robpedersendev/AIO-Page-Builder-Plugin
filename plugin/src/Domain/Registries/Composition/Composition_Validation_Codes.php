<?php
/**
 * Composition validation codes and severity (spec §14.7, composition-validation-state-machine.md §4).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Composition;

defined( 'ABSPATH' ) || exit;

/**
 * Validation code constants and severity (blocking vs warning).
 * Used by composition validation to produce explainable results.
 */
final class Composition_Validation_Codes {

	/** One or more section keys do not exist in the section registry. */
	public const SECTION_MISSING = 'section_missing';

	/** Referenced section is deprecated with no replacement. */
	public const SECTION_DEPRECATED_NO_REPLACEMENT = 'section_deprecated_no_replacement';

	/** Referenced section is deprecated but has a replacement; suggest migration. */
	public const SECTION_DEPRECATED_HAS_REPLACEMENT = 'section_deprecated_has_replacement';

	/** Section order violates ordering rules. */
	public const ORDERING_INVALID = 'ordering_invalid';

	/** Two or more sections are adjacent in violation of compatibility rules. */
	public const COMPATIBILITY_ADJACENCY = 'compatibility_adjacency';

	/** Multiple sections with same purpose stacked without clear reason. */
	public const COMPATIBILITY_DUPLICATE_PURPOSE = 'compatibility_duplicate_purpose';

	/** Selected variant(s) conflict with compatibility or template rules. */
	public const VARIANT_CONFLICT = 'variant_conflict';

	/** Required structural anchor (e.g. opening/closing section) is missing. */
	public const STRUCTURAL_ANCHOR_MISSING = 'structural_anchor_missing';

	/** Helper content could not be generated for the composition. */
	public const HELPER_GENERATION_FAILED = 'helper_generation_failed';

	/** Field-group assignment cannot be derived. */
	public const FIELD_GROUP_DERIVATION_FAILED = 'field_group_derivation_failed';

	/** One-pager generation is not viable. */
	public const ONE_PAGER_GENERATION_FAILED = 'one_pager_generation_failed';

	/** Registry state has drifted from snapshot reference. */
	public const SNAPSHOT_DRIFT = 'snapshot_drift';

	/** No snapshot reference; cannot compare to creation-time registry state. */
	public const SNAPSHOT_MISSING = 'snapshot_missing';

	/** Source page template (if set) is missing or deprecated. */
	public const SOURCE_TEMPLATE_UNAVAILABLE = 'source_template_unavailable';

	/** Composition has no sections. */
	public const EMPTY_SECTION_LIST = 'empty_section_list';

	/** @var array<string, string> Code => severity (blocking|warning). */
	private static ?array $severity_map = null;

	/**
	 * Severity: blocking (failure).
	 */
	public const SEVERITY_BLOCKING = 'blocking';

	/**
	 * Severity: warning (non-blocking).
	 */
	public const SEVERITY_WARNING = 'warning';

	/**
	 * Returns severity for a validation code.
	 *
	 * @param string $code One of the CODE constants.
	 * @return string SEVERITY_BLOCKING or SEVERITY_WARNING.
	 */
	public static function get_severity( string $code ): string {
		$map = self::get_severity_map();
		return $map[ $code ] ?? self::SEVERITY_WARNING;
	}

	/**
	 * Returns whether the code is blocking.
	 *
	 * @param string $code One of the CODE constants.
	 * @return bool
	 */
	public static function is_blocking( string $code ): bool {
		return self::get_severity( $code ) === self::SEVERITY_BLOCKING;
	}

	/**
	 * Returns all validation codes that are blocking.
	 *
	 * @return list<string>
	 */
	public static function get_blocking_codes(): array {
		$blocking = array();
		foreach ( array_keys( self::get_severity_map() ) as $code ) {
			if ( self::is_blocking( $code ) ) {
				$blocking[] = $code;
			}
		}
		return $blocking;
	}

	/**
	 * Returns all validation codes that are warnings.
	 *
	 * @return list<string>
	 */
	public static function get_warning_codes(): array {
		$warnings = array();
		foreach ( array_keys( self::get_severity_map() ) as $code ) {
			if ( ! self::is_blocking( $code ) ) {
				$warnings[] = $code;
			}
		}
		return $warnings;
	}

	/**
	 * Returns whether the given string is a known validation code.
	 *
	 * @param string $code Code value.
	 * @return bool
	 */
	public static function is_known_code( string $code ): bool {
		return isset( self::get_severity_map()[ $code ] );
	}

	/**
	 * Returns code => severity map.
	 *
	 * @return array<string, string>
	 */
	public static function get_severity_map(): array {
		if ( self::$severity_map !== null ) {
			return self::$severity_map;
		}
		self::$severity_map = array(
			self::SECTION_MISSING                      => self::SEVERITY_BLOCKING,
			self::SECTION_DEPRECATED_NO_REPLACEMENT    => self::SEVERITY_BLOCKING,
			self::SECTION_DEPRECATED_HAS_REPLACEMENT   => self::SEVERITY_WARNING,
			self::ORDERING_INVALID                     => self::SEVERITY_BLOCKING,
			self::COMPATIBILITY_ADJACENCY              => self::SEVERITY_BLOCKING,
			self::COMPATIBILITY_DUPLICATE_PURPOSE      => self::SEVERITY_WARNING,
			self::VARIANT_CONFLICT                     => self::SEVERITY_BLOCKING,
			self::STRUCTURAL_ANCHOR_MISSING            => self::SEVERITY_BLOCKING,
			self::HELPER_GENERATION_FAILED             => self::SEVERITY_BLOCKING,
			self::FIELD_GROUP_DERIVATION_FAILED        => self::SEVERITY_BLOCKING,
			self::ONE_PAGER_GENERATION_FAILED          => self::SEVERITY_BLOCKING,
			self::SNAPSHOT_DRIFT                       => self::SEVERITY_WARNING,
			self::SNAPSHOT_MISSING                     => self::SEVERITY_WARNING,
			self::SOURCE_TEMPLATE_UNAVAILABLE          => self::SEVERITY_WARNING,
			self::EMPTY_SECTION_LIST                    => self::SEVERITY_BLOCKING,
		);
		return self::$severity_map;
	}
}
