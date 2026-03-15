<?php
/**
 * Typed conflict result for multi-industry resolution (industry-conflict-resolution-contract.md, Prompt 370).
 * Immutable shape: conflict_type, source_industries, resolution_mode, explanation, severity.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Profile;

defined( 'ABSPATH' ) || exit;

/**
 * Represents one conflict or resolved outcome from primary/secondary industry signals.
 * Used by Industry_Weighted_Recommendation_Engine and UI for badges/explanations.
 */
final class Industry_Conflict_Result {

	/** Conflict type: section fit (preferred/discouraged). */
	public const CONFLICT_TYPE_SECTION_FIT = 'section_fit';

	/** Conflict type: page template fit. */
	public const CONFLICT_TYPE_TEMPLATE_FIT = 'template_fit';

	/** Conflict type: CTA pattern. */
	public const CONFLICT_TYPE_CTA_PATTERN = 'cta_pattern';

	/** Conflict type: page family. */
	public const CONFLICT_TYPE_PAGE_FAMILY = 'page_family';

	/** Conflict type: LPagery posture. */
	public const CONFLICT_TYPE_LPAGERY = 'lpagery';

	/** Conflict type: style preset. */
	public const CONFLICT_TYPE_STYLE_PRESET = 'style_preset';

	/** Conflict type: Build Plan item. */
	public const CONFLICT_TYPE_BUILD_PLAN_ITEM = 'build_plan_item';

	/** Conflict type: generic / uncategorized. */
	public const CONFLICT_TYPE_GENERIC = 'generic';

	/** Resolution: primary industry wins. */
	public const RESOLUTION_PRIMARY_WINS = 'primary_wins';

	/** Resolution: secondary influenced outcome. */
	public const RESOLUTION_SECONDARY_INFLUENCED = 'secondary_influenced';

	/** Resolution: combined (no conflict). */
	public const RESOLUTION_COMBINED = 'combined';

	/** Resolution: unresolved; fallback applied. */
	public const RESOLUTION_UNRESOLVED = 'unresolved';

	/** Resolution: no conflict (single industry or neutral). */
	public const RESOLUTION_NONE = 'none';

	/** Severity: informational only. */
	public const SEVERITY_INFO = 'info';

	/** Severity: warning; show badge/snippet. */
	public const SEVERITY_WARNING = 'warning_worthy';

	/** Severity: blocking; high visibility. */
	public const SEVERITY_BLOCKING = 'blocking';

	/** Severity: unresolved; caution. */
	public const SEVERITY_UNRESOLVED = 'unresolved';

	/** Array key: conflict type. */
	public const KEY_CONFLICT_TYPE = 'conflict_type';

	/** Array key: source industry keys (primary first). */
	public const KEY_SOURCE_INDUSTRIES = 'source_industries';

	/** Array key: resolution mode. */
	public const KEY_RESOLUTION_MODE = 'resolution_mode';

	/** Array key: explanation text. */
	public const KEY_EXPLANATION = 'explanation';

	/** Array key: severity. */
	public const KEY_SEVERITY = 'severity';

	/**
	 * Builds a conflict result array. Caller must pass valid constants or contract-defined values.
	 *
	 * @param string   $conflict_type     One of CONFLICT_TYPE_*.
	 * @param string[] $source_industries Industry keys (primary first).
	 * @param string   $resolution_mode  One of RESOLUTION_*.
	 * @param string   $explanation       Short human-readable explanation.
	 * @param string   $severity          One of SEVERITY_*.
	 * @return array<string, mixed>
	 */
	public static function create(
		string $conflict_type,
		array $source_industries,
		string $resolution_mode,
		string $explanation,
		string $severity
	): array {
		return array(
			self::KEY_CONFLICT_TYPE      => $conflict_type,
			self::KEY_SOURCE_INDUSTRIES  => array_values( array_filter( array_map( 'strval', $source_industries ) ) ),
			self::KEY_RESOLUTION_MODE    => $resolution_mode,
			self::KEY_EXPLANATION        => trim( $explanation ),
			self::KEY_SEVERITY           => $severity,
		);
	}

	/**
	 * Normalizes a raw array into a valid conflict result shape. Missing or invalid fields get safe defaults.
	 *
	 * @param array<string, mixed> $raw
	 * @return array<string, mixed>
	 */
	public static function from_array( array $raw ): array {
		$type = isset( $raw[ self::KEY_CONFLICT_TYPE ] ) && is_string( $raw[ self::KEY_CONFLICT_TYPE ] )
			? trim( $raw[ self::KEY_CONFLICT_TYPE ] )
			: self::CONFLICT_TYPE_GENERIC;
		$sources = isset( $raw[ self::KEY_SOURCE_INDUSTRIES ] ) && is_array( $raw[ self::KEY_SOURCE_INDUSTRIES ] )
			? array_values( array_filter( array_map( function ( $v ) {
				return is_string( $v ) ? trim( $v ) : '';
			}, $raw[ self::KEY_SOURCE_INDUSTRIES ] ) ) )
			: array();
		$mode = isset( $raw[ self::KEY_RESOLUTION_MODE ] ) && is_string( $raw[ self::KEY_RESOLUTION_MODE ] )
			? trim( $raw[ self::KEY_RESOLUTION_MODE ] )
			: self::RESOLUTION_NONE;
		$explanation = isset( $raw[ self::KEY_EXPLANATION ] ) && is_string( $raw[ self::KEY_EXPLANATION ] )
			? trim( $raw[ self::KEY_EXPLANATION ] )
			: '';
		$severity = isset( $raw[ self::KEY_SEVERITY ] ) && is_string( $raw[ self::KEY_SEVERITY ] )
			? trim( $raw[ self::KEY_SEVERITY ] )
			: self::SEVERITY_INFO;
		return array(
			self::KEY_CONFLICT_TYPE      => $type,
			self::KEY_SOURCE_INDUSTRIES  => $sources,
			self::KEY_RESOLUTION_MODE    => $mode,
			self::KEY_EXPLANATION        => $explanation,
			self::KEY_SEVERITY           => $severity,
		);
	}

	/**
	 * Returns whether the result should be surfaced as a warning (warning_worthy, blocking, or unresolved).
	 *
	 * @param array<string, mixed> $result Result from create() or from_array().
	 * @return bool
	 */
	public static function should_surface_warning( array $result ): bool {
		$severity = (string) ( $result[ self::KEY_SEVERITY ] ?? self::SEVERITY_INFO );
		return in_array( $severity, array( self::SEVERITY_WARNING, self::SEVERITY_BLOCKING, self::SEVERITY_UNRESOLVED ), true );
	}
}
