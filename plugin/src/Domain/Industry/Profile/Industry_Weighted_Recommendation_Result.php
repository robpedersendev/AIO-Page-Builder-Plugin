<?php
/**
 * Result of weighted multi-industry recommendation resolution (Prompt 371).
 * Holds final score, contributing industries, conflict results, and explanation summary per item.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Profile;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable result for one item (section, template, or Build Plan item) after weighted precedence.
 */
final class Industry_Weighted_Recommendation_Result {

	/** Array key: final score. */
	public const KEY_FINAL_SCORE = 'final_score';

	/** Array key: contributing industry keys (primary first). */
	public const KEY_CONTRIBUTING_INDUSTRIES = 'contributing_industries';

	/** Array key: conflict result records (Industry_Conflict_Result shapes). */
	public const KEY_CONFLICT_RESULTS = 'conflict_results';

	/** Array key: explanation summary text. */
	public const KEY_EXPLANATION_SUMMARY = 'explanation_summary';

	/** Array key: unresolved or high-severity flag for UI. */
	public const KEY_HAS_WARNING = 'has_warning';

	/**
	 * Builds a weighted result array. Safe for single-industry (conflict_results empty).
	 *
	 * @param int                              $final_score            Final score (from resolver).
	 * @param array<string>                    $contributing_industries Industry keys that contributed.
	 * @param array<int, array<string, mixed>> $conflict_results Conflict result shapes from Industry_Conflict_Result.
	 * @param string                           $explanation_summary    Short summary for UI.
	 * @return array<string, mixed>
	 */
	public static function create(
		int $final_score,
		array $contributing_industries,
		array $conflict_results,
		string $explanation_summary
	): array {
		$has_warning = false;
		foreach ( $conflict_results as $c ) {
			if ( Industry_Conflict_Result::should_surface_warning( $c ) ) {
				$has_warning = true;
				break;
			}
		}
		return array(
			self::KEY_FINAL_SCORE             => $final_score,
			self::KEY_CONTRIBUTING_INDUSTRIES => array_values( array_filter( array_map( 'strval', $contributing_industries ) ) ),
			self::KEY_CONFLICT_RESULTS        => $conflict_results,
			self::KEY_EXPLANATION_SUMMARY     => trim( $explanation_summary ),
			self::KEY_HAS_WARNING             => $has_warning,
		);
	}

	/**
	 * Normalizes raw array into result shape. Missing fields get safe defaults.
	 *
	 * @param array<string, mixed> $raw
	 * @return array<string, mixed>
	 */
	public static function from_array( array $raw ): array {
		$score       = isset( $raw[ self::KEY_FINAL_SCORE ] ) && is_numeric( $raw[ self::KEY_FINAL_SCORE ] )
			? (int) $raw[ self::KEY_FINAL_SCORE ]
			: 0;
		$industries  = isset( $raw[ self::KEY_CONTRIBUTING_INDUSTRIES ] ) && is_array( $raw[ self::KEY_CONTRIBUTING_INDUSTRIES ] )
			? array_values( array_filter( array_map( 'strval', $raw[ self::KEY_CONTRIBUTING_INDUSTRIES ] ) ) )
			: array();
		$conflicts   = isset( $raw[ self::KEY_CONFLICT_RESULTS ] ) && is_array( $raw[ self::KEY_CONFLICT_RESULTS ] )
			? array_map( array( Industry_Conflict_Result::class, 'from_array' ), $raw[ self::KEY_CONFLICT_RESULTS ] )
			: array();
		$summary     = isset( $raw[ self::KEY_EXPLANATION_SUMMARY ] ) && is_string( $raw[ self::KEY_EXPLANATION_SUMMARY ] )
			? trim( $raw[ self::KEY_EXPLANATION_SUMMARY ] )
			: '';
		$has_warning = ! empty( $raw[ self::KEY_HAS_WARNING ] );
		return array(
			self::KEY_FINAL_SCORE             => $score,
			self::KEY_CONTRIBUTING_INDUSTRIES => $industries,
			self::KEY_CONFLICT_RESULTS        => $conflicts,
			self::KEY_EXPLANATION_SUMMARY     => $summary,
			self::KEY_HAS_WARNING             => $has_warning,
		);
	}
}
